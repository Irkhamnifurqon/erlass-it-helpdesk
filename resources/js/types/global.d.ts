import type { Auth } from '@/types/auth';
import type { Team } from '@/types/teams';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            teams: Team[];
            notifications: {
                id: string;
                event: string;
                message: string;
                ticket_id: number | null;
                read_at: string | null;
                created_at: string;
            }[];
            helpdesk: {
                roles: string[];
                department?: string;
            };
            [key: string]: unknown;
        };
    }
}
