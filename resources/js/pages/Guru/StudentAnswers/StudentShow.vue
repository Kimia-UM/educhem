<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

const props = defineProps<{
    classroom: any;
    student: any;
    topics: any[];
    answers: any[];
    isEvaluationSent: boolean;
    isEvaluationFinished: boolean;
}>();

const localAnswers = ref([...props.answers]);

watch(() => props.answers, (newAnswers) => {
    localAnswers.value = [...newAnswers];
}, { deep: true });

const activeTopicId = ref<number | null>(
    props.topics.length > 0 ? props.topics[0].id : null
);

const activeTopic = computed(() => {
    return props.topics.find(t => t.id === activeTopicId.value) || null;
});

// Helper untuk mendapatkan jawaban per fase
const getPhaseAnswers = (phaseId: number) => {
    return localAnswers.value.filter(a => a.phase_id === phaseId);
};

// Mengecek kebenaran auto-grading untuk PG dan PG Kompleks
const checkAutoGrade = (answer: any) => {
    const content = answer.content;
    const correctIndices = content.correct_answers || [];
    const options = content.content_data?.options || [];
    const studentAns = answer.answer_data;

    // Resolve indeks kunci jawaban menjadi teks opsi
    const correctTexts = correctIndices
        .map((idx: any) => options[Number(idx)])
        .filter((v: any) => v !== undefined);

    if (content.type === 'eval_mcq') {
        // PG biasa: jawaban siswa berupa teks opsi
        return correctTexts.includes(String(studentAns));
    } else if (content.type === 'eval_cmcq') {
        // PG Kompleks: jawaban siswa berupa array teks opsi atau JSON string
        let studentAnsArray = studentAns;
        if (typeof studentAns === 'string') {
            try { studentAnsArray = JSON.parse(studentAns); } catch(e) { return false; }
        }
        if (!Array.isArray(studentAnsArray)) return false;
        
        const isSameLength = studentAnsArray.length === correctTexts.length;
        const hasAllCorrect = correctTexts.every((c: any) => studentAnsArray.map(String).includes(String(c)));
        return isSameLength && hasAllCorrect;
    }
    return null;
};

// Helper untuk parsing jawaban CMCQ (bisa berupa array atau JSON string)
const parseCmcqAnswer = (answerData: any): string[] => {
    if (Array.isArray(answerData)) return answerData;
    if (typeof answerData === 'string') {
        try { 
            const parsed = JSON.parse(answerData);
            return Array.isArray(parsed) ? parsed : [answerData];
        } catch(e) { return [answerData]; }
    }
    return [];
};

const evaluatingIds = ref<Record<number, boolean>>({});

// Menilai soal uraian / kecualikan PG
const evaluateAnswer = (answerId: number, evaluation: 'benar' | 'setengah_benar' | 'salah' | 'tidak_dinilai' | null) => {
    evaluatingIds.value[answerId] = true;

    // Optimistic Update
    const ans = localAnswers.value.find((a: any) => a.id === answerId);
    const prevEval = ans ? ans.evaluation : null;
    if (ans) ans.evaluation = evaluation;

    router.post(route('guru.answers.evaluate', answerId), {
        evaluation
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Berhasil', {
                id: 'evaluate-success',
                description: 'Penilaian berhasil disimpan.'
            });
        },
        onError: () => {
            // Revert if error
            if (ans) ans.evaluation = prevEval;
            toast.error('Gagal', {
                description: 'Terjadi kesalahan saat menyimpan penilaian.'
            });
        },
        onFinish: () => {
            evaluatingIds.value[answerId] = false;
        }
    });
};

const finishEvaluation = () => {
    router.post(route('guru.classes.students.finish-evaluation', {
        classroom: props.classroom.id,
        student: props.student.id
    }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil', {
                description: 'Evaluasi telah ditandai selesai.'
            });
        },
        onError: () => {
            toast.error('Gagal', {
                description: 'Terjadi kesalahan saat menyelesaikan evaluasi.'
            });
        }
    });
};

const editEvaluation = () => {
    router.post(route('guru.classes.students.edit-evaluation', {
        classroom: props.classroom.id,
        student: props.student.id
    }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil', {
                description: 'Kunci evaluasi dibuka. Anda dapat mengedit kembali.'
            });
        },
        onError: () => {
            toast.error('Gagal', {
                description: 'Terjadi kesalahan saat membuka kunci evaluasi.'
            });
        }
    });
};

const sendEvaluation = () => {
    router.post(route('guru.classes.students.send-evaluation', {
        classroom: props.classroom.id,
        student: props.student.id
    }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil Dikirim', {
                description: 'Hasil evaluasi berhasil dikirimkan ke siswa.'
            });
        },
        onError: () => {
            toast.error('Gagal', {
                description: 'Terjadi kesalahan saat mengirimkan evaluasi.'
            });
        }
    });
};

// Jawaban yang difilter berdasarkan topik aktif
const activeTopicAnswers = computed(() => {
    if (!activeTopic.value) return [];
    const phaseIds = activeTopic.value.phases.map((p: any) => p.id);
    return localAnswers.value.filter(a => phaseIds.includes(a.phase_id));
});

// Hitung progress (per topik aktif)
const totalEvaluated = computed(() => {
    return activeTopicAnswers.value.filter(a => {
        if (['eval_mcq', 'eval_cmcq'].includes(a.content.type)) return true;
        return a.evaluation !== null;
    }).length;
});
const totalQuestions = computed(() => activeTopicAnswers.value.length);
const progressPercent = computed(() => totalQuestions.value > 0 ? Math.round((totalEvaluated.value / totalQuestions.value) * 100) : 0);

// Jawaban yang ikut dihitung skor (exclude tidak_dinilai)
const scoredAnswers = computed(() => {
    return activeTopicAnswers.value.filter(a => a.evaluation !== 'tidak_dinilai');
});

const totalScore = computed(() => {
    let score = 0;
    scoredAnswers.value.forEach(a => {
        if (['eval_mcq', 'eval_cmcq'].includes(a.content.type)) {
            if (checkAutoGrade(a)) score += 1;
        } else {
            if (a.evaluation === 'benar') score += 2;
            else if (a.evaluation === 'setengah_benar') score += 1;
        }
    });
    return score;
});

const maxScore = computed(() => {
    let mScore = 0;
    scoredAnswers.value.forEach(a => {
        if (['eval_mcq', 'eval_cmcq'].includes(a.content.type)) mScore += 1;
        else mScore += 2;
    });
    return mScore;
});
const scorePercent = computed(() => maxScore.value > 0 ? Math.round((totalScore.value / maxScore.value) * 100) : 0);

const getScoreText = (evaluation: string | null) => {
    switch (evaluation) {
        case 'benar': return '2';
        case 'setengah_benar': return '1';
        case 'salah': return '0';
        case 'tidak_dinilai': return '—';
        default: return '-';
    }
};

const isImage = (url: string | null) => {
    if (!url) return false;
    return /\.(jpeg|jpg|gif|png|webp)/i.test(url);
};
</script>

<template>
    <Head :title="`Evaluasi Siswa: ${student.name}`" />

    <div class="min-h-screen bg-[#F8FAFC] px-4 py-6 font-sans md:px-8 lg:px-10">
        <div class="mx-auto max-w-5xl">
            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="mb-3 flex items-center gap-2 text-[12px] font-bold text-slate-500">
                        <Link :href="route('guru.classes.show', { class: classroom.id, tab: 'siswa' })" class="hover:text-indigo-600 transition-colors">Kelas {{ classroom.class_name }}</Link>
                        <i class="pi pi-chevron-right text-[8px]"></i>
                        <span class="text-indigo-600">Evaluasi Siswa</span>
                    </div>
                    <h1 class="text-[24px] font-extrabold text-slate-900 flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[14px] text-indigo-700 font-bold uppercase">
                            {{ student.name.substring(0, 2) }}
                        </div>
                        {{ student.name }}
                    </h1>
                    <p class="text-[13px] text-slate-500 mt-1">{{ student.email }}</p>
                </div>
                
                <div class="flex flex-col items-start md:items-end gap-3 md:gap-1.5">
                    <div class="flex flex-wrap items-center gap-3 w-full justify-start md:justify-end">
                        <!-- Status Badge -->
                        <div class="flex items-center gap-2 mr-1">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status:</span>
                            <span v-if="!isEvaluationFinished" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-[12px] font-bold border border-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Sedang Dinilai
                            </span>
                            <span v-else-if="isEvaluationFinished && !isEvaluationSent" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-[12px] font-bold border border-blue-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Belum Dikirim
                            </span>
                            <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-[12px] font-bold border border-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Telah Dikirim
                            </span>
                        </div>

                        <!-- Tombol Selesai / Edit Evaluasi -->
                        <Button 
                            v-if="!isEvaluationFinished"
                            @click="finishEvaluation"
                            :disabled="progressPercent < 100"
                            class="px-4 py-2.5 rounded-xl font-bold transition-all bg-indigo-600 text-white hover:bg-indigo-700 disabled:bg-slate-100 disabled:text-slate-400 disabled:border disabled:border-slate-200"
                        >
                            <i class="pi pi-lock mr-2 text-[12px]"></i> Selesai Evaluasi
                        </Button>
                        
                        <Button 
                            v-else
                            @click="editEvaluation"
                            class="px-4 py-2.5 rounded-xl font-bold transition-all bg-white text-rose-600 border border-rose-200 hover:bg-rose-50"
                        >
                            <i class="pi pi-unlock mr-2 text-[12px]"></i> Edit Evaluasi
                        </Button>

                        <!-- Tombol Kirim / Kirim Ulang Hasil -->
                        <Button 
                            @click="sendEvaluation" 
                            :disabled="!isEvaluationFinished"
                            :class="[
                                'px-4 py-2.5 rounded-xl font-bold shadow-sm transition-all',
                                !isEvaluationFinished
                                    ? 'bg-slate-100 text-slate-400 border border-slate-200 opacity-70 cursor-not-allowed'
                                    : isEvaluationSent 
                                        ? 'bg-emerald-600 text-white hover:bg-emerald-700 hover:shadow-md'
                                        : 'bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-md'
                            ]"
                        >
                            <i :class="['pi mr-2', isEvaluationSent ? 'pi-sync' : 'pi-send']"></i>
                            {{ isEvaluationSent ? 'Perbarui & Kirim Ulang' : 'Kirim Hasil ke Siswa' }}
                        </Button>

                        <!-- Tombol Cetak Evaluasi (PDF) -->
                        <a
                            :href="route('guru.classes.students.print', { classroom: classroom.id, student: student.id })"
                            target="_blank"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl font-bold transition-all bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 shadow-sm"
                        >
                            <i class="pi pi-print mr-2 text-[12px] text-slate-500"></i> Cetak Evaluasi (PDF)
                        </a>
                    </div>
                    
                    <div class="text-[11px] text-slate-400 font-medium max-w-[350px] text-left md:text-right">
                        <p v-if="progressPercent < 100 && !isEvaluationFinished">
                            Selesaikan evaluasi seluruh soal ({{ progressPercent }}%) terlebih dahulu.
                        </p>
                        <p v-else-if="!isEvaluationFinished">
                            Semua soal telah dinilai. Klik "Selesai Evaluasi" untuk mengunci & mengirim hasil.
                        </p>
                        <p v-else-if="isEvaluationFinished && !isEvaluationSent">
                            Hasil evaluasi telah dikunci. Anda sekarang dapat mengirimkannya ke siswa.
                        </p>
                        <p v-else>
                            Hasil telah terkirim. Klik "Edit" untuk mengubah kembali, atau "Kirim Ulang" untuk memperbarui.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Progress Box -->
            <Card class="mb-8 p-5 bg-white border-slate-200">
                <div class="flex justify-between items-end mb-2">
                    <div>
                        <h3 class="text-[14px] font-bold text-slate-800">Nilai Siswa</h3>
                        <p class="text-[12px] text-slate-500">
                            <span v-if="activeTopic" class="text-indigo-600 font-semibold">{{ activeTopic.title }}</span>
                            — Skor maksimal: {{ maxScore }} ({{ totalEvaluated }}/{{ totalQuestions }} dievaluasi)
                        </p>
                    </div>
                    <span class="text-[20px] font-extrabold" :class="scorePercent >= 75 ? 'text-emerald-600' : (scorePercent >= 50 ? 'text-amber-500' : 'text-rose-600')">{{ totalScore }} <span class="text-[14px] font-bold text-slate-400">Poin</span></span>
                </div>
                <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-500" :class="scorePercent >= 75 ? 'bg-emerald-500' : (scorePercent >= 50 ? 'bg-amber-500' : 'bg-rose-500')" :style="{ width: scorePercent + '%' }"></div>
                </div>
            </Card>

            <!-- Pemilihan Topik -->
            <div v-if="topics.length > 0" class="mb-8 flex flex-wrap gap-3">
                <button 
                    v-for="topic in topics" 
                    :key="topic.id"
                    @click="activeTopicId = topic.id"
                    :class="[
                        'px-5 py-2.5 rounded-full text-[14px] font-bold transition-all shadow-sm',
                        activeTopicId === topic.id 
                            ? 'bg-indigo-600 text-white ring-2 ring-indigo-600 ring-offset-2 border-transparent' 
                            : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
                    ]"
                >
                    {{ topic.title }}
                </button>
            </div>

            <!-- Konten Evaluasi (Berdasarkan Topik yang Dipilih) -->
            <div v-if="activeTopic" class="flex flex-col gap-10">
                <div v-for="phase in activeTopic.phases" :key="phase.id" class="bg-white rounded-2xl border border-slate-200 p-4 md:p-8 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-indigo-500"></div>
                    
                    <!-- Judul Fase -->
                    <div class="text-center mb-8 pb-4 border-b border-slate-100">
                        <h2 class="text-[20px] font-extrabold text-slate-900">{{ phase.name }}</h2>
                    </div>

                        <div v-if="getPhaseAnswers(phase.id).length === 0" class="text-center py-10 rounded-xl border border-dashed border-slate-200 bg-slate-50">
                            <i class="pi pi-file-excel text-slate-300 text-3xl mb-3 block"></i>
                            <p class="text-[14px] font-medium text-slate-500">Siswa belum menjawab soal di fase ini.</p>
                        </div>

                        <div v-else class="flex flex-col gap-5">
                            <Card v-for="(answer, index) in getPhaseAnswers(phase.id)" :key="answer.id" class="p-4 md:p-5 border-slate-200 bg-white shadow-sm hover:shadow-md transition-shadow">
                                <!-- Pertanyaan -->
                                <div class="mb-4">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 block">Pertanyaan {{ index + 1 }}</span>
                                    <div class="text-[14px] font-medium text-slate-800" v-html="answer.content.content_data?.question || answer.content.content_data?.label || answer.content.content_data?.content"></div>
                                </div>

                                <!-- Jawaban PG/PG Kompleks -->
                                <div v-if="['eval_mcq', 'eval_cmcq'].includes(answer.content.type)" class="bg-slate-50 rounded-lg p-4 border border-slate-100 mb-4">
                                    <div class="text-[12px] font-bold text-slate-500 mb-2">Jawaban Siswa:</div>
                                    <ul class="list-disc pl-5 text-[13px] text-slate-700">
                                        <template v-if="answer.content.type === 'eval_mcq'">
                                            <li>{{ answer.answer_data }}</li>
                                        </template>
                                        <template v-else>
                                            <li v-for="ans in parseCmcqAnswer(answer.answer_data)" :key="ans">
                                                {{ ans }}
                                            </li>
                                        </template>
                                    </ul>
                                    
                                    <div class="mt-4 pt-3 border-t border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[12px] font-bold text-slate-600">Auto-grading:</span>
                                            <span v-if="checkAutoGrade(answer)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-600 text-[12px] font-bold border border-emerald-100">
                                                <i class="pi pi-check-circle"></i> Benar
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-50 text-rose-600 text-[12px] font-bold border border-rose-100">
                                                <i class="pi pi-times-circle"></i> Salah
                                            </span>
                                            <span class="ml-2 text-[12px] font-extrabold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                                <template v-if="answer.evaluation === 'tidak_dinilai'">Tidak Dinilai</template>
                                                <template v-else>Skor Akhir: {{ checkAutoGrade(answer) ? 1 : 0 }} / 1</template>
                                            </span>
                                        </div>
                                        <div class="flex gap-2">
                                            <button v-if="answer.evaluation === 'tidak_dinilai'"
                                                @click="evaluateAnswer(answer.id, null)"
                                                :disabled="isEvaluationFinished || evaluatingIds[answer.id]"
                                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all bg-white text-indigo-600 border border-indigo-200 hover:bg-indigo-50 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <i class="pi pi-check-square mr-1" :class="evaluatingIds[answer.id] ? 'pi-spinner pi-spin' : ''"></i> Masukkan ke Penilaian
                                            </button>
                                            <button v-else
                                                @click="evaluateAnswer(answer.id, 'tidak_dinilai')"
                                                :disabled="isEvaluationFinished || evaluatingIds[answer.id]"
                                                class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all bg-white text-slate-500 border border-slate-200 hover:bg-slate-50 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <i class="pi pi-ban mr-1" :class="evaluatingIds[answer.id] ? 'pi-spinner pi-spin' : ''"></i> Kecualikan dari Penilaian
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="answer.evaluation === 'tidak_dinilai'" class="mt-2 text-[11px] text-rose-500 font-bold italic bg-rose-50 p-2 rounded-md border border-rose-100 inline-block">
                                        * Soal pilihan ganda ini dikecualikan dari perhitungan skor akhir.
                                    </div>
                                </div>

                                <!-- Jawaban Upload File -->
                                <div v-else-if="answer.content.type === 'eval_file'" class="bg-slate-50 rounded-lg p-4 border border-slate-100 mb-4">
                                    <div class="text-[12px] font-bold text-slate-500 mb-2">Jawaban Siswa (File/Gambar):</div>
                                    <div v-if="isImage(answer.answer_data)" class="mt-2">
                                        <img :src="answer.answer_data" alt="Uploaded Image" class="max-h-64 rounded-lg border border-slate-200 shadow-sm mb-2" />
                                        <a :href="answer.answer_data" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800">
                                            <i class="pi pi-external-link"></i> Lihat Gambar Ukuran Penuh
                                        </a>
                                    </div>
                                    <div v-else-if="answer.answer_data" class="flex items-center gap-2 mt-2">
                                        <i class="pi pi-file text-lg text-slate-400"></i>
                                        <a :href="answer.answer_data" target="_blank" class="font-bold text-indigo-600 hover:underline">
                                            Lihat File Terunggah
                                        </a>
                                    </div>
                                    <div v-else class="text-[14px] text-slate-400 italic">Siswa belum mengunggah file.</div>
                                </div>

                                <!-- Jawaban Uraian -->
                                <div v-else class="bg-slate-50 rounded-lg p-4 border border-slate-100 mb-4">
                                    <div class="text-[12px] font-bold text-slate-500 mb-2">Jawaban Siswa:</div>
                                    <div v-if="answer.content.type === 'eval_essay' || answer.content.type === 'input_text'" class="text-[14px] text-slate-800 break-words rich-text-content" v-html="answer.answer_data || ''"></div>
                                    <div v-else class="text-[14px] text-slate-800 break-words whitespace-pre-wrap">{{ answer.answer_data }}</div>
                                </div>

                                <!-- Form Evaluasi Manual (Hanya untuk uraian) -->
                                <div v-if="!['eval_mcq', 'eval_cmcq'].includes(answer.content.type)" class="flex flex-wrap items-center gap-2.5 pt-2 w-full">
                                    <span class="text-[12px] font-bold text-slate-500 mr-2">Evaluasi:</span>
                                    <button 
                                        @click="evaluateAnswer(answer.id, 'benar')"
                                        :disabled="isEvaluationFinished || evaluatingIds[answer.id]"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-[12px] font-bold transition-all border',
                                            isEvaluationFinished || evaluatingIds[answer.id] ? 'opacity-50 cursor-not-allowed' : '',
                                            answer.evaluation === 'benar' ? 'bg-emerald-500 text-white border-emerald-600 shadow-sm' : 'bg-white text-emerald-600 border-emerald-200 hover:bg-emerald-50'
                                        ]"
                                    >
                                        <i class="pi mr-1 text-[10px]" :class="evaluatingIds[answer.id] ? 'pi-spinner pi-spin' : 'pi-check'"></i> Benar (Skor: 2)
                                    </button>
                                    <button 
                                        @click="evaluateAnswer(answer.id, 'setengah_benar')"
                                        :disabled="isEvaluationFinished || evaluatingIds[answer.id]"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-[12px] font-bold transition-all border',
                                            isEvaluationFinished || evaluatingIds[answer.id] ? 'opacity-50 cursor-not-allowed' : '',
                                            answer.evaluation === 'setengah_benar' ? 'bg-amber-500 text-white border-amber-600 shadow-sm' : 'bg-white text-amber-600 border-amber-200 hover:bg-amber-50'
                                        ]"
                                    >
                                        <i class="pi mr-1 text-[10px]" :class="evaluatingIds[answer.id] ? 'pi-spinner pi-spin' : 'pi-minus'"></i> Setengah Benar (Skor: 1)
                                    </button>
                                    <button 
                                        @click="evaluateAnswer(answer.id, 'salah')"
                                        :disabled="isEvaluationFinished || evaluatingIds[answer.id]"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-[12px] font-bold transition-all border',
                                            isEvaluationFinished || evaluatingIds[answer.id] ? 'opacity-50 cursor-not-allowed' : '',
                                            answer.evaluation === 'salah' ? 'bg-rose-500 text-white border-rose-600 shadow-sm' : 'bg-white text-rose-600 border-rose-200 hover:bg-rose-50'
                                        ]"
                                    >
                                        <i class="pi mr-1 text-[10px]" :class="evaluatingIds[answer.id] ? 'pi-spinner pi-spin' : 'pi-times'"></i> Salah (Skor: 0)
                                    </button>
                                    <button 
                                        @click="evaluateAnswer(answer.id, 'tidak_dinilai')"
                                        :disabled="isEvaluationFinished || evaluatingIds[answer.id]"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-[12px] font-bold transition-all border',
                                            isEvaluationFinished || evaluatingIds[answer.id] ? 'opacity-50 cursor-not-allowed' : '',
                                            answer.evaluation === 'tidak_dinilai' ? 'bg-slate-500 text-white border-slate-600 shadow-sm' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50'
                                        ]"
                                    >
                                        <i class="pi mr-1 text-[10px]" :class="evaluatingIds[answer.id] ? 'pi-spinner pi-spin' : 'pi-ban'"></i> Tidak Dinilai
                                    </button>

                                    <!-- Tampilkan skor saat ini -->
                                    <span class="ml-auto text-[12px] font-extrabold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                        <template v-if="answer.evaluation === 'tidak_dinilai'">Tidak Dinilai</template>
                                        <template v-else>Skor Akhir: {{ getScoreText(answer.evaluation) }} / 2</template>
                                    </span>
                                </div>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>

<script lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

export default {
    layout: AppLayout,
}
</script>
