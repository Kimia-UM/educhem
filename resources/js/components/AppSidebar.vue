<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
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
    if (typeof window === 'undefined') return false;
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
    if (typeof window === 'undefined') return false;
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
    <Sidebar variant="inset" collapsible="icon" class="border-none">
        
        <SidebarHeader class="relative overflow-hidden border-b border-white/5 bg-gradient-to-b from-[#112a4a] to-transparent p-5 flex items-center justify-center transition-all duration-300 group-data-[collapsible=icon]:p-3">
            <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-blue-500/10 blur-3xl group-data-[collapsible=icon]:hidden"></div>
            
            <img 
                src="/assets/images/Logo1.png" 
                alt="Educhem Logo" 
                class="relative h-10 w-auto max-w-full object-contain transition-all duration-300 hover:scale-105 group-data-[collapsible=icon]:hidden"
            />
            <img 
                src="/assets/images/Logo_only.png" 
                alt="Educhem Icon" 
                class="hidden h-7 w-auto object-contain transition-all duration-300 hover:scale-115 group-data-[collapsible=icon]:block"
            />
        </SidebarHeader>

        <SidebarContent class="px-3 py-6">
            <SidebarMenu>
                <template v-for="(item, index) in menuItems" :key="item.label">
                    
                    <div v-if="index === 0" class="mb-3 px-3 text-[10.5px] font-bold uppercase tracking-[0.2em] text-slate-500/80 group-data-[collapsible=icon]:hidden">
                        Main Menu
                    </div>

                    <div v-if="item.label === 'Manajemen Kelas' || item.label === 'Kelas Saya'" class="mb-3 mt-8 px-3 text-[10.5px] font-bold uppercase tracking-[0.2em] text-slate-500/80 group-data-[collapsible=icon]:hidden">
                        Learning Content
                    </div>

                    <SidebarMenuItem v-if="item.label !== 'Kelas Saya' && item.label !== 'Manajemen Kelas'" class="mb-1.5 group">
                        <template v-if="item.route">
                            <SidebarMenuButton as-child :is-active="item.route ? isActiveRoute(item.route) : false">
                                <Link :href="route(item.route)" class="flex h-11 w-full items-center rounded-lg px-3 transition-all duration-300 ease-in-out hover:bg-white/5">
                                    <i :class="item.icon" class="mr-3 text-[18px] opacity-80 transition-transform duration-300 group-hover:scale-110 group-hover:text-blue-400 group-data-[collapsible=icon]:mr-0"></i>
                                    <span class="text-[14px] font-medium tracking-wide group-data-[collapsible=icon]:hidden">{{ item.label }}</span>
                                    
                                    <span v-if="item.label === 'Reset Password' && $page.props.pendingPasswordResetsCount > 0"
                                        class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-gradient-to-r from-rose-500 to-red-600 px-1.5 text-[10px] font-bold text-white shadow-sm ring-1 ring-white/20 group-data-[collapsible=icon]:hidden">
                                        {{ $page.props.pendingPasswordResetsCount }}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </template>

                        <template v-else>
                            <SidebarMenuButton disabled class="flex h-11 w-full items-center rounded-lg px-3 opacity-50">
                                <i :class="item.icon" class="mr-3 text-[18px] text-slate-400 group-data-[collapsible=icon]:mr-0"></i>
                                <span class="text-[14px] font-medium text-slate-400 group-data-[collapsible=icon]:hidden">{{ item.label }}</span>
                                <span class="ml-auto rounded-full bg-white/10 px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-widest text-slate-300 shadow-inner group-data-[collapsible=icon]:hidden">Soon</span>
                            </SidebarMenuButton>
                        </template>
                    </SidebarMenuItem>

                    <Collapsible v-else-if="item.label === 'Manajemen Kelas' && userRole === 'GURU'" as-child default-open class="group/collapsible mb-1.5">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton as-child :is-active="!page.url.startsWith('/guru/dashboard')">
                                    <Link :href="route('guru.classes.index')" @click="handleCollapsibleLinkClick('guru.classes.index', undefined, !page.url.startsWith('/guru/dashboard'), $event)" class="flex h-11 items-center rounded-lg px-3 hover:bg-white/5">
                                        <i :class="item.icon" class="mr-3 text-[18px] opacity-80 transition-transform duration-300 group-hover/collapsible:text-blue-400 group-data-[collapsible=icon]:mr-0"></i>
                                        <span class="text-[14px] font-medium tracking-wide group-data-[collapsible=icon]:hidden">{{ item.label }}</span>
                                        <i class="pi pi-chevron-down ml-auto text-xs opacity-50 transition-transform duration-300 group-data-[state=open]/collapsible:rotate-180 group-data-[collapsible=icon]:hidden"></i>
                                    </Link>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            
                            <CollapsibleContent class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                                <SidebarMenuSub class="mt-2 ml-5 border-l border-white/10 pl-0">
                                    <Collapsible as-child v-for="classroom in $page.props.sidebarClasses" :key="classroom.id" class="group/class" :default-open="isClassActive(classroom.id)">
                                        <SidebarMenuSubItem class="relative pl-3">
                                            <div class="absolute -left-[1px] top-[18px] h-px w-3 bg-white/10"></div>
                                            
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton as-child :is-active="isClassActive(classroom.id)">
                                                    <Link :href="route('guru.classes.show', classroom.id)" @click="handleCollapsibleLinkClick('guru.classes.show', classroom.id, isClassActive(classroom.id), $event)" class="flex h-9 rounded-md">
                                                        <span class="text-[13.5px] font-semibold tracking-wide">{{ classroom.class_name }}</span>
                                                        <i class="pi pi-chevron-down ml-auto text-[10px] opacity-40 transition-transform duration-300 group-data-[state=open]/class:rotate-180"></i>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>
                                            
                                            <CollapsibleContent class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                                                <SidebarMenuSub class="mt-1 mb-2 ml-3 border-l border-white/5 pl-0 space-y-0.5">
                                                    
                                                    <template v-for="(subItem, subIdx) in [
                                                        { label: 'Topik Pembelajaran', icon: 'pi-book', active: (route().current('guru.classes.show') && route().params.class == classroom.id && $page.props.defaultTab !== 'siswa' && $page.props.defaultTab !== 'rekapNilai') || ((route().current('guru.classes.topics.*') || route().current('guru.phases.*')) && route().params.classroom == classroom.id), route: route('guru.classes.show', classroom.id) },
                                                        { label: 'Daftar Siswa', icon: 'pi-users', active: route().current('guru.classes.show') && route().params.class == classroom.id && $page.props.defaultTab === 'siswa', route: route('guru.classes.show', { class: classroom.id, tab: 'siswa' }) },
                                                        { label: 'Log Chatbot AI', icon: 'pi-comments', active: route().current('guru.classes.ai-chat-logs.index', {classroom: classroom.id}), route: route('guru.classes.ai-chat-logs.index', {classroom: classroom.id}) },
                                                        { label: 'Rekap Nilai', icon: 'pi-percentage', active: route().current('guru.classes.show') && route().params.class == classroom.id && $page.props.defaultTab === 'rekapNilai', route: route('guru.classes.show', { class: classroom.id, tab: 'rekapNilai' }) }
                                                    ]" :key="subIdx">
                                                        <SidebarMenuSubItem class="relative pl-3">
                                                            <div class="absolute -left-[1px] top-1/2 h-px w-3 -translate-y-1/2 bg-white/5"></div>
                                                            <SidebarMenuSubButton as-child :is-active="subItem.active" class="h-8 rounded-md text-[12.5px] opacity-70 transition-all hover:opacity-100">
                                                                <Link :href="subItem.route" class="flex items-center gap-2">
                                                                    <i :class="`pi ${subItem.icon} text-[11px]`"></i>
                                                                    <span>{{ subItem.label }}</span>
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    </template>

                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuSubItem>
                                    </Collapsible>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible v-else-if="item.label === 'Kelas Saya' && userRole === 'SISWA'" as-child default-open class="group/collapsible mb-1.5">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton as-child :is-active="!page.url.startsWith('/siswa/dashboard')">
                                    <Link :href="route('siswa.classes.index')" @click="handleCollapsibleLinkClick('siswa.classes.index', undefined, !page.url.startsWith('/siswa/dashboard'), $event)" class="flex h-11 items-center rounded-lg px-3 hover:bg-white/5">
                                        <i :class="item.icon" class="mr-3 text-[18px] opacity-80 transition-transform duration-300 group-hover/collapsible:text-blue-400 group-data-[collapsible=icon]:mr-0"></i>
                                        <span class="text-[14px] font-medium tracking-wide group-data-[collapsible=icon]:hidden">{{ item.label }}</span>
                                        <i class="pi pi-chevron-down ml-auto text-xs opacity-50 transition-transform duration-300 group-data-[state=open]/collapsible:rotate-180 group-data-[collapsible=icon]:hidden"></i>
                                    </Link>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            
                            <CollapsibleContent class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                                <SidebarMenuSub class="mt-2 ml-5 border-l border-white/10 pl-0">
                                    <Collapsible as-child v-for="classroom in $page.props.sidebarClasses" :key="classroom.id" class="group/class" :default-open="isClassActive(classroom.id)">
                                        <SidebarMenuSubItem class="relative pl-3">
                                            <div class="absolute -left-[1px] top-[18px] h-px w-3 bg-white/10"></div>
                                            
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton as-child :is-active="isClassActive(classroom.id)">
                                                    <Link :href="route('siswa.classes.show', {classroom: classroom.id})" @click="handleCollapsibleLinkClick('siswa.classes.show', { classroom: classroom.id }, isClassActive(classroom.id), $event)" class="flex h-9 rounded-md">
                                                        <span class="text-[13.5px] font-semibold tracking-wide">{{ classroom.class_name }}</span>
                                                        <i class="pi pi-chevron-down ml-auto text-[10px] opacity-40 transition-transform duration-300 group-data-[state=open]/class:rotate-180"></i>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>
                                            
                                            <CollapsibleContent class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                                                <SidebarMenuSub class="mt-1 ml-3 border-l border-white/5 pl-0 space-y-1">
                                                    <Collapsible as-child v-for="topic in classroom.topics" :key="topic.id" class="group/topic" :default-open="route().current('siswa.worksheet.*') && route().params.topic == topic.id">
                                                        <SidebarMenuSubItem class="relative pl-3">
                                                            <div class="absolute -left-[1px] top-[16px] h-px w-3 bg-white/5"></div>
                                                            
                                                            <CollapsibleTrigger as-child>
                                                                <SidebarMenuSubButton as-child class="h-8 rounded-md text-[13px] opacity-80 transition-all hover:opacity-100" :is-active="route().current('siswa.worksheet.*') && route().params.topic == topic.id">
                                                                    <Link v-if="topic.phases && topic.phases.length > 0" :href="route('siswa.worksheet.show', {classroom: classroom.id, topic: topic.id, phase: topic.phases[0].id})" @click="handleCollapsibleLinkClick('siswa.worksheet.show', { classroom: classroom.id, topic: topic.id, phase: topic.phases[0].id }, route().current('siswa.worksheet.*') && route().params.topic == topic.id, $event)">
                                                                        <span class="truncate">{{ topic.title }}</span>
                                                                        <i class="pi pi-chevron-down ml-auto text-[9px] opacity-50 transition-transform duration-300 group-data-[state=open]/topic:rotate-180"></i>
                                                                    </Link>
                                                                    <div v-else class="flex w-full cursor-pointer items-center">
                                                                        <span class="truncate">{{ topic.title }}</span>
                                                                        <i class="pi pi-chevron-down ml-auto text-[9px] opacity-50 transition-transform duration-300 group-data-[state=open]/topic:rotate-180"></i>
                                                                    </div>
                                                                </SidebarMenuSubButton>
                                                            </CollapsibleTrigger>

                                                            <CollapsibleContent class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                                                                <SidebarMenuSub class="mt-1 mb-2 ml-3 border-l border-white/5 pl-0 space-y-0.5">
                                                                    <SidebarMenuSubItem v-for="phase in topic.phases" :key="phase.id" class="relative pl-3">
                                                                        <div class="absolute -left-[1px] top-1/2 h-px w-3 -translate-y-1/2 bg-white/5"></div>
                                                                        
                                                                        <SidebarMenuSubButton as-child class="h-7 rounded-md text-[12px] opacity-60 transition-all hover:opacity-100" :is-active="route().current('siswa.worksheet.show', {classroom: classroom.id, topic: topic.id, phase: phase.id})">
                                                                            <Link :href="route('siswa.worksheet.show', {classroom: classroom.id, topic: topic.id, phase: phase.id})" class="flex items-center gap-1.5">
                                                                                <i class="pi pi-file-edit text-[9px]"></i>
                                                                                <span class="truncate">{{ phase.name }}</span>
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

        <SidebarFooter class="border-t border-white/5 bg-[#081627] p-4 group-data-[collapsible=icon]:p-2">
            <div class="rounded-xl border border-white/5 bg-white/[0.03] p-1.5 shadow-sm transition-all hover:bg-white/[0.08] hover:border-white/10 group-data-[collapsible=icon]:p-1">
                <NavUser class="text-white bg-transparent hover:bg-transparent" />
            </div>
        </SidebarFooter>
        
    </Sidebar>
</template>

<style scoped>
/* 1. Base Navy Background Override (Safety Fallback) */
:deep(.bg-sidebar) {
    background-color: #0b1e36 !important;
    --sidebar-foreground: #cbd5e1; /* Slate 300 base */
    --sidebar-border: rgba(255, 255, 255, 0.05);
}
:deep(.text-sidebar-foreground) {
    color: #cbd5e1 !important;
}

/* 2. LEVEL 1: Active Main Menu - Enterprise LMS Style */
/* Background gradient halus, Indicator Bar kiri, & shadow lembut */
:deep([data-sidebar="menu-button"][data-active='true']) {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.02) 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border-left: 3.5px solid #3b82f6 !important;
    border-radius: 4px 8px 8px 4px !important;
    box-shadow: inset 1px 0 0 rgba(255,255,255,0.05) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
:deep([data-sidebar="menu-button"][data-active='true'] i) {
    color: #60a5fa !important; /* Icon blue when active */
    opacity: 1 !important;
}

/* 3. LEVEL 2-4: Active Sub Menu (Kelas, Topik, Fase) */
/* Lebih subtle tanpa shadow berat, fokus pada readability */
:deep([data-sidebar="menu-sub-button"][data-active='true']) {
    background-color: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}
:deep([data-sidebar="menu-sub-button"][data-active='true'] i) {
    color: #93c5fd !important;
}

/* 4. Hover States untuk item yang tidak aktif */
:deep([data-sidebar="menu-button"]:not([data-active='true']):hover),
:deep([data-sidebar="menu-sub-button"]:not([data-active='true']):hover) {
    color: #ffffff !important;
}

/* 5. Tooltip/Collapsed specific adjustments */
:deep([data-state="collapsed"] .text-\[10\.5px\]) {
    display: none !important; /* Sembunyikan label saat ditutup */
}

/* 6. Custom collapsed size for buttons and alignment */
:deep([data-collapsible="icon"] [data-sidebar="menu-button"]) {
    width: 2.5rem !important; /* 40px */
    height: 2.5rem !important; /* 40px */
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 auto !important;
}
</style>