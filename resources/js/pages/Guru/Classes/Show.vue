<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import katex from 'katex';
import { marked } from 'marked';
import { ref, watch, computed } from 'vue';
import { toast } from 'vue-sonner';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import 'katex/dist/katex.min.css';

const stripHtml = (html: string | null | undefined): string => {
    if (!html) {
        return '';
    }

    return html
        .replace(/<\/?[^>]+(>|$)/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
};

const props = defineProps<{
    classroom: {
        id: number;
        class_name: string;
        class_code: string;
        description: string | null;
        created_at: string;
        // Data Topik (Bab)
        topics?: Array<{
            id: number;
            title: string;
            description: string | null;
            is_published: boolean;
            pivot?: { is_open: boolean; is_published: boolean };
        }>;
        // Data Siswa (Peserta Kelas)
        students?: Array<{
            id: number;
            name: string;
            email: string;
            pivot?: {
                created_at: string;
                is_evaluation_sent?: boolean;
                pre_test_score?: number | null;
                post_test_score?: number | null;
            }; // Tanggal bergabung dan status evaluasi
        }>;
    };
    chatLogs?: {
        data: Array<{
            id: number;
            user_id: number;
            prompt: string;
            response: string | null;
            created_at: string;
            user: {
                id: number;
                name: string;
            };
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    filters?: {
        search?: string;
    };
    defaultTab?: string;
}>();

const activeTab = ref<'topik' | 'siswa' | 'chatLogs' | 'rekapNilai'>(
    (props.defaultTab as any) || 'topik',
);

// Sinkronisasi perubahan prop ketika pengguna klik navigasi di sidebar (Inertia Visit)
watch(
    () => props.defaultTab,
    (newTab) => {
        if (
            newTab &&
            ['topik', 'siswa', 'chatLogs', 'rekapNilai'].includes(newTab)
        ) {
            activeTab.value = newTab as any;
        }
    },
);

const searchQuery = ref(props.filters?.search || '');

let searchTimeout: any = null;
watch(searchQuery, (newVal) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            route('guru.classes.ai-chat-logs.index', props.classroom.id),
            { search: newVal },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 400);
});

const formatDate = (dateString: string) => {
    try {
        const date = new Date(dateString);

        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateString;
    }
};

const renderMarkdown = (text: string | null) => {
    if (!text) {
        return '';
    }

    const mathBlocks: string[] = [];

    // 1. Amankan Block Math ($$ rumus baris baru $$)
    let processedText = text.replace(/\$\$(.+?)\$\$/gs, (match, math) => {
        try {
            const rendered = katex.renderToString(math, { displayMode: true });
            mathBlocks.push(
                `<div class="my-3 overflow-x-auto">${rendered}</div>`,
            );

            return `%%MATH_BLOCK_TOKEN_${mathBlocks.length - 1}%%`;
        } catch {
            return match;
        }
    });

    // 2. Amankan Inline Math (Rumus kimia di dalam baris kalimat seperti $H_2O$)
    processedText = processedText.replace(/\$(.+?)\$/g, (match, math) => {
        try {
            const rendered = katex.renderToString(math, { displayMode: false });
            mathBlocks.push(rendered);

            return `%%MATH_BLOCK_TOKEN_${mathBlocks.length - 1}%%`;
        } catch {
            return match;
        }
    });

    // 3. Render teks narasi utama ke format HTML via marked
    let finalHtml = marked.parse(processedText, { breaks: true }) as string;

    // 4. Kembalikan kode HTML KaTeX yang sudah matang ke posisinya masing-masing
    mathBlocks.forEach((renderedMath, index) => {
        finalHtml = finalHtml
            .split(`%%MATH_BLOCK_TOKEN_${index}%%`)
            .join(renderedMath);
    });

    return finalHtml;
};

const handleTabChange = (
    tab: 'topik' | 'siswa' | 'chatLogs' | 'rekapNilai',
) => {
    if (tab === 'chatLogs') {
        if (route().current('guru.classes.show')) {
            router.get(
                route('guru.classes.ai-chat-logs.index', props.classroom.id),
            );
        } else {
            activeTab.value = 'chatLogs';
        }
    } else {
        if (route().current('guru.classes.ai-chat-logs.index')) {
            // Kembali ke halaman kelas jika sedang berada di log chat AI
            router.get(
                route('guru.classes.show', {
                    class: props.classroom.id,
                    tab: tab,
                }),
            );
        } else {
            // Berada di halaman kelas, langsung ganti tab state
            activeTab.value = tab;
            // Update URL query string secara senyap (tanpa reload Inertia)
            window.history.replaceState(
                {},
                '',
                route('guru.classes.show', {
                    class: props.classroom.id,
                    tab: tab,
                }),
            );
        }
    }
};

const isLogTimedOut = (dateString: string) => {
    try {
        const createdTimeStr = dateString;
        const utcTimeStr =
            createdTimeStr.endsWith('Z') || createdTimeStr.includes('+')
                ? createdTimeStr
                : createdTimeStr.replace(' ', 'T') + 'Z';

        const createdTime = new Date(utcTimeStr).getTime();
        const nowTime = new Date().getTime();
        const diffSeconds = (nowTime - createdTime) / 1000;

        return diffSeconds > 90;
    } catch {
        return false;
    }
};

// ==========================================
// LOGIKA SALIN KODE KELAS
// ==========================================
const copyCode = () => {
    navigator.clipboard.writeText(props.classroom.class_code);
    toast.success('Kode Disalin!', {
        description: `Kode kelas ${props.classroom.class_code} berhasil disalin ke clipboard.`,
    });
};

// ==========================================
// LOGIKA BUAT TOPIK BARU
// ==========================================
const isCreateTopicModalOpen = ref(false);
const topicForm = useForm({ title: '', description: '' });

const openTopicModal = () => {
    isCreateTopicModalOpen.value = true;
};
const closeTopicModal = () => {
    isCreateTopicModalOpen.value = false;
    setTimeout(() => {
        topicForm.reset();
        topicForm.clearErrors();
    }, 200);
};

const submitTopic = () => {
    // Sesuai dengan route resource: guru.classes.topics.store
    topicForm.post(route('guru.classes.topics.store', props.classroom.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeTopicModal();
            toast.success('Topik Berhasil Dibuat', {
                description: `Topik "${topicForm.title}" siap digunakan.`,
            });
        },
        onError: () => {
            toast.error('Gagal Menyimpan', {
                description: `Mohon periksa kembali form pengisian topik Anda.`,
            });
        },
    });
};

const studentSearchQuery = ref('');
const filteredStudents = computed(() => {
    const students = props.classroom.students || [];

    if (!studentSearchQuery.value) {
        return students;
    }

    return students.filter((s) =>
        s.name.toLowerCase().includes(studentSearchQuery.value.toLowerCase()),
    );
});

const currentPage = ref(1);
const itemsPerPage = ref(10);

watch(studentSearchQuery, () => {
    currentPage.value = 1;
});

watch(itemsPerPage, () => {
    currentPage.value = 1;
});

const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;

    return filteredStudents.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(filteredStudents.value.length / itemsPerPage.value) || 1;
});

const isKickModalOpen = ref(false);
const studentToKick = ref<any | null>(null);

const kickStudent = (student: any) => {
    studentToKick.value = student;
    isKickModalOpen.value = true;
};

const closeKickModal = () => {
    isKickModalOpen.value = false;
    studentToKick.value = null;
};

const executeKickStudent = () => {
    if (studentToKick.value) {
        router.delete(
            route('guru.classes.students.kick', {
                classroom: props.classroom.id,
                student: studentToKick.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeKickModal();
                    toast.success('Siswa Dikeluarkan', {
                        description: `Siswa telah berhasil dikeluarkan dari kelas.`,
                    });
                },
                onError: () => {
                    closeKickModal();
                    toast.error('Gagal', {
                        description:
                            'Terjadi kesalahan saat mengeluarkan siswa.',
                    });
                },
            },
        );
    }
};

const scoresForm = ref<
    Record<
        number,
        {
            pre_test_score: number | null;
            post_test_score: number | null;
            loading: boolean;
        }
    >
>({});

watch(
    () => props.classroom.students,
    (students) => {
        if (!students) {
            return;
        }

        students.forEach((s) => {
            scoresForm.value[s.id] = {
                pre_test_score:
                    s.pivot?.pre_test_score !== undefined
                        ? s.pivot.pre_test_score
                        : null,
                post_test_score:
                    s.pivot?.post_test_score !== undefined
                        ? s.pivot.post_test_score
                        : null,
                loading: false,
            };
        });
    },
    { immediate: true },
);

const saveScores = (studentId: number) => {
    const scoreData = scoresForm.value[studentId];

    if (!scoreData) {
        return;
    }

    scoreData.loading = true;
    router.post(
        route('guru.classes.students.scores.update', {
            classroom: props.classroom.id,
            student: studentId,
        }),
        {
            pre_test_score: scoreData.pre_test_score,
            post_test_score: scoreData.post_test_score,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                scoreData.loading = false;
            },
            onSuccess: () => {
                toast.success('Berhasil', {
                    description: 'Nilai berhasil disimpan.',
                });
            },
            onError: (errors) => {
                const firstError =
                    Object.values(errors)[0] ||
                    'Terjadi kesalahan saat menyimpan nilai.';
                toast.error('Gagal', {
                    description: String(firstError),
                });
            },
        },
    );
};
</script>

<template>
    <Head :title="`Kelas: ${classroom.class_name}`" />

    <div class="min-h-screen bg-[#F8FAFC] px-4 py-6 font-sans md:px-8 lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
            >
                <div>
                    <div
                        class="mb-3 flex items-center gap-2 text-[12px] font-bold text-slate-500"
                    >
                        <Link
                            :href="route('guru.dashboard')"
                            class="transition-colors hover:text-indigo-600"
                            >Dashboard</Link
                        >
                        <i class="pi pi-chevron-right text-[8px]"></i>
                        <span class="transition-colors hover:text-indigo-600"
                            >Manajemen Kelas</span
                        >
                        <i class="pi pi-chevron-right text-[8px]"></i>
                        <span class="text-indigo-600">{{
                            classroom.class_name
                        }}</span>
                    </div>
                    <h1
                        class="flex items-center gap-3 text-[28px] font-extrabold tracking-tight text-slate-900"
                    >
                        {{ classroom.class_name }}
                    </h1>
                    <div
                        v-if="classroom.description"
                        v-html="classroom.description"
                        class="rich-text-content mt-2 max-w-3xl text-[14px] leading-relaxed font-medium text-slate-600"
                    ></div>
                    <p
                        v-else
                        class="mt-2 max-w-3xl text-[14px] leading-relaxed font-medium text-slate-600"
                    >
                        Tidak ada deskripsi khusus untuk kelas ini.
                    </p>
                </div>

                <button
                    @click="copyCode"
                    class="group flex shrink-0 cursor-pointer items-center gap-4 rounded-xl border border-indigo-100 bg-white p-3 shadow-sm transition-all hover:border-indigo-300 hover:shadow-md md:w-auto"
                >
                    <div class="flex flex-col items-start">
                        <span
                            class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                            >Kode Kelas</span
                        >
                        <span
                            class="font-mono text-[20px] font-extrabold tracking-widest text-indigo-600 group-hover:text-indigo-700"
                        >
                            {{ classroom.class_code }}
                        </span>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 transition-colors group-hover:bg-indigo-600 group-hover:text-white"
                    >
                        <i class="pi pi-copy text-[15px]"></i>
                    </div>
                </button>
            </div>

            <div
                class="mb-6 flex scrollbar-none items-center gap-6 overflow-x-auto border-b border-slate-200 pb-0.5"
            >
                <button
                    @click="handleTabChange('topik')"
                    :class="[
                        'relative shrink-0 pb-3 text-[14px] font-bold transition-all',
                        activeTab === 'topik'
                            ? 'text-indigo-600'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                >
                    <i class="pi pi-book mr-1.5 text-[12px]"></i> Topik
                    Pembelajaran
                    <div
                        v-if="activeTab === 'topik'"
                        class="absolute bottom-0 left-0 h-0.5 w-full rounded-t-full bg-indigo-600"
                    ></div>
                </button>
                <button
                    @click="handleTabChange('siswa')"
                    :class="[
                        'relative shrink-0 pb-3 text-[14px] font-bold transition-all',
                        activeTab === 'siswa'
                            ? 'text-indigo-600'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                >
                    <i class="pi pi-users mr-1.5 text-[12px]"></i> Daftar Siswa
                    <span
                        v-if="classroom.students"
                        class="ml-1 rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] text-indigo-700"
                        >{{ classroom.students.length }}</span
                    >
                    <div
                        v-if="activeTab === 'siswa'"
                        class="absolute bottom-0 left-0 h-0.5 w-full rounded-t-full bg-indigo-600"
                    ></div>
                </button>
                <button
                    @click="handleTabChange('chatLogs')"
                    :class="[
                        'relative shrink-0 pb-3 text-[14px] font-bold transition-all',
                        activeTab === 'chatLogs'
                            ? 'text-indigo-600'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                >
                    <i class="pi pi-comments mr-1.5 text-[12px]"></i> Log
                    Chatbot AI
                    <div
                        v-if="activeTab === 'chatLogs'"
                        class="absolute bottom-0 left-0 h-0.5 w-full rounded-t-full bg-indigo-600"
                    ></div>
                </button>
                <button
                    @click="handleTabChange('rekapNilai')"
                    :class="[
                        'relative shrink-0 pb-3 text-[14px] font-bold transition-all',
                        activeTab === 'rekapNilai'
                            ? 'text-indigo-600'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                >
                    <i class="pi pi-percentage mr-1.5 text-[12px]"></i> Rekap
                    Nilai
                    <div
                        v-if="activeTab === 'rekapNilai'"
                        class="absolute bottom-0 left-0 h-0.5 w-full rounded-t-full bg-indigo-600"
                    ></div>
                </button>
            </div>

            <div
                v-show="activeTab === 'topik'"
                class="animate-in duration-300 fade-in"
            >
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-[16px] font-bold text-slate-800">
                        Daftar Topik (Bab)
                    </h2>
                    <Button
                        @click="openTopicModal"
                        class="h-9 bg-indigo-600 text-[12px] text-white hover:bg-indigo-700"
                    >
                        <i class="pi pi-plus mr-1.5"></i> Buat Topik Baru
                    </Button>
                </div>

                <div
                    v-if="classroom.topics && classroom.topics.length > 0"
                    class="flex flex-col gap-4"
                >
                    <Card
                        v-for="topic in classroom.topics"
                        :key="topic.id"
                        class="group overflow-hidden border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div
                            class="flex flex-col justify-between gap-4 p-5 md:flex-row md:items-center"
                        >
                            <div class="flex flex-1 items-start gap-4">
                                <div
                                    class="mt-1 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 transition-colors group-hover:bg-indigo-600 group-hover:text-white md:mt-0"
                                >
                                    <i class="pi pi-folder text-xl"></i>
                                </div>
                                <div>
                                    <h3
                                        class="text-[16px] font-bold text-slate-900 transition-colors group-hover:text-indigo-600"
                                    >
                                        {{ topic.title }}
                                        <span
                                            v-if="!topic.is_published"
                                            class="ml-2 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 align-middle text-[10px] font-bold text-amber-600"
                                        >
                                            DRAFT
                                        </span>
                                    </h3>
                                    <p
                                        class="mt-1 line-clamp-2 max-w-2xl text-[13px] text-slate-500"
                                    >
                                        {{
                                            stripHtml(topic.description) ||
                                            'Tidak ada deskripsi.'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex w-full items-center justify-end gap-4 self-end border-t border-slate-100 pt-3 md:w-auto md:self-auto md:border-none md:pt-0"
                            >
                                <Link
                                    :href="
                                        route('guru.classes.topics.show', {
                                            classroom: classroom.id,
                                            topic: topic.id,
                                        })
                                    "
                                >
                                    <Button
                                        class="h-10 bg-indigo-600 px-5 text-[13px] text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md"
                                    >
                                        Kelola Fase LC5E
                                        <i
                                            class="pi pi-arrow-right ml-2 text-[11px]"
                                        ></i>
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center"
                >
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50"
                    >
                        <i class="pi pi-book text-3xl text-indigo-300"></i>
                    </div>
                    <h4 class="mb-1 text-[16px] font-bold text-slate-800">
                        Belum Ada Topik
                    </h4>
                    <p
                        class="mb-5 max-w-[350px] text-[13px] font-medium text-slate-500"
                    >
                        Topik berfungsi sebagai folder/bab untuk mengelompokkan
                        materi dan tugas siklus LC5E Anda.
                    </p>
                    <Button
                        @click="openTopicModal"
                        variant="outline"
                        class="h-9 border-indigo-200 text-[12px] text-indigo-600 hover:bg-indigo-50"
                    >
                        <i class="pi pi-plus mr-1.5"></i> Buat Topik Pertama
                    </Button>
                </div>
            </div>

            <div
                v-show="activeTab === 'siswa'"
                class="animate-in duration-300 fade-in"
            >
                <div
                    class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-[16px] font-bold text-slate-800">
                            Siswa Terdaftar
                        </h2>
                        <p class="mt-1 text-[12px] text-slate-500">
                            Daftar seluruh siswa aktif yang terdaftar di kelas
                            ini.
                        </p>
                    </div>

                    <!-- Controls -->
                    <div
                        class="flex w-full flex-col items-center gap-3 sm:w-auto sm:flex-row"
                    >
                        <!-- Entries Selector (DataTable Style) -->
                        <div
                            class="flex h-9 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1 sm:w-auto sm:justify-start"
                        >
                            <span
                                class="text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                >Tampilkan</span
                            >
                            <select
                                v-model="itemsPerPage"
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

                        <Button
                            @click="copyCode"
                            variant="outline"
                            class="inline-flex h-9 w-full items-center justify-center gap-1.5 border-slate-200 bg-white text-[12px] sm:w-auto"
                        >
                            <i class="pi pi-copy text-[12px]"></i>
                            <span>Salin Kode</span>
                        </Button>

                        <!-- Search Input -->
                        <div class="relative w-full sm:w-64">
                            <i
                                class="pi pi-search absolute top-1/2 left-3 -translate-y-1/2 text-[12px] text-slate-400"
                            ></i>
                            <input
                                v-model="studentSearchQuery"
                                type="text"
                                placeholder="Cari nama siswa..."
                                class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 pr-3 pl-8 text-[12px] font-medium text-slate-800 transition-colors focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-if="classroom.students && classroom.students.length > 0"
                    class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <table
                        class="w-full border-collapse text-left text-[13px] text-slate-600"
                    >
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                        >
                            <tr>
                                <th class="px-6 py-4 font-bold">No</th>
                                <th class="px-6 py-4 font-bold">Nama Siswa</th>
                                <th class="px-6 py-4 text-center font-bold">
                                    Tanggal Bergabung
                                </th>
                                <th class="px-6 py-4 text-center font-bold">
                                    Status Evaluasi
                                </th>
                                <th class="px-6 py-4 text-center font-bold">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredStudents.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-10 text-center text-slate-400 italic"
                                >
                                    <i
                                        class="pi pi-users mb-2 block text-2xl"
                                    ></i>
                                    Tidak ada data siswa ditemukan.
                                </td>
                            </tr>
                            <tr
                                v-for="(siswa, idx) in paginatedStudents"
                                :key="siswa.id"
                                class="transition-colors hover:bg-slate-50/50"
                            >
                                <td
                                    class="px-6 py-4 font-medium text-slate-400"
                                >
                                    {{
                                        (currentPage - 1) * itemsPerPage +
                                        idx +
                                        1
                                    }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-indigo-100 bg-indigo-50 text-[11px] font-bold text-indigo-600 uppercase"
                                        >
                                            {{ siswa.name.substring(0, 2) }}
                                        </div>
                                        <div>
                                            <p
                                                class="leading-tight font-bold text-slate-900"
                                            >
                                                {{ siswa.name }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-[11px] text-slate-400"
                                            >
                                                {{ siswa.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="text-[12.5px] font-medium text-slate-600"
                                    >
                                        {{
                                            siswa.pivot?.created_at
                                                ? formatDate(
                                                      siswa.pivot.created_at,
                                                  )
                                                : '-'
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        v-if="siswa.pivot?.is_evaluation_sent"
                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-100 bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-600"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        Telah Dikirim
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full border border-amber-100 bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-600"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"
                                        ></span>
                                        Belum Dikirim
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'guru.classes.students.show',
                                                    {
                                                        classroom: classroom.id,
                                                        student: siswa.id,
                                                    },
                                                )
                                            "
                                        >
                                            <Button
                                                class="inline-flex h-8 items-center gap-1 rounded-xl bg-indigo-600 px-3 text-[11px] font-bold text-white shadow-sm hover:bg-indigo-700"
                                            >
                                                <i
                                                    class="pi pi-search text-[10px]"
                                                ></i>
                                                <span>Detail Evaluasi</span>
                                            </Button>
                                        </Link>

                                        <Button
                                            @click="kickStudent(siswa)"
                                            variant="outline"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-xl border-rose-100 px-3 text-[11px] font-bold text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                            title="Keluarkan Siswa"
                                        >
                                            <i
                                                class="pi pi-user-minus text-[10px]"
                                            ></i>
                                            <span>Keluarkan</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination Controls -->
                    <div
                        v-if="filteredStudents.length > 0"
                        class="flex flex-col items-center justify-between gap-4 border-t border-slate-100 bg-white px-6 py-4 sm:flex-row"
                    >
                        <p class="text-[12.5px] font-medium text-slate-500">
                            Menampilkan
                            <span class="font-extrabold text-slate-800">{{
                                (currentPage - 1) * itemsPerPage + 1
                            }}</span>
                            sampai
                            <span class="font-extrabold text-slate-800">{{
                                Math.min(
                                    currentPage * itemsPerPage,
                                    filteredStudents.length,
                                )
                            }}</span>
                            dari
                            <span class="font-extrabold text-slate-800">{{
                                filteredStudents.length
                            }}</span>
                            siswa
                        </p>

                        <div
                            v-if="totalPages > 1"
                            class="flex items-center gap-1.5"
                        >
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-8 w-8 cursor-pointer rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent"
                                :disabled="currentPage === 1"
                                @click="currentPage--"
                            >
                                <i class="pi pi-chevron-left text-[10px]"></i>
                            </Button>

                            <Button
                                v-for="page in totalPages"
                                :key="page"
                                variant="outline"
                                class="h-8 w-8 cursor-pointer rounded-lg border text-[12px] font-bold transition-all"
                                :class="
                                    currentPage === page
                                        ? 'border-indigo-600 bg-indigo-600 text-white hover:bg-indigo-700 hover:text-white'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                                "
                                @click="currentPage = page"
                            >
                                {{ page }}
                            </Button>

                            <Button
                                variant="outline"
                                size="icon"
                                class="h-8 w-8 cursor-pointer rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent"
                                :disabled="currentPage === totalPages"
                                @click="currentPage++"
                            >
                                <i class="pi pi-chevron-right text-[10px]"></i>
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center"
                >
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-50"
                    >
                        <i class="pi pi-users text-3xl text-slate-300"></i>
                    </div>
                    <h4 class="mb-1 text-[16px] font-bold text-slate-800">
                        Belum Ada Siswa
                    </h4>
                    <p
                        class="max-w-[350px] text-[13px] font-medium text-slate-500"
                    >
                        Bagikan kode unik di atas kepada siswa Anda agar mereka
                        dapat bergabung ke kelas ini.
                    </p>
                </div>
            </div>

            <div
                v-show="activeTab === 'chatLogs'"
                class="animate-in duration-300 fade-in"
            >
                <div
                    class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
                        <h2 class="text-[16px] font-bold text-slate-800">
                            Log Interaksi AI Chatbot
                        </h2>
                        <p class="mt-1 text-[13px] font-medium text-slate-500">
                            Pantau riwayat pertanyaan siswa ke AI Tutor beserta
                            respon jawabannya di kelas ini.
                        </p>
                    </div>
                    <div
                        class="flex w-full flex-col items-center gap-3 sm:w-auto sm:flex-row"
                    >
                        <a
                            :href="
                                route('guru.classes.print.chat-logs', {
                                    classroom: classroom.id,
                                    search: searchQuery,
                                })
                            "
                            target="_blank"
                            class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[12.5px] font-bold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 sm:w-auto"
                        >
                            <i
                                class="pi pi-print text-[13px] text-slate-500"
                            ></i>
                            <span>Cetak Laporan (PDF)</span>
                        </a>
                        <div class="relative w-full sm:w-64">
                            <i
                                class="pi pi-search absolute top-1/2 left-3 -translate-y-1/2 text-[13px] text-slate-400"
                            ></i>
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama siswa..."
                                class="h-9 rounded-xl border-slate-200 bg-white pr-4 pl-9 text-[13px] shadow-sm focus-visible:ring-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-if="chatLogs && chatLogs.data && chatLogs.data.length > 0"
                    class="flex flex-col gap-5"
                >
                    <Card
                        v-for="log in chatLogs.data"
                        :key="log.id"
                        class="overflow-hidden border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="flex flex-col gap-4">
                            <!-- Header Info -->
                            <div
                                class="flex items-center justify-between border-b border-slate-100 pb-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full border border-indigo-100 bg-indigo-50 text-[13px] font-extrabold text-indigo-600 uppercase"
                                    >
                                        {{
                                            (log.user?.name || 'S').substring(
                                                0,
                                                2,
                                            )
                                        }}
                                    </div>
                                    <div>
                                        <h4
                                            class="text-[14px] leading-none font-bold text-slate-900"
                                        >
                                            {{
                                                log.user?.name ||
                                                'Siswa Terhapus'
                                            }}
                                        </h4>
                                        <span
                                            class="mt-1 block text-[11px] font-semibold text-slate-400"
                                        >
                                            Siswa
                                        </span>
                                    </div>
                                </div>
                                <span
                                    class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-400"
                                >
                                    <i class="pi pi-clock mr-1 text-[9px]"></i
                                    >{{ formatDate(log.created_at) }}
                                </span>
                            </div>

                            <!-- Q&A Content -->
                            <div class="space-y-4">
                                <!-- Student Prompt -->
                                <div
                                    class="group relative overflow-hidden rounded-xl border border-slate-100 bg-slate-50 p-4"
                                >
                                    <div
                                        class="absolute top-0 left-0 h-full w-1 bg-indigo-400"
                                    ></div>
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-bold tracking-wide text-indigo-500 uppercase"
                                    >
                                        <i class="pi pi-user text-[10px]"></i>
                                        Pertanyaan Siswa
                                    </div>
                                    <div
                                        class="text-[13.5px] leading-relaxed font-medium whitespace-pre-wrap text-slate-700"
                                    >
                                        {{ log.prompt }}
                                    </div>
                                </div>

                                <!-- AI Response -->
                                <div
                                    v-if="!log.response"
                                    class="relative overflow-hidden rounded-xl border border-dashed p-4"
                                    :class="
                                        isLogTimedOut(log.created_at)
                                            ? 'border-rose-200 bg-rose-50/40'
                                            : 'animate-pulse border-amber-200 bg-amber-50/40'
                                    "
                                >
                                    <div
                                        class="absolute top-0 left-0 h-full w-1"
                                        :class="
                                            isLogTimedOut(log.created_at)
                                                ? 'bg-rose-400'
                                                : 'bg-amber-400'
                                        "
                                    ></div>
                                    <div
                                        class="flex items-center gap-2 text-[11px] font-bold tracking-wide uppercase"
                                        :class="
                                            isLogTimedOut(log.created_at)
                                                ? 'text-rose-600'
                                                : 'text-amber-600'
                                        "
                                    >
                                        <i
                                            class="pi text-[10px]"
                                            :class="
                                                isLogTimedOut(log.created_at)
                                                    ? 'pi-exclamation-triangle'
                                                    : 'pi-spinner pi-spin'
                                            "
                                        ></i>
                                        {{
                                            isLogTimedOut(log.created_at)
                                                ? 'Gagal mendapatkan respon AI (Timeout / Kuota Limit)'
                                                : 'Menunggu respon AI...'
                                        }}
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="relative overflow-hidden rounded-xl border border-emerald-100 bg-emerald-50/10 p-4 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div
                                        class="absolute top-0 left-0 h-full w-1 bg-emerald-400"
                                    ></div>
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-bold tracking-wide text-emerald-600 uppercase"
                                    >
                                        <i
                                            class="pi pi-sparkles text-[10px]"
                                        ></i>
                                        Jawaban AI Tutor
                                    </div>
                                    <div
                                        v-html="renderMarkdown(log.response)"
                                        class="prose prose-sm prose-slate max-w-none text-[13.5px] leading-relaxed break-words text-slate-700"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Pagination -->
                    <div
                        v-if="chatLogs.links && chatLogs.links.length > 3"
                        class="mt-4 flex items-center justify-between rounded-xl border border-t border-slate-200 bg-slate-50/50 p-4 px-6"
                    >
                        <span
                            class="hidden text-[13px] font-medium text-slate-500 sm:block"
                            >Paginasi Halaman</span
                        >
                        <div class="flex items-center gap-1">
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-for="(link, index) in chatLogs.links"
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
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center"
                >
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50"
                    >
                        <i class="pi pi-comments text-3xl text-indigo-300"></i>
                    </div>
                    <h4 class="mb-1 text-[16px] font-bold text-slate-800">
                        Tidak Ada Log Chat
                    </h4>
                    <p
                        class="max-w-[350px] text-[13px] font-medium text-slate-500"
                    >
                        Belum ada pertanyaan siswa yang terekam untuk kelas ini,
                        atau pencarian Anda tidak mencocokkan nama siswa mana
                        pun.
                    </p>
                </div>
            </div>

            <!-- Tab Rekap Nilai -->
            <div
                v-show="activeTab === 'rekapNilai'"
                class="animate-in duration-300 fade-in"
            >
                <div
                    class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="text-[16px] font-bold text-slate-800">
                            Rekap Nilai Pre-test & Post-test Siswa
                        </h2>
                        <p class="mt-1 text-[12px] text-slate-500">
                            Inputkan nilai awal (sebelum pembelajaran) dan nilai
                            akhir (setelah pembelajaran) untuk masing-masing
                            siswa.
                        </p>
                    </div>

                    <!-- Controls (Ekspor & Cari) -->
                    <div
                        class="flex w-full flex-col items-center gap-3 sm:w-auto sm:flex-row"
                    >
                        <a
                            :href="
                                route('guru.classes.export.grades', {
                                    classroom: classroom.id,
                                })
                            "
                            class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[12.5px] font-bold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 sm:w-auto"
                        >
                            <i
                                class="pi pi-file-excel text-[13px] text-emerald-600"
                            ></i>
                            <span>Ekspor Excel (.csv)</span>
                        </a>

                        <!-- Search Input -->
                        <div class="relative w-full sm:w-64">
                            <i
                                class="pi pi-search absolute top-1/2 left-3 -translate-y-1/2 text-[12px] text-slate-400"
                            ></i>
                            <input
                                v-model="studentSearchQuery"
                                type="text"
                                placeholder="Cari nama siswa..."
                                class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 pr-3 pl-8 text-[12px] font-medium text-slate-800 transition-colors focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm"
                >
                    <table
                        class="w-full border-collapse text-left text-[13px] text-slate-600"
                    >
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                        >
                            <tr>
                                <th class="px-6 py-4 font-bold">No</th>
                                <th class="px-6 py-4 font-bold">Nama Siswa</th>
                                <th class="px-6 py-4 text-center font-bold">
                                    Nilai Awal (Pre-test)
                                </th>
                                <th class="px-6 py-4 text-center font-bold">
                                    Nilai Akhir (Post-test)
                                </th>
                                <th class="px-6 py-4 text-center font-bold">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredStudents.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-10 text-center text-slate-400 italic"
                                >
                                    <i
                                        class="pi pi-users mb-2 block text-2xl"
                                    ></i>
                                    Tidak ada data siswa ditemukan.
                                </td>
                            </tr>
                            <tr
                                v-for="(siswa, idx) in filteredStudents"
                                :key="siswa.id"
                                class="transition-colors hover:bg-slate-50/50"
                            >
                                <td
                                    class="px-6 py-4 font-medium text-slate-400"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-[11px] font-bold text-indigo-600 uppercase"
                                        >
                                            {{ siswa.name.substring(0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">
                                                {{ siswa.name }}
                                            </p>
                                            <p
                                                class="text-[11px] text-slate-400"
                                            >
                                                {{ siswa.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex justify-center"
                                        v-if="scoresForm[siswa.id]"
                                    >
                                        <input
                                            v-model.number="
                                                scoresForm[siswa.id]
                                                    .pre_test_score
                                            "
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="-"
                                            class="h-9 w-20 rounded-xl border border-slate-200 bg-white text-center text-[13px] font-bold text-slate-800 transition-colors focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex justify-center"
                                        v-if="scoresForm[siswa.id]"
                                    >
                                        <input
                                            v-model.number="
                                                scoresForm[siswa.id]
                                                    .post_test_score
                                            "
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="-"
                                            class="h-9 w-20 rounded-xl border border-slate-200 bg-white text-center text-[13px] font-bold text-slate-800 transition-colors focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="flex justify-center"
                                        v-if="scoresForm[siswa.id]"
                                    >
                                        <Button
                                            @click="saveScores(siswa.id)"
                                            :disabled="
                                                scoresForm[siswa.id].loading
                                            "
                                            class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-[12px] font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                        >
                                            <i
                                                v-if="
                                                    scoresForm[siswa.id].loading
                                                "
                                                class="pi pi-spin pi-spinner"
                                            ></i>
                                            <i v-else class="pi pi-save"></i>
                                            <span>Simpan</span>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="isCreateTopicModalOpen"
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
                            <i class="pi pi-folder-plus text-[15px]"></i>
                        </div>
                        <span
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100"
                            >Buat Topik Baru</span
                        >
                    </div>
                    <button
                        @click="closeTopicModal"
                        class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-900 dark:hover:text-slate-300"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>
                <form @submit.prevent="submitTopic" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >Judul Topik
                                <span class="text-rose-500">*</span></label
                            >
                            <Input
                                v-model="topicForm.title"
                                type="text"
                                required
                                class="h-11 rounded-xl border-slate-200 bg-white text-[14px] shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus-visible:border-amber-500 focus-visible:ring-amber-500/20 dark:border-slate-800 dark:bg-slate-900"
                            />
                            <span
                                v-if="topicForm.errors.title"
                                class="mt-1.5 block text-[11px] font-semibold text-rose-500"
                                >{{ topicForm.errors.title }}</span
                            >
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 uppercase dark:text-slate-300"
                                >Deskripsi (Opsional)</label
                            >
                            <RichTextEditor
                                v-model="topicForm.description"
                                placeholder="Tujuan pembelajaran, deskripsi modul, dll..."
                            />
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeTopicModal"
                            class="h-10 rounded-xl border border-slate-200 px-5 text-[13px] font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="topicForm.processing"
                            class="h-10 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 text-[13px] font-bold text-white shadow-md shadow-indigo-100 hover:from-indigo-700 hover:to-indigo-800 dark:shadow-none"
                        >
                            <i
                                v-if="topicForm.processing"
                                class="pi pi-spinner pi-spin mr-2"
                            ></i>
                            <span v-else>Simpan Topik</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal Konfirmasi Keluarkan Siswa -->
    <Teleport to="body">
        <div
            v-if="isKickModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1e36]/40 px-4 backdrop-blur-[6px] transition-all dark:bg-black/60"
        >
            <div
                class="w-full max-w-[420px] animate-in overflow-hidden rounded-3xl border border-slate-100/80 bg-white shadow-[0_20px_50px_rgba(244,63,94,0.08),_0_10px_30px_rgba(244,63,94,0.03)] duration-200 zoom-in-95 fade-in dark:border-slate-800/50 dark:bg-slate-950"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-rose-50/50 to-rose-100/20 px-6 py-4.5 dark:border-slate-800 dark:from-slate-900/50"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200/50 bg-rose-50 text-rose-600 dark:bg-rose-950/30"
                        >
                            <i
                                class="pi pi-exclamation-triangle text-[15px]"
                            ></i>
                        </div>
                        <span
                            class="text-base font-extrabold text-slate-800 dark:text-slate-100"
                            >Keluarkan Siswa?</span
                        >
                    </div>
                    <button
                        @click="closeKickModal"
                        class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-600 dark:text-slate-500"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <p
                        class="dark:text-slate-350 text-[14px] leading-relaxed text-slate-600"
                    >
                        Apakah Anda yakin ingin mengeluarkan
                        <strong>{{ studentToKick?.name }}</strong> dari kelas?
                    </p>
                    <div
                        class="mt-3 flex items-start gap-2.5 rounded-2xl border border-rose-100 bg-rose-50/50 p-4 text-[12px] leading-normal text-rose-700"
                    >
                        <i
                            class="pi pi-info-circle mt-0.5 shrink-0 text-[14px]"
                        ></i>
                        <span>
                            <strong>Tindakan ini tidak bisa dibatalkan!</strong>
                            Semua data pengerjaan, nilai pre-test/post-test, dan
                            jawaban lembar kerja siswa ini di kelas ini akan
                            dihapus secara permanen dari sistem.
                        </span>
                    </div>
                </div>

                <!-- Footer / Action Buttons -->
                <div
                    class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50/50 p-6 dark:border-slate-800"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeKickModal"
                        class="h-10 rounded-xl border border-slate-200 px-5 text-[13px] font-bold text-slate-600 hover:bg-slate-50"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        @click="executeKickStudent"
                        class="h-10 rounded-xl bg-rose-600 px-6 text-[13px] font-bold text-white shadow-md shadow-rose-100 hover:bg-rose-700"
                    >
                        Ya, Keluarkan
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
/* Merapikan margin pembungkus bawaan parser markdown agar simetris di balon chat */
:deep(.prose p) {
    margin-top: 0 !important;
    margin-bottom: 0.5rem !important;
    line-height: 1.6;
}
:deep(.prose p:last-child) {
    margin-bottom: 0 !important;
}

/* Memastikan output KaTeX tidak merusak layout bubble chat */
:deep(.katex-display) {
    margin: 0.5em 0 !important;
    overflow-x: auto;
    overflow-y: hidden;
}
</style>
