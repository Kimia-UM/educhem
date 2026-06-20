<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import NavUser from '@/components/NavUser.vue';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
    SidebarMenuSub,
    SidebarMenuSubItem,
    SidebarMenuSubButton,
} from '@/components/ui/sidebar';

const page = usePage();
const userRole = computed(
    () => page.props.auth.user?.roles?.[0]?.name || 'SISWA',
);

const isActiveRoute = (routePattern: string) => {
    if (routePattern === 'guru.dashboard') {
        return page.url.startsWith('/guru/dashboard');
    }
    if (routePattern === 'siswa.dashboard') {
        return page.url.startsWith('/siswa/dashboard');
    }
    if (routePattern === 'admin.dashboard') {
        return page.url.startsWith('/admin/dashboard');
    }
    if (routePattern === 'admin.users.index') {
        return page.url.startsWith('/admin/users');
    }
    if (routePattern === 'admin.password-resets.index') {
        return page.url.startsWith('/admin/password-resets');
    }
    return route().current(routePattern) || (routePattern.endsWith('.index') && route().current(routePattern.replace('.index', '.*')));
};

const isClassActive = (classroomId: number) => {
    if (userRole.value === 'GURU') {
        return (route().current('guru.classes.show') && route().params.class == classroomId) || 
               ((route().current('guru.classes.topics.*') || route().current('guru.phases.*') || route().current('guru.classes.ai-chat-logs.*')) && route().params.classroom == classroomId);
    }
    if (userRole.value === 'SISWA') {
        return route().current('siswa.classes.show', {classroom: classroomId}) || 
               (route().current('siswa.worksheet.*') && route().params.classroom == classroomId);
    }
    return false;
};

const handleCollapsibleLinkClick = (routeName: string, params: any, isActive: boolean, event: MouseEvent) => {
    if (!isActive) {
        event.preventDefault();
        event.stopPropagation();
        router.visit(route(routeName, params));
    }
};

const menuItems = computed(() => {
    if (userRole.value === 'ADMIN') {
        return [
            {
                label: 'Dashboard',
                icon: 'pi pi-home',
                route: 'admin.dashboard',
            },
            {
                label: 'Manajemen User',
                icon: 'pi pi-users',
                route: 'admin.users.index',
            },
            {
                label: 'Reset Password',
                icon: 'pi pi-key',
                route: 'admin.password-resets.index',
            },
        ];
    }

    if (userRole.value === 'GURU') {
        return [
            { label: 'Dashboard', icon: 'pi pi-home', route: 'guru.dashboard' },
            {
                label: 'Manajemen Kelas',
                icon: 'pi pi-book',
                route: 'guru.classes.index',
            },
        ];
    }

    if (userRole.value === 'SISWA') {
        return [
            {
                label: 'Overview',
                icon: 'pi pi-th-large',
                route: 'siswa.dashboard',
            },
            {
                label: 'Kelas Saya',
                icon: 'pi pi-book',
                route: 'siswa.classes.index',
            },
        ];
    }

    return [];
});
</script>

<template>
    <Sidebar variant="inset" class="border-none">
        <SidebarHeader class="border-b border-white/5 p-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm"
                >
                    <i class="pi pi-share-alt text-xl"></i>
                </div>
                <div class="flex flex-col overflow-hidden">
                    <span
                        class="truncate text-lg leading-tight font-bold text-white"
                    >
                        EduChem<span class="text-blue-400">.</span>
                    </span>
                    <span
                        class="truncate text-[10px] font-semibold tracking-wider text-blue-200/80 uppercase"
                    >
                        {{ userRole }} Workspace
                    </span>
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent class="px-2 py-4">
            <SidebarMenu>
                <template v-for="item in menuItems" :key="item.label">
                    <!-- Standard Menu Items -->
                    <SidebarMenuItem v-if="item.label !== 'Kelas Saya' && item.label !== 'Manajemen Kelas'" class="mb-1">
                        <template v-if="item.route">
                            <SidebarMenuButton
                                as-child
                                :is-active="item.route ? isActiveRoute(item.route) : false"
                            >
                                <Link :href="route(item.route)" class="h-11 rounded-lg w-full flex items-center">
                                    <i :class="item.icon" class="mr-2 text-[18px]"></i>
                                    <span class="text-[14px] font-medium">{{ item.label }}</span>
                                    <span 
                                        v-if="item.label === 'Reset Password' && $page.props.pendingPasswordResetsCount > 0"
                                        class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow-sm"
                                    >
                                        {{ $page.props.pendingPasswordResetsCount }}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </template>

                        <template v-else>
                            <SidebarMenuButton disabled class="h-11 rounded-lg opacity-60">
                                <i :class="item.icon" class="mr-2 text-[18px] text-slate-300"></i>
                                <span class="text-[14px] font-medium text-slate-300">{{ item.label }}</span>
                                <span class="ml-auto rounded-full bg-white/10 px-2 py-0.5 text-[9px] font-bold tracking-widest text-slate-300 uppercase">Soon</span>
                            </SidebarMenuButton>
                        </template>
                    </SidebarMenuItem>

                    <!-- Dynamic Menu for 'Manajemen Kelas' (Guru) -->
                    <Collapsible v-else-if="item.label === 'Manajemen Kelas' && userRole === 'GURU'" as-child default-open class="group/collapsible mb-1">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton as-child :is-active="!page.url.startsWith('/guru/dashboard')">
                                    <Link :href="route('guru.classes.index')" @click="handleCollapsibleLinkClick('guru.classes.index', undefined, !page.url.startsWith('/guru/dashboard'), $event)">
                                        <i :class="item.icon" class="mr-2 text-[18px]"></i>
                                        <span class="text-[14px] font-medium">{{ item.label }}</span>
                                        <i class="pi pi-chevron-down ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180"></i>
                                    </Link>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            
                            <CollapsibleContent>
                                <SidebarMenuSub class="mt-1">
                                    <!-- LEVEL 2: Classes -->
                                    <Collapsible as-child v-for="classroom in $page.props.sidebarClasses" :key="classroom.id" class="group/class" :default-open="isClassActive(classroom.id)">
                                        <SidebarMenuSubItem>
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton as-child :is-active="isClassActive(classroom.id)">
                                                    <Link :href="route('guru.classes.show', classroom.id)" @click="handleCollapsibleLinkClick('guru.classes.show', classroom.id, isClassActive(classroom.id), $event)">
                                                        <span class="font-semibold">{{ classroom.class_name }}</span>
                                                        <i class="pi pi-chevron-down ml-auto text-[10px] transition-transform group-data-[state=open]/class:rotate-180"></i>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>
                                            
                                            <CollapsibleContent>
                                                <SidebarMenuSub class="border-l border-white/10 ml-3 pl-2 mt-1">
                                                    <!-- Topik Pembelajaran -->
                                                    <SidebarMenuSubItem class="mb-1">
                                                        <SidebarMenuSubButton as-child class="text-[12.5px] opacity-90 hover:opacity-100" :is-active="(route().current('guru.classes.show') && route().params.class == classroom.id && $page.props.defaultTab !== 'siswa' && $page.props.defaultTab !== 'rekapNilai') || ((route().current('guru.classes.topics.*') || route().current('guru.phases.*')) && route().params.classroom == classroom.id)">
                                                            <Link :href="route('guru.classes.show', classroom.id)">
                                                                <i class="pi pi-book mr-1 text-[11px]"></i>
                                                                <span>Topik Pembelajaran</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>

                                                    <!-- Daftar Siswa -->
                                                    <SidebarMenuSubItem class="mb-1">
                                                        <SidebarMenuSubButton as-child class="text-[12.5px] opacity-90 hover:opacity-100" :is-active="route().current('guru.classes.show') && route().params.class == classroom.id && $page.props.defaultTab === 'siswa'">
                                                            <!-- Mengarah ke halaman kelas, karena Daftar Siswa adalah tab di dalam halaman tersebut -->
                                                            <Link :href="route('guru.classes.show', { class: classroom.id, tab: 'siswa' })">
                                                                <i class="pi pi-users mr-1 text-[11px]"></i>
                                                                <span>Daftar Siswa</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>

                                                    <!-- Log Chatbot AI -->
                                                    <SidebarMenuSubItem class="mb-1">
                                                        <SidebarMenuSubButton as-child class="text-[12.5px] opacity-90 hover:opacity-100" :is-active="route().current('guru.classes.ai-chat-logs.index', {classroom: classroom.id})">
                                                            <Link :href="route('guru.classes.ai-chat-logs.index', {classroom: classroom.id})">
                                                                <i class="pi pi-comments mr-1 text-[11px]"></i>
                                                                <span>Log Chatbot AI</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>

                                                    <!-- Rekap Nilai -->
                                                    <SidebarMenuSubItem class="mb-1">
                                                        <SidebarMenuSubButton as-child class="text-[12.5px] opacity-90 hover:opacity-100" :is-active="route().current('guru.classes.show') && route().params.class == classroom.id && $page.props.defaultTab === 'rekapNilai'">
                                                            <Link :href="route('guru.classes.show', { class: classroom.id, tab: 'rekapNilai' })">
                                                                <i class="pi pi-percentage mr-1 text-[11px]"></i>
                                                                <span>Rekap Nilai</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuSubItem>
                                    </Collapsible>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <!-- 4-Level Dynamic Menu for 'Kelas Saya' -->
                    <Collapsible v-else as-child default-open class="group/collapsible mb-1">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton as-child :is-active="!page.url.startsWith('/siswa/dashboard')">
                                    <Link :href="route('siswa.classes.index')" @click="handleCollapsibleLinkClick('siswa.classes.index', undefined, !page.url.startsWith('/siswa/dashboard'), $event)">
                                        <i :class="item.icon" class="mr-2 text-[18px]"></i>
                                        <span class="text-[14px] font-medium">{{ item.label }}</span>
                                        <i class="pi pi-chevron-down ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180"></i>
                                    </Link>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            
                            <CollapsibleContent>
                                <SidebarMenuSub class="mt-1">
                                    <!-- LEVEL 2: Classes -->
                                    <Collapsible as-child v-for="classroom in $page.props.sidebarClasses" :key="classroom.id" class="group/class" :default-open="isClassActive(classroom.id)">
                                        <SidebarMenuSubItem>
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton as-child :is-active="isClassActive(classroom.id)">
                                                    <Link :href="route('siswa.classes.show', {classroom: classroom.id})" @click="handleCollapsibleLinkClick('siswa.classes.show', { classroom: classroom.id }, isClassActive(classroom.id), $event)">
                                                        <span class="font-semibold">{{ classroom.class_name }}</span>
                                                        <i class="pi pi-chevron-down ml-auto text-[10px] transition-transform group-data-[state=open]/class:rotate-180"></i>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>
                                            
                                            <CollapsibleContent>
                                                <SidebarMenuSub class="border-l border-white/10 ml-3 pl-2 mt-1">
                                                    <!-- LEVEL 3: Topics -->
                                                    <Collapsible as-child v-for="topic in classroom.topics" :key="topic.id" class="group/topic" :default-open="route().current('siswa.worksheet.*') && route().params.topic == topic.id">
                                                        <SidebarMenuSubItem>
                                                            <CollapsibleTrigger as-child>
                                                                <SidebarMenuSubButton as-child class="text-[13px] opacity-90" :is-active="route().current('siswa.worksheet.*') && route().params.topic == topic.id">
                                                                    <!-- Jika topik memiliki fase, klik topik langsung mengarah ke fase pertama! -->
                                                                    <Link v-if="topic.phases && topic.phases.length > 0" :href="route('siswa.worksheet.show', {classroom: classroom.id, topic: topic.id, phase: topic.phases[0].id})" @click="handleCollapsibleLinkClick('siswa.worksheet.show', { classroom: classroom.id, topic: topic.id, phase: topic.phases[0].id }, route().current('siswa.worksheet.*') && route().params.topic == topic.id, $event)">
                                                                        <span>{{ topic.title }}</span>
                                                                        <i class="pi pi-chevron-down ml-auto text-[9px] transition-transform group-data-[state=open]/topic:rotate-180"></i>
                                                                    </Link>
                                                                    <div v-else class="cursor-pointer flex items-center w-full">
                                                                        <span>{{ topic.title }}</span>
                                                                        <i class="pi pi-chevron-down ml-auto text-[9px] transition-transform group-data-[state=open]/topic:rotate-180"></i>
                                                                    </div>
                                                                </SidebarMenuSubButton>
                                                            </CollapsibleTrigger>

                                                            <CollapsibleContent>
                                                                <SidebarMenuSub class="border-l border-white/10 ml-3 pl-2 mt-1 mb-2">
                                                                    <!-- LEVEL 4: Phases -->
                                                                    <SidebarMenuSubItem v-for="phase in topic.phases" :key="phase.id">
                                                                        <SidebarMenuSubButton as-child class="text-[12px] opacity-75 hover:opacity-100" :is-active="route().current('siswa.worksheet.show', {classroom: classroom.id, topic: topic.id, phase: phase.id})">
                                                                            <Link :href="route('siswa.worksheet.show', {classroom: classroom.id, topic: topic.id, phase: phase.id})">
                                                                                <i class="pi pi-file-edit mr-1 text-[10px]"></i>
                                                                                <span>{{ phase.name }}</span>
                                                                            </Link>
                                                                        </SidebarMenuSubButton>
                                                                    </SidebarMenuSubItem>
                                                                </SidebarMenuSub>
                                                            </CollapsibleContent>
                                                        </SidebarMenuSubItem>
                                                    </Collapsible>
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuSubItem>
                                    </Collapsible>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>
                </template>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter class="border-t border-white/5 p-4">
            <NavUser
                class="rounded-lg text-white transition-colors hover:bg-white/10"
            />
        </SidebarFooter>
    </Sidebar>
</template>

<style scoped>
/* TRICK AMAN: Memaksa background sidebar menjadi Biru Navy via CSS agar Vue Template tidak hancur */
:deep(.bg-sidebar) {
    background-color: #0b1e36 !important;
    --sidebar-foreground: #ffffff;
    --sidebar-accent: rgba(255, 255, 255, 0.08);
    --sidebar-accent-foreground: #ffffff;
    --sidebar-border: rgba(255, 255, 255, 0.08);
}
:deep(.text-sidebar-foreground) {
    color: #ffffff !important; /* White for inactive items as requested */
    transition: color 0.2s ease !important;
}

/* Custom Hover state overrides for Sidebar Buttons on Dark Navy Background */
:deep([data-sidebar="menu-button"]:not([data-active='true']):hover),
:deep([data-sidebar="menu-sub-button"]:not([data-active='true']):hover) {
    background-color: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;
    transition: all 0.2s ease !important;
}

/* 1. Hanya tombol Menu Utama yang mendapatkan efek Glow Solid & Border Kiri Putih */
:deep([data-sidebar="menu-button"][data-active='true']) {
    background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-primary-hover) 100%) !important;
    color: white !important;
    font-weight: 700 !important;
    box-shadow: 0 4px 14px 0 rgba(var(--theme-primary-rgb), 0.4) !important;
    border-left: 3.5px solid #ffffff !important;
    transition: all 0.3s ease !important;
}
:deep([data-sidebar="menu-button"][data-active='true']:hover) {
    opacity: 0.95 !important;
}

/* 2. Tombol Sub-Menu (Level 2 ke bawah seperti Kelas, Topik, Fase) mendapatkan warna soft transparan tanpa glow tebal */
:deep([data-sidebar="menu-sub-button"][data-active='true']) {
    background: rgba(var(--theme-primary-rgb), 0.15) !important;
    color: #ffffff !important; /* Bright white text for readability on navy background */
    font-weight: 600 !important;
    box-shadow: none !important;
    border-left: 2.5px solid var(--theme-primary) !important;
    transition: all 0.2s ease !important;
}
:deep([data-sidebar="menu-sub-button"][data-active='true']:hover) {
    background: rgba(var(--theme-primary-rgb), 0.25) !important; /* Slightly brighter hover when active */
}
</style>
