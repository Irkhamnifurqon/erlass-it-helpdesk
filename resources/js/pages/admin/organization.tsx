import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useMemo } from 'react';
import InputError from '@/components/input-error';
import { toast } from 'sonner';

type Position = { id: number; name: string; is_active: boolean };
type Role = { id: number; name: string };
type Department = {
    id: number;
    name: string;
    code?: string;
    positions: Position[];
};
type OrganizationUser = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    department?: { name: string };
    position?: { name: string };
    roles: { name: string }[];
};

export default function Organization({
    departments,
    roles,
    users,
}: {
    departments: Department[];
    roles: Role[];
    users: OrganizationUser[];
}) {
    const department = useForm({ name: '', code: '' });
    const position = useForm({ department_id: '', name: '' });
    const user = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        department_id: '',
        position_id: '',
        role: 'Karyawan',
    });
    const userPositions = useMemo(
        () =>
            departments.find(
                (item) => String(item.id) === user.data.department_id,
            )?.positions ?? [],
        [departments, user.data.department_id],
    );
    return (
        <>
            <Head title="Pengguna" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Manajemen Pengguna dan Organisasi
                    </h1>
                    <p className="text-muted-foreground">
                        Kelola departemen dan jabatan tanpa menghapus histori.
                    </p>
                </div>
                <div className="overflow-hidden rounded-xl border">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">Daftar seluruh akun</h2>
                        <p className="text-sm text-muted-foreground">
                            {users.length} akun terdaftar
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="p-3">Nama</th>
                                    <th className="p-3">Email</th>
                                    <th className="p-3">Divisi</th>
                                    <th className="p-3">Jabatan</th>
                                    <th className="p-3">Role</th>
                                    <th className="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map((item) => (
                                    <tr key={item.id} className="border-t">
                                        <td className="p-3 font-medium">
                                            {item.name}
                                        </td>
                                        <td className="p-3">{item.email}</td>
                                        <td className="p-3">
                                            {item.department?.name ?? '-'}
                                        </td>
                                        <td className="p-3">
                                            {item.position?.name ?? '-'}
                                        </td>
                                        <td className="p-3">
                                            {item.roles[0]?.name ===
                                            'Administrator'
                                                ? 'Admin IT'
                                                : 'Karyawan'}
                                        </td>
                                        <td className="p-3">
                                            {item.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div className="grid gap-6 lg:grid-cols-2">
                    <form
                        className="space-y-4 rounded-xl border p-5 lg:col-span-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            user.post('/admin/users', {
                                onSuccess: () => {
                                    user.reset();
                                    toast.success(
                                        'Akun pengguna berhasil dibuat',
                                    );
                                },
                            });
                        }}
                    >
                        <h2 className="font-semibold">Buat akun pengguna</h2>
                        <div className="grid gap-4 md:grid-cols-3">
                            <Input
                                placeholder="Nama"
                                value={user.data.name}
                                onChange={(event) =>
                                    user.setData('name', event.target.value)
                                }
                            />
                            <Input
                                placeholder="Email"
                                type="email"
                                value={user.data.email}
                                onChange={(event) =>
                                    user.setData('email', event.target.value)
                                }
                            />
                            <select
                                className="rounded-md border border-input bg-background px-3 py-2"
                                value={user.data.role}
                                onChange={(event) =>
                                    user.setData('role', event.target.value)
                                }
                            >
                                {roles.map((role) => (
                                    <option key={role.id} value={role.name}>
                                        {role.name === 'Administrator'
                                            ? 'Admin'
                                            : 'Karyawan'}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="rounded-md border border-input bg-background px-3 py-2"
                                required
                                value={user.data.department_id}
                                onChange={(event) => {
                                    user.setData(
                                        'department_id',
                                        event.target.value,
                                    );
                                    user.setData('position_id', '');
                                }}
                            >
                                <option value="">Pilih divisi</option>
                                {departments.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="rounded-md border border-input bg-background px-3 py-2"
                                required
                                value={user.data.position_id}
                                onChange={(event) =>
                                    user.setData(
                                        'position_id',
                                        event.target.value,
                                    )
                                }
                                disabled={!user.data.department_id}
                            >
                                <option value="">Pilih jabatan</option>
                                {userPositions.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                            <Input
                                placeholder="Password minimal 8 karakter"
                                type="password"
                                value={user.data.password}
                                onChange={(event) =>
                                    user.setData('password', event.target.value)
                                }
                            />
                            <Input
                                placeholder="Ulangi password"
                                type="password"
                                value={user.data.password_confirmation}
                                onChange={(event) =>
                                    user.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <InputError
                            message={
                                user.errors.name ??
                                user.errors.email ??
                                user.errors.password ??
                                user.errors.department_id ??
                                user.errors.position_id ??
                                user.errors.role
                            }
                        />
                        <Button disabled={user.processing}>Buat akun</Button>
                    </form>
                    <details className="rounded-xl border p-5">
                        <summary className="cursor-pointer font-semibold">
                            Tambah departemen
                        </summary>
                        <form
                            className="mt-4 space-y-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                department.post('/admin/departments', {
                                    onSuccess: () => department.reset(),
                                });
                            }}
                        >
                            <div className="space-y-2">
                                <Label>Nama</Label>
                                <Input
                                    value={department.data.name}
                                    onChange={(event) =>
                                        department.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Kode</Label>
                                <Input
                                    value={department.data.code}
                                    onChange={(event) =>
                                        department.setData(
                                            'code',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <Button disabled={department.processing}>
                                Simpan departemen
                            </Button>
                        </form>
                    </details>
                    <details className="rounded-xl border p-5">
                        <summary className="cursor-pointer font-semibold">
                            Tambah jabatan
                        </summary>
                        <form
                            className="mt-4 space-y-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                position.post('/admin/positions', {
                                    onSuccess: () => position.reset(),
                                });
                            }}
                        >
                            <div className="space-y-2">
                                <Label>Departemen</Label>
                                <select
                                    className="w-full rounded-md border border-input bg-background px-3 py-2"
                                    value={position.data.department_id}
                                    onChange={(event) =>
                                        position.setData(
                                            'department_id',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">Pilih departemen</option>
                                    {departments.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label>Nama jabatan</Label>
                                <Input
                                    value={position.data.name}
                                    onChange={(event) =>
                                        position.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <Button disabled={position.processing}>
                                Simpan jabatan
                            </Button>
                        </form>
                    </details>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    {departments.map((item) => (
                        <div key={item.id} className="rounded-xl border p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h2 className="font-semibold">
                                        {item.name}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {item.code}
                                    </p>
                                </div>
                            </div>
                            <div className="mt-4 space-y-2">
                                {item.positions.map((job) => (
                                    <div
                                        key={job.id}
                                        className="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2 text-sm"
                                    >
                                        <span>{job.name}</span>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.patch(
                                                    `/admin/positions/${job.id}/toggle`,
                                                )
                                            }
                                        >
                                            {job.is_active
                                                ? 'Nonaktifkan'
                                                : 'Aktifkan'}
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
