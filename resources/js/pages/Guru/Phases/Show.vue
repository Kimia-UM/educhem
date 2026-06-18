<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
// PASTIKAN FILE INI SUDAH ADA DI FOLDER COMPONENTS

const props = defineProps<{
    classroom: {
        id: number;
        class_name: string;
    };
    topic: {
        id: number;
        title: string;
    };
    phase: {
        id: number;
        name: string;
        is_ai_enabled: boolean;
        is_chatbot_enabled: boolean;
        ai_prompt_setting: string | null;
        chatbot_prompt_setting: string | null;
        contents: Array<{
            id: number;
            type: string;
            content_data: any;
            order: number;
        }>;
    };
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
}>();

// Helper untuk Forum Diskusi
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

const refreshDiscussions = () => {
    router.reload({
        only: ['discussions'],
        preserveScroll: true,
        onSuccess: () => toast.success('Diskusi diperbarui', { icon: '🔄' }),
    });
};

// ==========================================
// 1. LOGIKA UPDATE FASE (AI & CHATBOT SETTINGS)
// ==========================================
const isTogglingAI = ref(false);
const localAisEnabled = ref(!!props.phase.is_ai_enabled);

watch(
    () => props.phase.is_ai_enabled,
    (newVal) => {
        localAisEnabled.value = !!newVal;
    },
);

const toggleAI = () => {
    if (isTogglingAI.value) {
return;
}

    isTogglingAI.value = true;
    localAisEnabled.value = !localAisEnabled.value;

    router.put(
        route('guru.phases.update', { phase: props.phase.id }),
        {
            name: props.phase.name,
            is_ai_enabled: localAisEnabled.value,
            is_chatbot_enabled: localChatbotEnabled.value,
            ai_prompt_setting: props.phase.ai_prompt_setting,
            chatbot_prompt_setting: props.phase.chatbot_prompt_setting,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success(
                    localAisEnabled.value
                        ? 'AI Assistant Aktif'
                        : 'AI Assistant Nonaktif',
                    { icon: '🤖' },
                ),
            onError: () => {
                localAisEnabled.value = !localAisEnabled.value;
                toast.error('Gagal mengubah status AI');
            },
            onFinish: () => (isTogglingAI.value = false),
        },
    );
};

const isTogglingChatbot = ref(false);
const localChatbotEnabled = ref(!!props.phase.is_chatbot_enabled);

watch(
    () => props.phase.is_chatbot_enabled,
    (newVal) => {
        localChatbotEnabled.value = !!newVal;
    },
);

const toggleChatbot = () => {
    if (isTogglingChatbot.value) {
return;
}

    isTogglingChatbot.value = true;
    localChatbotEnabled.value = !localChatbotEnabled.value;

    router.put(
        route('guru.phases.update', { phase: props.phase.id }),
        {
            name: props.phase.name,
            is_ai_enabled: localAisEnabled.value,
            is_chatbot_enabled: localChatbotEnabled.value,
            ai_prompt_setting: props.phase.ai_prompt_setting,
            chatbot_prompt_setting: props.phase.chatbot_prompt_setting,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success(
                    localChatbotEnabled.value
                        ? 'Chatbot AI Aktif'
                        : 'Chatbot AI Nonaktif',
                    { icon: '💬' },
                ),
            onError: () => {
                localChatbotEnabled.value = !localChatbotEnabled.value;
                toast.error('Gagal mengubah status Chatbot');
            },
            onFinish: () => (isTogglingChatbot.value = false),
        },
    );
};

const saveAIPrompt = () => {
    router.put(
        route('guru.phases.update', { phase: props.phase.id }),
        {
            name: props.phase.name,
            is_ai_enabled: localAisEnabled.value,
            is_chatbot_enabled: localChatbotEnabled.value,
            ai_prompt_setting: props.phase.ai_prompt_setting,
            chatbot_prompt_setting: props.phase.chatbot_prompt_setting,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success('Instruksi AI Disimpan', { icon: '✨' }),
        },
    );
};

// ==========================================
// 2. LOGIKA KONTEN DINAMIS (BUILDER)
// ==========================================
const localContents = ref<any[]>([]);

watch(
    () => props.phase.contents,
    (newContents) => {
        const cloned = JSON.parse(JSON.stringify(newContents || []));
        cloned.forEach((c: any) => {
            if (!c.correct_answers || !Array.isArray(c.correct_answers)) {
                c.correct_answers = [];
            }

            if (!c.content_data || Array.isArray(c.content_data)) {
                c.content_data = {};
            }

            // Inisialisasi struktur JSON sesuai Tipe Komponen
            if (c.type === 'text' && (c.content_data.body === undefined || c.content_data.body === null)) {
                c.content_data.body = '';
            }

            if (c.type === 'image' && (c.content_data.url === undefined || c.content_data.url === null)) {
                c.content_data.url = '';
            }

            if (c.type === 'h5p' && (c.content_data.path === undefined || c.content_data.path === null)) {
                c.content_data.path = '';
            }

            // Migrasi tipe lama 'input_text' menjadi 'eval_essay'
            if (c.type === 'input_text') {
                c.type = 'eval_essay';
            }

            if (
                ['eval_essay', 'eval_short', 'eval_file'].includes(c.type) &&
                (c.content_data.question === undefined || c.content_data.question === null)
            ) {
                c.content_data.question = c.content_data.label || ''; // Support legacy label
            }

            if (['eval_mcq', 'eval_cmcq'].includes(c.type)) {
                if (c.content_data.question === undefined || c.content_data.question === null) {
                    c.content_data.question = c.content_data.label || '';
                }

                if (!Array.isArray(c.content_data.options)) {
                    c.content_data.options = ['Opsi 1', 'Opsi 2'];
                }
            }

            // Inisialisasi untuk Forum Diskusi
            if (
                c.type === 'discussion' &&
                (c.content_data.topic === undefined || c.content_data.topic === null)
            ) {
                c.content_data.topic = '';
            }
        });
        localContents.value = cloned;
    },
    { immediate: true, deep: true },
);

const addContent = (type: string) => {
    let initialData = {};

    if (type === 'text') {
        initialData = { body: '' };
    }

    if (['eval_mcq', 'eval_cmcq'].includes(type)) {
        initialData = { question: '', options: ['Pilihan A', 'Pilihan B'] };
    }

    if (['eval_essay', 'eval_short'].includes(type)) {
        initialData = { question: '' };
    }

    // Template data awal untuk tipe baru
    if (type === 'eval_file') {
        initialData = {
            question: 'Unggah foto hasil kerja atau buku catatan Anda di sini.',
        };
    }

    if (type === 'discussion') {
        initialData = {
            topic: 'Mari berdiskusi! Apa pendapat Anda tentang materi di atas?',
        };
    }

    router.post(
        route('guru.contents.store', { phase: props.phase.id }),
        {
            type: type,
            content_data: initialData,
            correct_answers: [],
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success(`Blok ditambahkan.`),
        },
    );
};

const saveContent = (content: any) => {
    router.put(
        route('guru.contents.update', { content: content.id }),
        {
            content_data: content.content_data,
            correct_answers: content.correct_answers || [],
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Tersimpan', { icon: '💾' }),
        },
    );
};

const removeContent = (id: number) => {
    if (confirm('Yakin ingin menghapus blok materi ini permanen?')) {
        router.delete(route('guru.contents.destroy', { content: id }), {
            preserveScroll: true,
            onSuccess: () => toast.success('Blok dihapus.'),
        });
    }
};

// HELPER UNTUK OPSI PILIHAN GANDA
const addOption = (content: any) => {
    content.content_data.options.push(`Pilihan Baru`);
};

const removeOption = (content: any, index: number) => {
    content.content_data.options.splice(index, 1);
    if (content.correct_answers && Array.isArray(content.correct_answers)) {
        content.correct_answers = content.correct_answers
            .map((val: number) => {
                if (val === index) return null;
                if (val > index) return val - 1;
                return val;
            })
            .filter((val: any) => val !== null);
    }
};

const toggleCorrectAnswer = (content: any, index: number) => {
    if (!content.correct_answers) {
        content.correct_answers = [];
    }
    const idx = content.correct_answers.indexOf(index);
    if (idx > -1) {
        content.correct_answers.splice(idx, 1);
    } else {
        content.correct_answers.push(index);
    }
};
</script>

<template>
    <Head :title="`Builder: ${phase.name}`" />

    <div class="min-h-screen bg-[#F8FAFC] px-6 py-8 font-sans lg:px-10">
        <div class="mx-auto max-w-4xl">
            <div
                class="mb-6 flex items-center gap-2 text-[12px] font-bold text-slate-500"
            >
                <Link
                    :href="route('guru.classes.show', classroom.id)"
                    class="transition-colors hover:text-indigo-600"
                    >{{ classroom.class_name }}</Link
                >
                <i class="pi pi-chevron-right text-[8px]"></i>
                <Link
                    :href="
                        route('guru.classes.topics.show', {
                            classroom: classroom.id,
                            topic: topic.id,
                        })
                    "
                    class="transition-colors hover:text-indigo-600"
                    >{{ topic.title }}</Link
                >
                <i class="pi pi-chevron-right text-[8px]"></i>
                <span class="text-indigo-600"
                    >Builder Fase: {{ phase.name }}</span
                >
            </div>

            <Card
                class="mb-8 overflow-hidden rounded-2xl border-none bg-white shadow-sm"
            >
                <div
                    class="flex flex-col justify-between gap-4 bg-slate-900 px-8 py-6 text-white md:flex-row md:items-center"
                >
                    <div>
                        <span
                            class="mb-1 block text-[10px] font-black tracking-widest text-indigo-400 uppercase"
                            >Siklus LC5E</span
                        >
                        <h1 class="text-2xl font-black">{{ phase.name }}</h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- AI Assistant Feedback Toggle -->
                        <div
                            class="flex items-center gap-4 rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 shadow-inner"
                        >
                            <div class="flex flex-col">
                                <span
                                    class="text-[11px] font-bold tracking-wider text-slate-300 uppercase"
                                    >AI Assistant Feedback</span
                                >
                                <span class="text-[10px] text-slate-400">{{
                                    localAisEnabled ? 'Aktif' : 'Nonaktif'
                                }}</span>
                            </div>
                            <div
                                class="ml-2 cursor-pointer"
                                @click.prevent="toggleAI"
                            >
                                 <Switch
                                     :checked="localAisEnabled"
                                     :disabled="isTogglingAI"
                                     class="pointer-events-none"
                                     :class="localAisEnabled ? '!bg-indigo-500' : '!bg-slate-600'"
                                 />
                            </div>
                        </div>

                        <!-- Chatbot AI Toggle -->
                        <div
                            class="flex items-center gap-4 rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 shadow-inner"
                        >
                            <div class="flex flex-col">
                                <span
                                    class="text-[11px] font-bold tracking-wider text-slate-300 uppercase"
                                    >Chatbot AI Siswa</span
                                >
                                <span class="text-[10px] text-slate-400">{{
                                    localChatbotEnabled ? 'Aktif' : 'Nonaktif'
                                }}</span>
                            </div>
                            <div
                                class="ml-2 cursor-pointer"
                                @click.prevent="toggleChatbot"
                            >
                                 <Switch
                                     :checked="localChatbotEnabled"
                                     :disabled="isTogglingChatbot"
                                     class="pointer-events-none"
                                     :class="localChatbotEnabled ? '!bg-sky-500' : '!bg-slate-600'"
                                 />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="localAisEnabled"
                    class="animate-in border-b border-indigo-50 bg-indigo-50/50 p-8 duration-300 fade-in"
                >
                    <label
                        class="mb-2 flex items-center gap-2 text-[12px] font-black tracking-widest text-indigo-600 uppercase"
                    >
                        <i class="pi pi-sparkles"></i> Prompt Instruksi AI
                        (Opsional)
                    </label>
                    <p class="mb-4 text-[12px] text-slate-500">
                        Atur bagaimana AI harus mengevaluasi jawaban siswa pada
                        fase ini. Contoh:
                        <i
                            >"Jika siswa salah, berikan clue terkait ciri atom
                            karbon, jangan langsung beritahu jawabannya."</i
                        >
                    </p>
                    <textarea
                        v-model="phase.ai_prompt_setting"
                        @blur="saveAIPrompt"
                        placeholder="Ketik instruksi evaluator AI di sini..."
                        class="min-h-[100px] w-full resize-y rounded-xl border border-indigo-200 bg-white p-4 text-[14px] text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                    ></textarea>
                </div>

                <!-- Prompt Instruksi Chatbot AI -->
                <div
                    v-if="localChatbotEnabled"
                    class="animate-in border-b border-indigo-50 bg-sky-50/20 p-8 duration-300 fade-in"
                >
                    <label
                        class="mb-2 flex items-center gap-2 text-[12px] font-black tracking-widest text-sky-600 uppercase"
                    >
                        <i class="pi pi-comments"></i> Prompt Instruksi Chatbot AI (Opsional)
                    </label>
                    <p class="mb-4 text-[12px] text-slate-500">
                        Atur kepribadian, gaya bahasa, atau materi khusus untuk Chatbot AI siswa pada fase ini. Contoh:
                        <i
                            >"Bantu siswa memahami konsep grafik laju reaksi dengan memberikan contoh analogi kendaraan bermotor, jangan berikan jawaban langsung."</i
                        >
                    </p>
                    <textarea
                        v-model="phase.chatbot_prompt_setting"
                        @blur="saveAIPrompt"
                        placeholder="Ketik instruksi khusus chatbot AI di sini..."
                        class="min-h-[100px] w-full resize-y rounded-xl border border-sky-200 bg-white p-4 text-[14px] text-slate-700 shadow-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20"
                    ></textarea>
                </div>
            </Card>

            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-slate-900">
                    Konstruksi Lembar Kerja Siswa
                </h2>
                <span
                    class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-bold text-slate-400 shadow-sm"
                >
                    <i class="pi pi-cloud-upload mr-1"></i> Auto-Save
                </span>
            </div>

            <div class="mb-8 space-y-6">
                <template v-if="localContents.length > 0">
                    <div
                        v-for="(content, index) in localContents"
                        :key="content.id"
                        class="group relative animate-in rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 slide-in-from-bottom-2 hover:border-indigo-300 hover:shadow-md"
                    >
                        <div
                            class="flex items-center justify-between rounded-t-2xl border-b border-slate-100 bg-slate-50/50 px-6 py-4"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-xl bg-indigo-100 text-[11px] font-black text-indigo-700"
                                    >{{ index + 1 }}</span
                                >
                                <span
                                    class="text-[12px] font-bold tracking-wider text-slate-600 uppercase"
                                >
                                    <i
                                        class="pi pi-align-left mr-1 text-slate-400"
                                        v-if="content.type === 'text'"
                                    ></i>
                                    <i
                                        class="pi pi-video mr-1 text-indigo-400"
                                        v-if="content.type === 'h5p'"
                                    ></i>
                                    <i
                                        class="pi pi-image mr-1 text-blue-400"
                                        v-if="content.type === 'image'"
                                    ></i>
                                    <i
                                        class="pi pi-check-circle mr-1 text-emerald-500"
                                        v-if="content.type === 'eval_mcq'"
                                    ></i>
                                    <i
                                        class="pi pi-list mr-1 text-emerald-500"
                                        v-if="content.type === 'eval_cmcq'"
                                    ></i>
                                    <i
                                        class="pi pi-pencil mr-1 text-amber-500"
                                        v-if="
                                            [
                                                'eval_short',
                                                'eval_essay',
                                            ].includes(content.type)
                                        "
                                    ></i>
                                    <i
                                        class="pi pi-upload mr-1 text-pink-500"
                                        v-if="content.type === 'eval_file'"
                                    ></i>
                                    <i
                                        class="pi pi-comments mr-1 text-sky-500"
                                        v-if="content.type === 'discussion'"
                                    ></i>
                                    Blok {{ content.type.replace('eval_', '') }}
                                </span>
                            </div>
                            <button
                                @click="removeContent(content.id)"
                                class="text-slate-300 transition-colors hover:text-rose-500"
                                title="Hapus Blok"
                            >
                                <i class="pi pi-trash"></i>
                            </button>
                        </div>

                        <div class="p-6">
                            <div v-if="content.type === 'text'">
                                <RichTextEditor
                                    v-model="content.content_data.body"
                                    placeholder="Tuliskan narasi penjelasan materi di sini..."
                                />
                                <div class="mt-3 flex justify-end">
                                    <Button
                                        @click="saveContent(content)"
                                        size="sm"
                                        class="bg-slate-800 text-white"
                                        >Simpan Teks</Button
                                    >
                                </div>
                            </div>

                            <div
                                v-if="content.type === 'image'"
                                class="space-y-4"
                            >
                                <Input
                                    v-model="content.content_data.url"
                                    @blur="saveContent(content)"
                                    placeholder="Paste URL Link Gambar di sini (https://...)"
                                    class="bg-slate-50"
                                />
                                <div
                                    v-if="content.content_data.url"
                                    class="flex justify-center rounded-xl border border-slate-100 bg-slate-50 p-4"
                                >
                                    <img
                                        :src="content.content_data.url"
                                        class="max-h-64 rounded-lg object-contain"
                                    />
                                </div>
                            </div>

                            <div v-if="content.type === 'h5p'">
                                <Input
                                    v-model="content.content_data.path"
                                    @blur="saveContent(content)"
                                    placeholder="Paste Link Embed H5P/Video Interaktif di sini..."
                                    class="mb-3 bg-slate-50"
                                />
                                <div
                                    v-if="content.content_data.path"
                                    class="aspect-video w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-900"
                                >
                                    <iframe
                                        :src="content.content_data.path"
                                        class="h-full w-full border-0"
                                    ></iframe>
                                </div>
                            </div>

                            <div
                                v-if="
                                    ['eval_mcq', 'eval_cmcq'].includes(
                                        content.type,
                                    )
                                "
                                class="space-y-5"
                            >
                                <div>
                                    <label
                                        class="mb-2 block text-[12px] font-bold text-slate-700"
                                        >Pertanyaan Soal</label
                                    >
                                    <RichTextEditor
                                        v-model="content.content_data.question"
                                        placeholder="Ketik pertanyaan di sini..."
                                    />
                                </div>
                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50 p-5"
                                >
                                                      <label
                                        class="mb-3 block text-[12px] font-bold text-slate-700"
                                        >Pilihan Jawaban (Tandai Jawaban yang Benar)</label
                                    >
                                    <div class="mb-4 space-y-3">
                                        <div
                                            v-for="(opt, oIdx) in content
                                                .content_data.options"
                                            :key="oIdx"
                                            class="flex items-center gap-3"
                                        >
                                            <div class="flex items-center justify-center">
                                                <!-- PG Biasa (Radio) -->
                                                <input
                                                    v-if="content.type === 'eval_mcq'"
                                                    type="radio"
                                                    :name="'correct_answer_' + content.id"
                                                    :checked="content.correct_answers && content.correct_answers.includes(oIdx)"
                                                    @change="content.correct_answers = [oIdx]"
                                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                    title="Tandai sebagai jawaban benar"
                                                />
                                                <!-- PG Kompleks (Checkbox) -->
                                                <input
                                                    v-if="content.type === 'eval_cmcq'"
                                                    type="checkbox"
                                                    :checked="content.correct_answers && content.correct_answers.includes(oIdx)"
                                                    @change="toggleCorrectAnswer(content, oIdx)"
                                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                    title="Tandai sebagai jawaban benar"
                                                />
                                            </div>
                                            <span
                                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-200 text-[11px] font-black text-slate-500"
                                                >{{
                                                    String.fromCharCode(
                                                        65 + oIdx,
                                                     )
                                                }}</span
                                            >
                                            <Input
                                                v-model="
                                                    content.content_data
                                                        .options[oIdx]
                                                "
                                                class="flex-1 border-slate-200 bg-white"
                                                placeholder="Ketik opsi jawaban..."
                                            />
                                            <button
                                                @click="
                                                    removeOption(content, oIdx)
                                                "
                                                class="text-rose-400 hover:text-rose-600"
                                            >
                                                <i class="pi pi-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <Button
                                        @click="addOption(content)"
                                        type="button"
                                        variant="outline"
                                        class="h-8 border-dashed border-slate-300 text-[11px] text-slate-600"
                                        ><i class="pi pi-plus mr-1"></i> Tambah
                                        Opsi</Button
                                    >
                                </div>
                                <div class="flex justify-end">
                                    <Button
                                        @click="saveContent(content)"
                                        size="sm"
                                        class="bg-indigo-600 text-white hover:bg-indigo-700"
                                        >Simpan Pertanyaan</Button
                                    >
                                </div>
                            </div>

                            <div
                                v-if="
                                    ['eval_short', 'eval_essay'].includes(
                                        content.type,
                                    )
                                "
                                class="space-y-4"
                            >
                                <label
                                    class="block text-[12px] font-bold text-slate-700"
                                    >Pertanyaan / Instruksi Kerja</label
                                >
                                <RichTextEditor
                                    v-model="content.content_data.question"
                                    placeholder="Ketik pertanyaan / perintah untuk siswa..."
                                />
                                <div class="flex justify-end">
                                    <Button
                                        @click="saveContent(content)"
                                        size="sm"
                                        class="bg-amber-600 text-white hover:bg-amber-700"
                                        >Simpan Evaluasi</Button
                                    >
                                </div>
                            </div>

                            <div
                                v-if="content.type === 'eval_file'"
                                class="space-y-4"
                            >
                                <label
                                    class="block text-[12px] font-bold text-slate-700"
                                    >Instruksi Upload File (Opsional)</label
                                >
                                <RichTextEditor
                                    v-model="content.content_data.question"
                                    placeholder="Ketik instruksi upload... (Contoh: Fotokan hasil coretan rumusmu)"
                                />

                                <div
                                    class="mt-4 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center opacity-70"
                                >
                                    <i
                                        class="pi pi-cloud-upload mb-2 text-3xl text-slate-400"
                                    ></i>
                                    <p class="text-sm font-bold text-slate-600">
                                        Area Upload Siswa
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Siswa akan melihat area form unggah
                                        (Drag & Drop) foto/dokumen di sini.
                                    </p>
                                </div>
                                <div class="flex justify-end">
                                    <Button
                                        @click="saveContent(content)"
                                        size="sm"
                                        class="bg-pink-600 text-white hover:bg-pink-700"
                                        >Simpan Instruksi</Button
                                    >
                                </div>
                            </div>

                            <div
                                v-if="content.type === 'discussion'"
                                class="space-y-4"
                            >
                                <label
                                    class="block text-[12px] font-bold text-slate-700"
                                    >Topik / Pemantik Diskusi</label
                                >
                                <RichTextEditor
                                    v-model="content.content_data.topic"
                                    placeholder="Ketik pertanyaan untuk memantik diskusi siswa..."
                                />

                                <!-- Live Discussion Feed -->
                                <div class="mt-4 rounded-xl border border-slate-200 bg-white">
                                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-comments text-sky-500"></i>
                                            <span class="text-[12px] font-bold text-slate-700">Forum Diskusi Kelas (Live)</span>
                                            <span v-if="discussions && discussions.length > 0" class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-black text-sky-600">
                                                {{ discussions.length }} komentar
                                            </span>
                                        </div>
                                        <button @click="refreshDiscussions" class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-bold text-slate-500 transition-colors hover:bg-sky-50 hover:text-sky-600">
                                            <i class="pi pi-refresh text-[10px]"></i> Refresh
                                        </button>
                                    </div>

                                    <div class="max-h-72 min-h-[120px] overflow-y-auto p-4">
                                        <!-- Kosong -->
                                        <div v-if="!discussions || discussions.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                                            <i class="pi pi-inbox mb-2 text-2xl text-slate-300"></i>
                                            <p class="text-[12px] font-bold text-slate-400">Belum ada komentar dari siswa.</p>
                                            <p class="text-[10px] text-slate-400">Komentar akan muncul di sini setelah siswa berdiskusi.</p>
                                        </div>

                                        <!-- Daftar Komentar -->
                                        <div v-else class="space-y-3">
                                            <div v-for="discussion in discussions" :key="discussion.id" class="flex gap-2.5">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-400 text-[9px] font-black text-white">
                                                    {{ discussion.user ? getInitials(discussion.user.name) : '??' }}
                                                </div>
                                                <div class="flex-1">
                                                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                                        <div class="mb-0.5 flex items-center gap-2">
                                                            <span class="text-[11px] font-bold text-slate-800">{{ discussion.user?.name || 'Anonim' }}</span>
                                                            <span class="text-[9px] text-slate-400">{{ formatTime(discussion.created_at) }}</span>
                                                        </div>
                                                        <p class="text-[12px] leading-relaxed text-slate-600">{{ discussion.message }}</p>
                                                    </div>
                                                    <!-- Replies -->
                                                    <div v-if="discussion.replies && discussion.replies.length > 0" class="mt-1.5 space-y-1.5 border-l-2 border-sky-100 pl-3">
                                                        <div v-for="reply in discussion.replies" :key="reply.id" class="flex gap-2">
                                                            <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-400 text-[7px] font-black text-white">
                                                                {{ reply.user ? getInitials(reply.user.name) : '??' }}
                                                            </div>
                                                            <div class="rounded-md bg-white px-2.5 py-1.5">
                                                                <span class="text-[10px] font-bold text-slate-700">{{ reply.user?.name || 'Anonim' }}</span>
                                                                <span class="ml-1 text-[8px] text-slate-400">{{ formatTime(reply.created_at) }}</span>
                                                                <p class="text-[11px] text-slate-600">{{ reply.message }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <Button
                                        @click="saveContent(content)"
                                        size="sm"
                                        class="bg-sky-600 text-white hover:bg-sky-700"
                                        >Simpan Forum Diskusi</Button
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-white/50 py-16 text-center"
                >
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50 text-indigo-400"
                    >
                        <i class="pi pi-th-large text-3xl"></i>
                    </div>
                    <h3 class="text-[16px] font-bold text-slate-800">
                        Lembar Kerja Kosong
                    </h3>
                    <p class="mt-1 text-[13px] text-slate-500">
                        Mulai susun modul Anda dengan menu di bawah ini.
                    </p>
                </div>
            </div>

            <div
                class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-6 text-center shadow-sm"
            >
                <h3 class="mb-4 text-[13px] font-bold text-indigo-900">
                    <i class="pi pi-plus-circle mr-1 text-indigo-500"></i>
                    Tambah Komponen Baru ke Fase Ini
                </h3>
                <div class="flex flex-wrap justify-center gap-3">
                    <Button
                        @click="addContent('text')"
                        variant="outline"
                        class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                        ><i class="pi pi-align-left mr-2 text-slate-400"></i>
                        Teks Materi</Button
                    >
                    <Button
                        @click="addContent('h5p')"
                        variant="outline"
                        class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                        ><i class="pi pi-video mr-2 text-indigo-400"></i> Media
                        H5P</Button
                    >

                    <div
                        class="mx-1 hidden h-8 w-px bg-indigo-200 lg:block"
                    ></div>

                    <Button
                        @click="addContent('eval_mcq')"
                        variant="outline"
                        class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                        ><i
                            class="pi pi-check-circle mr-2 text-emerald-500"
                        ></i>
                        Pilihan Ganda</Button
                    >
                    <Button
                        @click="addContent('eval_cmcq')"
                        variant="outline"
                        class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                        ><i class="pi pi-list mr-2 text-emerald-500"></i>
                        Pilihan Kompleks</Button
                    >
                    <Button
                        @click="addContent('eval_short')"
                        variant="outline"
                        class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                        ><i class="pi pi-minus mr-2 text-amber-500"></i> Jawaban
                        Singkat</Button
                    >
                    <Button
                        @click="addContent('eval_essay')"
                        variant="outline"
                        class="border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                        ><i class="pi pi-align-justify mr-2 text-amber-500"></i>
                        Esai Panjang</Button
                    >

                    <div
                        class="mx-1 hidden h-8 w-px bg-indigo-200 lg:block"
                    ></div>

                    <Button
                        @click="addContent('eval_file')"
                        variant="outline"
                        class="border-pink-200 bg-pink-50/50 text-pink-700 hover:bg-pink-100"
                        ><i class="pi pi-upload mr-2 text-pink-500"></i> Upload
                        Gambar</Button
                    >
                    <Button
                        @click="addContent('discussion')"
                        variant="outline"
                        class="border-sky-200 bg-sky-50/50 text-sky-700 hover:bg-sky-100"
                        ><i class="pi pi-comments mr-2 text-sky-500"></i> Forum
                        Diskusi</Button
                    >
                </div>
            </div>
        </div>
    </div>
</template>
