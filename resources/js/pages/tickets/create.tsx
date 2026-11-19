import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Option = { id: number; name: string };
export default function CreateTicket({
    categories,
    priorities,
}: {
    categories: Option[];
    priorities: Option[];
}) {
    const form = useForm({
        title: '',
        description: '',
        category_id: '',
        priority_id: '',
    });
    return (
        <>
            <Head title="Buat Tiket" />
            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Buat tiket baru</h1>
                    <p className="text-muted-foreground">
                        Jelaskan kendala IT yang Anda alami.
                    </p>
                </div>
                <form
                    className="space-y-5 rounded-xl border p-6"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/tickets');
                    }}
                >
                    <div className="space-y-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                        />
                        {form.errors.title && (
                            <p className="text-sm text-destructive">
                                {form.errors.title}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="category">Kategori</Label>
                            <select
                                id="category"
                                className="w-full rounded-md border border-input bg-background px-3 py-2"
                                value={form.data.category_id}
                                onChange={(e) =>
                                    form.setData('category_id', e.target.value)
                                }
                            >
                                <option value="">Pilih kategori</option>
                                {categories.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="priority">Prioritas</Label>
                            <select
                                id="priority"
                                className="w-full rounded-md border border-input bg-background px-3 py-2"
                                value={form.data.priority_id}
                                onChange={(e) =>
                                    form.setData('priority_id', e.target.value)
                                }
                            >
                                <option value="">Pilih prioritas</option>
                                {priorities.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <textarea
                            id="description"
                            rows={8}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={form.data.description}
                            onChange={(
                                e: React.ChangeEvent<HTMLTextAreaElement>,
                            ) => form.setData('description', e.target.value)}
                        />
                        {form.errors.description && (
                            <p className="text-sm text-destructive">
                                {form.errors.description}
                            </p>
                        )}
                    </div>
                    <Button disabled={form.processing}>Kirim tiket</Button>
                </form>
            </div>
        </>
    );
}
