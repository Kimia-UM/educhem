<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import katex from 'katex';
import { marked } from 'marked';
import { ref, watch, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import 'katex/dist/katex.min.css';

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
            is_published?: boolean;
            pivot?: { is_open: boolean }; // Fallback jika menggunakan Pivot Tabel
        }>;
        // Data Siswa (Peserta Kelas)
        students?: Array<{
            id: number;
            name: string;
            email: string;
            pivot?: { created_at: string, is_evaluation_sent?: boolean }; // Tanggal bergabung dan status evaluasi
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
    (props.defaultTab as any) || 'topik'
);

// Sinkronisasi perubahan prop ketika pengguna klik navigasi di sidebar (Inertia Visit)
watch(() => props.defaultTab, (newTab) => {
    if (newTab && ['topik', 'siswa', 'chatLogs', 'rekapNilai'].includes(newTab)) {
        activeTab.value = newTab as any;
    }
});

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
            }
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
    } catch (e) {
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
            mathBlocks.push(`<div class="my-3 overflow-x-auto">${rendered}</div>`);

            return `%%MATH_BLOCK_TOKEN_${mathBlocks.length - 1}%%`;
        } catch (e) {
            return match;
        }
    });

    // 2. Amankan Inline Math (Rumus kimia di dalam baris kalimat seperti $H_2O$)
    processedText = processedText.replace(/\$(.+?)\$/g, (match, math) => {
        try {
            const rendered = katex.renderToString(math, { displayMode: false });
            mathBlocks.push(rendered);

            return `%%MATH_BLOCK_TOKEN_${mathBlocks.length - 1}%%`;
        } catch (e) {
            return match;
        }
    });

    // 3. Render teks narasi utama ke format HTML via marked
    let finalHtml = marked.parse(processedText, { breaks: true }) as string;

    // 4. Kembalikan kode HTML KaTeX yang sudah matang ke posisinya masing-masing
    mathBlocks.forEach((renderedMath, index) => {
        finalHtml = finalHtml.split(`%%MATH_BLOCK_TOKEN_${index}%%`).join(renderedMath);
    });

    return finalHtml;
};

const handleTabChange = (tab: 'topik' | 'siswa' | 'chatLogs' | 'rekapNilai') => {
    if (tab === 'chatLogs') {
        if (route().current('guru.classes.show')) {
            router.get(route('guru.classes.ai-chat-logs.index', props.classroom.id));
        } else {
            activeTab.value = 'chatLogs';
        }
    } else {
        if (route().current('guru.classes.ai-chat-logs.index')) {
            // Kembali ke halaman kelas jika sedang berada di log chat AI
            router.get(route('guru.classes.show', { class: props.classroom.id, tab: tab }));
        } else {
            // Berada di halaman kelas, langsung ganti tab state
            activeTab.value = tab;
            // Update URL query string secara senyap (tanpa reload Inertia)
            window.history.replaceState({}, '', route('guru.classes.show', { class: props.classroom.id, tab: tab }));
        }
    }
};

const isLogTimedOut = (dateString: string) => {
    try {
        const createdTimeStr = dateString;
        const utcTimeStr = (createdTimeStr.endsWith('Z') || createdTimeStr.includes('+')) 
            ? createdTimeStr 
            : createdTimeStr.replace(' ', 'T') + 'Z';
        
        const createdTime = new Date(utcTimeStr).getTime();
        const nowTime = new Date().getTime();
        const diffSeconds = (nowTime - createdTime) / 1000;

        return diffSeconds > 45;
    } catch (e) {
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
        icon: '📋',
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
                icon: '📚',
            });
        },
        onError: () => {
            toast.error('Gagal Menyimpan', {
                description: `Mohon periksa kembali form pengisian topik Anda.`,
                icon: '⚠️',
            });
        },
    });
};

const studentSearchQuery = ref('');
const filteredStudents = computed(() => {
    const students = props.classroom.students || [];
    if (!studentSearchQuery.value) return students;
    return students.filter(s => s.name.toLowerCase().includes(studentSearchQuery.value.toLowerCase()));
});

const scoresForm = ref<Record<number, { pre_test_score: number | null, post_test_score: number | null, loading: boolean }>>({});

watch(() => props.classroom.students, (students) => {
    if (!students) return;
    students.forEach(s => {
        scoresForm.value[s.id] = {
            pre_test_score: s.pivot?.pre_test_score !== undefined ? s.pivot.pre_test_score : null,
            post_test_score: s.pivot?.post_test_score !== undefined ? s.pivot.post_test_score : null,
            loading: false
        };
    });
}, { immediate: true });

const saveScores = (studentId: number) => {
    const scoreData = scoresForm.value[studentId];
    if (!scoreData) return;
    
    scoreData.loading = true;
    router.post(route('guru.classes.students.scores.update', {
        classroom: props.classroom.id,
        student: studentId
    }), {
        pre_test_score: scoreData.pre_test_score,
        post_test_score: scoreData.post_test_score
    }, {
        preserveScroll: true,
        onFinish: () => {
            scoreData.loading = false;
        },
        onSuccess: () => {
            toast.success('Berhasil', {
                description: 'Nilai berhasil disimpan.',
                icon: '✅',
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] || 'Terjadi kesalahan saat menyimpan nilai.';
            toast.error('Gagal', {
                description: String(firstError),
                icon: '⚠️',
            });
        }
    });
};
</script>

<template>
    <Head :title="`Kelas: ${classroom.class_name}`" />

    <div class="min-h-screen bg-[#F8FAFC] px-6 py-8 font-sans lg:px-10">
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
                    <p
                        class="mt-2 max-w-3xl text-[14px] leading-relaxed font-medium text-slate-600"
                    >
                        {{
                            classroom.description ||
                            'Tidak ada deskripsi khusus untuk kelas ini.'
                        }}
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

            <div class="mb-6 flex items-center gap-6 border-b border-slate-200">
                <button
                    @click="handleTabChange('topik')"
                    :class="[
                        'relative pb-3 text-[14px] font-bold transition-all',
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
                        'relative pb-3 text-[14px] font-bold transition-all',
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
                        'relative pb-3 text-[14px] font-bold transition-all',
                        activeTab === 'chatLogs'
                            ? 'text-indigo-600'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                >
                    <i class="pi pi-comments mr-1.5 text-[12px]"></i> Log Chatbot AI
                    <div
                        v-if="activeTab === 'chatLogs'"
                        class="absolute bottom-0 left-0 h-0.5 w-full rounded-t-full bg-indigo-600"
                    ></div>
                </button>
                <button
                    @click="handleTabChange('rekapNilai')"
                    :class="[
                        'relative pb-3 text-[14px] font-bold transition-all',
                        activeTab === 'rekapNilai'
                            ? 'text-indigo-600'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                >
                    <i class="pi pi-percentage mr-1.5 text-[12px]"></i> Rekap Nilai
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
                                            v-if="
                                                !(
                                                    topic.is_published ??
                                                    topic.pivot?.is_open
                                                )
                                            "
                                            class="ml-2 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 align-middle text-[10px] font-bold text-amber-600"
                                        >
                                            DRAFT
                                        </span>
                                    </h3>
                                    <p
                                        class="mt-1 line-clamp-2 max-w-2xl text-[13px] text-slate-500"
                                    >
                                        {{
                                            topic.description ||
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
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-[16px] font-bold text-slate-800">
                        Siswa Terdaftar
                    </h2>
                    <Button
                        @click="copyCode"
                        variant="outline"
                        class="h-9 border-slate-200 bg-white text-[12px]"
                    >
                        <i class="pi pi-copy mr-1.5"></i> Salin Kode
                    </Button>
                </div>

                <div
                    v-if="classroom.students && classroom.students.length > 0"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="(siswa, index) in classroom.students"
                        :key="siswa.id"
                        :href="route('guru.classes.students.show', { classroom: classroom.id, student: siswa.id })"
                        class="block"
                    >
                        <Card
                            class="flex h-full items-center gap-4 border-slate-200 bg-white p-4 shadow-sm transition-colors hover:border-indigo-400 hover:shadow-md cursor-pointer"
                        >
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 font-bold text-slate-500 uppercase"
                            >
                                {{ siswa.name.substring(0, 2) }}
                            </div>
                            <div class="flex-1 overflow-hidden">
                                <h4
                                    class="truncate text-[14px] font-bold text-slate-900 flex items-center justify-between"
                                >
                                    {{ siswa.name }}
                                    <span v-if="siswa.pivot?.is_evaluation_sent" class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-600 border border-emerald-100 px-1.5 py-0.5 rounded-md text-[10px] font-extrabold ml-2">
                                        <i class="pi pi-check text-[8px]"></i> Dikirim
                                    </span>
                                </h4>
                                <p class="truncate text-[11px] text-slate-500">
                                    {{ siswa.email }}
                                </p>
                            </div>
                        </Card>
                    </Link>
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
                <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-[16px] font-bold text-slate-800">
                            Log Interaksi AI Chatbot
                        </h2>
                        <p class="mt-1 text-[13px] font-medium text-slate-500">
                            Pantau riwayat pertanyaan siswa ke AI Tutor beserta respon jawabannya di kelas ini.
                        </p>
                    </div>
                    <div class="relative w-full max-w-[300px]">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]"></i>
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama siswa..."
                            class="h-9 pl-9 pr-4 rounded-xl border-slate-200 text-[13px] bg-white shadow-sm focus-visible:ring-indigo-500"
                        />
                    </div>
                </div>

                <div v-if="chatLogs && chatLogs.data && chatLogs.data.length > 0" class="flex flex-col gap-5">
                    <Card
                        v-for="log in chatLogs.data"
                        :key="log.id"
                        class="overflow-hidden border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="flex flex-col gap-4">
                            <!-- Header Info -->
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-[13px] font-extrabold text-indigo-600 uppercase border border-indigo-100">
                                        {{ (log.user?.name || 'S').substring(0, 2) }}
                                    </div>
                                    <div>
                                        <h4 class="text-[14px] font-bold text-slate-900 leading-none">
                                            {{ log.user?.name || 'Siswa Terhapus' }}
                                        </h4>
                                        <span class="mt-1 text-[11px] font-semibold text-slate-400 block">
                                            Siswa
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-md">
                                    <i class="pi pi-clock mr-1 text-[9px]"></i>{{ formatDate(log.created_at) }}
                                </span>
                            </div>

                            <!-- Q&A Content -->
                            <div class="space-y-4">
                                <!-- Student Prompt -->
                                <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 relative overflow-hidden group">
                                    <div class="absolute top-0 left-0 w-1 h-full bg-indigo-400"></div>
                                    <div class="flex items-center gap-2 mb-2 text-[11px] font-bold text-indigo-500 tracking-wide uppercase">
                                        <i class="pi pi-user text-[10px]"></i> Pertanyaan Siswa
                                    </div>
                                    <div class="text-[13.5px] text-slate-700 font-medium whitespace-pre-wrap leading-relaxed">
                                        {{ log.prompt }}
                                    </div>
                                </div>

                                <!-- AI Response -->
                                <div v-if="!log.response" class="rounded-xl border border-dashed p-4 relative overflow-hidden"
                                    :class="isLogTimedOut(log.created_at) 
                                        ? 'border-rose-200 bg-rose-50/40' 
                                        : 'border-amber-200 bg-amber-50/40 animate-pulse'"
                                >
                                    <div class="absolute top-0 left-0 w-1 h-full"
                                        :class="isLogTimedOut(log.created_at) ? 'bg-rose-400' : 'bg-amber-400'"
                                    ></div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold tracking-wide uppercase"
                                        :class="isLogTimedOut(log.created_at) ? 'text-rose-600' : 'text-amber-600'"
                                    >
                                        <i class="pi text-[10px]"
                                            :class="isLogTimedOut(log.created_at) ? 'pi-exclamation-triangle' : 'pi-spinner pi-spin'"
                                        ></i>
                                        {{ isLogTimedOut(log.created_at) ? 'Gagal mendapatkan respon AI (Timeout / Kuota Limit)' : 'Menunggu respon AI...' }}
                                    </div>
                                </div>
                                <div v-else class="rounded-xl border border-emerald-100 bg-emerald-50/10 p-4 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                                    <div class="absolute top-0 left-0 w-1 h-full bg-emerald-400"></div>
                                    <div class="flex items-center gap-2 mb-2 text-[11px] font-bold text-emerald-600 tracking-wide uppercase">
                                        <i class="pi pi-sparkles text-[10px]"></i> Jawaban AI Tutor
                                    </div>
                                    <div v-html="renderMarkdown(log.response)" class="prose prose-sm prose-slate max-w-none text-[13.5px] text-slate-700 leading-relaxed break-words"></div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Pagination -->
                    <div v-if="chatLogs.links && chatLogs.links.length > 3" class="border-t border-slate-200 bg-slate-50/50 p-4 px-6 flex items-center justify-between mt-4 rounded-xl border">
                        <span class="text-[13px] font-medium text-slate-500 hidden sm:block">Paginasi Halaman</span>
                        <div class="flex items-center gap-1">
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-for="(link, index) in chatLogs.links"
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
                        Belum ada pertanyaan siswa yang terekam untuk kelas ini, atau pencarian Anda tidak mencocokkan nama siswa mana pun.
                    </p>
                </div>
            </div>

            <!-- Tab Rekap Nilai -->
            <div
                v-show="activeTab === 'rekapNilai'"
                class="animate-in duration-300 fade-in"
            >
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <h2 class="text-[16px] font-bold text-slate-800">
                            Rekap Nilai Pre-test & Post-test Siswa
                        </h2>
                        <p class="text-[12px] text-slate-500 mt-1">
                            Inputkan nilai awal (sebelum pembelajaran) dan nilai akhir (setelah pembelajaran) untuk masing-masing siswa.
                        </p>
                    </div>
                    
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <i class="pi pi-search absolute top-1/2 left-3 -translate-y-1/2 text-[12px] text-slate-400"></i>
                        <input
                            v-model="studentSearchQuery"
                            type="text"
                            placeholder="Cari nama siswa..."
                            class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 pr-3 pl-8 text-[12px] font-medium text-slate-800 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full border-collapse text-left text-[13px] text-slate-600">
                        <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 font-bold">No</th>
                                <th class="px-6 py-4 font-bold">Nama Siswa</th>
                                <th class="px-6 py-4 font-bold text-center">Nilai Awal (Pre-test)</th>
                                <th class="px-6 py-4 font-bold text-center">Nilai Akhir (Post-test)</th>
                                <th class="px-6 py-4 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredStudents.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">
                                    <i class="pi pi-users text-2xl mb-2 block"></i>
                                    Tidak ada data siswa ditemukan.
                                </td>
                            </tr>
                            <tr
                                v-for="(siswa, idx) in filteredStudents"
                                :key="siswa.id"
                                class="hover:bg-slate-50/50 transition-colors"
                            >
                                <td class="px-6 py-4 font-medium text-slate-400">
                                    {{ idx + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-[11px] text-indigo-600 font-bold uppercase">
                                            {{ siswa.name.substring(0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900">{{ siswa.name }}</p>
                                            <p class="text-[11px] text-slate-400">{{ siswa.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center" v-if="scoresForm[siswa.id]">
                                        <input
                                            v-model.number="scoresForm[siswa.id].pre_test_score"
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="-"
                                            class="h-9 w-20 rounded-xl border border-slate-200 bg-white text-center text-[13px] font-bold text-slate-800 transition-colors focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center" v-if="scoresForm[siswa.id]">
                                        <input
                                            v-model.number="scoresForm[siswa.id].post_test_score"
                                            type="number"
                                            min="0"
                                            max="100"
                                            placeholder="-"
                                            class="h-9 w-20 rounded-xl border border-slate-200 bg-white text-center text-[13px] font-bold text-slate-800 transition-colors focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center" v-if="scoresForm[siswa.id]">
                                        <Button
                                            @click="saveScores(siswa.id)"
                                            :disabled="scoresForm[siswa.id].loading"
                                            class="h-9 rounded-xl bg-indigo-600 px-4 text-[12px] font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 inline-flex items-center gap-1.5"
                                        >
                                            <i v-if="scoresForm[siswa.id].loading" class="pi pi-spin pi-spinner"></i>
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
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1e36]/40 dark:bg-black/60 px-4 backdrop-blur-[6px] transition-all"
        >
            <div
                class="w-full max-w-[450px] animate-in overflow-hidden rounded-3xl bg-white dark:bg-slate-950 border border-slate-100/80 dark:border-slate-800/50 shadow-[0_20px_50px_rgba(245,158,11,0.08),_0_10px_30px_rgba(99,102,241,0.05)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)] duration-200 zoom-in-95 fade-in"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 bg-gradient-to-r from-amber-50/50 via-rose-50/30 to-orange-50/40 dark:from-slate-900/50 dark:via-slate-900/30 dark:to-slate-900/40 px-6 py-4.5"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/50 dark:border-amber-900/30 text-amber-600 dark:text-amber-400">
                            <i class="pi pi-folder-plus text-[15px]"></i>
                        </div>
                        <span class="text-base font-extrabold text-slate-800 dark:text-slate-100">Buat Topik Baru</span>
                    </div>
                    <button
                        @click="closeTopicModal"
                        class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors hover:bg-slate-50 dark:hover:bg-slate-900"
                    >
                        <i class="pi pi-times text-sm"></i>
                    </button>
                </div>
                <form @submit.prevent="submitTopic" class="p-6">
                    <div class="space-y-5">
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 dark:text-slate-300 uppercase"
                                >Judul Topik
                                <span class="text-rose-500">*</span></label
                            >
                            <Input
                                v-model="topicForm.title"
                                type="text"
                                required
                                class="h-11 rounded-xl border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-[14px] shadow-sm focus-visible:ring-amber-500/20 focus-visible:border-amber-500 focus:border-amber-500 focus:ring-amber-500/20"
                            />
                            <span
                                v-if="topicForm.errors.title"
                                class="mt-1.5 block text-[11px] font-semibold text-rose-500"
                                >{{ topicForm.errors.title }}</span
                            >
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-[12px] font-bold tracking-wider text-slate-700 dark:text-slate-300 uppercase"
                                >Deskripsi (Opsional)</label
                            >
                            <textarea
                                v-model="topicForm.description"
                                rows="3"
                                class="w-full resize-none rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-[14px] shadow-sm transition-all outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus-visible:ring-amber-500/20 focus:outline-hidden"
                            ></textarea>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeTopicModal"
                            class="h-10 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 px-5 font-bold text-[13px] text-slate-600 dark:text-slate-300"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="topicForm.processing"
                            class="h-10 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 px-6 font-bold text-white shadow-md shadow-indigo-100 dark:shadow-none text-[13px]"
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
