<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
} from '@/components/ui/sidebar'; // Menggunakan hierarki wajib Shadcn

const page = usePage();
const userRole = computed(() => page.props.auth.user?.roles?.[0]?.name || 'SISWA');

const menuItems = computed(() => {
    if (userRole.value === 'ADMIN') {
        return [
            { label: 'Dashboard', icon: 'pi pi-home', route: 'admin.dashboard' },
            { label: 'Manajemen User', icon: 'pi pi-users', route: 'admin.users.index' },
        ];
    }
    if (userRole.value === 'GURU') {
        return [
            { label: 'Dashboard', icon: 'pi pi-home', route: 'guru.dashboard' },
            { label: 'Manajemen Kelas', icon: 'pi pi-book', route: 'guru.classes.index' },
            { label: 'Topik Pembelajaran', icon: 'pi pi-book', route: null },
            { label: 'Worksheet POE', icon: 'pi pi-file-edit', route: null },
            { label: 'Video H5P', icon: 'pi pi-youtube', route: null },
            { label: 'Exercises & Test', icon: 'pi pi-pen-to-square', route: null },
            { label: 'AI Assistant Logs', icon: 'pi pi-sparkles', route: null },
        ];
    }
    if (userRole.value === 'SISWA') {
        return [
            { label: 'Overview', icon: 'pi pi-th-large', route: 'siswa.dashboard' },
            { label: 'Kelas Saya', icon: 'pi pi-book', route: 'siswa.classes.index' },
            { label: 'Latihan Soal', icon: 'pi pi-pencil', route: null },
            { label: 'Riwayat Progress', icon: 'pi pi-chart-bar', route: null },
        ];
    }
    return [];
});
</script>

<template>
    <Sidebar variant="inset" class="border-none">
        
        <SidebarHeader class="p-4 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 shadow-sm text-white">
                    <i class="pi pi-share-alt text-xl"></i>
                </div>
                <div class="flex flex-col overflow-hidden">
                    <span class="text-lg font-bold text-white leading-tight truncate">
                        EduChem<span class="text-blue-400">.</span>
                    </span>
                    <span class="text-[10px] font-semibold tracking-wider text-blue-200/80 uppercase truncate">
                        {{ userRole }} Workspace
                    </span>
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent class="px-2 py-4">
            <SidebarMenu>
                <SidebarMenuItem v-for="item in menuItems" :key="item.label" class="mb-1">
                    
                    <template v-if="item.route">
                        <SidebarMenuButton as-child :is-active="item.route ? route().current(item.route) : false">
                            <Link :href="route(item.route)" class="h-11 rounded-lg">
                                <i :class="item.icon" class="text-[18px] mr-2"></i>
                                <span class="text-[14px] font-medium">{{ item.label }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </template>

                    <template v-else>
                        <SidebarMenuButton disabled class="h-11 rounded-lg opacity-60">
                            <i :class="item.icon" class="text-[18px] mr-2 text-slate-300"></i>
                            <span class="text-[14px] font-medium text-slate-300">{{ item.label }}</span>
                            <span class="ml-auto text-[9px] font-bold bg-white/10 text-slate-300 px-2 py-0.5 rounded-full uppercase tracking-widest">Soon</span>
                        </SidebarMenuButton>
                    </template>

                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter class="border-t border-white/5 p-4">
            <NavUser class="text-white hover:bg-white/10 rounded-lg transition-colors" />
        </SidebarFooter>
        
    </Sidebar>
</template>

<style scoped>
/* TRICK AMAN: Memaksa background sidebar menjadi Biru Navy via CSS agar Vue Template tidak hancur */
:deep(.bg-sidebar) {
    background-color: #0B1E36 !important;
}
:deep(.text-sidebar-foreground) {
    color: #e2e8f0 !important;
}
/* Style untuk indikator Menu yang aktif (Biru Terang) */
:deep([data-active="true"]) {
    background-color: #2563eb !important; /* Biru terang */
    color: white !important;
}
</style>