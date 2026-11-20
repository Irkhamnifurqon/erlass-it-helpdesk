<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/organization', [
            'departments' => Department::where('is_active', true)
                ->with(['positions' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'roles' => Role::whereIn('name', ['Administrator', 'Karyawan'])->orderBy('name')->get(['id', 'name']),
            'users' => User::with(['department', 'position', 'roles'])
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'department_id', 'position_id', 'is_active']),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'department_id' => ['required', 'exists:departments,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'role' => ['required', 'in:Administrator,Karyawan'],
        ]);
        $position = Position::query()
            ->whereKey($data['position_id'])
            ->where('department_id', $data['department_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'department_id' => $data['department_id'],
            'position_id' => $position->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->roles()->sync([Role::where('name', $data['role'])->valueOrFail('id')]);

        return back()->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:departments,name'], 'code' => ['nullable', 'string', 'max:20', 'unique:departments,code']]);
        Department::create($data);

        return back()->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function storePosition(Request $request): RedirectResponse
    {
        $data = $request->validate(['department_id' => ['required', 'exists:departments,id'], 'name' => ['required', 'string', 'max:120']]);
        $position = Position::firstOrCreate($data, ['is_active' => true]);
        abort_if(! $position->wasRecentlyCreated, 422, 'Jabatan tersebut sudah tersedia di departemen ini.');

        return back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function togglePosition(Position $position): RedirectResponse
    {
        $position->update(['is_active' => ! $position->is_active]);

        return back()->with('success', 'Status jabatan diperbarui.');
    }
}
