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
        $supervisor = $this->user('Supervisor IT', $it);
        $support = $this->user('IT Support', $it);
        $manager = $this->user('Manager Departemen', $marketing);
        $head = $this->user('Kepala Departemen', $marketing);
        $employee = $this->user('Karyawan', $marketing);
        $otherEmployee = $this->user('Karyawan', $it);
        $marketingTicket = $this->ticket($employee);
        $itTicket = $this->ticket($otherEmployee);
        $marketingTicket->assignments()->create(['assigned_to' => $support->id, 'assigned_by' => $supervisor->id, 'assigned_at' => now()]);

        foreach ([$admin, $supervisor] as $user) {
            $this->actingAs($user)->get(route('tickets.show', $itTicket))->assertOk();
        }
        $this->actingAs($support)->get(route('tickets.show', $marketingTicket))->assertOk();
        $this->actingAs($manager)->get(route('tickets.show', $marketingTicket))->assertOk();
        $this->actingAs($head)->get(route('tickets.show', $marketingTicket))->assertOk();
        $this->actingAs($employee)->get(route('tickets.show', $marketingTicket))->assertOk();
        $this->actingAs($manager)->get(route('tickets.show', $itTicket))->assertForbidden();
        $this->actingAs($head)->get(route('tickets.show', $itTicket))->assertForbidden();
        $this->actingAs($employee)->get(route('tickets.show', $itTicket))->assertForbidden();
    }

    public function test_manager_and_head_cannot_create_internal_comments(): void
    {
        $department = Department::create(['name' => 'Marketing']);
        $manager = $this->user('Manager Departemen', $department);
        $head = $this->user('Kepala Departemen', $department);
        $ticket = $this->ticket($manager);

        foreach ([$manager, $head] as $user) {
            $this->actingAs($user)
                ->post(route('tickets.comment', $ticket), ['body' => 'Catatan rahasia', 'is_internal' => true])
                ->assertForbidden();
        }
    }

    public function test_only_administrator_can_access_organization_master(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = $this->user('Administrator', $department);
        $supervisor = $this->user('Supervisor IT', $department);

        $this->actingAs($admin)->get(route('admin.organization'))->assertOk();
        $this->actingAs($supervisor)->get(route('admin.organization'))->assertForbidden();
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
