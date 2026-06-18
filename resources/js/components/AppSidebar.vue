<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
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
                                :is-active="item.route ? route().current(item.route) : false"
                            >
                                <Link :href="route(item.route)" class="h-11 rounded-lg">
                                    <i :class="item.icon" class="mr-2 text-[18px]"></i>
                                    <span class="text-[14px] font-medium">{{ item.label }}</span>
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
                                <SidebarMenuButton as-child :is-active="route().current('guru.classes.*') || route().current('guru.phases.*')">
                                    <Link :href="route('guru.classes.index')">
                                        <i :class="item.icon" class="mr-2 text-[18px]"></i>
                                        <span class="text-[14px] font-medium">{{ item.label }}</span>
                                        <i class="pi pi-chevron-down ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180"></i>
                                    </Link>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            
                            <CollapsibleContent>
                                <SidebarMenuSub class="mt-1">
                                    <!-- LEVEL 2: Classes -->
                                    <Collapsible as-child v-for="classroom in $page.props.sidebarClasses" :key="classroom.id" class="group/class" :default-open="(route().current('guru.classes.show') && route().params.class == classroom.id) || ((route().current('guru.classes.topics.*') || route().current('guru.phases.*') || route().current('guru.classes.ai-chat-logs.*')) && route().params.classroom == classroom.id)">
                                        <SidebarMenuSubItem>
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton as-child :is-active="(route().current('guru.classes.show') && route().params.class == classroom.id) || ((route().current('guru.classes.topics.*') || route().current('guru.phases.*') || route().current('guru.classes.ai-chat-logs.*')) && route().params.classroom == classroom.id)">
                                                    <Link :href="route('guru.classes.show', classroom.id)">
                                                        <span class="font-semibold">{{ classroom.class_name }}</span>
                                                        <i class="pi pi-chevron-down ml-auto text-[10px] transition-transform group-data-[state=open]/class:rotate-180"></i>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>
                                            
                                            <CollapsibleContent>
                                                <SidebarMenuSub class="border-l border-white/10 ml-3 pl-2 mt-1">
                                                    <!-- Topik Pembelajaran -->
                                                    <SidebarMenuSubItem class="mb-1">
                                                        <SidebarMenuSubButton as-child class="text-[12.5px] opacity-90 hover:opacity-100" :is-active="(route().current('guru.classes.show') && route().params.class == classroom.id && $page.props.defaultTab !== 'siswa') || ((route().current('guru.classes.topics.*') || route().current('guru.phases.*')) && route().params.classroom == classroom.id)">
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
                                <SidebarMenuButton as-child :is-active="route().current('siswa.classes.*') || route().current('siswa.worksheet.*')">
                                    <Link :href="route('siswa.classes.index')">
                                        <i :class="item.icon" class="mr-2 text-[18px]"></i>
                                        <span class="text-[14px] font-medium">{{ item.label }}</span>
                                        <i class="pi pi-chevron-down ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180"></i>
                                    </Link>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            
                            <CollapsibleContent>
                                <SidebarMenuSub class="mt-1">
                                    <!-- LEVEL 2: Classes -->
                                    <Collapsible as-child v-for="classroom in $page.props.sidebarClasses" :key="classroom.id" class="group/class" :default-open="route().current('siswa.classes.show', {classroom: classroom.id}) || (route().current('siswa.worksheet.*') && route().params.classroom == classroom.id)">
                                        <SidebarMenuSubItem>
                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton as-child :is-active="route().current('siswa.classes.show', {classroom: classroom.id}) || (route().current('siswa.worksheet.*') && route().params.classroom == classroom.id)">
                                                    <Link :href="route('siswa.classes.show', {classroom: classroom.id})">
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
                                                                    <Link v-if="topic.phases && topic.phases.length > 0" :href="route('siswa.worksheet.show', {classroom: classroom.id, topic: topic.id, phase: topic.phases[0].id})">
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
}
:deep(.text-sidebar-foreground) {
    color: #e2e8f0 !important;
}
/* Style untuk indikator Menu yang aktif (Biru Terang) */
:deep([data-active='true']) {
    background-color: #2563eb !important; /* Biru terang */
    color: white !important;
}
</style>
