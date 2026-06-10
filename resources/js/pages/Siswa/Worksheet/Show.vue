<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';
import { ref, onMounted } from 'vue';
import { marked } from 'marked';
import FloatingChatbot from '@/components/FloatingChatbot.vue';


const props = defineProps<{
    classroom: { id: number; class_name: string; };
    topic: { id: number; title: string; };
    phase: {
        id: number;
        name: string;
        is_ai_enabled: boolean;
        contents: Array<{
            id: number;
            type: string;
            content_data: any;
            order: number;
        }>;
    };
    studentAnswers: Record<number, string>;
    aiFeedbacks: Record<number, string>;
}>();

const answers = ref<Record<number, any>>({});
const isSubmitting = ref<Record<number, boolean>>({});

// STATE UNTUK LOADING AI REAL-TIME
const isWaitingForAI = ref<Record<number, boolean>>({});
const pollIntervals: Record<number, any> = {};
const pollAttempts: Record<number, number> = {};

// FUNGSI AUTO-POLLING (Cek server setiap 3 detik di latar belakang)
const startPollingAI = (contentId: number) => {
    isWaitingForAI.value[contentId] = true;
    pollAttempts[contentId] = 0;

    // Bersihkan interval lama jika ada (mencegah bug double-polling)
    if (pollIntervals[contentId]) clearInterval(pollIntervals[contentId]);

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
                    toast.success('Evaluasi AI Selesai!', { icon: '✨' });
                } 
                // Jika sudah 15 kali percobaan (45 detik) tapi AI belum jawab (Timeout/Error API)
                else if (pollAttempts[contentId] >= 15) {
                    clearInterval(pollIntervals[contentId]);
                    isWaitingForAI.value[contentId] = false;
                    toast.error('Waktu tunggu AI habis. Silakan klik "Cek Hasil AI" nanti.');
                }
            }
        });
    }, 3000); // interval 3000 ms = 3 detik
};

// Fungsi Manual (Fallback) jika polling gagal/timeout
const isRefreshingAI = ref<Record<number, boolean>>({});
const cekEvaluasiAI = (contentId: number) => {
    isRefreshingAI.value[contentId] = true;
    router.reload({
        only: ['aiFeedbacks'],
        preserveScroll: true,
        onSuccess: () => {
            // Berikan feedback ke user apakah data sudah selesai diproses atau belum
            if (props.aiFeedbacks && props.aiFeedbacks[contentId]) {
                toast.success('Hasil AI berhasil ditarik!');
                isWaitingForAI.value[contentId] = false;
            } else {
                toast.info('Sistem masih memproses. Coba lagi dalam beberapa detik ya.', { icon: '⏳' });
            }
        },
        onFinish: () => { isRefreshingAI.value[contentId] = false; }
    });
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

    props.phase.contents.forEach(content => {
        if (content.type === 'eval_cmcq' && !answers.value[content.id]) {
            answers.value[content.id] = [];
        }
    });
});

const saveAnswer = (contentId: number) => {
    let answerData = answers.value[contentId];
    
    if (Array.isArray(answerData) && answerData.length === 0) return;
    if (!Array.isArray(answerData) && (!answerData || answerData.trim() === '')) {
        toast.warning('Isi jawaban terlebih dahulu!');
        return;
    }

    const answerText = Array.isArray(answerData) ? JSON.stringify(answerData) : answerData;
    isSubmitting.value[contentId] = true;

    router.post(route('siswa.answers.store', props.phase.id), {
        content_id: contentId,
        answer_text: answerText
    }, {
        preserveScroll: true,
        preserveState: true, 
        onSuccess: () => {
            toast.success('Jawaban terkirim! Menunggu AI...', { icon: '🚀' });
            
            // Hapus feedback lama dari layar
            if (props.aiFeedbacks) props.aiFeedbacks[contentId] = '';
            
            // Mulai Auto-Polling jika fase ini mengaktifkan fitur AI
            if (props.phase.is_ai_enabled) {
                startPollingAI(contentId);
            }
        },
        onError: () => {
            toast.error('Gagal Mengirim', { description: 'Periksa koneksi internet Anda.', icon: '⚠️' });
        },
        onFinish: () => {
            isSubmitting.value[contentId] = false;
        }
    });
};

const uploadFile = (contentId: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

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
            toast.error('Gagal mengunggah file. Pastikan ukurannya di bawah 2MB.');
        },
        onFinish: () => { 
            isSubmitting.value[contentId] = false; 
            target.value = ''; 
        }
    });
};

const renderMarkdown = (text: string) => {
    if (!text) return '';
    return marked.parse(text); 
};
</script>

<template>
    <Head :title="`Materi: ${phase.name}`" />

    <div class="min-h-screen bg-[#F4F7F9] px-4 py-6 md:px-8">
        
        <!-- Header -->
        <div class="mx-auto max-w-4xl mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500 mb-1">
                    <Link :href="route('siswa.classes.show', classroom.id)" class="hover:text-indigo-600 transition-colors">
                        <span class="text-indigo-600">{{ classroom.class_name }}</span>
                    </Link>
                    <i class="pi pi-chevron-right text-[8px]"></i>
                    <span>{{ topic.title }}</span>
                </div>
                <h1 class="text-xl font-black text-slate-900">{{ phase.name }}</h1>
            </div>
            
            <div v-if="phase.is_ai_enabled" class="flex items-center gap-2 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100">
                <i class="pi pi-sparkles text-indigo-500 text-sm"></i>
                <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-widest">AI Assistant Aktif</span>
            </div>
        </div>

        <div class="mx-auto max-w-4xl space-y-6 mb-12">
            <div v-for="content in phase.contents" :key="content.id">
                
                <div v-if="content.type === 'text'" class="prose prose-slate max-w-none text-[15px] leading-relaxed text-slate-700 bg-white p-6 rounded-2xl shadow-sm border border-slate-100" v-html="content.content_data.body"></div>

                <div v-if="content.type === 'image' && content.content_data.url" class="flex justify-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                    <img :src="content.content_data.url" class="max-h-[500px] rounded-xl object-contain" alt="Materi Visual" />
                </div>

                <div v-if="content.type === 'h5p' && content.content_data.path" class="bg-white p-2 rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="w-full aspect-video rounded-xl overflow-hidden bg-slate-900">
                        <iframe :src="content.content_data.path" class="w-full h-full border-0" allowfullscreen="allowfullscreen" allow="geolocation *; microphone *; camera *; midi *; encrypted-media *" title="Interactive Video POE"></iframe>
                    </div>
                </div>

                <!-- SOAL PILIHAN GANDA (MCQ) -->
                <div v-if="content.type === 'eval_mcq'" class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 shadow-sm relative overflow-hidden">
                    <div class="absolute left-0 top-0 h-full w-1.5 bg-indigo-500"></div>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="pi pi-question-circle text-indigo-500"></i>
                        <h4 class="font-bold text-slate-800" v-html="content.content_data.question"></h4>
                    </div>
                    <div class="space-y-2 pl-6">
                        <label v-for="(option, idx) in content.content_data.options" :key="idx" class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-indigo-200 cursor-pointer transition-all shadow-sm" :class="{'border-indigo-400 ring-1 ring-indigo-400 bg-indigo-50/50': answers[content.id] === option}">
                            <input type="radio" :name="'mcq_'+content.id" :value="option" v-model="answers[content.id]" @change="saveAnswer(content.id)" class="text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <span class="text-[14px] text-slate-700" v-html="option"></span>
                        </label>
                    </div>
                    <div class="mt-3 flex justify-end min-h-[20px]">
                        <span v-if="isSubmitting[content.id]" class="text-[11px] font-bold text-indigo-500"><i class="pi pi-spinner pi-spin mr-1"></i> Menyimpan...</span>
                    </div>
                </div>

                <!-- SOAL CHECKBOX (CMCQ) -->
                <div v-if="content.type === 'eval_cmcq'" class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 shadow-sm relative overflow-hidden">
                    <div class="absolute left-0 top-0 h-full w-1.5 bg-indigo-500"></div>
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <i class="pi pi-list text-indigo-500"></i>
                        <h4 class="font-bold text-slate-800" v-html="content.content_data.question"></h4>
                        <span class="text-[9px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded uppercase font-black tracking-wider shadow-sm">Pilih lebih dari satu</span>
                    </div>
                    <div class="space-y-2 pl-6">
                        <label v-for="(option, idx) in content.content_data.options" :key="idx" class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-indigo-200 cursor-pointer transition-all shadow-sm" :class="{'border-indigo-400 ring-1 ring-indigo-400 bg-indigo-50/50': answers[content.id]?.includes(option)}">
                            <input type="checkbox" :value="option" v-model="answers[content.id]" @change="saveAnswer(content.id)" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <span class="text-[14px] text-slate-700" v-html="option"></span>
                        </label>
                    </div>
                    <div class="mt-3 flex justify-end min-h-[20px]">
                        <span v-if="isSubmitting[content.id]" class="text-[11px] font-bold text-indigo-500"><i class="pi pi-spinner pi-spin mr-1"></i> Menyimpan...</span>
                    </div>
                </div>

                <!-- SOAL JAWABAN SINGKAT -->
                <div v-if="content.type === 'eval_short'" class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 shadow-sm relative overflow-hidden">
                    <div class="absolute left-0 top-0 h-full w-1.5 bg-indigo-500"></div>
                    <label class="block text-[14px] font-extrabold text-slate-800 mb-3 flex items-center gap-2">
                        <i class="pi pi-pencil text-indigo-500"></i> <span v-html="content.content_data.question"></span>
                    </label>
                    
                    <!-- Kotak Input terkunci saat menunggu AI -->
                    <input type="text" v-model="answers[content.id]" placeholder="Ketik jawaban singkat Anda..." class="w-full bg-white border border-indigo-200 rounded-xl text-[14px] p-3.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-inner transition-all text-slate-700 disabled:opacity-70 disabled:cursor-not-allowed" :disabled="isSubmitting[content.id] || isWaitingForAI[content.id]" />
                    
                    <div class="mt-3 flex justify-end items-center gap-3 min-h-[32px]">
                        <!-- Tombol disembunyikan saat sedang proses -->
                        <Button v-if="!isWaitingForAI[content.id]" @click="saveAnswer(content.id)" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm text-xs px-5 h-9 font-bold transition-all hover:scale-105 active:scale-95" :disabled="isSubmitting[content.id]">
                            <i class="pi pi-send mr-1.5" :class="{'pi-spin pi-spinner': isSubmitting[content.id]}"></i> Kirim Jawaban
                        </Button>
                    </div>

                    <!-- AREA BUBBLE AI -->
                    <div v-if="phase.is_ai_enabled" class="mt-4">
                        
                        <!-- 1. State Loading Berjalan (Animasi) -->
                        <div v-if="isWaitingForAI[content.id]" class="flex items-center gap-4 bg-white border border-indigo-100 px-6 py-5 rounded-2xl shadow-sm animate-in fade-in slide-in-from-bottom-2 duration-500 relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-indigo-50 to-purple-50 opacity-50 animate-pulse"></div>
                            <i class="pi pi-sparkles text-indigo-500 text-2xl animate-spin" style="animation-duration: 3s;"></i>
                            <div class="flex flex-col relative z-10">
                                <span class="text-[14px] font-bold text-indigo-800">Guru AI sedang menganalisis jawabanmu...</span>
                                <span class="text-[12px] text-indigo-500">Tunggu sebentar, sedang menyusun feedback.</span>
                            </div>
                        </div>

                        <!-- 2. State Tampil Hasil AI -->
                        <div v-else-if="aiFeedbacks && aiFeedbacks[content.id]" class="relative bg-gradient-to-br from-indigo-50 to-purple-50 p-6 rounded-2xl border border-indigo-100 shadow-sm animate-in zoom-in-95 duration-300">
                            <div class="absolute -top-3 left-6 bg-white border border-indigo-200 px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                                <i class="pi pi-sparkles text-indigo-500 text-[10px]"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Feedback Guru AI</span>
                            </div>
                            <div class="prose prose-sm prose-slate max-w-none mt-3 leading-relaxed" v-html="renderMarkdown(aiFeedbacks[content.id])"></div>
                        </div>

                        <!-- 3. State Fallback (Tombol Manual jika ada jawaban lama belum dinilai) -->
                        <div v-else-if="answers[content.id] && !isSubmitting[content.id]" class="flex flex-col sm:flex-row sm:items-center justify-between bg-slate-50 border border-slate-200 px-5 py-3 rounded-xl gap-3">
                            <span class="text-[12px] text-slate-500 font-medium">Klik tombol untuk memuat ulang hasil AI jika belum muncul.</span>
                            <Button @click="cekEvaluasiAI(content.id)" variant="outline" size="sm" class="h-8 border-slate-300 text-xs bg-white text-slate-600" :disabled="isRefreshingAI[content.id]">
                                <i class="pi pi-refresh mr-1" :class="{'pi-spin': isRefreshingAI[content.id]}"></i> Cek Hasil AI
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- SOAL ESAI PANJANG -->
                <div v-if="content.type === 'eval_essay' || content.type === 'input_text'" class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 shadow-sm relative overflow-hidden">
                    <div class="absolute left-0 top-0 h-full w-1.5 bg-indigo-500"></div>
                    <label class="block text-[14px] font-extrabold text-slate-800 mb-3 flex items-center gap-2">
                        <i class="pi pi-align-left text-indigo-500"></i> <span v-html="content.content_data.question || content.content_data.label || 'Tuliskan jawaban atau prediksi Anda di bawah ini:'"></span>
                    </label>
                    
                    <!-- Textarea terkunci saat menunggu AI -->
                    <textarea v-model="answers[content.id]" placeholder="Ketik uraian jawaban Anda di sini..." class="w-full bg-white border border-indigo-200 rounded-xl text-[14px] p-4 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 min-h-[140px] resize-y shadow-inner transition-all text-slate-700 disabled:opacity-70 disabled:cursor-not-allowed" :disabled="isSubmitting[content.id] || isWaitingForAI[content.id]"></textarea>
                    
                    <div class="mt-3 flex justify-end items-center gap-3 min-h-[32px]">
                        <!-- Tombol disembunyikan saat sedang proses -->
                        <Button v-if="!isWaitingForAI[content.id]" @click="saveAnswer(content.id)" size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm text-xs px-5 h-9 font-bold transition-all hover:scale-105 active:scale-95" :disabled="isSubmitting[content.id]">
                            <i class="pi pi-send mr-1.5" :class="{'pi-spin pi-spinner': isSubmitting[content.id]}"></i> Kirim Jawaban
                        </Button>
                    </div>

                    <!-- AREA BUBBLE AI -->
                    <div v-if="phase.is_ai_enabled" class="mt-4">
                        
                        <!-- 1. State Loading Berjalan (Animasi) -->
                        <div v-if="isWaitingForAI[content.id]" class="flex items-center gap-4 bg-white border border-indigo-100 px-6 py-5 rounded-2xl shadow-sm animate-in fade-in slide-in-from-bottom-2 duration-500 relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-indigo-50 to-purple-50 opacity-50 animate-pulse"></div>
                            <i class="pi pi-sparkles text-indigo-500 text-2xl animate-spin" style="animation-duration: 3s;"></i>
                            <div class="flex flex-col relative z-10">
                                <span class="text-[14px] font-bold text-indigo-800">Guru AI sedang menganalisis jawabanmu...</span>
                                <span class="text-[12px] text-indigo-500">Tunggu sebentar, sedang menyusun feedback.</span>
                            </div>
                        </div>

                        <!-- 2. State Tampil Hasil AI -->
                        <div v-else-if="aiFeedbacks && aiFeedbacks[content.id]" class="relative bg-gradient-to-br from-indigo-50 to-purple-50 p-6 rounded-2xl border border-indigo-100 shadow-sm animate-in zoom-in-95 duration-300">
                            <div class="absolute -top-3 left-6 bg-white border border-indigo-200 px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                                <i class="pi pi-sparkles text-indigo-500 text-[10px]"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Feedback Guru AI</span>
                            </div>
                            <div class="prose prose-sm prose-slate max-w-none mt-3 leading-relaxed" v-html="renderMarkdown(aiFeedbacks[content.id])"></div>
                        </div>

                        <!-- 3. State Fallback (Tombol Manual jika ada jawaban lama belum dinilai) -->
                        <div v-else-if="answers[content.id] && !isSubmitting[content.id]" class="flex flex-col sm:flex-row sm:items-center justify-between bg-slate-50 border border-slate-200 px-5 py-3 rounded-xl gap-3">
                            <span class="text-[12px] text-slate-500 font-medium">Klik tombol untuk memuat ulang hasil AI jika belum muncul.</span>
                            <Button @click="cekEvaluasiAI(content.id)" variant="outline" size="sm" class="h-8 border-slate-300 text-xs bg-white text-slate-600" :disabled="isRefreshingAI[content.id]">
                                <i class="pi pi-refresh mr-1" :class="{'pi-spin': isRefreshingAI[content.id]}"></i> Cek Hasil AI
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- SOAL UPLOAD FILE -->
                <div v-if="content.type === 'eval_file'" class="bg-pink-50/40 p-6 rounded-2xl border border-pink-100 shadow-sm relative overflow-hidden">
                    <div class="absolute left-0 top-0 h-full w-1.5 bg-pink-500"></div>
                    <label class="block text-[14px] font-extrabold text-slate-800 mb-3 flex items-center gap-2">
                        <i class="pi pi-camera text-pink-500"></i> <span v-html="content.content_data.question"></span>
                    </label>
                    <div class="relative mt-2 flex flex-col items-center justify-center w-full h-36 border-2 border-pink-200 border-dashed rounded-xl cursor-pointer bg-white hover:bg-pink-50 transition-colors group">
                        <input type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*, application/pdf" @change="(e) => uploadFile(content.id, e)" />
                        <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                            <i class="pi pi-cloud-upload text-3xl text-pink-400 mb-2 group-hover:-translate-y-1 transition-transform"></i>
                            <p class="mb-1 text-[13px] text-slate-500"><span class="font-bold text-pink-600">Klik untuk upload</span> atau Drag & Drop foto</p>
                            <p class="text-[11px] text-slate-400">Format: PNG, JPG, atau PDF (Maks 2MB)</p>
                        </div>
                    </div>
                    <div v-if="isSubmitting[content.id]" class="mt-3 text-[12px] font-bold text-pink-500"><i class="pi pi-spinner pi-spin mr-1"></i> Sedang mengunggah...</div>
                    <div v-else-if="answers[content.id] === 'uploaded' || (answers[content.id] && answers[content.id].includes('/storage/'))" class="mt-3 inline-flex items-center text-[12px] font-bold text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200">
                        <i class="pi pi-check-circle mr-2"></i> File Anda berhasil diunggah dan diamankan.
                    </div>
                </div>

                <!-- FORUM DISKUSI -->
                <div v-if="content.type === 'discussion'" class="bg-sky-50/40 p-6 rounded-2xl border border-sky-100 shadow-sm relative overflow-hidden">
                    <div class="absolute left-0 top-0 h-full w-1.5 bg-sky-500"></div>
                    <label class="block text-[14px] font-extrabold text-slate-800 mb-3 flex items-center gap-2">
                        <i class="pi pi-comments text-sky-500"></i> <span v-html="content.content_data.topic"></span>
                    </label>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4 h-64 overflow-y-auto flex flex-col gap-4 shadow-inner">
                        <div class="text-center text-[11px] text-slate-400 font-bold my-auto uppercase tracking-wider">
                            Forum Diskusi akan segera aktif...
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" placeholder="Ketik komentar atau balasan Anda di sini..." class="flex-1 bg-white border border-slate-200 rounded-xl text-[13px] px-4 py-2.5 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors shadow-sm" />
                        <Button class="bg-sky-600 hover:bg-sky-700 text-white rounded-xl px-5 shadow-sm transition-colors"><i class="pi pi-send"></i></Button>
                    </div>
                </div>

            </div>

            <div class="flex justify-end pt-4">
                <Link :href="route('siswa.classes.show', classroom.id)">
                    <Button class="h-11 px-8 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-bold shadow-md">
                        Selesai <i class="pi pi-check-circle ml-2"></i>
                    </Button>
                </Link>
            </div>
            
        </div>
    </div>
    <FloatingChatbot/>
</template>