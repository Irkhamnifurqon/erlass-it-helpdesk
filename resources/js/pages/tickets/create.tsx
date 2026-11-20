import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';

type Subcategory = { id: number; name: string };
type IssueType = { id: number; name: string; subcategory_id?: number };
type Category = {
    id: number;
    name: string;
    subcategories: Subcategory[];
    issue_types: IssueType[];
};
type Option = { id: number; name: string };
const priorityLabels: Record<string, string> = {
    Low: 'Rendah',
    Medium: 'Sedang',
    High: 'Tinggi',
    Critical: 'Kritis',
};

export default function CreateTicket({
    categories,
    priorities,
}: {
    categories: Category[];
    priorities: Option[];
}) {
    const form = useForm<{
        title: string;
        description: string;
        category_id: string;
        issue_type_id: string;
        priority_id: string;
        attachments: File[];
    }>({
        title: '',
        description: '',
        category_id: '',
        issue_type_id: '',
        priority_id: '',
        attachments: [],
    });
    const allIssueTypes = useMemo(
        () =>
            categories.flatMap((item) =>
                item.issue_types.map((issue) => ({
                    ...issue,
                    categoryName: item.name,
                    categoryId: item.id,
                })),
            ),
        [categories],
    );
    const [issueQuery, setIssueQuery] = useState('');
    const [issueFocused, setIssueFocused] = useState(false);
    const issueTypes = allIssueTypes.filter(
        (item) =>
            (!form.data.category_id ||
                String(item.categoryId) === form.data.category_id) &&
            item.name.toLowerCase().includes(issueQuery.toLowerCase()),
    );

    return (
        <>
            <Head title="Buat Tiket" />
            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Buat tiket baru</h1>
                    <p className="text-muted-foreground">
                        Tiket langsung masuk ke antrean Tim IT tanpa approval.
                    </p>
                </div>
                <form
                    className="space-y-5 rounded-xl border p-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/tickets', {
                            onSuccess: () =>
                                toast.success('Tiket berhasil dibuat'),
                        });
                    }}
                >
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="category">Kategori</Label>
                            <select
                                id="category"
                                required
                                className="w-full rounded-md border border-input bg-background px-3 py-2"
                                value={form.data.category_id}
                                onChange={(event) => {
                                    form.setData(
                                        'category_id',
                                        event.target.value,
                                    );
                                    form.setData('issue_type_id', '');
                                    form.setData('title', '');
                                    setIssueQuery('');
                                }}
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
                            <Label htmlFor="issue_type">
                                Jenis kendala / judul
                            </Label>
                            <div className="relative">
                                <input
                                    id="issue_type"
                                    required
                                    className="w-full rounded-md border border-input bg-background px-3 py-2"
                                    value={form.data.title}
                                    placeholder="Ketik untuk mencari jenis kendala..."
                                    onFocus={() => setIssueFocused(true)}
                                    onBlur={() =>
                                        window.setTimeout(
                                            () => setIssueFocused(false),
                                            150,
                                        )
                                    }
                                    onChange={(event) => {
                                        const value = event.target.value;
                                        setIssueQuery(value);
                                        setIssueFocused(true);
                                        const selected = allIssueTypes.find(
                                            (item) => item.name === value,
                                        );
                                        form.setData('title', value);
                                        form.setData(
                                            'issue_type_id',
                                            selected ? String(selected.id) : '',
                                        );
                                        if (selected) {
                                            form.setData(
                                                'category_id',
                                                String(selected.categoryId),
                                            );
                                        }
                                    }}
                                />
                                {issueFocused && (
                                    <div className="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-popover p-1 shadow-lg">
                                        {issueTypes.map((item) => (
                                            <button
                                                type="button"
                                                key={item.id}
                                                className="flex w-full items-center justify-between rounded px-3 py-2 text-left text-sm hover:bg-accent"
                                                onMouseDown={(event) =>
                                                    event.preventDefault()
                                                }
                                                onClick={() => {
                                                    const isOther =
                                                        item.name === 'Lainnya';
                                                    form.setData(
                                                        'title',
                                                        isOther
                                                            ? ''
                                                            : item.name,
                                                    );
                                                    form.setData(
                                                        'issue_type_id',
                                                        String(item.id),
                                                    );
                                                    form.setData(
                                                        'category_id',
                                                        String(item.categoryId),
                                                    );
                                                    setIssueQuery(item.name);
                                                    setIssueFocused(false);
                                                }}
                                            >
                                                <span>{item.name}</span>
                                                <span className="ml-3 text-xs text-muted-foreground">
                                                    {item.categoryName}
                                                </span>
                                            </button>
                                        ))}
                                        {issueTypes.length === 0 && (
                                            <button
                                                type="button"
                                                className="w-full rounded px-3 py-2 text-left text-sm font-medium text-primary hover:bg-accent"
                                                onMouseDown={(event) =>
                                                    event.preventDefault()
                                                }
                                                onClick={() => {
                                                    form.setData(
                                                        'issue_type_id',
                                                        '',
                                                    );
                                                    setIssueFocused(false);
                                                }}
                                            >
                                                Lainnya: ketik kendala secara
                                                manual
                                            </button>
                                        )}
                                    </div>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Pilih jenis yang tersedia atau pilih Lainnya
                                untuk mengetik judul sendiri.
                            </p>
                        </div>
                    </div>
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="priority">Prioritas</Label>
                            <select
                                id="priority"
                                required
                                className="w-full rounded-md border border-input bg-background px-3 py-2"
                                value={form.data.priority_id}
                                onChange={(event) =>
                                    form.setData(
                                        'priority_id',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Pilih prioritas</option>
                                {priorities.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {priorityLabels[item.name] ?? item.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="attachments">
                                Bukti foto/video
                            </Label>
                            <input
                                key={form.data.attachments
                                    .map(
                                        (file) =>
                                            `${file.name}-${file.lastModified}`,
                                    )
                                    .join('|')}
                                id="attachments"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,video/mp4,video/quicktime,video/webm"
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                onChange={(event) => {
                                    const files = Array.from(
                                        event.target.files ?? [],
                                    );
                                    const oversized = files.find(
                                        (file) => file.size > 50 * 1024 * 1024,
                                    );
                                    if (oversized) {
                                        toast.error(
                                            `${oversized.name} melebihi batas 50 MB`,
                                        );
                                        event.target.value = '';
                                        return;
                                    }
                                    form.setData(
                                        'attachments',
                                        files.slice(0, 5),
                                    );
                                }}
                            />
                            <p className="text-xs text-muted-foreground">
                                Lampiran (JPG, PNG, PDF, DOC, XLS, TXT, MP4,
                                MOV, WebM; maks. 50 MB)
                            </p>
                            {form.data.attachments.length > 0 && (
                                <ul className="space-y-2 text-sm">
                                    {form.data.attachments.map(
                                        (file, index) => (
                                            <li
                                                key={`${file.name}-${file.lastModified}`}
                                                className="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2"
                                            >
                                                <span className="truncate">
                                                    {file.name}
                                                </span>
                                                <button
                                                    type="button"
                                                    className="ml-3 text-destructive hover:underline"
                                                    onClick={() =>
                                                        form.setData(
                                                            'attachments',
                                                            form.data.attachments.filter(
                                                                (
                                                                    _,
                                                                    fileIndex,
                                                                ) =>
                                                                    fileIndex !==
                                                                    index,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    Hapus
                                                </button>
                                            </li>
                                        ),
                                    )}
                                </ul>
                            )}
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <textarea
                            id="description"
                            required
                            rows={8}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                    </div>
                    <Button disabled={form.processing}>Kirim tiket</Button>
                </form>
            </div>
        </>
    );
}
