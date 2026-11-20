import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useState } from 'react';
import {
    BarChart3,
    Building2,
    Clock3,
    Download,
    FileSpreadsheet,
    Star,
    TicketCheck,
} from 'lucide-react';

type Row = {
    name?: string;
    month?: string;
    total: number;
    rating_count?: number;
    average_rating?: number | null;
};
type Analytics = {
    monthly: Row[];
    categories: Row[];
    departments: Row[];
    agents: Row[];
    average_resolution_minutes: number | null;
};

export default function Reports({ analytics }: { analytics: Analytics }) {
    const [month, setMonth] = useState('');
    const query = month ? `?month=${encodeURIComponent(month)}` : '';
    return (
        <>
            <Head title="Laporan Tiket IT Help Desk" />
            <div className="max-w-5xl space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        LAPORAN TIKET IT HELP DESK
                    </h1>
                    <p className="text-muted-foreground">
                        Evaluasi kinerja operasional dan unduh laporan.
                    </p>
                </div>
                <div className="grid gap-4 rounded-xl border p-5 md:grid-cols-[1fr_auto_auto]">
                    <div className="space-y-2">
                        <label className="text-sm font-medium" htmlFor="month">
                            Bulan laporan
                        </label>
                        <Input
                            id="month"
                            type="month"
                            value={month}
                            onChange={(event) => setMonth(event.target.value)}
                        />
                    </div>
                    <Button asChild className="h-11">
                        <a href={`/admin/reports/excel${query}`}>
                            <FileSpreadsheet className="size-4" />
                            Unduh Excel
                        </a>
                    </Button>
                    <Button variant="outline" asChild className="h-11">
                        <a href={`/admin/reports/pdf${query}`}>
                            <Download className="size-4" />
                            Unduh PDF
                        </a>
                    </Button>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <section className="rounded-xl border p-5">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <BarChart3 className="size-4 text-primary" />
                            Ringkasan per bulan
                        </h2>
                        {analytics.monthly.length > 0 ? (
                            <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                {analytics.monthly
                                    .slice(0, 6)
                                    .map((row, index) => (
                                        <div
                                            key={row.month}
                                            className="flex items-center gap-3 rounded-xl border bg-gradient-to-br from-blue-50 to-cyan-50 p-4 dark:from-blue-950/30 dark:to-cyan-950/20"
                                        >
                                            <div className="flex size-11 items-center justify-center rounded-full bg-white text-blue-700 shadow-sm dark:bg-slate-900 dark:text-blue-300">
                                                <TicketCheck className="size-5" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-xs text-muted-foreground">
                                                    Periode {row.month}
                                                </p>
                                                <p className="mt-0.5 text-lg font-bold">
                                                    {row.total} tiket
                                                </p>
                                            </div>
                                            {index === 0 && (
                                                <span className="ml-auto rounded-full bg-blue-600 px-2 py-1 text-[10px] font-semibold text-white">
                                                    Terbaru
                                                </span>
                                            )}
                                        </div>
                                    ))}
                            </div>
                        ) : (
                            <div className="mt-5 rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                                Belum ada data laporan bulanan.
                            </div>
                        )}
                    </section>
                    <section className="rounded-xl border p-5">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <Clock3 className="size-4 text-amber-500" />
                            Rata-rata penyelesaian
                        </h2>
                        <p className="mt-5 text-4xl font-bold">
                            {analytics.average_resolution_minutes !== null
                                ? `${Math.round(analytics.average_resolution_minutes)} menit`
                                : 'Belum ada data'}
                        </p>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Sejak tiket dibuat hingga ditandai selesai.
                        </p>
                    </section>
                    <section className="rounded-xl border p-5">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <TicketCheck className="size-4 text-cyan-500" />
                            Tiket per kategori
                        </h2>
                        <div className="mt-4 space-y-3">
                            {analytics.categories.map((row) => {
                                const max = Math.max(
                                    1,
                                    ...analytics.categories.map(
                                        (item) => item.total,
                                    ),
                                );
                                return (
                                    <div key={row.name}>
                                        <div className="mb-1 flex justify-between text-sm">
                                            <span>{row.name}</span>
                                            <span className="font-semibold">
                                                {row.total}
                                            </span>
                                        </div>
                                        <div className="h-2 rounded-full bg-muted">
                                            <div
                                                className="h-2 rounded-full bg-cyan-500"
                                                style={{
                                                    width: `${(row.total / max) * 100}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                    <section className="rounded-xl border p-5">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <Star className="size-4 text-yellow-500" />
                            Kinerja petugas
                        </h2>
                        <div className="mt-4 space-y-3">
                            {analytics.agents.map((row) => (
                                <div
                                    key={row.name}
                                    className="rounded-lg border p-3"
                                >
                                    <p className="font-medium">
                                        {row.name}: {row.total} tiket
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {row.rating_count
                                            ? ` · Rating ${Number(row.average_rating).toFixed(1)}/5 (${row.rating_count} penilaian)`
                                            : ' · Belum ada rating'}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>
                    <section className="rounded-xl border p-5 md:col-span-2">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <Building2 className="size-4 text-violet-500" />
                            Tiket per departemen
                        </h2>
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {analytics.departments.map((row) => (
                                <div
                                    key={row.name}
                                    className="flex items-center justify-between rounded-lg bg-muted/50 p-3 text-sm"
                                >
                                    <span>{row.name}</span>
                                    <span className="rounded-full bg-violet-100 px-2 py-1 font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                                        {row.total}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
