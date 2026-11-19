import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Position = { id: number; name: string; is_active: boolean };
type Department = {
    id: number;
    name: string;
    code?: string;
    positions: Position[];
};

export default function Organization({
    departments,
}: {
    departments: Department[];
}) {
    const department = useForm({ name: '', code: '' });
    const position = useForm({ department_id: '', name: '' });
    return (
        <>
            <Head title="Organisasi" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Master Organisasi
                    </h1>
                    <p className="text-muted-foreground">
                        Kelola departemen dan jabatan tanpa menghapus histori.
                    </p>
                </div>
                <div className="grid gap-6 lg:grid-cols-2">
                    <form
                        className="space-y-4 rounded-xl border p-5"
                        onSubmit={(event) => {
                            event.preventDefault();
                            department.post('/admin/departments', {
                                onSuccess: () => department.reset(),
                            });
                        }}
                    >
                        <h2 className="font-semibold">Tambah departemen</h2>
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
                    <form
                        className="space-y-4 rounded-xl border p-5"
                        onSubmit={(event) => {
                            event.preventDefault();
                            position.post('/admin/positions', {
                                onSuccess: () => position.reset(),
                            });
                        }}
                    >
                        <h2 className="font-semibold">Tambah jabatan</h2>
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
                                    position.setData('name', event.target.value)
                                }
                            />
                        </div>
                        <Button disabled={position.processing}>
                            Simpan jabatan
                        </Button>
                    </form>
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
