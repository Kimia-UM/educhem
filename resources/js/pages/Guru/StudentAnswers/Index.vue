<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { marked } from 'marked';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

const props = defineProps<{
    classroom: { id: number; class_name: string };
    topic: { id: number; title: string };
    phase: {
        id: number;
        name: string;
        contents: Array<{
            id: number;
            type: string;
            content_data: any;
            order: number;
        }>;
    };
    answersGrouped: Record<
        number,
        Array<{
            id: number;
            user_id: number;
            content_id: number;
            answer_data: string | null;
            ai_feedback: string | null;
            created_at: string;
            user: { id: number; name: string; email: string };
        }>
    >;
    totalStudents: number;
}>();

const expandedContent = ref<Record<number, boolean>>({});

const toggleExpand = (contentId: number) => {
    expandedContent.value[contentId] = !expandedContent.value[contentId];
};

const getAnswersForContent = (contentId: number) => {
    return props.answersGrouped[contentId] || [];
};

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        eval_mcq: 'Pilihan Ganda',
        eval_cmcq: 'Pilihan Kompleks',
        eval_short: 'Jawaban Singkat',
        eval_essay: 'Esai Panjang',
        eval_file: 'Upload File',
        input_text: 'Input Teks',
    };

    return labels[type] || type;
};

const getTypeIcon = (type: string) => {
    const icons: Record<string, string> = {
        eval_mcq: 'pi-check-circle',
        eval_cmcq: 'pi-list',
        eval_short: 'pi-minus',
        eval_essay: 'pi-align-justify',
        eval_file: 'pi-upload',
        input_text: 'pi-pencil',
    };

    return icons[type] || 'pi-question';
};

const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
        eval_mcq: 'text-emerald-500',
        eval_cmcq: 'text-emerald-500',
        eval_short: 'text-amber-500',
        eval_essay: 'text-amber-500',
        eval_file: 'text-pink-500',
        input_text: 'text-indigo-500',
    };

    return colors[type] || 'text-slate-500';
};

const formatAnswer = (answer: string | null, type: string): string => {
    if (!answer) {
return '-';
}

    // Jika tipe checkbox/cmcq, coba parse JSON array
    if (type === 'eval_cmcq') {
        try {
            const arr = JSON.parse(answer);

            if (Array.isArray(arr)) {
return arr.join(', ');
}
        } catch (e) {
            // fallback
        }
    }

    return answer;
};

const isFileAnswer = (answer: string | null): boolean => {
    if (!answer) {
return false;
}

    return answer.includes('/storage/');
};

const isImageFile = (answer: string | null): boolean => {
    if (!answer) {
return false;
}

    return /\.(jpg|jpeg|png|gif|webp)$/i.test(answer);
};

const formatDate = (dateString: string) => {
    try {
        const date = new Date(dateString);

        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
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

    return marked.parse(text);
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const stripHtml = (html: string | null | undefined): string => {
    if (!html) {
return '-';
}

    const tmp = document.createElement('div');
    tmp.innerHTML = html;

    return tmp.textContent || tmp.innerText || '-';
};
</script>

<template>
    <Head :title="`Rekap Jawaban: ${phase.name}`" />

    <div class="min-h-screen bg-[#F4F7F9] px-4 py-6 font-sans md:px-8">
        <div class="mx-auto max-w-6xl">
            <!-- Breadcrumb -->
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
                <span class="text-indigo-600">Rekap Jawaban</span>
            </div>

            <!-- Header Card -->
            <Card
                class="mb-8 overflow-hidden border-none bg-white shadow-sm"
            >
                <div
                    class="flex flex-col justify-between gap-4 bg-gradient-to-r from-slate-900 to-slate-800 px-8 py-6 text-white md:flex-row md:items-center"
                >
                    <div>
                        <span
                            class="mb-1 block text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >Rekap Jawaban Siswa</span
                        >
                        <h1 class="text-2xl font-black">{{ phase.name }}</h1>
                        <p class="mt-1 text-[13px] text-slate-300">
                            {{ topic.title }} · {{ classroom.class_name }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div
                            class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 text-center shadow-inner"
                        >
                            <div
                                class="text-2xl font-black text-emerald-400"
                            >
                                {{ totalStudents }}
                            </div>
                            <div
                                class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                            >
                                Total Siswa
                            </div>
                        </div>
                        <div
                            class="rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 text-center shadow-inner"
                        >
                            <div
                                class="text-2xl font-black text-indigo-400"
                            >
                                {{ phase.contents?.length || 0 }}
                            </div>
                            <div
                                class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                            >
                                Soal / Tugas
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Empty State -->
            <div
                v-if="!phase.contents || phase.contents.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center"
            >
                <div
                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-50"
                >
                    <i class="pi pi-inbox text-3xl text-slate-300"></i>
                </div>
                <h4 class="mb-1 text-[16px] font-bold text-slate-800">
                    Tidak Ada Soal Evaluasi
                </h4>
                <p
                    class="max-w-[350px] text-[13px] font-medium text-slate-500"
                >
                    Fase ini belum memiliki blok evaluasi (soal). Tambahkan
                    soal terlebih dahulu di Builder Fase.
                </p>
            </div>

            <!-- Content List -->
            <div v-else class="space-y-5">
                <div
                    v-for="(content, cIdx) in phase.contents"
                    :key="content.id"
                >
                    <Card
                        class="overflow-hidden border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                    >
                        <!-- Question Header (Clickable to expand) -->
                        <button
                            @click="toggleExpand(content.id)"
                            class="flex w-full items-center justify-between px-6 py-4 text-left transition-colors hover:bg-slate-50/50"
                        >
                            <div class="flex items-center gap-4">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-[13px] font-black text-slate-600"
                                >
                                    {{ cIdx + 1 }}
                                </span>
                                <div>
                                    <div
                                        class="mb-1 flex items-center gap-2"
                                    >
                                        <i
                                            class="pi text-[12px]"
                                            :class="[
                                                getTypeIcon(content.type),
                                                getTypeColor(content.type),
                                            ]"
                                        ></i>
                                        <span
                                            class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                        >
                                            {{
                                                getTypeLabel(content.type)
                                            }}
                                        </span>
                                    </div>
                                    <h3
                                        class="max-w-2xl text-[14px] font-bold text-slate-800"
                                    >
                                        {{
                                            stripHtml(
                                                content.content_data
                                                    ?.question ||
                                                    content.content_data
                                                        ?.label ||
                                                    'Pertanyaan tanpa judul',
                                            )
                                        }}
                                    </h3>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <!-- Badge: Jumlah yang sudah menjawab -->
                                <div
                                    class="flex items-center gap-2 rounded-lg border px-3 py-1.5"
                                    :class="
                                        getAnswersForContent(content.id)
                                            .length > 0
                                            ? 'border-emerald-200 bg-emerald-50'
                                            : 'border-slate-200 bg-slate-50'
                                    "
                                >
                                    <i
                                        class="pi pi-users text-[11px]"
                                        :class="
                                            getAnswersForContent(content.id)
                                                .length > 0
                                                ? 'text-emerald-500'
                                                : 'text-slate-400'
                                        "
                                    ></i>
                                    <span
                                        class="text-[12px] font-bold"
                                        :class="
                                            getAnswersForContent(content.id)
                                                .length > 0
                                                ? 'text-emerald-700'
                                                : 'text-slate-500'
                                        "
                                    >
                                        {{
                                            getAnswersForContent(content.id)
                                                .length
                                        }}
                                        / {{ totalStudents }}
                                    </span>
                                </div>
                                <i
                                    class="pi text-[12px] text-slate-400 transition-transform duration-200"
                                    :class="
                                        expandedContent[content.id]
                                            ? 'pi-chevron-up'
                                            : 'pi-chevron-down'
                                    "
                                ></i>
                            </div>
                        </button>

                        <!-- Expanded Answer Table -->
                        <div
                            v-if="expandedContent[content.id]"
                            class="animate-in border-t border-slate-100 duration-200 fade-in slide-in-from-top-1"
                        >
                            <!-- No Answers State -->
                            <div
                                v-if="
                                    getAnswersForContent(content.id)
                                        .length === 0
                                "
                                class="flex flex-col items-center justify-center py-12 text-center"
                            >
                                <i
                                    class="pi pi-inbox mb-2 text-2xl text-slate-300"
                                ></i>
                                <p
                                    class="text-[13px] font-bold text-slate-400"
                                >
                                    Belum ada siswa yang menjawab soal ini.
                                </p>
                            </div>

                            <!-- Answer List -->
                            <div
                                v-else
                                class="divide-y divide-slate-100"
                            >
                                <div
                                    v-for="(answer, aIdx) in getAnswersForContent(content.id)"
                                    :key="answer.id"
                                    class="flex gap-4 px-6 py-4 transition-colors hover:bg-slate-50/50"
                                >
                                    <!-- Student Avatar -->
                                    <div class="flex shrink-0 flex-col items-center gap-1 pt-0.5">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-full border border-indigo-100 bg-indigo-50 text-[11px] font-black text-indigo-600 uppercase"
                                        >
                                            {{
                                                getInitials(
                                                    answer.user?.name ||
                                                        'X',
                                                )
                                            }}
                                        </div>
                                        <span class="text-[9px] font-bold text-slate-400">
                                            #{{ aIdx + 1 }}
                                        </span>
                                    </div>

                                    <!-- Answer Content -->
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="mb-1.5 flex items-center gap-2"
                                        >
                                            <span
                                                class="text-[13px] font-bold text-slate-800"
                                                >{{
                                                    answer.user?.name ||
                                                    'Siswa Terhapus'
                                                }}</span
                                            >
                                            <span
                                                class="text-[10px] text-slate-400"
                                                >{{
                                                    answer.user?.email
                                                }}</span
                                            >
                                            <span
                                                class="ml-auto text-[10px] font-bold text-slate-400"
                                            >
                                                <i class="pi pi-clock mr-0.5 text-[8px]"></i>
                                                {{
                                                    formatDate(
                                                        answer.created_at,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <!-- Answer Body -->
                                        <div
                                            class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"
                                        >
                                            <!-- File Upload Answer -->
                                            <div
                                                v-if="
                                                    isFileAnswer(
                                                        answer.answer_data,
                                                    )
                                                "
                                            >
                                                <img
                                                    v-if="
                                                        isImageFile(
                                                            answer.answer_data,
                                                        )
                                                    "
                                                    :src="answer.answer_data"
                                                    class="max-h-48 rounded-lg object-contain"
                                                    alt="Upload siswa"
                                                />
                                                <a
                                                    v-else
                                                    :href="answer.answer_data"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-[12px] font-bold text-indigo-600 transition-colors hover:bg-indigo-100"
                                                >
                                                    <i
                                                        class="pi pi-file"
                                                    ></i>
                                                    Lihat File
                                                </a>
                                            </div>
                                            <!-- Text Answer -->
                                            <p
                                                v-else
                                                class="whitespace-pre-wrap text-[13px] leading-relaxed text-slate-700"
                                            >
                                                {{
                                                    formatAnswer(
                                                        answer.answer_data,
                                                        content.type,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <!-- AI Feedback -->
                                        <div
                                            v-if="answer.ai_feedback"
                                            class="mt-2 rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/80 to-purple-50/50 px-4 py-3"
                                        >
                                            <div
                                                class="mb-1.5 flex items-center gap-1.5"
                                            >
                                                <i
                                                    class="pi pi-sparkles text-[10px] text-indigo-500"
                                                ></i>
                                                <span
                                                    class="text-[10px] font-black tracking-widest text-indigo-600 uppercase"
                                                    >Feedback AI</span
                                                >
                                            </div>
                                            <div
                                                class="prose prose-sm prose-slate max-w-none text-[12px] leading-relaxed"
                                                v-html="
                                                    renderMarkdown(
                                                        answer.ai_feedback,
                                                    )
                                                "
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Back Button -->
            <div class="mt-8 flex justify-start">
                <Link
                    :href="
                        route('guru.classes.topics.show', {
                            classroom: classroom.id,
                            topic: topic.id,
                        })
                    "
                >
                    <Button
                        variant="outline"
                        class="h-10 border-slate-200 px-6 text-[13px] font-bold text-slate-600 hover:bg-slate-50"
                    >
                        <i class="pi pi-arrow-left mr-2 text-[10px]"></i>
                        Kembali ke Topik
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
