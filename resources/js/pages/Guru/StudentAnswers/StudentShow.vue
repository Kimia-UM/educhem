<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

const props = defineProps<{
    classroom: any;
    student: any;
    topics: any[];
    answers: any[];
    isEvaluationSent: boolean;
    isEvaluationFinished: boolean;
    phaseSubmissionStatuses: Record<
        number,
        { isSubmitted: boolean; hasAnswers: boolean }
    >;
}>();

const localAnswers = ref([...props.answers]);

watch(
    () => props.answers,
    (newAnswers) => {
        localAnswers.value = [...newAnswers];
    },
    { deep: true },
);

const activeTopicId = ref<number | null>(
    props.topics.length > 0 ? props.topics[0].id : null,
);

const activeTopic = computed(() => {
    return props.topics.find((t) => t.id === activeTopicId.value) || null;
});

// Helper untuk mendapatkan jawaban per fase
const getPhaseAnswers = (phaseId: number) => {
    return localAnswers.value.filter((a) => a.phase_id === phaseId);
};

const getPhaseSubmissionStatus = (phaseId: number) => {
    return (
        props.phaseSubmissionStatuses[phaseId] || {
            isSubmitted: false,
            hasAnswers: false,
        }
    );
};

const phaseToReopen = ref<any | null>(null);
const reopeningPhaseId = ref<number | null>(null);

const confirmReopenPhase = (phase: any) => {
    phaseToReopen.value = phase;
};

const cancelReopenPhase = () => {
    if (reopeningPhaseId.value === null) {
        phaseToReopen.value = null;
    }
};

const reopenPhaseSubmission = () => {
    const phase = phaseToReopen.value;

    if (!phase) {
        return;
    }

    reopeningPhaseId.value = phase.id;
    router.post(
        route('guru.classes.students.phases.reopen-submission', {
            classroom: props.classroom.id,
            student: props.student.id,
            phase: phase.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                phaseToReopen.value = null;
                toast.success('Submit berhasil dibatalkan', {
                    description: `${props.student.name} sekarang dapat mengedit fase ${phase.name}.`,
                });
            },
            onError: () => {
                toast.error('Gagal membuka jawaban', {
                    description:
                        'Jawaban belum dapat dibuka. Silakan coba kembali.',
                });
            },
            onFinish: () => {
                reopeningPhaseId.value = null;
            },
        },
    );
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
            try {
                studentAnsArray = JSON.parse(studentAns);
            } catch (e) {
                return false;
            }
        }

        if (!Array.isArray(studentAnsArray)) {
            return false;
        }

        const isSameLength = studentAnsArray.length === correctTexts.length;
        const hasAllCorrect = correctTexts.every((c: any) =>
            studentAnsArray.map(String).includes(String(c)),
        );

        return isSameLength && hasAllCorrect;
    }

    return null;
};

// Helper untuk parsing jawaban CMCQ (bisa berupa array atau JSON string)
const parseCmcqAnswer = (answerData: any): string[] => {
    if (Array.isArray(answerData)) {
        return answerData;
    }

    if (typeof answerData === 'string') {
        try {
            const parsed = JSON.parse(answerData);

            return Array.isArray(parsed) ? parsed : [answerData];
        } catch (e) {
            return [answerData];
        }
    }

    return [];
};

const evaluatingIds = ref<Record<number, boolean>>({});

// Menilai soal uraian / kecualikan PG
const evaluateAnswer = (
    answerId: number,
    evaluation: 'benar' | 'setengah_benar' | 'salah' | 'tidak_dinilai' | null,
) => {
    evaluatingIds.value[answerId] = true;

    // Optimistic Update
    const ans = localAnswers.value.find((a: any) => a.id === answerId);
    const prevEval = ans ? ans.evaluation : null;

    if (ans) {
        ans.evaluation = evaluation;
    }

    router.post(
        route('guru.answers.evaluate', answerId),
        {
            evaluation,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('Berhasil', {
                    id: 'evaluate-success',
                    description: 'Penilaian berhasil disimpan.',
                });
            },
            onError: () => {
                // Revert if error
                if (ans) {
                    ans.evaluation = prevEval;
                }

                toast.error('Gagal', {
                    description: 'Terjadi kesalahan saat menyimpan penilaian.',
                });
            },
            onFinish: () => {
                evaluatingIds.value[answerId] = false;
            },
        },
    );
};

const finishEvaluation = () => {
    router.post(
        route('guru.classes.students.finish-evaluation', {
            classroom: props.classroom.id,
            student: props.student.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Berhasil', {
                    description: 'Evaluasi telah ditandai selesai.',
                });
            },
            onError: () => {
                toast.error('Gagal', {
                    description:
                        'Terjadi kesalahan saat menyelesaikan evaluasi.',
                });
            },
        },
    );
};

const editEvaluation = () => {
    router.post(
        route('guru.classes.students.edit-evaluation', {
            classroom: props.classroom.id,
            student: props.student.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Berhasil', {
                    description:
                        'Kunci evaluasi dibuka. Anda dapat mengedit kembali.',
                });
            },
            onError: () => {
                toast.error('Gagal', {
                    description:
                        'Terjadi kesalahan saat membuka kunci evaluasi.',
                });
            },
        },
    );
};

const sendEvaluation = () => {
    router.post(
        route('guru.classes.students.send-evaluation', {
            classroom: props.classroom.id,
            student: props.student.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Berhasil Dikirim', {
                    description: 'Hasil evaluasi berhasil dikirimkan ke siswa.',
                });
            },
            onError: () => {
                toast.error('Gagal', {
                    description: 'Terjadi kesalahan saat mengirimkan evaluasi.',
                });
            },
        },
    );
};

// Jawaban yang difilter berdasarkan topik aktif
const activeTopicAnswers = computed(() => {
    if (!activeTopic.value) {
        return [];
    }

    const phaseIds = activeTopic.value.phases.map((p: any) => p.id);

    return localAnswers.value.filter((a) => phaseIds.includes(a.phase_id));
});

// Hitung progress (per topik aktif)
const totalEvaluated = computed(() => {
    return activeTopicAnswers.value.filter((a) => {
        if (['eval_mcq', 'eval_cmcq'].includes(a.content.type)) {
            return true;
        }

        return a.evaluation !== null;
    }).length;
});
const totalQuestions = computed(() => activeTopicAnswers.value.length);
const progressPercent = computed(() =>
    totalQuestions.value > 0
        ? Math.round((totalEvaluated.value / totalQuestions.value) * 100)
        : 0,
);

// Jawaban yang ikut dihitung skor (exclude tidak_dinilai)
const scoredAnswers = computed(() => {
    return activeTopicAnswers.value.filter(
        (a) => a.evaluation !== 'tidak_dinilai',
    );
});

const totalScore = computed(() => {
    let score = 0;
    scoredAnswers.value.forEach((a) => {
        if (['eval_mcq', 'eval_cmcq'].includes(a.content.type)) {
            if (checkAutoGrade(a)) {
                score += 1;
            }
        } else {
            if (a.evaluation === 'benar') {
                score += 2;
            } else if (a.evaluation === 'setengah_benar') {
                score += 1;
            }
        }
    });

    return score;
});

const maxScore = computed(() => {
    let mScore = 0;
    scoredAnswers.value.forEach((a) => {
        if (['eval_mcq', 'eval_cmcq'].includes(a.content.type)) {
            mScore += 1;
        } else {
            mScore += 2;
        }
    });

    return mScore;
});
const scorePercent = computed(() =>
    maxScore.value > 0
        ? Math.round((totalScore.value / maxScore.value) * 100)
        : 0,
);

const getScoreText = (evaluation: string | null) => {
    switch (evaluation) {
        case 'benar':
            return '2';
        case 'setengah_benar':
            return '1';
        case 'salah':
            return '0';
        case 'tidak_dinilai':
            return '—';
        default:
            return '-';
    }
};

const isImage = (url: string | null) => {
    if (!url) {
        return false;
    }

    return /\.(jpeg|jpg|gif|png|webp)/i.test(url);
};
</script>

<template>
    <Head :title="`Evaluasi Siswa: ${student.name}`" />

    <div class="min-h-screen bg-[#F8FAFC] px-4 py-6 font-sans md:px-8 lg:px-10">
        <div class="mx-auto max-w-5xl">
            <!-- Header -->
            <div
                class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
            >
                <div>
                    <div
                        class="mb-3 flex items-center gap-2 text-[12px] font-bold text-slate-500"
                    >
                        <Link
                            :href="
                                route('guru.classes.show', {
                                    class: classroom.id,
                                    tab: 'siswa',
                                })
                            "
                            class="transition-colors hover:text-indigo-600"
                            >Kelas {{ classroom.class_name }}</Link
                        >
                        <i class="pi pi-chevron-right text-[8px]"></i>
                        <span class="text-indigo-600">Evaluasi Siswa</span>
                    </div>
                    <h1
                        class="flex items-center gap-3 text-[24px] font-extrabold text-slate-900"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[14px] font-bold text-indigo-700 uppercase"
                        >
                            {{ student.name.substring(0, 2) }}
                        </div>
                        {{ student.name }}
                    </h1>
                    <p class="mt-1 text-[13px] text-slate-500">
                        {{ student.email }}
                    </p>
                </div>

                <div
                    class="flex flex-col items-start gap-3 md:items-end md:gap-1.5"
                >
                    <div
                        class="flex w-full flex-wrap items-center justify-start gap-3 md:justify-end"
                    >
                        <!-- Status Badge -->
                        <div class="mr-1 flex items-center gap-2">
                            <span
                                class="text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                                >Status:</span
                            >
                            <span
                                v-if="!isEvaluationFinished"
                                class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1 text-[12px] font-bold text-amber-700"
                            >
                                <span
                                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"
                                ></span>
                                Sedang Dinilai
                            </span>
                            <span
                                v-else-if="
                                    isEvaluationFinished && !isEvaluationSent
                                "
                                class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-[12px] font-bold text-blue-700"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-blue-500"
                                ></span>
                                Belum Dikirim
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[12px] font-bold text-emerald-700"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                ></span>
                                Telah Dikirim
                            </span>
                        </div>

                        <!-- Tombol Selesai / Edit Evaluasi -->
                        <Button
                            v-if="!isEvaluationFinished"
                            @click="finishEvaluation"
                            :disabled="progressPercent < 100"
                            class="rounded-xl bg-indigo-600 px-4 py-2.5 font-bold text-white transition-all hover:bg-indigo-700 disabled:border disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400"
                        >
                            <i class="pi pi-lock mr-2 text-[12px]"></i> Selesai
                            Evaluasi
                        </Button>

                        <Button
                            v-else
                            @click="editEvaluation"
                            class="rounded-xl border border-rose-200 bg-white px-4 py-2.5 font-bold text-rose-600 transition-all hover:bg-rose-50"
                        >
                            <i class="pi pi-unlock mr-2 text-[12px]"></i> Edit
                            Evaluasi
                        </Button>

                        <!-- Tombol Kirim / Kirim Ulang Hasil -->
                        <Button
                            @click="sendEvaluation"
                            :disabled="!isEvaluationFinished"
                            :class="[
                                'rounded-xl px-4 py-2.5 font-bold shadow-sm transition-all',
                                !isEvaluationFinished
                                    ? 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400 opacity-70'
                                    : isEvaluationSent
                                      ? 'bg-emerald-600 text-white hover:bg-emerald-700 hover:shadow-md'
                                      : 'bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-md',
                            ]"
                        >
                            <i
                                :class="[
                                    'pi mr-2',
                                    isEvaluationSent ? 'pi-sync' : 'pi-send',
                                ]"
                            ></i>
                            {{
                                isEvaluationSent
                                    ? 'Perbarui & Kirim Ulang'
                                    : 'Kirim Hasil ke Siswa'
                            }}
                        </Button>

                        <!-- Tombol Cetak Evaluasi (PDF) -->
                        <a
                            :href="
                                route('guru.classes.students.print', {
                                    classroom: classroom.id,
                                    student: student.id,
                                })
                            "
                            target="_blank"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50"
                        >
                            <i
                                class="pi pi-print mr-2 text-[12px] text-slate-500"
                            ></i>
                            Cetak Evaluasi (PDF)
                        </a>
                    </div>

                    <div
                        class="max-w-[350px] text-left text-[11px] font-medium text-slate-400 md:text-right"
                    >
                        <p
                            v-if="
                                progressPercent < 100 && !isEvaluationFinished
                            "
                        >
                            Selesaikan evaluasi seluruh soal ({{
                                progressPercent
                            }}%) terlebih dahulu.
                        </p>
                        <p v-else-if="!isEvaluationFinished">
                            Semua soal telah dinilai. Klik "Selesai Evaluasi"
                            untuk mengunci & mengirim hasil.
                        </p>
                        <p
                            v-else-if="
                                isEvaluationFinished && !isEvaluationSent
                            "
                        >
                            Hasil evaluasi telah dikunci. Anda sekarang dapat
                            mengirimkannya ke siswa.
                        </p>
                        <p v-else>
                            Hasil telah terkirim. Klik "Edit" untuk mengubah
                            kembali, atau "Kirim Ulang" untuk memperbarui.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Progress Box -->
            <Card class="mb-8 border-slate-200 bg-white p-5">
                <div class="mb-2 flex items-end justify-between">
                    <div>
                        <h3 class="text-[14px] font-bold text-slate-800">
                            Nilai Siswa
                        </h3>
                        <p class="text-[12px] text-slate-500">
                            <span
                                v-if="activeTopic"
                                class="font-semibold text-indigo-600"
                                >{{ activeTopic.title }}</span
                            >
                            — Skor maksimal: {{ maxScore }} ({{
                                totalEvaluated
                            }}/{{ totalQuestions }} dievaluasi)
                        </p>
                    </div>
                    <span
                        class="text-[20px] font-extrabold"
                        :class="
                            scorePercent >= 75
                                ? 'text-emerald-600'
                                : scorePercent >= 50
                                  ? 'text-amber-500'
                                  : 'text-rose-600'
                        "
                        >{{ totalScore }}
                        <span class="text-[14px] font-bold text-slate-400"
                            >Poin</span
                        ></span
                    >
                </div>
                <div
                    class="h-2 w-full overflow-hidden rounded-full bg-slate-100"
                >
                    <div
                        class="h-full transition-all duration-500"
                        :class="
                            scorePercent >= 75
                                ? 'bg-emerald-500'
                                : scorePercent >= 50
                                  ? 'bg-amber-500'
                                  : 'bg-rose-500'
                        "
                        :style="{ width: scorePercent + '%' }"
                    ></div>
                </div>
            </Card>

            <!-- Pemilihan Topik -->
            <div v-if="topics.length > 0" class="mb-8 flex flex-wrap gap-3">
                <button
                    v-for="topic in topics"
                    :key="topic.id"
                    @click="activeTopicId = topic.id"
                    :class="[
                        'rounded-full px-5 py-2.5 text-[14px] font-bold shadow-sm transition-all',
                        activeTopicId === topic.id
                            ? 'border-transparent bg-indigo-600 text-white ring-2 ring-indigo-600 ring-offset-2'
                            : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
                    ]"
                >
                    {{ topic.title }}
                </button>
            </div>

            <!-- Konten Evaluasi (Berdasarkan Topik yang Dipilih) -->
            <div v-if="activeTopic" class="flex flex-col gap-10">
                <div
                    v-for="phase in activeTopic.phases"
                    :key="phase.id"
                    class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-8"
                >
                    <div
                        class="absolute top-0 left-0 h-1 w-full bg-indigo-500"
                    ></div>

                    <!-- Judul dan status submit fase -->
                    <div
                        class="mb-8 flex flex-col gap-4 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h2
                                class="text-[20px] font-extrabold text-slate-900"
                            >
                                {{ phase.name }}
                            </h2>
                            <span
                                v-if="
                                    getPhaseSubmissionStatus(phase.id)
                                        .isSubmitted
                                "
                                class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700"
                            >
                                <i class="pi pi-lock text-[10px]"></i>
                                Sudah dikumpulkan
                            </span>
                            <span
                                v-else-if="
                                    getPhaseSubmissionStatus(phase.id)
                                        .hasAnswers
                                "
                                class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700"
                            >
                                <i class="pi pi-pencil text-[10px]"></i>
                                Dapat diedit siswa
                            </span>
                            <span
                                v-else
                                class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-500"
                            >
                                <i class="pi pi-clock text-[10px]"></i>
                                Belum dikumpulkan
                            </span>
                        </div>
                        <Button
                            v-if="
                                getPhaseSubmissionStatus(phase.id).isSubmitted
                            "
                            type="button"
                            variant="outline"
                            class="border-amber-200 bg-amber-50 font-bold text-amber-700 hover:bg-amber-100 hover:text-amber-800"
                            :disabled="reopeningPhaseId === phase.id"
                            @click="confirmReopenPhase(phase)"
                        >
                            <i
                                class="pi mr-2 text-[12px]"
                                :class="
                                    reopeningPhaseId === phase.id
                                        ? 'pi-spinner pi-spin'
                                        : 'pi-unlock'
                                "
                            ></i>
                            Izinkan Siswa Mengedit
                        </Button>
                    </div>

                    <div
                        v-if="getPhaseAnswers(phase.id).length === 0"
                        class="rounded-xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center"
                    >
                        <i
                            class="pi pi-file-excel mb-3 block text-3xl text-slate-300"
                        ></i>
                        <p class="text-[14px] font-medium text-slate-500">
                            Siswa belum menjawab soal di fase ini.
                        </p>
                    </div>

                    <div v-else class="flex flex-col gap-5">
                        <Card
                            v-for="(answer, index) in getPhaseAnswers(phase.id)"
                            :key="answer.id"
                            class="border-slate-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md md:p-5"
                        >
                            <!-- Pertanyaan -->
                            <div class="mb-4">
                                <span
                                    class="mb-2 block text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                                    >Pertanyaan {{ index + 1 }}</span
                                >
                                <div
                                    class="text-[14px] font-medium text-slate-800"
                                    v-html="
                                        answer.content.content_data?.question ||
                                        answer.content.content_data?.label ||
                                        answer.content.content_data?.content
                                    "
                                ></div>
                            </div>

                            <!-- Jawaban PG/PG Kompleks -->
                            <div
                                v-if="
                                    ['eval_mcq', 'eval_cmcq'].includes(
                                        answer.content.type,
                                    )
                                "
                                class="mb-4 rounded-lg border border-slate-100 bg-slate-50 p-4"
                            >
                                <div
                                    class="mb-2 text-[12px] font-bold text-slate-500"
                                >
                                    Jawaban Siswa:
                                </div>
                                <ul
                                    class="list-disc pl-5 text-[13px] text-slate-700"
                                >
                                    <template
                                        v-if="
                                            answer.content.type === 'eval_mcq'
                                        "
                                    >
                                        <li>{{ answer.answer_data }}</li>
                                    </template>
                                    <template v-else>
                                        <li
                                            v-for="ans in parseCmcqAnswer(
                                                answer.answer_data,
                                            )"
                                            :key="ans"
                                        >
                                            {{ ans }}
                                        </li>
                                    </template>
                                </ul>

                                <div
                                    class="mt-4 flex flex-col justify-between gap-3 border-t border-slate-200 pt-3 md:flex-row md:items-center"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[12px] font-bold text-slate-600"
                                            >Auto-grading:</span
                                        >
                                        <span
                                            v-if="checkAutoGrade(answer)"
                                            class="inline-flex items-center gap-1.5 rounded-md border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[12px] font-bold text-emerald-600"
                                        >
                                            <i class="pi pi-check-circle"></i>
                                            Benar
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 rounded-md border border-rose-100 bg-rose-50 px-2.5 py-1 text-[12px] font-bold text-rose-600"
                                        >
                                            <i class="pi pi-times-circle"></i>
                                            Salah
                                        </span>
                                        <span
                                            class="ml-2 rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1 text-[12px] font-extrabold text-slate-700"
                                        >
                                            <template
                                                v-if="
                                                    answer.evaluation ===
                                                    'tidak_dinilai'
                                                "
                                                >Tidak Dinilai</template
                                            >
                                            <template v-else
                                                >Skor Akhir:
                                                {{
                                                    checkAutoGrade(answer)
                                                        ? 1
                                                        : 0
                                                }}
                                                / 1</template
                                            >
                                        </span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            v-if="
                                                answer.evaluation ===
                                                'tidak_dinilai'
                                            "
                                            @click="
                                                evaluateAnswer(answer.id, null)
                                            "
                                            :disabled="
                                                isEvaluationFinished ||
                                                evaluatingIds[answer.id]
                                            "
                                            class="rounded-lg border border-indigo-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-indigo-600 shadow-sm transition-all hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <i
                                                class="pi pi-check-square mr-1"
                                                :class="
                                                    evaluatingIds[answer.id]
                                                        ? 'pi-spinner pi-spin'
                                                        : ''
                                                "
                                            ></i>
                                            Masukkan ke Penilaian
                                        </button>
                                        <button
                                            v-else
                                            @click="
                                                evaluateAnswer(
                                                    answer.id,
                                                    'tidak_dinilai',
                                                )
                                            "
                                            :disabled="
                                                isEvaluationFinished ||
                                                evaluatingIds[answer.id]
                                            "
                                            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-500 shadow-sm transition-all hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <i
                                                class="pi pi-ban mr-1"
                                                :class="
                                                    evaluatingIds[answer.id]
                                                        ? 'pi-spinner pi-spin'
                                                        : ''
                                                "
                                            ></i>
                                            Kecualikan dari Penilaian
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="answer.evaluation === 'tidak_dinilai'"
                                    class="mt-2 inline-block rounded-md border border-rose-100 bg-rose-50 p-2 text-[11px] font-bold text-rose-500 italic"
                                >
                                    * Soal pilihan ganda ini dikecualikan dari
                                    perhitungan skor akhir.
                                </div>
                            </div>

                            <!-- Jawaban Upload File -->
                            <div
                                v-else-if="answer.content.type === 'eval_file'"
                                class="mb-4 rounded-lg border border-slate-100 bg-slate-50 p-4"
                            >
                                <div
                                    class="mb-2 text-[12px] font-bold text-slate-500"
                                >
                                    Jawaban Siswa (File/Gambar):
                                </div>
                                <div
                                    v-if="isImage(answer.answer_data)"
                                    class="mt-2"
                                >
                                    <img
                                        :src="answer.answer_data"
                                        alt="Uploaded Image"
                                        class="mb-2 max-h-64 rounded-lg border border-slate-200 shadow-sm"
                                    />
                                    <a
                                        :href="answer.answer_data"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800"
                                    >
                                        <i class="pi pi-external-link"></i>
                                        Lihat Gambar Ukuran Penuh
                                    </a>
                                </div>
                                <div
                                    v-else-if="answer.answer_data"
                                    class="mt-2 flex items-center gap-2"
                                >
                                    <i
                                        class="pi pi-file text-lg text-slate-400"
                                    ></i>
                                    <a
                                        :href="answer.answer_data"
                                        target="_blank"
                                        class="font-bold text-indigo-600 hover:underline"
                                    >
                                        Lihat File Terunggah
                                    </a>
                                </div>
                                <div
                                    v-else
                                    class="text-[14px] text-slate-400 italic"
                                >
                                    Siswa belum mengunggah file.
                                </div>
                            </div>

                            <!-- Jawaban Uraian -->
                            <div
                                v-else
                                class="mb-4 rounded-lg border border-slate-100 bg-slate-50 p-4"
                            >
                                <div
                                    class="mb-2 text-[12px] font-bold text-slate-500"
                                >
                                    Jawaban Siswa:
                                </div>
                                <div
                                    v-if="
                                        answer.content.type === 'eval_essay' ||
                                        answer.content.type === 'input_text'
                                    "
                                    class="rich-text-content text-[14px] break-words text-slate-800"
                                    v-html="answer.answer_data || ''"
                                ></div>
                                <div
                                    v-else
                                    class="text-[14px] break-words whitespace-pre-wrap text-slate-800"
                                >
                                    {{ answer.answer_data }}
                                </div>
                            </div>

                            <!-- Form Evaluasi Manual (Hanya untuk uraian) -->
                            <div
                                v-if="
                                    !['eval_mcq', 'eval_cmcq'].includes(
                                        answer.content.type,
                                    )
                                "
                                class="flex w-full flex-wrap items-center gap-2.5 pt-2"
                            >
                                <span
                                    class="mr-2 text-[12px] font-bold text-slate-500"
                                    >Evaluasi:</span
                                >
                                <button
                                    @click="evaluateAnswer(answer.id, 'benar')"
                                    :disabled="
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                    "
                                    :class="[
                                        'rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-all',
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                            ? 'cursor-not-allowed opacity-50'
                                            : '',
                                        answer.evaluation === 'benar'
                                            ? 'border-emerald-600 bg-emerald-500 text-white shadow-sm'
                                            : 'border-emerald-200 bg-white text-emerald-600 hover:bg-emerald-50',
                                    ]"
                                >
                                    <i
                                        class="pi mr-1 text-[10px]"
                                        :class="
                                            evaluatingIds[answer.id]
                                                ? 'pi-spinner pi-spin'
                                                : 'pi-check'
                                        "
                                    ></i>
                                    Benar (Skor: 2)
                                </button>
                                <button
                                    @click="
                                        evaluateAnswer(
                                            answer.id,
                                            'setengah_benar',
                                        )
                                    "
                                    :disabled="
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                    "
                                    :class="[
                                        'rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-all',
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                            ? 'cursor-not-allowed opacity-50'
                                            : '',
                                        answer.evaluation === 'setengah_benar'
                                            ? 'border-amber-600 bg-amber-500 text-white shadow-sm'
                                            : 'border-amber-200 bg-white text-amber-600 hover:bg-amber-50',
                                    ]"
                                >
                                    <i
                                        class="pi mr-1 text-[10px]"
                                        :class="
                                            evaluatingIds[answer.id]
                                                ? 'pi-spinner pi-spin'
                                                : 'pi-minus'
                                        "
                                    ></i>
                                    Setengah Benar (Skor: 1)
                                </button>
                                <button
                                    @click="evaluateAnswer(answer.id, 'salah')"
                                    :disabled="
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                    "
                                    :class="[
                                        'rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-all',
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                            ? 'cursor-not-allowed opacity-50'
                                            : '',
                                        answer.evaluation === 'salah'
                                            ? 'border-rose-600 bg-rose-500 text-white shadow-sm'
                                            : 'border-rose-200 bg-white text-rose-600 hover:bg-rose-50',
                                    ]"
                                >
                                    <i
                                        class="pi mr-1 text-[10px]"
                                        :class="
                                            evaluatingIds[answer.id]
                                                ? 'pi-spinner pi-spin'
                                                : 'pi-times'
                                        "
                                    ></i>
                                    Salah (Skor: 0)
                                </button>
                                <button
                                    @click="
                                        evaluateAnswer(
                                            answer.id,
                                            'tidak_dinilai',
                                        )
                                    "
                                    :disabled="
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                    "
                                    :class="[
                                        'rounded-lg border px-3 py-1.5 text-[12px] font-bold transition-all',
                                        isEvaluationFinished ||
                                        evaluatingIds[answer.id]
                                            ? 'cursor-not-allowed opacity-50'
                                            : '',
                                        answer.evaluation === 'tidak_dinilai'
                                            ? 'border-slate-600 bg-slate-500 text-white shadow-sm'
                                            : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50',
                                    ]"
                                >
                                    <i
                                        class="pi mr-1 text-[10px]"
                                        :class="
                                            evaluatingIds[answer.id]
                                                ? 'pi-spinner pi-spin'
                                                : 'pi-ban'
                                        "
                                    ></i>
                                    Tidak Dinilai
                                </button>

                                <!-- Tampilkan skor saat ini -->
                                <span
                                    class="ml-auto rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1 text-[12px] font-extrabold text-slate-700"
                                >
                                    <template
                                        v-if="
                                            answer.evaluation ===
                                            'tidak_dinilai'
                                        "
                                        >Tidak Dinilai</template
                                    >
                                    <template v-else
                                        >Skor Akhir:
                                        {{ getScoreText(answer.evaluation) }} /
                                        2</template
                                    >
                                </span>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="phaseToReopen"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/45 px-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-labelledby="reopen-phase-title"
                @click.self="cancelReopenPhase"
            >
                <div
                    class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl"
                >
                    <div
                        class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                    >
                        <i class="pi pi-unlock text-xl"></i>
                    </div>
                    <h3
                        id="reopen-phase-title"
                        class="text-lg font-extrabold text-slate-900"
                    >
                        Izinkan siswa mengedit kembali?
                    </h3>
                    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
                        Submit fase
                        <strong>{{ phaseToReopen.name }}</strong> milik
                        <strong>{{ student.name }}</strong> akan dibatalkan.
                        Jawaban lama tidak dihapus dan siswa harus menekan
                        <strong>Selesai</strong> lagi setelah mengedit.
                    </p>
                    <div
                        v-if="isEvaluationFinished || isEvaluationSent"
                        class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[12px] leading-relaxed text-amber-800"
                    >
                        <i class="pi pi-exclamation-triangle mr-1"></i>
                        Status evaluasi selesai dan hasil yang telah dikirim
                        akan dibatalkan agar nilai lama tidak ditampilkan
                        sebagai hasil final.
                    </div>
                    <div
                        class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="reopeningPhaseId !== null"
                            @click="cancelReopenPhase"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            class="bg-amber-600 font-bold text-white hover:bg-amber-700"
                            :disabled="reopeningPhaseId !== null"
                            @click="reopenPhaseSubmission"
                        >
                            <i
                                class="pi mr-2"
                                :class="
                                    reopeningPhaseId !== null
                                        ? 'pi-spinner pi-spin'
                                        : 'pi-unlock'
                                "
                            ></i>
                            Ya, Izinkan Edit
                        </Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>
