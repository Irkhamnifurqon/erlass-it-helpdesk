<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Position;
use App\Models\Role;
use App\Models\SlaPolicy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect(['Administrator', 'Supervisor IT', 'IT Support', 'Manager Departemen', 'Kepala Departemen', 'Karyawan'])->mapWithKeys(fn ($name) => [$name => Role::firstOrCreate(['name' => $name])]);
        $departments = collect([
            ['name' => 'IT', 'code' => 'IT'],
            ['name' => 'HR', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Operasional', 'code' => 'OPS'],
            ['name' => 'Sales', 'code' => 'SLS'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'Produksi', 'code' => 'PRD'],
        ])->mapWithKeys(fn ($department) => [
            $department['code'] => Department::firstOrCreate(['code' => $department['code']], ['name' => $department['name']]),
        ]);
        foreach ($departments as $department) {
            foreach (['Manager Departemen', 'Kepala Departemen', 'Karyawan'] as $position) {
                Position::firstOrCreate(['department_id' => $department->id, 'name' => $position], ['is_active' => true]);
            }
        }
        $categories = [
            'Hardware' => [60, 480], 'Software' => [60, 480], 'Network' => [30, 240],
            'Account' => [60, 240], 'Website' => [30, 480], 'Email' => [60, 240],
            'Access' => [120, 480], 'Other' => [120, 480],
        ];
        foreach ($categories as $name => [$response, $resolution]) {
            $category = Category::firstOrCreate(['name' => $name]);
            SlaPolicy::updateOrCreate(['category_id' => $category->id], [
                'response_target_minutes' => $response,
                'resolution_target_minutes' => $resolution,
                'is_active' => true,
            ]);
        }
        foreach ([['Low', 1, 'blue'], ['Medium', 2, 'yellow'], ['High', 3, 'orange'], ['Critical', 4, 'red']] as [$name, $level, $color]) {
            Priority::firstOrCreate(['name' => $name], ['level' => $level, 'sort_order' => $level, 'color' => $color]);
        }

        $accounts = [
            ['name' => 'Admin Erlass', 'email' => 'admin@erlass.test', 'role' => 'Administrator', 'department' => 'IT'],
            ['name' => 'Supervisor IT Erlass', 'email' => 'supervisor@erlass.test', 'role' => 'Supervisor IT', 'department' => 'IT'],
            ['name' => 'Support IT Erlass', 'email' => 'support@erlass.test', 'role' => 'IT Support', 'department' => 'IT'],
            ['name' => 'Karyawan HR', 'email' => 'karyawan.hr@erlass.test', 'role' => 'Karyawan', 'department' => 'HR'],
            ['name' => 'Karyawan Finance', 'email' => 'karyawan.finance@erlass.test', 'role' => 'Karyawan', 'department' => 'FIN'],
            ['name' => 'Karyawan Operasional', 'email' => 'karyawan.operasional@erlass.test', 'role' => 'Karyawan', 'department' => 'OPS'],
            ['name' => 'Karyawan Sales', 'email' => 'karyawan.sales@erlass.test', 'role' => 'Karyawan', 'department' => 'SLS'],
            ['name' => 'Manager Marketing', 'email' => 'manager.marketing@erlass.test', 'role' => 'Manager Departemen', 'department' => 'MKT'],
            ['name' => 'Kepala Marketing', 'email' => 'kepala.marketing@erlass.test', 'role' => 'Kepala Departemen', 'department' => 'MKT'],
            ['name' => 'Manager Produksi', 'email' => 'manager.produksi@erlass.test', 'role' => 'Manager Departemen', 'department' => 'PRD'],
            ['name' => 'Kepala Produksi', 'email' => 'kepala.produksi@erlass.test', 'role' => 'Kepala Departemen', 'department' => 'PRD'],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'department_id' => $departments[$account['department']]->id,
                    'position_id' => Position::where('department_id', $departments[$account['department']]->id)->where('name', $account['role'] === 'Karyawan' ? 'Karyawan' : $account['role'])->value('id'),
                    'is_active' => true,
                ],
            );
            $user->roles()->sync([$roles[$account['role']]->id]);
        }

        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'department_id' => $departments['IT']->id,
                'is_active' => true,
            ],
        );
        $testUser->roles()->sync([$roles['Karyawan']->id]);
    }
}
