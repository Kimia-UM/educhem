<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import RichTextEditor from '@/components/RichTextEditor.vue';

const props = defineProps<{
    classroom: {
        id: number;
        class_name: string;
    };
    topic: {
        id: number;
        title: string;
        description: string | null;
        pivot: { is_published: boolean; is_open: boolean };
        phases: Array<{
            id: number;
            name: string;
            is_ai_enabled: boolean;
            is_chatbot_enabled: boolean;
            order?: number;
        }>;
    };
}>();

// ==========================================
// 1. LOGIKA PUBLISH / UNPUBLISH (Optimistic)
// ==========================================
const isToggling = ref(false);
const localIsPublished = ref(!!props.topic.pivot?.is_published);

watch(
    () => props.topic.pivot?.is_published,
    (newVal) => {
        localIsPublished.value = !!newVal;
    },
);

const togglePublish = () => {
    if (isToggling.value) {
        return;
    }

    isToggling.value = true;

    // Optimistic Update
    localIsPublished.value = !localIsPublished.value;

    router.post(
        route('guru.classes.topics.toggle-publish', {
            classroom: props.classroom.id,
            topic: props.topic.id,
        }),
        {},
        {
            preserveScroll: true,
            preserveState: false,
            only: ['topic'],
            onSuccess: () => {
                toast.success(
                    localIsPublished.value
                        ? 'Materi Dipublikasikan'
                        : 'Materi Disembunyikan (Draft)',
                    {
                        description:
                            'Status publikasi topik berhasil diperbarui ke siswa.',
                        icon: localIsPublished.value ? '🚀' : '🔒',
                    },
                );
            },
            onError: () => {
                // Revert jika gagal
                localIsPublished.value = !localIsPublished.value;
                toast.error('Gagal mengubah status publikasi!');
            },
            onFinish: () => {
                isToggling.value = false;
            },
        },
    );
};

// ==========================================
// 2. LOGIKA EDIT TOPIK
// ==========================================
const isEditModalOpen = ref(false);
const editForm = useForm({
    title: props.topic.title,
    description: props.topic.description || '',
});

const openEditModal = () => {
    editForm.title = props.topic.title;
    editForm.description = props.topic.description || '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    setTimeout(() => editForm.clearErrors(), 200);
};

const submitEdit = () => {
    editForm.put(
        route('guru.classes.topics.update', {
            classroom: props.classroom.id,
            topic: props.topic.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
            },
            onError: () => {
                toast.error('Gagal Memperbarui Topik', {
                    description:
                        'Mohon periksa kembali isian form Anda. Judul topik wajib diisi.',
                    icon: '⚠️',
                });
            },
        },
    );
};

// ==========================================
// 3. LOGIKA HAPUS TOPIK
// ==========================================
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);

const openDeleteModal = () => (isDeleteModalOpen.value = true);
const closeDeleteModal = () => (isDeleteModalOpen.value = false);

const executeDelete = () => {
    isDeleting.value = true;
    router.delete(
        route('guru.classes.topics.destroy', {
            classroom: props.classroom.id,
            topic: props.topic.id,
        }),
        {
            onSuccess: () => {
                // Note: Redirect ditangani oleh controller (kembali ke detail kelas)
                toast.success('Topik Dihapus', {
                    description: `Topik "${props.topic.title}" berhasil dihapus permanen.`,
                });
            },
            onError: () => {
                isDeleting.value = false;
                toast.error('Gagal Menghapus', {
                    description: 'Terjadi kesalahan saat menghapus topik.',
                });
            },
        },
    );
};

// ==========================================
// 4. LOGIKA TAMBAH FASE (MODAL)
// ==========================================
const isCreatePhaseModalOpen = ref(false);
const createPhaseForm = useForm({
    name: '',
});

const openCreatePhaseModal = () => (isCreatePhaseModalOpen.value = true);
const closeCreatePhaseModal = () => {
    isCreatePhaseModalOpen.value = false;
    setTimeout(() => {
        createPhaseForm.reset();
        createPhaseForm.clearErrors();
    }, 200);
};

const submitCreatePhase = () => {
    createPhaseForm.post(
        route('guru.phases.store', {
            classroom: props.classroom.id,
            topic: props.topic.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeCreatePhaseModal();
                toast.success('Fase Berhasil Dibuat', {
                    description: 'Fase baru telah ditambahkan ke dalam topik.',
                    icon: '✨',
                });
            },
        },
    );
};

// ==========================================
// 5. LOGIKA UPDATE & HAPUS FASE
// ==========================================
const updatePhase = (phase: any) => {
    // Dipicu saat input field fase kehilangan fokus (blur)
    router.put(
        route('guru.phases.update', { phase: phase.id }),
        {
            name: phase.name,
            is_ai_enabled: !!phase.is_ai_enabled, // Paksa jadi boolean
            is_chatbot_enabled: !!phase.is_chatbot_enabled, // Paksa jadi boolean
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success('Nama fase diperbarui', {
                    description: `Perubahan tersimpan otomatis.`,
                }),
        },
    );
};

const isDeletePhaseModalOpen = ref(false);
const phaseIdToDelete = ref<number | null>(null);

const deletePhase = (id: number) => {
    phaseIdToDelete.value = id;
    isDeletePhaseModalOpen.value = true;
};

const closeDeletePhaseModal = () => {
    isDeletePhaseModalOpen.value = false;
    phaseIdToDelete.value = null;
};

const executeDeletePhase = () => {
    if (phaseIdToDelete.value) {
        router.delete(
            route('guru.phases.destroy', { phase: phaseIdToDelete.value }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeDeletePhaseModal();
                    toast.success('Fase Dihapus');
                },
                onError: () => {
                    closeDeletePhaseModal();
                    toast.error('Gagal Menghapus', {
                        description: 'Terjadi kesalahan saat menghapus fase.',
                    });
                },
            },
        );
    }
};
</script>

<template>
    <Head :title="`Kelola Topik: ${topic.title}`" />

    <div class="min-h-screen bg-[#F4F7F9] px-4 py-6 font-sans md:px-8 lg:px-10">
        <div class="mx-auto max-w-5xl">
            <div
                class="mb-6 flex items-center gap-2 text-[12px] font-bold text-slate-500"
            >
                <Link
                    :href="route('guru.dashboard')"
                    class="transition-colors hover:text-indigo-600"
                    >Dashboard</Link
                >
                <i class="pi pi-chevron-right text-[8px]"></i>
                <Link
                    :href="route('guru.classes.show', classroom.id)"
                    class="transition-colors hover:text-indigo-600"
                    >{{ classroom.class_name }}</Link
                >
                <i class="pi pi-chevron-right text-[8px]"></i>
                <span class="text-indigo-600">Kelola Topik</span>
            </div>

            <Card
                class="mb-8 overflow-hidden border-slate-200 bg-white p-4 shadow-sm md:p-6"
            >
                <div
                    class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between"
                >
                    <div class="flex flex-1 items-start gap-5">
                        <div
                            class="mt-1 flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 md:mt-0"
                        >
                            <i class="pi pi-folder-open text-2xl"></i>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center gap-3">
                                <h1
                                    class="text-2xl font-extrabold tracking-tight text-slate-900"
                                >
                                    {{ topic.title }}
                                </h1>
                                <!-- Badge: 3 state — loading, draft, published -->
                                <span
                                    v-if="isToggling"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-400"
                                >
                                    <span
                                        class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-slate-400"
                                    ></span>
                                    Menyimpan...
                                </span>
                                <span
                                    v-else-if="!localIsPublished"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-700"
                                >
                                    <span
                                        class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"
                                    ></span>
                                    Draft
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700"
                                >
                                    <span
                                        class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    ></span>
                                    Published
                                </span>
                            </div>
                            <div
                                v-if="topic.description"
                                v-html="topic.description"
                                class="rich-text-content max-w-2xl text-[14px] leading-relaxed text-slate-500"
                            ></div>
                            <p
                                v-else
                                class="max-w-2xl text-[14px] leading-relaxed text-slate-500"
                            >
                                Tidak ada deskripsi khusus untuk topik ini.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        <div
                            class="flex items-center justify-between gap-3 rounded-lg border px-4 py-2.5 transition-colors sm:justify-start"
                            :class="
                                isToggling
                                    ? 'cursor-not-allowed border-slate-100 bg-slate-50 opacity-70'
                                    : localIsPublished
                                      ? 'cursor-pointer border-emerald-200 bg-emerald-50'
                                      : 'cursor-pointer border-slate-100 bg-slate-50'
                            "
                        >
                            <div class="flex flex-col gap-0.5">
                                <span
                                    class="text-[12px] font-medium"
                                    :class="
                                        localIsPublished
                                            ? 'text-emerald-700'
                                            : 'text-slate-600'
                                    "
                                    >Publikasi ke siswa</span
                                >
                                <span
                                    class="text-[11px]"
                                    :class="
                                        isToggling
                                            ? 'text-slate-400'
                                            : localIsPublished
                                              ? 'text-emerald-600'
                                              : 'text-slate-400'
                                    "
                                >
                                    {{
                                        isToggling
                                            ? 'Menyimpan perubahan...'
                                            : localIsPublished
                                              ? 'Materi sudah terlihat oleh siswa'
                                              : 'Materi belum terlihat oleh siswa'
                                    }}
                                </span>
                            </div>
                            <button
                                type="button"
                                :disabled="isToggling"
                                @click="togglePublish"
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="
                                    isToggling
                                        ? 'bg-slate-200'
                                        : localIsPublished
                                          ? 'bg-emerald-500'
                                          : 'bg-slate-300'
                                "
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200"
                                    :class="
                                        localIsPublished
                                            ? 'translate-x-6'
                                            : 'translate-x-1'
                                    "
                                />
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                @click="openEditModal"
                                variant="outline"
                                class="h-10 border-slate-200 text-slate-600 hover:bg-slate-50"
                            >
                                <i class="pi pi-pencil mr-2"></i> Edit
                            </Button>
                            <Button
                                @click="openDeleteModal"
                                variant="outline"
                                class="h-10 border-rose-100 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            >
                                <i class="pi pi-trash"></i>
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">
                        Daftar Fase Pembelajaran (LC5E)
                    </h2>
                    <p class="text-[13px] text-slate-500">
                        Buat alur tahapan pembelajaran Learning Cycle 5E:
                        Engage, Explore, Explain, Elaborate, dan Evaluate.
                    </p>
                </div>
                <Button
                    @click="openCreatePhaseModal"
                    class="bg-indigo-600 text-white shadow-md hover:bg-indigo-700"
                >
                    <i class="pi pi-plus mr-2"></i> Tambah Fase Baru
                </Button>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(phase, pIdx) in topic.phases"
                    :key="phase.id"
                    class="relative"
                >
                    <Card
                        class="flex flex-col justify-between gap-4 overflow-hidden rounded-2xl border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-indigo-300 hover:shadow-md md:flex-row md:items-center md:p-5"
                    >
                        <div class="flex flex-1 items-center gap-4">
                            <span
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-[16px] font-black text-indigo-600"
                            >
                                {{ pIdx + 1 }}
                            </span>
                            <div class="flex-1">
                                <input
                                    v-model="phase.name"
                                    @blur="updatePhase(phase)"
                                    @keyup.enter="$event.target.blur()"
                                    class="w-full border-none bg-transparent p-0 text-[16px] font-extrabold text-slate-900 placeholder:text-slate-400 focus:ring-0 focus:outline-none"
                                    placeholder="Ketik Nama Fase... (contoh: Tahap Engage)"
                                />
                                <div
                                    class="mt-1 flex items-center gap-2 text-[11px] font-bold text-slate-500"
                                >
                                    <span
                                        v-if="phase.is_ai_enabled"
                                        class="text-emerald-500"
                                        ><i class="pi pi-sparkles"></i> AI
                                        Feedback Aktif</span
                                    >
                                    <span v-else
                                        ><i class="pi pi-minus-circle"></i> AI
                                        Feedback Nonaktif</span
                                    >
                                    <span class="text-slate-300">•</span>
                                    <span
                                        v-if="phase.is_chatbot_enabled"
                                        class="text-sky-500"
                                        ><i
                                            class="pi pi-comments text-[10px]"
                                        ></i>
                                        Chatbot Aktif</span
                                    >
                                    <span v-else
                                        ><i
                                            class="pi pi-minus-circle text-[10px]"
                                        ></i>
                                        Chatbot Nonaktif</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex w-full flex-wrap items-center justify-end gap-2.5 self-end border-t border-slate-100 pt-3 md:w-auto md:self-auto md:border-none md:pt-0"
                        >
                            <Link
                                :href="
                                    route('guru.student-answers.index', {
                                        classroom: classroom.id,
                                        topic: topic.id,
                                        phase: phase.id,
                                    })
                                "
                            >
                                <Button
                                    variant="outline"
                                    class="h-10 border-emerald-200 px-4 text-[13px] text-emerald-600 shadow-sm hover:bg-emerald-50 hover:text-emerald-700"
                                >
                                    <i
                                        class="pi pi-clipboard mr-2 text-[10px]"
                                    ></i>
                                    Lihat Jawaban
                                </Button>
                            </Link>
                            <Link
                                :href="
                                    route('guru.phases.show', {
                                        classroom: classroom.id,
                                        topic: topic.id,
                                        phase: phase.id,
                                    })
                                "
                            >
                                <Button
                                    class="h-10 border border-indigo-100 bg-indigo-50 px-5 text-indigo-600 shadow-sm hover:bg-indigo-100 hover:text-indigo-700"
                                >
                                    Kelola Isi Konten
                                    <i
                                        class="pi pi-arrow-right ml-2 text-[10px]"
                                    ></i>
                                </Button>
                            </Link>

                            <button
                                @click="deletePhase(phase.id)"
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-rose-100 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600"
                                title="Hapus Fase"
                            >
                                <i class="pi pi-trash text-sm"></i>
                            </button>
                        </div>
                    </Card>
                </div>
            </div>

            <div
                v-if="topic.phases.length === 0"
                class="mt-8 flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 py-24 text-center shadow-sm"
            >
                <div
                    class="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-slate-100"
                >
                    <i class="pi pi-sitemap text-4xl text-slate-400"></i>
                </div>
                <h4 class="mb-2 text-[18px] font-extrabold text-slate-800">
                    Belum Ada Fase Pembelajaran
                </h4>
                <p
                    class="mb-6 max-w-[420px] text-[14px] leading-relaxed font-medium text-slate-500"
                >
                    Mulai bangun alur belajar siswa Anda. Tambahkan materi/fase
                    seperti
                    <strong class="text-indigo-600">Engage</strong>,
                    <strong class="text-indigo-600">Explore</strong>, atau
                    <strong class="text-indigo-600">Explain</strong> secara
                    fleksibel sesuai model LC5E.
                </p>
                <Button
                    @click="openCreatePhaseModal"
                    class="h-11 bg-indigo-600 px-6 text-[13px] text-white shadow-md hover:bg-indigo-700"
                >
                    <i class="pi pi-plus mr-2"></i> Buat Fase Pertama
                </Button>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="isCreatePhaseModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1e36]/40 px-4 backdrop-blur-[6px] transition-all dark:bg-black/60"
        >
            <div
                class="w-full max-w-[450px] animate-in overflow-hidden rounded-3xl border border-slate-100/80 bg-white shadow-[0_20px_50px_rgba(245,158,11,0.08),_0_10px_30px_rgba(99,102,241,0.05)] duration-200 zoom-in-95 fade-in dark:border-slate-800/50 dark:bg-slate-950 dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)]"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-amber-50/50 via-rose-50/30 to-orange-50/40 px-6 py-4.5 dark:border-slate-800 dark:from-slate-900/50 dark:via-slate-900/30 dark:to-slate-900/40"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-amber-200/50 bg-amber-50 text-amber-600 dark:border-amber-900/30 dark:bg-amber-950/30 dark:text-amber-400"
                        >
                            <i class="pi pi-layer-group text-[15px]"></i>
                        </div>
                        <span
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100"
                            >Buat Fase Pembelajaran</span
                        >
                    </div>
                    <button
                        @click="closeCreatePhaseModal"
                        class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-900 dark:hover:text-slate-300"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>
                <form @submit.prevent="submitCreatePhase" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >Nama Fase (Siklus LC5E)
                                <span class="text-rose-500">*</span></label
                            >
                            <Input
                                v-model="createPhaseForm.name"
                                type="text"
                                required
                                placeholder="Contoh: Tahap Engage, Eksplorasi Mandiri..."
                                class="h-11 rounded-xl border-slate-200 bg-white text-[14px] shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus-visible:border-amber-500 focus-visible:ring-amber-500/20 dark:border-slate-800 dark:bg-slate-900"
                            />
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeCreatePhaseModal"
                            class="h-10 rounded-xl border border-slate-200 px-5 text-[13px] font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="createPhaseForm.processing"
                            class="h-10 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 text-[13px] font-bold text-white shadow-md shadow-indigo-100 hover:from-indigo-700 hover:to-indigo-800 dark:shadow-none"
                        >
                            <i
                                v-if="createPhaseForm.processing"
                                class="pi pi-spinner pi-spin mr-2"
                            ></i>
                            <span v-else>Simpan Fase</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1e36]/40 px-4 backdrop-blur-[6px] transition-all dark:bg-black/60"
        >
            <div
                class="w-full max-w-[450px] animate-in overflow-hidden rounded-3xl border border-slate-100/80 bg-white shadow-[0_20px_50px_rgba(245,158,11,0.08),_0_10px_30px_rgba(99,102,241,0.05)] duration-200 zoom-in-95 fade-in dark:border-slate-800/50 dark:bg-slate-950 dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)]"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-amber-50/50 via-rose-50/30 to-orange-50/40 px-6 py-4.5 dark:border-slate-800 dark:from-slate-900/50 dark:via-slate-900/30 dark:to-slate-900/40"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-amber-200/50 bg-amber-50 text-amber-600 dark:border-amber-900/30 dark:bg-amber-950/30 dark:text-amber-400"
                        >
                            <i class="pi pi-pencil text-[15px]"></i>
                        </div>
                        <span
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100"
                            >Edit Topik</span
                        >
                    </div>
                    <button
                        @click="closeEditModal"
                        class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-900 dark:hover:text-slate-300"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>
                <form @submit.prevent="submitEdit" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >Judul Topik
                                <span class="text-rose-500">*</span></label
                            >
                            <Input
                                v-model="editForm.title"
                                type="text"
                                required
                                class="h-11 rounded-xl border-slate-200 bg-white text-[14px] shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus-visible:border-amber-500 focus-visible:ring-amber-500/20 dark:border-slate-800 dark:bg-slate-900"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >Deskripsi</label
                            >
                            <RichTextEditor
                                v-model="editForm.description"
                                placeholder="Tujuan pembelajaran, deskripsi modul, dll..."
                            />
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEditModal"
                            class="h-10 rounded-xl border border-slate-200 px-5 text-[13px] font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="h-10 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 text-[13px] font-bold text-white shadow-md shadow-indigo-100 hover:from-indigo-700 hover:to-indigo-800 dark:shadow-none"
                        >
                            <i
                                v-if="editForm.processing"
                                class="pi pi-spinner pi-spin mr-2"
                            ></i>
                            <span v-else>Simpan Perubahan</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div
            v-if="isDeleteModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1e36]/40 px-4 backdrop-blur-[6px] transition-all dark:bg-black/60"
        >
            <div
                class="w-full max-w-[400px] animate-in overflow-hidden rounded-3xl border border-slate-100/80 bg-white p-6 text-center shadow-[0_20px_50px_rgba(245,158,11,0.08),_0_10px_30px_rgba(99,102,241,0.05)] duration-200 zoom-in-95 fade-in dark:border-slate-800/50 dark:bg-slate-950 dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)]"
            >
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border border-amber-100 bg-amber-50 text-amber-600 shadow-inner dark:border-amber-900/30 dark:bg-amber-950/30 dark:text-amber-400"
                >
                    <i class="pi pi-exclamation-triangle text-2xl"></i>
                </div>
                <h3
                    class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100"
                >
                    Hapus Topik Ini?
                </h3>
                <p
                    class="mt-2 text-[14px] leading-relaxed font-medium text-slate-500 dark:text-slate-400"
                >
                    Anda yakin ingin menghapus topik ini secara permanen?
                    Seluruh Fase & Konten di dalamnya akan ikut terhapus dan
                    tidak bisa dikembalikan.
                </p>
                <div
                    class="mt-8 flex flex-col-reverse justify-center gap-3 sm:flex-row"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeDeleteModal"
                        :disabled="isDeleting"
                        class="h-11 w-full rounded-xl border border-slate-200 px-6 text-[13px] font-bold text-slate-600 hover:bg-slate-50 sm:w-auto dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                    >
                        Batalkan
                    </Button>
                    <Button
                        type="button"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        class="h-11 w-full rounded-xl bg-gradient-to-r from-rose-500 to-red-600 px-6 text-[13px] font-bold text-white shadow-md shadow-rose-100 hover:from-rose-600 hover:to-red-700 sm:w-auto dark:shadow-none"
                    >
                        <i
                            v-if="isDeleting"
                            class="pi pi-spinner pi-spin mr-2"
                        ></i>
                        <span v-else>Ya, Hapus Topik</span>
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div
            v-if="isDeletePhaseModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1e36]/40 px-4 backdrop-blur-[6px] transition-all dark:bg-black/60"
        >
            <div
                class="w-full max-w-[400px] animate-in overflow-hidden rounded-3xl border border-slate-100/80 bg-white p-6 text-center shadow-[0_20px_50px_rgba(245,158,11,0.08),_0_10px_30px_rgba(99,102,241,0.05)] duration-200 zoom-in-95 fade-in dark:border-slate-800/50 dark:bg-slate-950 dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)]"
            >
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border border-amber-100 bg-amber-50 text-amber-600 shadow-inner dark:border-amber-900/30 dark:bg-amber-950/30 dark:text-amber-400"
                >
                    <i class="pi pi-exclamation-triangle text-2xl"></i>
                </div>
                <h3
                    class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100"
                >
                    Hapus Fase Ini?
                </h3>
                <p
                    class="mt-2 text-[14px] leading-relaxed font-medium text-slate-500 dark:text-slate-400"
                >
                    Hapus fase ini? Seluruh isi materi dan pertanyaan di
                    dalamnya akan ikut terhapus permanen.
                </p>
                <div
                    class="mt-8 flex flex-col-reverse justify-center gap-3 sm:flex-row"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeDeletePhaseModal"
                        class="h-11 w-full rounded-xl border border-slate-200 px-6 text-[13px] font-bold text-slate-600 hover:bg-slate-50 sm:w-auto dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                    >
                        Batalkan
                    </Button>
                    <Button
                        type="button"
                        @click="executeDeletePhase"
                        class="h-11 w-full rounded-xl bg-gradient-to-r from-rose-500 to-red-600 px-6 text-[13px] font-bold text-white shadow-md shadow-rose-100 hover:from-rose-600 hover:to-red-700 sm:w-auto dark:shadow-none"
                    >
                        Ya, Hapus Fase
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
