import type { Page, Router, createHeadManager } from '@inertiajs/core';
import type { Config, route as routeFn } from 'ziggy-js';
import type { Auth } from '@/types/auth';

type SidebarPhase = {
    id: number;
    name: string;
};

type SidebarTopic = {
    id: number;
    title: string;
    phases: SidebarPhase[];
};

type SidebarClassroom = {
    id: number;
    class_name: string;
    topics: SidebarTopic[];
};

declare global {
    const route: typeof routeFn;

    interface Window {
        Ziggy?: Config;
    }
}

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarClasses: SidebarClassroom[];
            pendingPasswordResetsCount: number;
            defaultTab?: string;
            mustVerifyEmail?: boolean;
            status?: string;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
        route: typeof routeFn;
    }
}
