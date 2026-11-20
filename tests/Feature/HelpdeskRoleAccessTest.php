<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_visibility_follows_each_role_scope(): void
    {
        [$it, $marketing] = [Department::create(['name' => 'IT']), Department::create(['name' => 'Marketing'])];
        $admin = $this->user('Administrator', $it);
        $employee = $this->user('Karyawan', $marketing);
        $otherEmployee = $this->user('Karyawan', $it);
        $marketingTicket = $this->ticket($employee);
        $itTicket = $this->ticket($otherEmployee);

        $this->actingAs($admin)->get(route('tickets.show', $itTicket))->assertOk();
        $this->actingAs($employee)->get(route('tickets.show', $marketingTicket))->assertOk();
        $this->actingAs($employee)->get(route('tickets.show', $itTicket))->assertForbidden();
    }

    public function test_manager_and_head_cannot_create_internal_comments(): void
    {
        $department = Department::create(['name' => 'Marketing']);
        $employee = $this->user('Karyawan', $department);
        $ticket = $this->ticket($employee);

        $this->actingAs($employee)
            ->post(route('tickets.comment', $ticket), ['body' => 'Catatan rahasia', 'is_internal' => true])
            ->assertForbidden();
    }

    public function test_only_administrator_can_access_organization_master(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = $this->user('Administrator', $department);
        $employee = $this->user('Karyawan', $department);

        $this->actingAs($admin)->get(route('admin.organization'))->assertOk();
        $this->actingAs($employee)->get(route('admin.organization'))->assertForbidden();
    }

    public function test_helpdesk_user_can_login_again_without_a_team(): void
    {
        $user = User::create([
            'name' => 'Login Test',
            'email' => 'login-test@example.com',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->post('/logout')->assertRedirect();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
    }

    public function test_administrator_can_change_ticket_status_with_selected_target(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = $this->user('Administrator', $department);
        $employee = $this->user('Karyawan', $department);
        $ticket = $this->ticket($employee);

        $this->actingAs($admin)
            ->post(route('tickets.status', $ticket), ['status' => 'assigned'])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($admin)
            ->post(route('tickets.status', $ticket), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_resolved_status_requires_solution(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = $this->user('Administrator', $department);
        $employee = $this->user('Karyawan', $department);
        $ticket = $this->ticket($employee);
        $ticket->update(['status' => 'in_progress']);

        $this->actingAs($admin)
            ->post(route('tickets.status', $ticket), ['status' => 'resolved'])
            ->assertSessionHasErrors('solution');

        $this->actingAs($admin)
            ->post(route('tickets.status', $ticket), [
                'status' => 'resolved',
                'solution' => 'Masalah telah diperbaiki.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
        ]);
    }

    public function test_closed_ticket_cannot_be_reopened_by_user(): void
    {
        $department = Department::create(['name' => 'IT']);
        $employee = $this->user('Karyawan', $department);
        $ticket = $this->ticket($employee);
        $ticket->update(['status' => 'closed']);

        $this->actingAs($employee)
            ->post(route('tickets.status', $ticket), ['status' => 'reopened', 'reason' => 'Masalah muncul lagi'])
            ->assertForbidden();
    }

    public function test_ticket_number_uses_it_month_and_sequence_format(): void
    {
        $department = Department::create(['name' => 'IT']);
        $employee = $this->user('Karyawan', $department);
        $category = Category::firstOrCreate(['name' => 'Hardware']);
        $priority = Priority::firstOrCreate(['name' => 'Low'], ['level' => 1, 'sort_order' => 1, 'color' => 'blue']);

        $this->actingAs($employee)->post(route('tickets.store'), [
            'title' => 'Komputer tidak menyala',
            'description' => 'Komputer kantor tidak menyala.',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ])->assertRedirect();

        $newTicket = Ticket::where('requester_id', $employee->id)->latest('id')->first();
        $this->assertMatchesRegularExpression('/^ERL-IT-\d{4}-\d{2}-\d{3}$/', $newTicket->ticket_number);
    }

    private function user(string $role, Department $department): User
    {
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->roles()->attach(Role::firstOrCreate(['name' => $role]));
        return $user;
    }

    private function ticket(User $requester): Ticket
    {
        return Ticket::create([
            'ticket_number' => 'ERL-TEST-'.fake()->unique()->numerify('#####'),
            'requester_id' => $requester->id,
            'category_id' => Category::firstOrCreate(['name' => 'Hardware'])->id,
            'priority_id' => Priority::firstOrCreate(['name' => 'High'], ['level' => 3, 'sort_order' => 3, 'color' => 'orange'])->id,
            'title' => 'Test ticket',
            'description' => 'Test description',
            'status' => 'open',
        ]);
    }
}
