<script setup lang="ts">
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, watch, h, computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import { toast } from 'vue-sonner';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

// IMPORT KOMPONEN ALERT DIALOG SHADCN

type User = {
    id: number;
    name: string;
    email: string;
    roles: Array<{ name: string }>;
};

const props = defineProps<{
    users: {
        data: User[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    },
    filters?: { search?: string; }
}>();

const form = useForm({});
const searchQuery = ref(props.filters?.search || '');

let searchTimeout: any = null;
watch(searchQuery, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('admin.users.index'), { search: value }, { preserveState: true, replace: true });
    }, 300);
});

// --- STATE UNTUK ALERT DIALOG ---
const isDeleteDialogOpen = ref(false);
const userToDelete = ref<number | null>(null);

const editRole = (userId: number) => {
    router.get(route('admin.users.edit', userId));
};

// Fungsi ini sekarang hanya membuka pop-up, tidak langsung menghapus
const confirmDeleteUser = (userId: number) => {
    userToDelete.value = userId;
    isDeleteDialogOpen.value = true;
};

// Fungsi ini dieksekusi ketika tombol "Hapus Akun" di dalam pop-up ditekan
const executeDelete = () => {
    if (userToDelete.value) {
        form.delete(route('admin.users.destroy', userToDelete.value), { 
            preserveScroll: true,
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                userToDelete.value = null;
            },
            onError: () => {
                toast.error('Gagal menghapus pengguna', {
                    description: 'Terjadi kesalahan saat menghapus data.',
                });
            }
        });
    }
};

// --- DEFINISI KOLOM TANSTACK ---
const columns: ColumnDef<User>[] = [
    {
        accessorKey: 'name',
        header: 'Nama Lengkap',
        cell: ({ row }) => h('div', { class: 'font-semibold text-slate-900 text-[14px]' }, row.getValue('name')),
    },
    {
        accessorKey: 'email',
        header: 'Email',
        cell: ({ row }) => h('div', { class: 'text-slate-500 text-[13px] font-medium' }, row.getValue('email')),
    },
    {
        accessorKey: 'roles',
        header: 'Role',
        cell: ({ row }) => {
            const roles: Array<{ name: string }> = row.getValue('roles');

            return h('div', { class: 'flex flex-wrap gap-1.5' }, roles.map(role => {
                let badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';

                if (role.name === 'ADMIN') {
badgeClass = 'bg-purple-50 text-purple-700 border-purple-200';
}

                if (role.name === 'GURU') {
badgeClass = 'bg-indigo-50 text-indigo-700 border-indigo-200';
}

                if (role.name === 'SISWA') {
badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
}

                return h(Badge, { 
                    variant: 'outline', 
                    class: `${badgeClass} text-[10px] uppercase font-bold px-2 py-0.5 tracking-wider` 
                }, () => role.name);
            }));
        },
    },
    {
        id: 'actions',
        header: () => h('div', { class: 'text-right' }, 'Aksi'),
        cell: ({ row }) => {
            const user = row.original;
            
            return h('div', { class: 'flex justify-end gap-2' }, [
                h(Button, {
                    size: 'icon',
                    variant: 'outline',
                    class: 'h-8 w-8 bg-white border-slate-200 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 shadow-sm transition-all',
                    onClick: () => editRole(user.id),
                    title: 'Ubah Role'
                }, () => h('i', { class: 'pi pi-pencil text-[12px]' })),
                
                // Memicu confirmDeleteUser alih-alih deleteUser langsung
                h(Button, {
                    size: 'icon',
                    variant: 'outline',
                    class: 'h-8 w-8 bg-white border-slate-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 shadow-sm transition-all',
                    onClick: () => confirmDeleteUser(user.id),
                    disabled: form.processing,
                    title: 'Hapus Akun'
                }, () => h('i', { class: 'pi pi-trash text-[12px]' }))
            ]);
        },
    },
];

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <div class="min-h-screen px-4 py-6 font-sans md:px-8 lg:px-10">
        <div
            class="mx-auto mb-8 flex max-w-7xl flex-col items-start justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1 class="text-[26px] font-bold tracking-tight text-slate-900">Manajemen Pengguna</h1>
                <p class="mt-1 text-[14px] font-medium text-slate-500">
                    Kelola hak akses dan peran (role) dari seluruh pengguna sistem.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    variant="outline"
                    size="icon"
                    class="h-9 w-9 rounded-lg border-slate-200 bg-white shadow-sm transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                >
                    <i class="pi pi-bell text-[14px]"></i>
                </Button>

                <div
                    class="flex cursor-pointer items-center gap-3 rounded-full border border-slate-200 bg-white py-1.5 pr-4 pl-1.5 shadow-sm transition-colors hover:bg-slate-50"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white shadow-inner"
                    >
                        <i class="pi pi-shield text-[10px]"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[13px] leading-none font-bold text-slate-800"
                            >{{ authUser?.name || 'Admin' }}</span
                        >
                        <span class="mt-0.5 text-[9px] font-bold tracking-wider text-indigo-500">ROLE: ADMIN</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto">
            <Card class="shadow-sm border-slate-200 rounded-xl bg-white overflow-hidden">
                <CardHeader class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-transparent">
                    <div>
                        <CardTitle class="text-[17px] font-bold text-slate-800">Daftar Pengguna</CardTitle>
                        <CardDescription class="text-[13px] text-slate-500 mt-1 font-medium">
                            Menampilkan <span class="font-semibold text-slate-700">{{ users.from || 0 }} - {{ users.to || 0 }}</span> dari <span class="font-semibold text-slate-700">{{ users.total || 0 }}</span>
                        </CardDescription>
                    </div>
                    
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-[260px]">
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <Input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Cari nama atau email..." 
                                class="pl-9 h-9 bg-white border-slate-200 shadow-sm focus-visible:ring-indigo-500 rounded-lg w-full text-[13px] placeholder:text-slate-400" 
                            />
                        </div>
                        <Link :href="route('admin.users.create')">
                            <Button class="h-9 bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm text-[12.5px] font-bold rounded-lg px-4 flex items-center gap-1.5 shrink-0">
                                <i class="pi pi-plus text-[10px]"></i>
                                Tambah Akun
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                
                <CardContent class="p-0">
                    <DataTable :columns="columns" :data="users.data" />
                </CardContent>

                <div v-if="users.links && users.links.length > 3" class="border-t border-slate-200 bg-slate-50/50 p-4 px-6 flex items-center justify-between">
                    <span class="text-[13px] font-medium text-slate-500 hidden sm:block">Paginasi Halaman</span>
                    <div class="flex items-center gap-1">
                        <Component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, index) in users.links"
                            :key="index"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-md text-[13px] font-semibold transition-colors"
                            :class="link.active 
                                ? 'bg-white border border-slate-200 shadow-sm text-slate-900' 
                                : (link.url ? 'text-slate-600 hover:bg-slate-200/50' : 'text-slate-300 cursor-not-allowed')"
                        ></Component>
                    </div>
                </div>
            </Card>
        </div>

        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent class="sm:max-w-[425px]">
                <AlertDialogHeader class="flex flex-col items-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/30 text-amber-600 dark:text-amber-400 shadow-inner">
                        <i class="pi pi-exclamation-triangle text-2xl"></i>
                    </div>
                    <AlertDialogTitle class="text-slate-900 dark:text-slate-100 text-xl font-extrabold text-center">
                        Hapus Akun?
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-slate-500 dark:text-slate-400 mt-2 text-[14px] leading-relaxed text-center">
                        Tindakan ini tidak dapat dibatalkan. Ini akan menghapus data pengguna secara permanen dari server dan mencabut semua akses yang mereka miliki.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter class="mt-8 flex flex-col-reverse justify-center gap-3 sm:flex-row w-full">
                    <AlertDialogCancel class="rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 font-bold text-[13px] text-slate-700 dark:text-slate-300 w-full sm:w-auto">Batal</AlertDialogCancel>
                    <AlertDialogAction 
                        @click="executeDelete" 
                        class="rounded-xl bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 font-bold text-white shadow-md shadow-rose-100 dark:shadow-none text-[13px] w-full sm:w-auto"
                    >
                        Ya, Hapus Akun
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </div>
</template>