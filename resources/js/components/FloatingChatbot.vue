<script setup lang="ts">
// Import ikon SVG premium dari Lucide
import axios from 'axios';
import katex from 'katex';
import { Bot, BotIcon, Send, X } from 'lucide-vue-next';
import { marked } from 'marked';
import { nextTick, onUnmounted, ref } from 'vue';
import { route } from 'ziggy-js';
import 'katex/dist/katex.min.css';

// Menerima judul materi untuk konteks AI dan id fase
const props = defineProps<{
    classroomId: number;
    topicTitle?: string;
    phaseId?: number;
}>();

// State untuk mengatur jendela chat terbuka/tertutup
const isOpen = ref(false);

// State untuk input teks dan animasi loading AI
const newMessage = ref('');
const isTyping = ref(false);

// Referensi ke elemen kotak pesan untuk auto-scroll
const messagesContainer = ref<HTMLElement | null>(null);

// Format array: { id, sender, text }
const messages = ref<Array<any>>([]);
const POLL_DELAYS_MS = [2_000, 3_000, 5_000, 8_000, 15_000];
const MAX_POLL_WINDOW_MS = 3 * 60 * 1000;
let pollingTimer: ReturnType<typeof setTimeout> | null = null;
let pendingLogId: number | null = null;
let pollAttempt = 0;
let pollStartedAtMs = 0;
let consecutivePollErrors = 0;

/**
 * REFACTOR FINAL: Fungsi render dengan token '%%' yang kebal dari manipulasi Markdown Parser.
 * Menggunakan metode Split-Join untuk menjamin akurasi penggantian string secara global.
 */
const renderMarkdown = (text: string) => {
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

// Fungsi mengambil riwayat chat dari database
const fetchChats = async () => {
    try {
        const response = await axios.get(route('siswa.chatbot.index'), {
            params: { classroom_id: props.classroomId },
        });
        const logs = response.data;

        const newMessages: Array<any> = [
            {
                id: 'welcome',
                sender: 'ai',
                text: 'Halo! 👋 Aku adalah AI Tutor pendamping belajarmu. Ada yang bikin kamu bingung?',
            },
        ];

        let isWaiting = false;
        let latestPendingLogId: number | null = null;
        let latestPendingCreatedAtMs = 0;

        logs.forEach((log: any) => {
            // Masukkan pesan siswa
            newMessages.push({
                id: `user_${log.id}`,
                sender: 'user',
                text: log.prompt,
            });

            // Masukkan pesan AI (jika sudah dijawab)
            if (log.response) {
                newMessages.push({
                    id: `ai_${log.id}`,
                    sender: 'ai',
                    text: log.response,
                });
            } else if (log.status === 'failed') {
                newMessages.push({
                    id: `ai_failed_${log.id}`,
                    sender: 'ai',
                    text: 'Maaf, AI Tutor belum berhasil menjawab pesan ini. Silakan tanyakan kembali nanti.',
                });
            } else {
                // Cek apakah chat log sudah terlalu lama (misal > 180 detik)
                let diffSeconds = 0;

                if (log.created_at) {
                    const createdTime = new Date(log.created_at).getTime();
                    const nowTime = new Date().getTime();
                    diffSeconds = (nowTime - createdTime) / 1000;
                }

                if (diffSeconds > 180) {
                    newMessages.push({
                        id: `ai_delayed_${log.id}`,
                        sender: 'ai',
                        text: 'Jawaban ini masih berada dalam antrean. Pesanmu sudah tersimpan; tutup panel dan cek kembali nanti tanpa perlu mengirim ulang.',
                    });
                } else {
                    isWaiting = true; // Menandakan ada pesan yang masih dalam antrean
                    latestPendingLogId = Number(log.id);
                    latestPendingCreatedAtMs = log.created_at
                        ? new Date(log.created_at).getTime()
                        : Date.now();
                }
            }
        });

        messages.value = newMessages;
        isTyping.value = isWaiting;
        pendingLogId = latestPendingLogId;
        pollAttempt = 0;
        consecutivePollErrors = 0;
        pollStartedAtMs = latestPendingCreatedAtMs || Date.now();

        // Manajemen Polling otomatis jika masih menunggu jawaban AI
        if (isWaiting && isOpen.value && !pollingTimer) {
            startPolling();
        } else if (!isWaiting && pollingTimer) {
            stopPolling();
        }
    } catch (error) {
        console.error('Gagal mengambil riwayat chat:', error);
    }
};

const startPolling = () => {
    if (pollingTimer || pendingLogId === null || !isOpen.value) {
        return;
    }

    const delay =
        POLL_DELAYS_MS[Math.min(pollAttempt, POLL_DELAYS_MS.length - 1)];

    pollingTimer = setTimeout(() => {
        pollingTimer = null;
        void pollChatStatus();
    }, delay);
};

const stopPolling = () => {
    if (pollingTimer) {
        clearTimeout(pollingTimer);
        pollingTimer = null;
    }
};

const addAiMessage = (logId: number, text: string, kind = 'ai') => {
    const messageId = `${kind}_${logId}`;
    const existing = messages.value.find((message) => message.id === messageId);

    if (existing) {
        existing.text = text;

        return;
    }

    messages.value.push({ id: messageId, sender: 'ai', text });
};

const finishPolling = () => {
    pendingLogId = null;
    isTyping.value = false;
    stopPolling();
    void scrollToBottom();
};

const pollChatStatus = async () => {
    if (pendingLogId === null || !isOpen.value) {
        return;
    }

    const logId = pendingLogId;

    if (Date.now() - pollStartedAtMs > MAX_POLL_WINDOW_MS) {
        addAiMessage(
            logId,
            'Jawaban ini masih berada dalam antrean. Pesanmu sudah tersimpan; tutup panel dan cek kembali nanti tanpa perlu mengirim ulang.',
            'ai_delayed',
        );
        finishPolling();

        return;
    }

    try {
        const response = await axios.get(
            route('siswa.chatbot.status', { chatLog: logId }),
            { headers: { Accept: 'application/json' } },
        );
        const result = response.data;
        consecutivePollErrors = 0;

        if (result.status === 'completed' && result.response) {
            addAiMessage(logId, result.response);
            finishPolling();

            return;
        }

        if (result.status === 'failed') {
            addAiMessage(
                logId,
                'Maaf, AI Tutor belum berhasil menjawab pesan ini. Silakan tanyakan kembali nanti.',
                'ai_failed',
            );
            finishPolling();

            return;
        }
    } catch (error: any) {
        consecutivePollErrors += 1;

        if (error?.response?.status === 404 || consecutivePollErrors >= 3) {
            addAiMessage(
                logId,
                'Pemeriksaan jawaban otomatis terhenti sementara. Pesanmu tetap tersimpan dan dapat dicek kembali nanti.',
                'ai_delayed',
            );
            finishPolling();

            return;
        }
    }

    pollAttempt += 1;
    startPolling();
};

const toggleChat = async () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        await fetchChats();
        scrollToBottom();
    } else {
        stopPolling();
    }
};

const scrollToBottom = async () => {
    await nextTick();

    if (messagesContainer.value) {
        messagesContainer.value.scrollTo({
            top: messagesContainer.value.scrollHeight,
            behavior: 'smooth',
        });
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || isTyping.value) {
        return;
    }

    const userText = newMessage.value;
    newMessage.value = '';

    // Tampilkan pesan siswa langsung di layar secara instan
    messages.value.push({ id: Date.now(), sender: 'user', text: userText });
    isTyping.value = true;
    scrollToBottom();

    try {
        // Kirim post request menuju backend controller rute siswa
        const response = await axios.post(route('siswa.chatbot.store'), {
            prompt: userText,
            topic_context: props.topicTitle || 'Materi Kimia',
            phase_id: props.phaseId,
            classroom_id: props.classroomId,
        });

        if (
            response.data &&
            response.data.status === 'success' &&
            response.data.response
        ) {
            // Direct Response / Cache Hit berhasil diterima langsung!
            messages.value.push({
                id: `ai_${response.data.log_id || Date.now()}`,
                sender: 'ai',
                text: response.data.response,
            });
            isTyping.value = false;
            stopPolling();
            scrollToBottom();
        } else {
            // Slot direct sedang sibuk atau provider timeout; pantau satu log
            // secara adaptif tanpa mengambil ulang seluruh riwayat chat.
            pendingLogId = Number(response.data.log_id);
            pollAttempt = 0;
            consecutivePollErrors = 0;
            pollStartedAtMs = Date.now();
            startPolling();
        }
    } catch (error: any) {
        isTyping.value = false;
        const errMsg =
            error?.response?.data?.message ||
            'Maaf, terjadi gangguan jaringan saat menghubungi AI Tutor.';
        messages.value.push({ id: Date.now(), sender: 'ai', text: errMsg });
        scrollToBottom();
    }
};

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <div class="fixed right-6 bottom-6 z-[9999] flex flex-col items-end">
        <transition
            enter-active-class="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] origin-bottom-right"
            enter-from-class="opacity-0 translate-y-10 scale-50"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-300 ease-in origin-bottom-right"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-10 scale-50"
        >
            <div
                v-show="isOpen"
                class="mb-4 flex w-80 flex-col overflow-hidden rounded-3xl border border-indigo-50 bg-white shadow-2xl sm:w-[380px]"
            >
                <div
                    class="animate-gradient relative z-10 flex items-center justify-between overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-[length:200%_auto] p-4 shadow-md"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-white/10 opacity-0 transition-opacity duration-700 hover:opacity-100"
                    ></div>

                    <div class="relative z-10 flex items-center gap-3">
                        <div
                            class="rounded-full bg-white/20 p-2.5 shadow-inner backdrop-blur-sm transition-transform duration-300 group-hover:rotate-12"
                        >
                            <bot-icon
                                class="h-5 w-5 animate-pulse text-white"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-[15px] font-bold tracking-wide text-white"
                            >
                                AI Tutor Pendamping
                            </h3>
                            <div class="mt-0.5 flex items-center gap-1.5">
                                <span
                                    class="h-2 w-2 animate-pulse rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"
                                ></span>
                                <span
                                    class="text-[11px] font-medium tracking-wider text-indigo-50"
                                    >Online & Siap Membantu</span
                                >
                            </div>
                        </div>
                    </div>
                    <button
                        @click="toggleChat"
                        class="relative z-10 rounded-xl bg-white/10 p-2 text-indigo-100 transition-all duration-300 hover:rotate-90 hover:bg-white/20 hover:text-white"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    ref="messagesContainer"
                    class="flex max-h-[450px] min-h-[350px] flex-1 flex-col gap-5 overflow-y-auto scroll-smooth bg-[#F8FAFC] p-5"
                >
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex w-full animate-in duration-500 ease-out fade-in slide-in-from-bottom-4"
                        :class="
                            msg.sender === 'user'
                                ? 'justify-end'
                                : 'justify-start'
                        "
                    >
                        <div
                            v-if="msg.sender === 'ai'"
                            class="flex max-w-[85%] gap-2.5"
                        >
                            <div
                                class="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-gradient-to-br from-indigo-100 to-purple-100 shadow-sm"
                            >
                                <Bot class="h-4 w-4 text-indigo-600" />
                            </div>
                            <div
                                class="rounded-2xl rounded-tl-sm border border-slate-200 bg-white p-4 text-[13.5px] text-slate-700 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div
                                    v-html="renderMarkdown(msg.text)"
                                    class="prose prose-sm prose-slate max-w-none break-words"
                                ></div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="max-w-[85%] transform rounded-2xl rounded-tr-sm bg-gradient-to-br from-indigo-500 to-indigo-600 p-3.5 text-[13.5px] leading-relaxed whitespace-pre-wrap text-white shadow-md transition-shadow duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                        >
                            {{ msg.text }}
                        </div>
                    </div>

                    <div
                        v-if="isTyping"
                        class="flex max-w-[85%] animate-in gap-2.5 duration-300 fade-in slide-in-from-bottom-2"
                    >
                        <div
                            class="mt-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-gradient-to-br from-indigo-100 to-purple-100 shadow-sm"
                        >
                            <Bot class="h-4 w-4 text-indigo-600" />
                        </div>
                        <div
                            class="flex items-center gap-1.5 rounded-2xl rounded-tl-sm border border-slate-200 bg-white px-4 py-3 shadow-sm"
                        >
                            <span
                                class="h-2 w-2 animate-bounce rounded-full bg-indigo-400"
                                style="animation-delay: 0ms"
                            ></span>
                            <span
                                class="h-2 w-2 animate-bounce rounded-full bg-indigo-400"
                                style="animation-delay: 150ms"
                            ></span>
                            <span
                                class="h-2 w-2 animate-bounce rounded-full bg-indigo-400"
                                style="animation-delay: 300ms"
                            ></span>
                        </div>
                    </div>
                </div>

                <div
                    class="border-t border-slate-100 bg-white p-3.5 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]"
                >
                    <form
                        @submit.prevent="sendMessage"
                        class="group relative flex items-center gap-2"
                    >
                        <input
                            v-model="newMessage"
                            type="text"
                            placeholder="Ketik pertanyaanmu di sini..."
                            class="w-full rounded-2xl border-transparent bg-slate-100 py-3.5 pr-12 pl-4 text-[13.5px] shadow-inner transition-all duration-300 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10"
                            :disabled="isTyping"
                        />
                        <button
                            type="submit"
                            class="absolute top-1/2 right-2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md transition-all duration-300 group-focus-within:bg-indigo-700 hover:bg-indigo-700 hover:shadow-lg active:scale-90 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!newMessage.trim() || isTyping"
                        >
                            <Send
                                class="ml-0.5 h-4 w-4"
                                :class="{ 'animate-pulse': newMessage.trim() }"
                            />
                        </button>
                    </form>
                    <div class="mt-2.5 text-center">
                        <span
                            class="text-[9px] font-semibold tracking-wider text-slate-400/80"
                            >AI DAPAT MELAKUKAN KESALAHAN. PERIKSA
                            KEMBALI.</span
                        >
                    </div>
                </div>
            </div>
        </transition>

        <div class="group relative">
            <span
                v-if="!isOpen"
                class="pointer-events-none absolute inset-0 animate-ping rounded-full bg-indigo-500 opacity-40"
                style="animation-duration: 2s"
            ></span>

            <button
                @click="toggleChat"
                class="relative flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-[0_8px_16px_rgba(79,70,229,0.3)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_24px_rgba(79,70,229,0.4)] active:scale-95"
            >
                <X
                    v-show="isOpen"
                    class="h-6 w-6 rotate-90 transition-transform duration-500"
                />
                <bot-icon
                    v-show="!isOpen"
                    class="h-6 w-6 transition-transform duration-500 group-hover:scale-110 group-hover:rotate-12"
                />

                <span
                    v-if="!isOpen"
                    class="absolute top-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-white bg-rose-500"
                ></span>
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes gradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}
.animate-gradient {
    animation: gradient 6s ease infinite;
}

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
