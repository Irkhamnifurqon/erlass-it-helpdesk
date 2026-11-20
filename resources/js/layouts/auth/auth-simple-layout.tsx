import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh items-center justify-center overflow-hidden bg-slate-950 p-6 md:p-10">
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-24 -left-24 size-96 rounded-full bg-blue-600/20 blur-3xl" />
                <div className="absolute right-0 -bottom-32 size-[28rem] rounded-full bg-cyan-500/10 blur-3xl" />
                <div className="absolute inset-0 [background-image:linear-gradient(rgba(255,255,255,.25)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.25)_1px,transparent_1px)] [background-size:32px_32px] opacity-[0.08]" />
            </div>
            <div className="relative w-full max-w-md rounded-2xl border border-white/10 bg-white p-7 shadow-2xl md:p-9">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <img
                                src="/images/erlass-logo.png"
                                alt="Erlass Prokreatif Indonesia"
                                className="h-20 w-auto object-contain"
                            />
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
