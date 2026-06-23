<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { marked } from 'marked';
import { ref, onMounted, computed } from 'vue';
import { toast } from 'vue-sonner';
import FloatingChatbot from '@/components/FloatingChatbot.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const props = defineProps<{
    classroom: { id: number; class_name: string };
    topic: { id: number; title: string };
    phase: {
        id: number;
        name: string;
        is_ai_enabled: boolean;
        is_chatbot_enabled: boolean;
        contents: Array<{
            id: number;
            type: string;
            content_data: any;
            order: number;
        }>;
    };
    studentAnswers: Record<number, string>;
    aiFeedbacks: Record<number, string>;
    discussions: Array<{
        id: number;
        user: { id: number; name: string };
        message: string;
        created_at: string;
        replies: Array<{
            id: number;
            user: { id: number; name: string };
            message: string;
            created_at: string;
        }>;
    }>;
    isLocked: boolean;
}>();

const answers = ref<Record<number, any>>({});
const isSubmitting = ref<Record<number, boolean>>({});

// STATE UNTUK LOADING AI REAL-TIME
const isWaitingForAI = ref<Record<number, boolean>>({});
const pollIntervals: Record<number, any> = {};
const pollAttempts: Record<number, number> = {};

// STATE UNTUK BUKA TUTUP FEEDBACK AI
const expandedAIFeedbacks = ref<Record<number, boolean>>({});

const isAIFeedbackExpanded = (contentId: number) => {
    return expandedAIFeedbacks.value[contentId] !== false;
};

const toggleAIFeedback = (contentId: number) => {
    if (expandedAIFeedbacks.value[contentId] === undefined) {
        expandedAIFeedbacks.value[contentId] = false;
    } else {
        expandedAIFeedbacks.value[contentId] = !expandedAIFeedbacks.value[contentId];
    }
};

// FUNGSI AUTO-POLLING (Cek server setiap 3 detik di latar belakang)
const startPollingAI = (contentId: number) => {
    isWaitingForAI.value[contentId] = true;
    pollAttempts[contentId] = 0;

    // Bersihkan interval lama jika ada (mencegah bug double-polling)
    if (pollIntervals[contentId]) {
clearInterval(pollIntervals[contentId]);
}

    pollIntervals[contentId] = setInterval(() => {
        pollAttempts[contentId]++;

        router.reload({
            only: ['aiFeedbacks'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                // Jika AI sudah memberikan jawaban baru (berisi teks)
                if (props.aiFeedbacks && props.aiFeedbacks[contentId]) {
                    clearInterval(pollIntervals[contentId]);
                    isWaitingForAI.value[contentId] = false;
                    expandedAIFeedbacks.value[contentId] = true; // Auto-expand when AI finishes
                    toast.success('Evaluasi AI Selesai!', { icon: '✨' });
                }
                // Jika sudah 15 kali percobaan (45 detik) tapi AI belum jawab (Timeout/Error API)
                else if (pollAttempts[contentId] >= 15) {
                    clearInterval(pollIntervals[contentId]);
                    isWaitingForAI.value[contentId] = false;
                    toast.error(
                        'Waktu tunggu AI habis. Silakan klik "Cek Hasil AI" nanti.',
                    );
                }
            },
        });
    }, 3000); // interval 3000 ms = 3 detik
};



onMounted(() => {
    if (props.studentAnswers) {
        for (const [key, value] of Object.entries(props.studentAnswers)) {
            try {
                answers.value[Number(key)] = JSON.parse(value);
            } catch (e) {
                answers.value[Number(key)] = value;
            }
        }
    }

    props.phase.contents.forEach((content) => {
        if (content.type === 'eval_cmcq' && !answers.value[content.id]) {
            answers.value[content.id] = [];
        }

        // Inisialisasi reactive key untuk forum diskusi
        if (content.type === 'discussion') {
            newMessage.value[content.id] = '';
        }
    });
});

const saveAnswer = (contentId: number) => {
    const answerData = answers.value[contentId];

    if (Array.isArray(answerData) && answerData.length === 0) {
return;
}

    if (
        !Array.isArray(answerData) &&
        (!answerData || answerData.trim() === '')
    ) {
        toast.warning('Isi jawaban terlebih dahulu!');

        return;
    }

    const answerText = Array.isArray(answerData)
        ? JSON.stringify(answerData)
        : answerData;
    isSubmitting.value[contentId] = true;

    router.post(
        route('siswa.answers.store', props.phase.id),
        {
            content_id: contentId,
            answer_text: answerText,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('Jawaban terkirim!', {
                    icon: '🚀',
                });

                // Hapus feedback lama dari layar
                if (props.aiFeedbacks) {
props.aiFeedbacks[contentId] = '';
}

                // Mulai Auto-Polling jika fase ini mengaktifkan fitur AI
                if (props.phase.is_ai_enabled) {
                    startPollingAI(contentId);
                }
            },
            onError: () => {
                toast.error('Gagal Mengirim', {
                    description: 'Periksa koneksi internet Anda.',
                    icon: '⚠️',
                });
            },
            onFinish: () => {
                isSubmitting.value[contentId] = false;
            },
        },
    );
};

const uploadFile = (contentId: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
return;
}

    isSubmitting.value[contentId] = true;
    const formData = new FormData();
    formData.append('content_id', contentId.toString());
    formData.append('answer_file', file);

    router.post(route('siswa.answers.store', props.phase.id), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('File/Foto berhasil diunggah!', { icon: '✅' });
            answers.value[contentId] = 'uploaded';
        },
        onError: () => {
            toast.error(
                'Gagal mengunggah file. Pastikan ukurannya di bawah 2MB.',
            );
        },
        onFinish: () => {
            isSubmitting.value[contentId] = false;
            target.value = '';
        },
    });
};

const renderMarkdown = (text: string) => {
    if (!text) {
return '';
}

    return marked.parse(text);
};

const isConfirmFinishModalOpen = ref(false);

const handleFinish = () => {
    if (props.isLocked) {
        router.visit(route('siswa.classes.show', props.classroom.id));
    } else {
        isConfirmFinishModalOpen.value = true;
    }
};

const executeFinish = () => {
    isConfirmFinishModalOpen.value = false;
    router.post(route('siswa.phases.complete', { classroom: props.classroom.id, phase: props.phase.id }), {}, {
        onSuccess: () => {
            toast.success('Fase pembelajaran selesai!', { icon: '✅' });
        }
    });
};

// ==========================================
// FORUM DISKUSI
// ==========================================
const newMessage = ref<Record<number, string>>({});
const isPostingMessage = ref<Record<number, boolean>>({});
const replyingTo = ref<Record<number, { id: number; name: string } | null>>({});

const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) {
return 'Baru saja';
}

    if (diffMins < 60) {
return `${diffMins} menit lalu`;
}

    const diffHours = Math.floor(diffMins / 60);

    if (diffHours < 24) {
return `${diffHours} jam lalu`;
}

    const diffDays = Math.floor(diffHours / 24);

    return `${diffDays} hari lalu`;
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const startReply = (contentId: number, discussion: any) => {
    replyingTo.value[contentId] = {
        id: discussion.id,
        name: discussion.user?.name || 'Anonim'
    };
    
    // Focus the specific input field
    const inputEl = document.getElementById(`disc-input-${contentId}`);

    if (inputEl) {
        inputEl.focus();
    }
};

const cancelReply = (contentId: number) => {
    replyingTo.value[contentId] = null;
};

const getTruncatedMessage = (contentId: number, discussionId: number) => {
    const disc = props.discussions?.find(d => d.id === discussionId);

    if (!disc) {
return '';
}

    return disc.message.length > 40 ? disc.message.substring(0, 40) + '...' : disc.message;
};

const postMessage = (contentId: number) => {
    const message = newMessage.value[contentId]?.trim();

    if (!message) {
        toast.warning('Ketik pesan terlebih dahulu!');

        return;
    }

    isPostingMessage.value[contentId] = true;

    const payload: Record<string, any> = {
        message: message,
        content_id: contentId,
    };

    if (replyingTo.value[contentId]) {
        payload.parent_id = replyingTo.value[contentId].id;
    }

    router.post(
        route('siswa.discussions.store', props.phase.id),
        payload,
        {
            preserveScroll: true,
            onSuccess: () => {
                newMessage.value[contentId] = '';
                replyingTo.value[contentId] = null;
                toast.success('Komentar terkirim!', { icon: '💬' });
            },
            onError: () => {
                toast.error('Gagal mengirim komentar.');
            },
            onFinish: () => {
                isPostingMessage.value[contentId] = false;
            },
        },
    );
};

const refreshDiscussions = () => {
    router.reload({
        only: ['discussions'],
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Diskusi diperbarui', { icon: '🔄' });
        },
    });
};
</script>

<template>
    <Head :title="`Materi: ${phase.name}`" />

    <div class="min-h-screen bg-[#F4F7F9] px-4 py-6 md:px-8">
        <!-- Header -->
        <div
            class="mx-auto mb-6 flex max-w-4xl flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center"
        >
            <div>
                <div
                    class="mb-1 flex items-center gap-2 text-[11px] font-bold text-slate-500"
                >
                    <Link
                        :href="route('siswa.classes.show', classroom.id)"
                        class="transition-colors hover:text-indigo-600"
                    >
                        <span class="text-indigo-600">{{
                            classroom.class_name
                        }}</span>
                    </Link>
                    <i class="pi pi-chevron-right text-[8px]"></i>
                    <span>{{ topic.title }}</span>
                </div>
                <h1 class="text-xl font-black text-slate-900">
                    {{ phase.name }}
                </h1>
            </div>

            <div
                v-if="phase.is_ai_enabled"
                class="flex items-center gap-2 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5"
            >
                <i class="pi pi-sparkles text-sm text-indigo-500"></i>
                <span
                    class="text-[11px] font-bold tracking-widest text-indigo-600 uppercase"
                    >AI Assistant Aktif</span
                >
            </div>
        </div>

        <div class="mx-auto mb-12 max-w-4xl space-y-6">
            <div v-for="content in phase.contents" :key="content.id">
                <div
                    v-if="content.type === 'text'"
                    class="prose prose-slate max-w-none rounded-2xl border border-slate-100 bg-white p-6 text-[15px] leading-relaxed text-slate-700 shadow-sm rich-text-content"
                    v-html="content.content_data.body"
                ></div>

                <div
                    v-if="content.type === 'image' && content.content_data.url"
                    class="flex justify-center rounded-2xl border border-slate-100 bg-white p-4 shadow-sm"
                >
                    <img
                        :src="content.content_data.url"
                        class="max-h-[500px] rounded-xl object-contain"
                        alt="Materi Visual"
                    />
                </div>

                <div
                    v-if="content.type === 'h5p' && content.content_data.path"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                            <span class="text-sm font-bold text-slate-700">Materi Interaktif</span>
                        </div>
                        <a
                            :href="content.content_data.path"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200"
                        >
                            <i class="pi pi-external-link"></i>
                            <span>Buka di Tab Baru</span>
                        </a>
                    </div>
                    <div
                        class="w-full rounded-xl bg-slate-900"
                        style="max-height: 85vh; overflow-y: auto; -webkit-overflow-scrolling: touch;"
                    >
                        <iframe
                            :src="content.content_data.path"
                            class="w-full border-0"
                            style="height: 1000px; width: 125%; transform: scale(0.8); transform-origin: top left;overflow-y: hidden;"
                            scrolling="no"
                            allowfullscreen="allowfullscreen"
                            allow="
                                geolocation *;
                                microphone *;
                                camera *;
                                midi *;
                                encrypted-media *;
                            "
                            sandbox="allow-scripts allow-forms allow-same-origin allow-downloads"
                            title="Interactive Video LC5E"
                        ></iframe>
                    </div>
                </div>

                <!-- SOAL PILIHAN GANDA (MCQ) -->
                <div
                    v-if="content.type === 'eval_mcq'"
                    class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-indigo-50/50 p-6 shadow-sm"
                >
                    <div
                        class="absolute top-0 left-0 h-full w-1.5 bg-indigo-500"
                    ></div>
                    <div class="mb-4 flex items-center gap-2">
                        <i class="pi pi-question-circle text-indigo-500"></i>
                        <h4
                            class="font-bold text-slate-800 rich-text-content"
                            v-html="content.content_data.question || content.content_data.label || ''"
                        ></h4>
                    </div>
                    <div class="space-y-2 pl-6">
                        <label
                            v-for="(option, idx) in content.content_data
                                .options"
                            :key="idx"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition-all hover:border-indigo-200 hover:bg-slate-50"
                            :class="{
                                'border-indigo-400 bg-indigo-50/50 ring-1 ring-indigo-400':
                                    answers[content.id] === option,
                            }"
                        >
                            <input
                                type="radio"
                                :name="'mcq_' + content.id"
                                :value="option"
                                v-model="answers[content.id]"
                                @change="saveAnswer(content.id)"
                                :disabled="props.isLocked"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span
                                class="text-[14px] text-slate-700 rich-text-content"
                                v-html="option"
                            ></span>
                        </label>
                    </div>
                    <div class="mt-3 flex min-h-[20px] justify-end">
                        <span
                            v-if="isSubmitting[content.id]"
                            class="text-[11px] font-bold text-indigo-500"
                            ><i class="pi pi-spinner pi-spin mr-1"></i>
                            Menyimpan...</span
                        >
                    </div>
                </div>

                <!-- SOAL CHECKBOX (CMCQ) -->
                <div
                    v-if="content.type === 'eval_cmcq'"
                    class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-indigo-50/50 p-6 shadow-sm"
                >
                    <div
                        class="absolute top-0 left-0 h-full w-1.5 bg-indigo-500"
                    ></div>
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <i class="pi pi-list text-indigo-500"></i>
                        <h4
                            class="font-bold text-slate-800 rich-text-content"
                            v-html="content.content_data.question || content.content_data.label || ''"
                        ></h4>
                        <span
                            class="rounded bg-amber-100 px-2 py-0.5 text-[9px] font-black tracking-wider text-amber-700 uppercase shadow-sm"
                            >Pilih lebih dari satu</span
                        >
                    </div>
                    <div class="space-y-2 pl-6">
                        <label
                            v-for="(option, idx) in content.content_data
                                .options"
                            :key="idx"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition-all hover:border-indigo-200 hover:bg-slate-50"
                            :class="{
                                'border-indigo-400 bg-indigo-50/50 ring-1 ring-indigo-400':
                                    answers[content.id]?.includes(option),
                            }"
                        >
                            <input
                                type="checkbox"
                                :value="option"
                                v-model="answers[content.id]"
                                @change="saveAnswer(content.id)"
                                :disabled="props.isLocked"
                                class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500"
                            />
                            <span
                                class="text-[14px] text-slate-700 rich-text-content"
                                v-html="option"
                            ></span>
                        </label>
                    </div>
                    <div class="mt-3 flex min-h-[20px] justify-end">
                        <span
                            v-if="isSubmitting[content.id]"
                            class="text-[11px] font-bold text-indigo-500"
                            ><i class="pi pi-spinner pi-spin mr-1"></i>
                            Menyimpan...</span
                        >
                    </div>
                </div>

                <!-- SOAL JAWABAN SINGKAT -->
                <div
                    v-if="content.type === 'eval_short'"
                    class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-indigo-50/50 p-6 shadow-sm"
                >
                    <div
                        class="absolute top-0 left-0 h-full w-1.5 bg-indigo-500"
                    ></div>
                    <label
                        class="mb-3 block flex items-center gap-2 text-[14px] font-extrabold text-slate-800"
                    >
                        <i class="pi pi-pencil text-indigo-500"></i>
                        <span class="rich-text-content" v-html="content.content_data.question || content.content_data.label || ''"></span>
                    </label>

                    <!-- Kotak Input terkunci saat menunggu AI atau terkunci -->
                    <input
                        type="text"
                        v-model="answers[content.id]"
                        placeholder="Ketik jawaban singkat Anda..."
                        class="w-full rounded-xl border border-indigo-200 bg-white p-3.5 text-[14px] text-slate-700 shadow-inner transition-all focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="
                            isSubmitting[content.id] ||
                            isWaitingForAI[content.id] ||
                            props.isLocked
                        "
                    />

                    <div
                        class="mt-3 flex min-h-[32px] items-center justify-end gap-3"
                    >
                        <!-- Tombol disembunyikan saat sedang proses atau terkunci -->
                        <Button
                            v-if="!isWaitingForAI[content.id] && !props.isLocked"
                            @click="saveAnswer(content.id)"
                            size="sm"
                            class="h-9 rounded-xl bg-indigo-600 px-5 text-xs font-bold text-white shadow-sm transition-all hover:scale-105 hover:bg-indigo-700 active:scale-95"
                            :disabled="isSubmitting[content.id]"
                        >
                            <i
                                class="pi pi-send mr-1.5"
                                :class="{
                                    'pi-spin pi-spinner':
                                        isSubmitting[content.id],
                                }"
                            ></i>
                            Kirim Jawaban
                        </Button>
                    </div>

                    <!-- AREA BUBBLE AI -->
                    <div v-if="phase.is_ai_enabled" class="mt-4">
                        <!-- 1. State Loading Berjalan (Animasi) -->
                        <div
                            v-if="isWaitingForAI[content.id]"
                            class="relative flex animate-in items-center gap-4 overflow-hidden rounded-2xl border border-indigo-100 bg-white px-6 py-5 shadow-sm duration-500 fade-in slide-in-from-bottom-2"
                        >
                            <div
                                class="absolute inset-0 animate-pulse bg-gradient-to-r from-indigo-50 to-purple-50 opacity-50"
                            ></div>
                            <i
                                class="pi pi-sparkles animate-spin text-2xl text-indigo-500"
                                style="animation-duration: 3s"
                            ></i>
                            <div class="relative z-10 flex flex-col">
                                <span
                                    class="text-[14px] font-bold text-indigo-800"
                                    >Guru AI sedang menganalisis
                                    jawabanmu...</span
                                >
                                <span class="text-[12px] text-indigo-500"
                                    >Tunggu sebentar, sedang menyusun
                                    feedback.</span
                                >
                            </div>
                        </div>

                        <!-- 2. State Tampil Hasil AI -->
                        <div
                            v-else-if="aiFeedbacks && aiFeedbacks[content.id]"
                            class="relative animate-in rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-purple-50 shadow-sm duration-300 zoom-in-95 transition-all"
                            :class="isAIFeedbackExpanded(content.id) ? 'p-6' : 'py-3.5 px-6'"
                        >
                            <div
                                class="absolute -top-3 left-6 flex items-center gap-1.5 rounded-full border border-indigo-200 bg-white px-3 py-1 shadow-sm"
                            >
                                <i
                                    class="pi pi-sparkles text-[10px] text-indigo-500"
                                ></i>
                                <span
                                    class="text-[10px] font-black tracking-widest text-indigo-600 uppercase"
                                    >Feedback Guru AI</span
                                >
                            </div>
                            <button
                                type="button"
                                @click="toggleAIFeedback(content.id)"
                                class="absolute -top-3 right-6 flex items-center gap-1 rounded-full border border-indigo-200 bg-white px-3 py-1 text-[10px] font-bold text-indigo-600 shadow-sm hover:bg-indigo-50 active:scale-95 transition-all"
                            >
                                <i
                                    class="pi text-[8px]"
                                    :class="isAIFeedbackExpanded(content.id) ? 'pi-chevron-up' : 'pi-chevron-down'"
                                ></i>
                                <span>{{ isAIFeedbackExpanded(content.id) ? 'Sembunyikan' : 'Lihat' }}</span>
                            </button>
                            <div
                                v-show="isAIFeedbackExpanded(content.id)"
                                class="prose prose-sm prose-slate mt-3 max-w-none leading-relaxed rich-text-content animate-in fade-in duration-200"
                                v-html="renderMarkdown(aiFeedbacks[content.id])"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- SOAL ESAI PANJANG -->
                <div
                    v-if="
                        content.type === 'eval_essay' ||
                        content.type === 'input_text'
                    "
                    class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-indigo-50/50 p-6 shadow-sm"
                >
                    <div
                        class="absolute top-0 left-0 h-full w-1.5 bg-indigo-500"
                    ></div>
                    <label
                        class="mb-3 block flex items-center gap-2 text-[14px] font-extrabold text-slate-800"
                    >
                        <i class="pi pi-align-left text-indigo-500"></i>
                        <span
                            class="rich-text-content"
                            v-html="
                                content.content_data.question ||
                                content.content_data.label ||
                                'Tuliskan jawaban atau prediksi Anda di bawah ini:'
                            "
                        ></span>
                    </label>

                    <!-- Tampilan Read-Only saat phase terkunci (jawaban sudah dikirim) -->
                    <div
                        v-if="props.isLocked && answers[content.id]"
                        class="rich-text-content ql-editor-readonly min-h-[80px] rounded-xl border border-slate-200 bg-slate-50 px-[18px] py-[14px] text-[14px] leading-relaxed text-slate-800"
                        v-html="answers[content.id]"
                    ></div>

                    <!-- Pesan kosong jika terkunci tapi belum ada jawaban -->
                    <div
                        v-else-if="props.isLocked && !answers[content.id]"
                        class="flex min-h-[80px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-[13px] text-slate-400"
                    >
                        <i class="pi pi-minus-circle mr-2 text-slate-300"></i>
                        Tidak ada jawaban yang dikirimkan.
                    </div>

                    <!-- Editor aktif saat masih bisa mengisi jawaban -->
                    <RichTextEditor
                        v-else
                        v-model="answers[content.id]"
                        variant="student"
                        placeholder="Ketik uraian jawaban Anda di sini..."
                        :disabled="isSubmitting[content.id] || isWaitingForAI[content.id]"
                    />

                    <div
                        class="mt-3 flex min-h-[32px] items-center justify-end gap-3"
                    >
                        <!-- Tombol disembunyikan saat sedang proses atau terkunci -->
                        <Button
                            v-if="!isWaitingForAI[content.id] && !props.isLocked"
                            @click="saveAnswer(content.id)"
                            size="sm"
                            class="h-9 rounded-xl bg-indigo-600 px-5 text-xs font-bold text-white shadow-sm transition-all hover:scale-105 hover:bg-indigo-700 active:scale-95"
                            :disabled="isSubmitting[content.id]"
                        >
                            <i
                                class="pi pi-send mr-1.5"
                                :class="{
                                    'pi-spin pi-spinner':
                                        isSubmitting[content.id],
                                }"
                            ></i>
                            Kirim Jawaban
                        </Button>
                    </div>

                    <!-- AREA BUBBLE AI -->
                    <div v-if="phase.is_ai_enabled" class="mt-4">
                        <!-- 1. State Loading Berjalan (Animasi) -->
                        <div
                            v-if="isWaitingForAI[content.id]"
                            class="relative flex animate-in items-center gap-4 overflow-hidden rounded-2xl border border-indigo-100 bg-white px-6 py-5 shadow-sm duration-500 fade-in slide-in-from-bottom-2"
                        >
                            <div
                                class="absolute inset-0 animate-pulse bg-gradient-to-r from-indigo-50 to-purple-50 opacity-50"
                            ></div>
                            <i
                                class="pi pi-sparkles animate-spin text-2xl text-indigo-500"
                                style="animation-duration: 3s"
                            ></i>
                            <div class="relative z-10 flex flex-col">
                                <span
                                    class="text-[14px] font-bold text-indigo-800"
                                    >Guru AI sedang menganalisis
                                    jawabanmu...</span
                                >
                                <span class="text-[12px] text-indigo-500"
                                    >Tunggu sebentar, sedang menyusun
                                    feedback.</span
                                >
                            </div>
                        </div>

                        <!-- 2. State Tampil Hasil AI -->
                        <div
                            v-else-if="aiFeedbacks && aiFeedbacks[content.id]"
                            class="relative animate-in rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-purple-50 shadow-sm duration-300 zoom-in-95 transition-all"
                            :class="isAIFeedbackExpanded(content.id) ? 'p-6' : 'py-3.5 px-6'"
                        >
                            <div
                                class="absolute -top-3 left-6 flex items-center gap-1.5 rounded-full border border-indigo-200 bg-white px-3 py-1 shadow-sm"
                            >
                                <i
                                    class="pi pi-sparkles text-[10px] text-indigo-500"
                                ></i>
                                <span
                                    class="text-[10px] font-black tracking-widest text-indigo-600 uppercase"
                                    >Feedback Guru AI</span
                                >
                            </div>
                            <button
                                type="button"
                                @click="toggleAIFeedback(content.id)"
                                class="absolute -top-3 right-6 flex items-center gap-1 rounded-full border border-indigo-200 bg-white px-3 py-1 text-[10px] font-bold text-indigo-600 shadow-sm hover:bg-indigo-50 active:scale-95 transition-all"
                            >
                                <i
                                    class="pi text-[8px]"
                                    :class="isAIFeedbackExpanded(content.id) ? 'pi-chevron-up' : 'pi-chevron-down'"
                                ></i>
                                <span>{{ isAIFeedbackExpanded(content.id) ? 'Sembunyikan' : 'Lihat' }}</span>
                            </button>
                            <div
                                v-show="isAIFeedbackExpanded(content.id)"
                                class="prose prose-sm prose-slate mt-3 max-w-none leading-relaxed rich-text-content animate-in fade-in duration-200"
                                v-html="renderMarkdown(aiFeedbacks[content.id])"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- SOAL UPLOAD FILE -->
                <div
                    v-if="content.type === 'eval_file'"
                    class="relative overflow-hidden rounded-2xl border border-pink-100 bg-pink-50/40 p-6 shadow-sm"
                >
                    <div
                        class="absolute top-0 left-0 h-full w-1.5 bg-pink-500"
                    ></div>
                    <label
                        class="mb-3 block flex items-center gap-2 text-[14px] font-extrabold text-slate-800"
                    >
                        <i class="pi pi-camera text-pink-500"></i>
                        <span class="rich-text-content" v-html="content.content_data.question || content.content_data.label || ''"></span>
                    </label>
                    <div
                        v-if="!props.isLocked"
                        class="group relative mt-2 flex h-36 w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-pink-200 bg-white transition-colors hover:bg-pink-50"
                    >
                        <input
                            type="file"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            accept="image/*, application/pdf"
                            @change="(e) => uploadFile(content.id, e)"
                        />
                        <div
                            class="pointer-events-none flex flex-col items-center justify-center pt-5 pb-6"
                        >
                            <i
                                class="pi pi-cloud-upload mb-2 text-3xl text-pink-400 transition-transform group-hover:-translate-y-1"
                            ></i>
                            <p class="mb-1 text-[13px] text-slate-500">
                                <span class="font-bold text-pink-600"
                                    >Klik untuk upload</span
                                >
                                atau Drag & Drop foto
                            </p>
                            <p class="text-[11px] text-slate-400">
                                Format: PNG, JPG, atau PDF (Maks 2MB)
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="props.isLocked && !(answers[content.id] === 'uploaded' || (answers[content.id] && answers[content.id].includes('/storage/')))"
                        class="mt-2 inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] font-medium text-slate-500"
                    >
                        <i class="pi pi-info-circle mr-2"></i> Tidak ada file yang diunggah.
                    </div>
                    <div
                        v-if="isSubmitting[content.id]"
                        class="mt-3 text-[12px] font-bold text-pink-500"
                    >
                        <i class="pi pi-spinner pi-spin mr-1"></i> Sedang
                        mengunggah...
                    </div>
                    <div
                        v-else-if="
                            answers[content.id] === 'uploaded' ||
                            (answers[content.id] &&
                                answers[content.id].includes('/storage/'))
                        "
                        class="mt-3 inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[12px] font-bold text-emerald-600"
                    >
                        <i class="pi pi-check-circle mr-2"></i> File Anda
                        berhasil diunggah dan diamankan.
                    </div>
                </div>

                <!-- FORUM DISKUSI -->
                <div
                    v-if="content.type === 'discussion'"
                    class="relative overflow-hidden rounded-2xl border border-sky-100 bg-sky-50/40 p-6 shadow-sm"
                >
                    <div
                        class="absolute top-0 left-0 h-full w-1.5 bg-sky-500"
                    ></div>
                    <div class="mb-3 flex items-center justify-between">
                        <label
                            class="flex items-center gap-2 text-[14px] font-extrabold text-slate-800"
                        >
                            <i class="pi pi-comments text-sky-500"></i>
                            <span class="rich-text-content" v-html="content.content_data.topic"></span>
                        </label>
                        <button
                            @click="refreshDiscussions"
                            class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-500 transition-colors hover:bg-sky-50 hover:text-sky-600"
                        >
                            <i class="pi pi-refresh text-[10px]"></i> Refresh
                        </button>
                    </div>

                    <!-- Area Komentar -->
                    <div
                        class="mb-4 flex max-h-80 min-h-[200px] flex-col gap-3 overflow-y-auto rounded-xl border border-slate-200 bg-white p-4 shadow-inner"
                    >
                        <!-- Jika belum ada komentar -->
                        <div
                            v-if="!discussions || discussions.length === 0"
                            class="my-auto text-center"
                        >
                            <i
                                class="pi pi-comments mb-2 text-3xl text-slate-300"
                            ></i>
                            <p
                                class="text-[12px] font-bold tracking-wider text-slate-400 uppercase"
                            >
                                Jadilah yang pertama berkomentar!
                            </p>
                        </div>

                        <!-- Daftar Komentar -->
                        <template v-else>
                            <div
                                v-for="discussion in discussions"
                                :key="discussion.id"
                                class="flex gap-3"
                            >
                                <!-- Avatar -->
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[10px] font-black text-white"
                                    :class="
                                        authUser &&
                                        discussion.user?.id === authUser.id
                                            ? 'bg-sky-500'
                                            : 'bg-indigo-400'
                                    "
                                >
                                    {{
                                        discussion.user
                                            ? getInitials(discussion.user.name)
                                            : '??'
                                    }}
                                </div>
                                <!-- Bubble -->
                                <div class="flex-1">
                                    <div
                                        class="rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-2.5"
                                    >
                                        <div
                                            class="mb-1 flex items-center gap-2"
                                        >
                                            <span
                                                class="text-[12px] font-bold text-slate-800"
                                                >{{
                                                    discussion.user?.name ||
                                                    'Anonim'
                                                }}</span
                                            >
                                            <span
                                                class="text-[10px] text-slate-400"
                                                >{{
                                                    formatTime(
                                                        discussion.created_at,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                        <p
                                            class="text-[13px] leading-relaxed text-slate-700"
                                        >
                                            {{ discussion.message }}
                                        </p>
                                    </div>
                                    <div class="mt-1 flex items-center gap-3 pl-1">
                                        <button
                                            @click="startReply(content.id, discussion)"
                                            class="text-[11px] font-bold text-slate-400 hover:text-sky-600 transition-colors"
                                        >
                                            Balas
                                        </button>
                                    </div>

                                    <!-- Replies -->
                                    <div
                                        v-if="
                                            discussion.replies &&
                                            discussion.replies.length > 0
                                        "
                                        class="mt-2 space-y-2 border-l-2 border-sky-100 pl-4"
                                    >
                                        <div
                                            v-for="reply in discussion.replies"
                                            :key="reply.id"
                                            class="flex gap-2"
                                        >
                                            <div
                                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[8px] font-black text-white"
                                                :class="
                                                    authUser &&
                                                    reply.user?.id ===
                                                        authUser.id
                                                        ? 'bg-sky-400'
                                                        : 'bg-slate-400'
                                                "
                                            >
                                                {{
                                                    reply.user
                                                        ? getInitials(
                                                              reply.user.name,
                                                          )
                                                        : '??'
                                                }}
                                            </div>
                                            <div
                                                class="rounded-lg bg-white px-3 py-2"
                                            >
                                                <span
                                                    class="text-[11px] font-bold text-slate-700"
                                                    >{{
                                                        reply.user?.name ||
                                                        'Anonim'
                                                    }}</span
                                                >
                                                <span
                                                    class="ml-2 text-[9px] text-slate-400"
                                                    >{{
                                                        formatTime(
                                                            reply.created_at,
                                                        )
                                                    }}</span
                                                >
                                                <p
                                                    class="mt-0.5 text-[12px] text-slate-600"
                                                >
                                                    {{ reply.message }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Info Sedang Membalas -->
                    <div
                        v-if="replyingTo[content.id]"
                        class="mb-2 flex items-center justify-between rounded-xl bg-sky-50 px-3.5 py-2 text-[12px] text-sky-700 border border-sky-100"
                    >
                        <div class="flex items-center gap-1.5">
                            <i class="pi pi-comments text-sky-500"></i>
                            <span>
                                Membalas <strong>@{{ replyingTo[content.id].name }}</strong>: "{{ getTruncatedMessage(content.id, replyingTo[content.id].id) }}"
                            </span>
                        </div>
                        <button
                            @click="cancelReply(content.id)"
                            class="text-[11px] font-extrabold text-sky-600 hover:text-sky-800 transition-colors uppercase tracking-wider"
                        >
                            Batal
                        </button>
                    </div>

                    <!-- Input Kirim Pesan -->
                    <div class="flex gap-2">
                        <input
                            type="text"
                            :id="'disc-input-' + content.id"
                            v-model="newMessage[content.id]"
                            placeholder="Ketik komentar atau balasan Anda di sini..."
                            class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[13px] shadow-sm transition-colors focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                            :disabled="isPostingMessage[content.id]"
                            @keyup.enter="postMessage(content.id)"
                        />
                        <Button
                            @click="postMessage(content.id)"
                            class="rounded-xl bg-sky-600 px-5 text-white shadow-sm transition-all hover:bg-sky-700 active:scale-95"
                            :disabled="isPostingMessage[content.id]"
                        >
                            <i
                                class="pi"
                                :class="
                                    isPostingMessage[content.id]
                                        ? 'pi-spinner pi-spin'
                                        : 'pi-send'
                                "
                            ></i>
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <Button
                    @click="handleFinish"
                    class="h-11 rounded-xl bg-blue-500 px-8 font-bold text-white shadow-md hover:bg-blue-600"
                >
                    Selesai <i class="pi pi-check-circle ml-2"></i>
                </Button>
            </div>
        </div>
    </div>
    <FloatingChatbot v-if="phase.is_chatbot_enabled" :topicTitle="topic.title" :phaseId="phase.id" />

    <Teleport to="body">
        <div
            v-if="isConfirmFinishModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1e36]/40 dark:bg-black/60 px-4 backdrop-blur-[6px] transition-all"
        >
            <div
                class="w-full max-w-[400px] animate-in overflow-hidden rounded-3xl bg-white dark:bg-slate-950 border border-slate-100/80 dark:border-slate-800/50 shadow-[0_20px_50px_rgba(245,158,11,0.08),_0_10px_30px_rgba(99,102,241,0.05)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)] p-6 text-center duration-200 zoom-in-95 fade-in"
            >
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/30 text-amber-600 dark:text-amber-400 shadow-inner"
                >
                    <i class="pi pi-exclamation-triangle text-2xl"></i>
                </div>
                <h3
                    class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100"
                >
                    Selesaikan Fase?
                </h3>
                <p
                    class="mt-2 text-[14px] leading-relaxed font-medium text-slate-500 dark:text-slate-400"
                >
                    Apakah Anda yakin ingin menyelesaikan fase ini? Setelah diselesaikan, jawaban Anda tidak dapat diubah lagi.
                </p>
                <div
                    class="mt-8 flex flex-col-reverse justify-center gap-3 sm:flex-row"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="isConfirmFinishModalOpen = false"
                        class="h-11 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 px-6 font-bold text-slate-600 dark:text-slate-300 text-[13px] w-full sm:w-auto"
                    >
                        Batalkan
                    </Button>
                    <Button
                        type="button"
                        @click="executeFinish"
                        class="h-11 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 px-6 font-bold text-white shadow-md shadow-blue-100 dark:shadow-none text-[13px] w-full sm:w-auto"
                    >
                        Ya, Selesaikan
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
/* 
 * RICH TEXT CONTENT STYLES
 * Restores styles stripped by Tailwind CSS Preflight for Quill HTML outputs
 */

/* Subscript & Superscript */
.rich-text-content sub,
.rich-text-content sup {
    font-size: 75% !important;
    line-height: 0 !important;
    position: relative !important;
    vertical-align: baseline !important;
}
.rich-text-content sub {
    bottom: -0.25em !important;
}
.rich-text-content sup {
    top: -0.5em !important;
}

/* Basic Formatting */
.rich-text-content strong,
.rich-text-content b {
    font-weight: 700 !important;
}
.rich-text-content em,
.rich-text-content i {
    font-style: italic !important;
}
.rich-text-content u {
    text-decoration: underline !important;
}
.rich-text-content s,
.rich-text-content strike,
.rich-text-content del {
    text-decoration: line-through !important;
}

/* Standard HTML lists (useful for markdown parsing or general lists) */
.rich-text-content ol:not(.ql-editor ol) {
    list-style-type: decimal !important;
    padding-left: 1.5rem !important;
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
}
.rich-text-content ul:not(.ql-editor ul) {
    list-style-type: disc !important;
    padding-left: 1.5rem !important;
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
}
.rich-text-content li {
    display: list-item !important;
}

/* Quill Editor Flat Lists counter logic */
.rich-text-content ol {
    list-style-type: none !important;
    counter-reset: list-0;
    padding-left: 1.5rem !important;
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
}
.rich-text-content ol > li {
    counter-increment: list-0;
    position: relative;
    list-style-type: none !important;
}
.rich-text-content ol > li::before {
    content: counter(list-0, decimal) ". ";
    position: absolute;
    left: -1.5rem;
    width: 1.25rem;
    text-align: right;
}

/* Level 1 Ordered List */
.rich-text-content ol > li.ql-indent-1 {
    counter-increment: list-1;
    padding-left: 1.5rem !important;
}
.rich-text-content ol > li.ql-indent-1::before {
    content: counter(list-1, lower-alpha) ". ";
    left: 0rem;
}
.rich-text-content ol > li.ql-indent-1 {
    counter-reset: list-2;
}

/* Level 2 Ordered List */
.rich-text-content ol > li.ql-indent-2 {
    counter-increment: list-2;
    padding-left: 3rem !important;
}
.rich-text-content ol > li.ql-indent-2::before {
    content: counter(list-2, lower-roman) ". ";
    left: 1.5rem;
}
.rich-text-content ol > li.ql-indent-2 {
    counter-reset: list-3;
}

/* Level 3 Ordered List */
.rich-text-content ol > li.ql-indent-3 {
    counter-increment: list-3;
    padding-left: 4.5rem !important;
}
.rich-text-content ol > li.ql-indent-3::before {
    content: counter(list-3, decimal) ". ";
    left: 3rem;
}

/* Unordered Lists */
.rich-text-content ul {
    list-style-type: none !important;
    padding-left: 1.5rem !important;
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
}
.rich-text-content ul > li {
    position: relative;
    list-style-type: none !important;
}
.rich-text-content ul > li::before {
    content: "•";
    position: absolute;
    left: -1.25rem;
}

/* Level 1 Unordered List */
.rich-text-content ul > li.ql-indent-1 {
    padding-left: 1.5rem !important;
}
.rich-text-content ul > li.ql-indent-1::before {
    content: "○";
    left: 0.25rem;
}

/* Level 2 Unordered List */
.rich-text-content ul > li.ql-indent-2 {
    padding-left: 3rem !important;
}
.rich-text-content ul > li.ql-indent-2::before {
    content: "▪";
    left: 1.75rem;
}

/* Standard Nested Lists inside rich-text-content (just in case) */
.rich-text-content ol ol,
.rich-text-content ul ol {
    list-style-type: lower-alpha !important;
    margin-left: 1.5rem !important;
}
.rich-text-content ol ol ol,
.rich-text-content ul ol ol {
    list-style-type: lower-roman !important;
    margin-left: 1.5rem !important;
}
.rich-text-content ul ul,
.rich-text-content ol ul {
    list-style-type: circle !important;
    margin-left: 1.5rem !important;
}
.rich-text-content ul ul ul,
.rich-text-content ol ul ul {
    list-style-type: square !important;
    margin-left: 1.5rem !important;
}

/* Alignment */
.rich-text-content .ql-align-center {
    text-align: center !important;
}
.rich-text-content .ql-align-right {
    text-align: right !important;
}
.rich-text-content .ql-align-justify {
    text-align: justify !important;
}

/* Paragraph spacing */
.rich-text-content p {
    margin-bottom: 0.5rem !important;
}
.rich-text-content p:last-child {
    margin-bottom: 0 !important;
}

/* Inline Images */
.rich-text-content img {
    max-width: 100% !important;
    height: auto !important;
    border-radius: 0.375rem !important;
    display: inline-block !important;
    margin: 8px 0 !important;
    border: 1px solid #f1f5f9 !important;
}

/* Hyperlinks */
.rich-text-content a {
    color: #4f46e5 !important; /* Indigo 600 */
    text-decoration: underline !important;
    font-weight: 600 !important;
    transition: color 0.15s ease !important;
}
.rich-text-content a:hover {
    color: #3730a3 !important; /* Indigo 800 */
}
</style>
