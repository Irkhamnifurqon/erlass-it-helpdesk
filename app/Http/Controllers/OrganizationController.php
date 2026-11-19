<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/organization', [
            'departments' => Department::with(['positions' => fn ($query) => $query->orderBy('name')])->orderBy('name')->get(),
        ]);
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
