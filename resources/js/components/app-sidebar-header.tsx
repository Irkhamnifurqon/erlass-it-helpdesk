import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Bell } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { notifications } = usePage().props;
    const unreadCount = notifications.filter((item) => !item.read_at).length;
    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button
                            type="button"
                            className="relative inline-flex size-10 items-center justify-center rounded-full border bg-background text-foreground shadow-sm transition hover:border-primary hover:bg-accent"
                            aria-label="Notifikasi"
                        >
                            <Bell className="size-5" />
                            {unreadCount > 0 && (
                                <span className="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground ring-2 ring-background">
                                    {unreadCount > 9 ? '9+' : unreadCount}
                                </span>
                            )}
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-80">
                        <DropdownMenuLabel className="flex items-center gap-2">
                            <Bell className="size-4" />
                            Notifikasi
                        </DropdownMenuLabel>
                        <div className="max-h-80 overflow-y-auto">
                            {notifications.map((item) => (
                                <DropdownMenuItem
                                    key={item.id}
                                    className="cursor-pointer py-3 whitespace-normal"
                                    onSelect={() => {
                                        router.post(
                                            `/notifications/${item.id}/read`,
                                            {},
                                            {
                                                preserveScroll: true,
                                                onSuccess: () =>
                                                    item.ticket_id &&
                                                    router.visit(
                                                        `/tickets/${item.ticket_id}`,
                                                    ),
                                            },
                                        );
                                    }}
                                >
                                    <span
                                        className={
                                            item.read_at
                                                ? 'text-muted-foreground'
                                                : 'font-medium'
                                        }
                                    >
                                        {item.message}
                                    </span>
                                </DropdownMenuItem>
                            ))}
                            {notifications.length === 0 && (
                                <p className="px-3 py-4 text-sm text-muted-foreground">
                                    Belum ada notifikasi.
                                </p>
                            )}
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
