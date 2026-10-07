<script setup lang="ts">
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, watch, h, computed } from 'vue';
import { toast } from 'vue-sonner';
import DataTable from '@/components/DataTable.vue';
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
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
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
    };
    filters?: { search?: string; per_page?: number | string };
}>();

const form = useForm({});
const searchQuery = ref(props.filters?.search || '');
const perPage = ref(Number(props.filters?.per_page) || 10);

const performSearch = () => {
    router.get(
        route('admin.users.index'),
        {
            search: searchQuery.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

let searchTimeout: any = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch();
    }, 300);
});

watch(perPage, () => {
    performSearch();
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
            },
        });
    }
};

// --- DEFINISI KOLOM TANSTACK ---
const columns: ColumnDef<User>[] = [
    {
        accessorKey: 'name',
        header: 'Nama Lengkap',
        cell: ({ row }) =>
            h(
                'div',
                { class: 'font-semibold text-slate-900 text-[14px]' },
                row.getValue('name'),
            ),
    },
    {
        accessorKey: 'email',
        header: 'Email',
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-slate-500 text-[13px] font-medium' },
                row.getValue('email'),
            ),
    },
    {
        accessorKey: 'roles',
        header: 'Role',
        cell: ({ row }) => {
            const roles: Array<{ name: string }> = row.getValue('roles');

            return h(
                'div',
                { class: 'flex flex-wrap gap-1.5' },
                roles.map((role) => {
                    let badgeClass =
                        'bg-slate-100 text-slate-700 border-slate-200';

                    if (role.name === 'ADMIN') {
                        badgeClass =
                            'bg-purple-50 text-purple-700 border-purple-200';
                    }

                    if (role.name === 'GURU') {
                        badgeClass =
                            'bg-indigo-50 text-indigo-700 border-indigo-200';
                    }

                    if (role.name === 'SISWA') {
                        badgeClass =
                            'bg-emerald-50 text-emerald-700 border-emerald-200';
                    }

                    return h(
                        Badge,
                        {
                            variant: 'outline',
                            class: `${badgeClass} text-[10px] uppercase font-bold px-2 py-0.5 tracking-wider`,
                        },
                        () => role.name,
                    );
                }),
            );
        },
    },
    {
        id: 'actions',
        header: () => h('div', { class: 'text-right' }, 'Aksi'),
        cell: ({ row }) => {
            const user = row.original;

            return h('div', { class: 'flex justify-end gap-2' }, [
                h(
                    Button,
                    {
                        size: 'icon',
                        variant: 'outline',
                        class: 'h-8 w-8 bg-white border-slate-200 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 shadow-sm transition-all',
                        onClick: () => editRole(user.id),
                        title: 'Ubah Role',
                    },
                    () => h('i', { class: 'pi pi-pencil text-[12px]' }),
                ),

                // Memicu confirmDeleteUser alih-alih deleteUser langsung
                h(
                    Button,
                    {
                        size: 'icon',
                        variant: 'outline',
                        class: 'h-8 w-8 bg-white border-slate-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 shadow-sm transition-all',
                        onClick: () => confirmDeleteUser(user.id),
                        disabled: form.processing,
                        title: 'Hapus Akun',
                    },
                    () => h('i', { class: 'pi pi-trash text-[12px]' }),
                ),
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
                <h1 class="text-[26px] font-bold tracking-tight text-slate-900">
                    Manajemen Pengguna
                </h1>
                <p class="mt-1 text-[14px] font-medium text-slate-500">
                    Kelola hak akses dan peran (role) dari seluruh pengguna
                    sistem.
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
                        <span
                            class="text-[13px] leading-none font-bold text-slate-800"
                            >{{ authUser?.name || 'Admin' }}</span
                        >
                        <span
                            class="mt-0.5 text-[9px] font-bold tracking-wider text-indigo-500"
                            >ROLE: ADMIN</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl">
            <Card
                class="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm"
            >
                <CardHeader
                    class="flex flex-col justify-between gap-4 border-b border-transparent p-6 sm:flex-row sm:items-center"
                >
                    <div>
                        <CardTitle class="text-[17px] font-bold text-slate-800"
                            >Daftar Pengguna</CardTitle
                        >
                        <CardDescription
                            class="mt-1 text-[13px] font-medium text-slate-500"
                        >
                            Menampilkan
                            <span class="font-semibold text-slate-700"
                                >{{ users.from || 0 }} -
                                {{ users.to || 0 }}</span
                            >
                            dari
                            <span class="font-semibold text-slate-700">{{
                                users.total || 0
                            }}</span>
                        </CardDescription>
                    </div>

                    <div class="flex w-full items-center gap-3 sm:w-auto">
                        <!-- Entries Selector (DataTable Style) -->
                        <div
                            class="flex h-9 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1 sm:w-auto sm:justify-start"
                        >
                            <span
                                class="text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                >Tampilkan</span
                            >
                            <select
                                v-model="perPage"
                                class="h-6 cursor-pointer rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-bold text-slate-700 outline-none focus:border-indigo-500"
                            >
                                <option :value="5">5</option>
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                            <span
                                class="text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                >Data</span
                            >
                        </div>

                        <div class="relative w-full sm:w-[260px]">
                            <i
                                class="pi pi-search absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400"
                            ></i>
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama atau email..."
                                class="h-9 w-full rounded-lg border-slate-200 bg-white pl-9 text-[13px] shadow-sm placeholder:text-slate-400 focus-visible:ring-indigo-500"
                            />
                        </div>
                        <Link :href="route('admin.users.create')">
                            <Button
                                class="flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-4 text-[12.5px] font-bold text-white shadow-sm hover:bg-indigo-700"
                            >
                                <i class="pi pi-plus text-[10px]"></i>
                                Tambah Akun
                            </Button>
                        </Link>
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <DataTable :columns="columns" :data="users.data" />
                </CardContent>

                <div
                    v-if="users.links && users.links.length > 3"
                    class="flex items-center justify-between border-t border-slate-200 bg-slate-50/50 p-4 px-6"
                >
                    <span
                        class="hidden text-[13px] font-medium text-slate-500 sm:block"
                        >Paginasi Halaman</span
                    >
                    <div class="flex items-center gap-1">
                        <Component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, index) in users.links"
                            :key="index"
                            :href="link.url"
                            class="rounded-md px-3 py-1.5 text-[13px] font-semibold transition-colors"
                            :class="
                                link.active
                                    ? 'border border-slate-200 bg-white text-slate-900 shadow-sm'
                                    : link.url
                                      ? 'text-slate-600 hover:bg-slate-200/50'
                                      : 'cursor-not-allowed text-slate-300'
                            "
                        >
                            <span v-html="link.label"></span>
                        </Component>
                    </div>
                </div>
            </Card>
        </div>

        <AlertDialog
            :open="isDeleteDialogOpen"
            @update:open="isDeleteDialogOpen = $event"
        >
            <AlertDialogContent class="sm:max-w-[425px]">
                <AlertDialogHeader class="flex flex-col items-center">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-amber-100 bg-amber-50 text-amber-600 shadow-inner dark:border-amber-900/30 dark:bg-amber-950/30 dark:text-amber-400"
                    >
                        <i class="pi pi-exclamation-triangle text-2xl"></i>
                    </div>
                    <AlertDialogTitle
                        class="text-center text-xl font-extrabold text-slate-900 dark:text-slate-100"
                    >
                        Hapus Akun?
                    </AlertDialogTitle>
                    <AlertDialogDescription
                        class="mt-2 text-center text-[14px] leading-relaxed text-slate-500 dark:text-slate-400"
                    >
                        Tindakan ini tidak dapat dibatalkan. Ini akan menghapus
                        data pengguna secara permanen dari server dan mencabut
                        semua akses yang mereka miliki.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter
                    class="mt-8 flex w-full flex-col-reverse justify-center gap-3 sm:flex-row"
                >
                    <AlertDialogCancel
                        class="w-full rounded-xl border border-slate-200 text-[13px] font-bold text-slate-700 hover:bg-slate-50 sm:w-auto dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                        >Batal</AlertDialogCancel
                    >
                    <AlertDialogAction
                        @click="executeDelete"
                        class="w-full rounded-xl bg-gradient-to-r from-rose-500 to-red-600 text-[13px] font-bold text-white shadow-md shadow-rose-100 hover:from-rose-600 hover:to-red-700 sm:w-auto dark:shadow-none"
                    >
                        Ya, Hapus Akun
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
