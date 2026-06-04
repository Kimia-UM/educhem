<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { toast } from 'vue-sonner';

const props = defineProps<{
    classes: Array<{
        id: number;
        class_name: string;
        class_code: string;
        description: string | null;
        created_at: string;
        students_count?: number;
    }>;
}>();

const searchQuery = ref('');

const filteredClasses = computed(() => {
    if (!searchQuery.value) return props.classes;

    const query = searchQuery.value.toLowerCase();
    return props.classes.filter(
        (cls) =>
            cls.class_name.toLowerCase().includes(query) ||
            cls.class_code.toLowerCase().includes(query),
    );
});

const copyCode = (code: string) => {
    navigator.clipboard.writeText(code);
    toast.success('Kode Disalin!', {
        description: `Kode kelas ${code} berhasil disalin ke clipboard.`,
        icon: '📋',
    });
};

// --- 1. LOGIKA BUAT KELAS BARU ---
const isCreateModalOpen = ref(false);

const createForm = useForm({
    class_name: '',
    description: '',
});

const openCreateModal = () => {
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    setTimeout(() => {
        createForm.reset();
        createForm.clearErrors();
    }, 200);
};

const submitCreate = () => {
    const createdClassName = createForm.class_name;

    createForm.post(route('guru.classes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.success('Kelas Aktif!', {
                description: `Kelas "${createdClassName}" berhasil dibuat. Kode unik siap dibagikan ke siswa Anda.`,
                icon: '🚀',
            });
        },
        onError: () => {
            toast.error('Gagal Membuat Kelas', {
                description:
                    'Mohon periksa kembali isian form Anda. Nama kelas wajib diisi.',
                icon: '⚠️',
            });
        },
    });
};

// --- 2. LOGIKA EDIT KELAS ---
const isEditModalOpen = ref(false);
const editingClassId = ref<number | null>(null);

const editForm = useForm({
    class_name: '',
    description: '',
});

const openEditModal = (cls: any) => {
    editingClassId.value = cls.id;
    editForm.class_name = cls.class_name;
    editForm.description = cls.description || '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    setTimeout(() => {
        editForm.reset();
        editingClassId.value = null;
    }, 200);
};

const submitEdit = () => {
    if (!editingClassId.value) return;

    editForm.put(route('guru.classes.update', editingClassId.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.success('Kelas Diperbarui', {
                description: 'Informasi kelas berhasil disimpan.',
                icon: '✅',
            });
        },
    });
};

// --- 3. LOGIKA CUSTOM MODAL HAPUS ---
const isDeleteModalOpen = ref(false);
const classToDelete = ref<{ id: number; name: string } | null>(null);
const isDeleting = ref(false);

const confirmDelete = (id: number, name: string) => {
    classToDelete.value = { id, name };
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    setTimeout(() => {
        classToDelete.value = null;
        isDeleting.value = false;
    }, 200);
};

const executeDelete = () => {
    if (!classToDelete.value) return;
    isDeleting.value = true;

    router.delete(route('guru.classes.destroy', classToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Kelas Dihapus', {
                description: `Kelas "${classToDelete.value?.name}" berhasil dihapus.`,
                icon: '🗑️',
            });
            closeDeleteModal();
        },
        onError: () => {
            toast.error('Gagal', {
                description: 'Terjadi kesalahan saat menghapus kelas.',
            });
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Manajemen Kelas" />

    <div class="min-h-screen bg-[#F8FAFC] px-6 py-8 font-sans lg:px-10">
        <div class="mx-auto mb-8 max-w-7xl">
            <div
                class="flex flex-col items-start justify-between gap-4 border-b border-slate-200 pb-6 md:flex-row md:items-end"
            >
                <div>
                    <h1
                        class="text-[26px] font-bold tracking-tight text-slate-900"
                    >
                        Manajemen Kelas
                    </h1>
                    <p class="mt-1 text-[14px] font-medium text-slate-500">
                        Kelola seluruh kelas, salin kode unik, dan pantau jumlah
                        siswa Anda.
                    </p>
                </div>

                <div
                    class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center"
                >
                    <div class="relative w-full sm:w-64">
                        <i
                            class="pi pi-search absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400"
                        ></i>
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama atau kode kelas..."
                            class="h-10 w-full rounded-lg border-slate-200 bg-white pl-9 text-[13px] focus-visible:ring-indigo-500"
                        />
                    </div>
                    <div class="flex-shrink-0">
                        <Button
                            @click="openCreateModal"
                            class="h-10 w-full bg-indigo-600 px-4 font-semibold text-white hover:bg-indigo-700 sm:w-auto"
                        >
                            <i class="pi pi-plus mr-2"></i> Buat Kelas
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl">
            <div
                v-if="filteredClasses.length > 0"
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="cls in filteredClasses"
                    :key="cls.id"
                    class="group flex flex-col rounded-xl border-slate-200 bg-white shadow-sm transition-all duration-200 hover:shadow-md"
                >
                    <CardHeader class="border-b border-slate-50 pb-3">
                        <div class="flex items-start justify-between">
                            <div class="pr-3">
                                <CardTitle
                                    class="text-[17px] leading-tight font-bold text-slate-900 transition-colors group-hover:text-indigo-600"
                                >
                                    {{ cls.class_name }}
                                </CardTitle>
                                <CardDescription
                                    class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-slate-500"
                                >
                                    <i class="pi pi-calendar text-[10px]"></i>
                                    Dibuat:
                                    {{
                                        new Date(
                                            cls.created_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                        })
                                    }}
                                </CardDescription>
                            </div>

                            <button
                                @click="copyCode(cls.class_code)"
                                class="group/code flex shrink-0 cursor-pointer flex-col items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50/50 p-2 transition-colors hover:bg-indigo-100"
                                title="Klik untuk menyalin kode"
                            >
                                <span
                                    class="font-mono text-[14px] font-bold tracking-widest text-indigo-700"
                                    >{{ cls.class_code }}</span
                                >
                                <span
                                    class="mt-0.5 text-[9px] font-bold tracking-wider text-indigo-400 uppercase group-hover/code:text-indigo-600"
                                >
                                    <i class="pi pi-copy mr-0.5 text-[8px]"></i>
                                    Salin
                                </span>
                            </button>
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col pt-4 pb-4">
                        <p
                            class="mb-4 line-clamp-2 flex-1 text-[13px] text-slate-600"
                        >
                            {{
                                cls.description ||
                                'Tidak ada deskripsi khusus untuk kelas ini.'
                            }}
                        </p>

                        <div class="mb-4 grid grid-cols-2 gap-3">
                            <div
                                class="flex flex-col rounded-lg border border-slate-100 bg-slate-50 p-2.5"
                            >
                                <span
                                    class="mb-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                                    >Total Siswa</span
                                >
                                <div
                                    class="flex items-center gap-1.5 text-[14px] font-bold text-slate-700"
                                >
                                    <i
                                        class="pi pi-users text-[12px] text-blue-500"
                                    ></i>
                                    {{ cls.students_count || 0 }}
                                </div>
                            </div>
                            <div
                                class="flex flex-col rounded-lg border border-slate-100 bg-slate-50 p-2.5"
                            >
                                <span
                                    class="mb-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                                    >Review POE</span
                                >
                                <div
                                    class="flex items-center gap-1.5 text-[14px] font-bold text-slate-700"
                                >
                                    <i
                                        class="pi pi-file-edit text-[12px] text-emerald-500"
                                    ></i>
                                    0
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <Link
                                :href="route('guru.classes.show', cls.id)"
                                class="flex-1"
                            >
                                <Button
                                    class="h-9 w-full bg-indigo-600 text-[12px] text-white hover:bg-indigo-700"
                                >
                                    Kelola Kelas
                                    <i
                                        class="pi pi-arrow-right ml-2 text-[10px]"
                                    ></i>
                                </Button>
                            </Link>

                            <Button
                                variant="outline"
                                size="icon"
                                @click="openEditModal(cls)"
                                class="h-9 w-9 shrink-0 border-amber-200 text-amber-500 transition-colors hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600"
                                title="Edit Kelas"
                            >
                                <i class="pi pi-pencil text-[13px]"></i>
                            </Button>

                            <Button
                                variant="outline"
                                size="icon"
                                @click="confirmDelete(cls.id, cls.class_name)"
                                class="h-9 w-9 shrink-0 border-rose-200 text-rose-500 transition-colors hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600"
                                title="Hapus Kelas"
                            >
                                <i class="pi pi-trash text-[13px]"></i>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-24 text-center"
            >
                <div
                    class="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-slate-50"
                >
                    <i
                        class="pi pi-search text-4xl text-slate-300"
                        v-if="searchQuery"
                    ></i>
                    <i
                        class="pi pi-folder-open text-4xl text-slate-300"
                        v-else
                    ></i>
                </div>
                <h4 class="mb-2 text-[18px] font-bold text-slate-800">
                    {{
                        searchQuery
                            ? 'Kelas Tidak Ditemukan'
                            : 'Belum Ada Kelas'
                    }}
                </h4>
                <p
                    class="mb-6 max-w-[400px] text-[14px] leading-relaxed font-medium text-slate-500"
                >
                    {{
                        searchQuery
                            ? `Sistem tidak dapat menemukan kelas dengan kata kunci "${searchQuery}". Coba gunakan kata kunci lain.`
                            : 'Anda belum memiliki kelas yang terdaftar. Silakan buat kelas pertama Anda sekarang.'
                    }}
                </p>
                <Button
                    v-if="!searchQuery"
                    @click="openCreateModal"
                    class="h-10 bg-indigo-600 px-6 font-semibold text-white hover:bg-indigo-700"
                >
                    <i class="pi pi-plus mr-2"></i> Buat Kelas Baru
                </Button>

                <Button
                    v-else
                    variant="outline"
                    @click="searchQuery = ''"
                    class="h-10 border-slate-200 px-6 font-semibold text-slate-600"
                >
                    Reset Pencarian
                </Button>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 px-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-[450px] animate-in overflow-hidden rounded-2xl bg-white shadow-2xl duration-200 zoom-in-95 fade-in"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4"
                >
                    <h3
                        class="flex items-center gap-2 text-lg font-extrabold text-slate-800"
                    >
                        <i class="pi pi-plus-circle text-indigo-600"></i> Buat
                        Kelas Baru
                    </h3>
                    <button
                        @click="closeCreateModal"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-500"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6">
                    <div class="space-y-5">
                        <div
                            class="flex items-start gap-2 rounded-lg border border-indigo-100 bg-indigo-50/50 p-3 text-[12px] font-medium text-indigo-700"
                        >
                            <i class="pi pi-info-circle mt-0.5"></i>
                            Sistem akan otomatis men-generate 6 digit kode unik
                            kelas setelah Anda menyimpan form ini.
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase"
                            >
                                Nama Kelas <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="createForm.class_name"
                                type="text"
                                required
                                placeholder="Contoh: Kimia X IPA 1"
                                class="h-11 rounded-lg border-slate-200 text-[14px] shadow-sm focus-visible:ring-indigo-500"
                            />
                            <span
                                v-if="createForm.errors.class_name"
                                class="mt-1.5 block text-[11px] font-semibold text-rose-500"
                            >
                                {{ createForm.errors.class_name }}
                            </span>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase"
                            >
                                Deskripsi Singkat
                            </label>
                            <textarea
                                v-model="createForm.description"
                                rows="3"
                                placeholder="Opsional: Tuliskan tujuan atau materi kelas..."
                                class="w-full resize-none rounded-lg border border-slate-200 p-3 text-[14px] shadow-sm transition-all outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeCreateModal"
                            class="h-10 border-slate-200 px-5 font-semibold text-slate-600"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            class="h-10 bg-indigo-600 px-6 font-semibold text-white shadow-md transition-colors hover:bg-indigo-700"
                        >
                            <i
                                v-if="createForm.processing"
                                class="pi pi-spinner pi-spin mr-2"
                            ></i>
                            Buat Kelas
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 px-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-[450px] animate-in overflow-hidden rounded-2xl bg-white shadow-2xl duration-200 zoom-in-95 fade-in"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4"
                >
                    <h3
                        class="flex items-center gap-2 text-lg font-extrabold text-slate-800"
                    >
                        <i class="pi pi-pencil text-indigo-600"></i> Edit
                        Informasi Kelas
                    </h3>
                    <button
                        @click="closeEditModal"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-500"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase"
                            >
                                Nama Kelas <span class="text-rose-500">*</span>
                            </label>
                            <Input
                                v-model="editForm.class_name"
                                type="text"
                                required
                                class="h-11 rounded-lg border-slate-200 text-[14px] shadow-sm focus-visible:ring-indigo-500"
                            />
                            <span
                                v-if="editForm.errors.class_name"
                                class="mt-1.5 block text-[11px] font-semibold text-rose-500"
                            >
                                {{ editForm.errors.class_name }}
                            </span>
                        </div>

                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase"
                            >
                                Deskripsi Singkat
                            </label>
                            <textarea
                                v-model="editForm.description"
                                rows="3"
                                class="w-full resize-none rounded-lg border border-slate-200 p-3 text-[14px] shadow-sm transition-all outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEditModal"
                            class="h-10 border-slate-200 px-5 font-semibold text-slate-600"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="h-10 bg-indigo-600 px-6 font-semibold text-white shadow-md transition-colors hover:bg-indigo-700"
                        >
                            <i
                                v-if="editForm.processing"
                                class="pi pi-spinner pi-spin mr-2"
                            ></i>
                            <i v-else class="pi pi-save mr-2 text-[12px]"></i>
                            Simpan Perubahan
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div
            v-if="isDeleteModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 px-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-[400px] animate-in overflow-hidden rounded-2xl bg-white p-6 text-center shadow-2xl duration-200 zoom-in-95 fade-in"
            >
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-rose-50"
                >
                    <i
                        class="pi pi-exclamation-triangle text-3xl text-rose-500"
                    ></i>
                </div>

                <h3
                    class="text-xl font-extrabold tracking-tight text-slate-900"
                >
                    Hapus Kelas?
                </h3>
                <p
                    class="mt-2 text-[14px] leading-relaxed font-medium text-slate-500"
                >
                    Anda yakin ingin menghapus kelas
                    <strong class="text-slate-800"
                        >"{{ classToDelete?.name }}"</strong
                    >
                    secara permanen? Seluruh data worksheet siswa di dalamnya
                    juga akan terhapus.
                </p>

                <div
                    class="mt-8 flex flex-col-reverse justify-center gap-3 sm:flex-row"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeDeleteModal"
                        :disabled="isDeleting"
                        class="h-11 w-full border-slate-200 px-6 font-semibold text-slate-600 sm:w-auto"
                    >
                        Batalkan
                    </Button>
                    <Button
                        type="button"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        class="h-11 w-full bg-rose-600 px-6 font-semibold text-white shadow-md transition-colors hover:bg-rose-700 sm:w-auto"
                    >
                        <i
                            v-if="isDeleting"
                            class="pi pi-spinner pi-spin mr-2"
                        ></i>
                        <span v-else>Ya, Hapus Kelas</span>
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
