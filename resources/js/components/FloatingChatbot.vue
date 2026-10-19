<script setup lang="ts">
import axios from 'axios';
import DOMPurify from 'dompurify';
import katex from 'katex';
import {
    Bot,
    BotIcon,
    ChevronLeft,
    Clock3,
    History,
    LoaderCircle,
    MessageSquarePlus,
    Send,
    Trash2,
    X,
} from 'lucide-vue-next';
import { marked } from 'marked';
import { nextTick, onUnmounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import 'katex/dist/katex.min.css';

const props = defineProps<{
    classroomId: number;
    topicTitle?: string;
    phaseId: number;
}>();

type MessageSender = 'user' | 'ai';

interface ChatMessage {
    id: string;
    sender: MessageSender;
    text: string;
}

interface ChatSession {
    id: string;
    title: string;
    is_legacy: boolean;
    has_pending: boolean;
    last_message_at: string | null;
    created_at: string | null;
}

interface ChatLog {
    id: number;
    prompt: string;
    response: string | null;
    status: 'queued' | 'processing' | 'completed' | 'failed' | null;
    created_at: string | null;
}

const welcomeMessage = (): ChatMessage => ({
    id: 'welcome',
    sender: 'ai',
    text: 'Halo! Aku AI Tutor pendamping belajarmu. Konsep kimia apa yang ingin kamu pahami?',
});

const isOpen = ref(false);
const isHistoryOpen = ref(false);
const isLoadingSessions = ref(false);
const isLoadingMessages = ref(false);
const isClearing = ref(false);
const showClearConfirmation = ref(false);
const newMessage = ref('');
const isTyping = ref(false);
const messagesContainer = ref<HTMLElement | null>(null);
const messages = ref<ChatMessage[]>([welcomeMessage()]);
const sessions = ref<ChatSession[]>([]);
const activeSessionId = ref<string | null>(null);

const POLL_DELAYS_MS = [2_000, 3_000, 5_000, 8_000, 15_000];
const MAX_POLL_WINDOW_MS = 3 * 60 * 1000;
let pollingTimer: ReturnType<typeof setTimeout> | null = null;
let pendingLogId: number | null = null;
let pollAttempt = 0;
let pollStartedAtMs = 0;
let consecutivePollErrors = 0;

const renderMarkdown = (text: string) => {
    if (!text) {
        return '';
    }

    const mathBlocks: string[] = [];
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

    processedText = processedText.replace(/\$(.+?)\$/g, (match, math) => {
        try {
            const rendered = katex.renderToString(math, { displayMode: false });
            mathBlocks.push(rendered);

            return `%%MATH_BLOCK_TOKEN_${mathBlocks.length - 1}%%`;
        } catch {
            return match;
        }
    });

    let finalHtml = marked.parse(processedText, { breaks: true }) as string;

    mathBlocks.forEach((renderedMath, index) => {
        finalHtml = finalHtml
            .split(`%%MATH_BLOCK_TOKEN_${index}%%`)
            .join(renderedMath);
    });

    return DOMPurify.sanitize(finalHtml);
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

const stopPolling = () => {
    if (pollingTimer) {
        clearTimeout(pollingTimer);
        pollingTimer = null;
    }
};

const resetPollingState = () => {
    stopPolling();
    pendingLogId = null;
    pollAttempt = 0;
    pollStartedAtMs = 0;
    consecutivePollErrors = 0;
    isTyping.value = false;
};

const startNewChat = () => {
    resetPollingState();
    activeSessionId.value = null;
    messages.value = [welcomeMessage()];
    newMessage.value = '';
    isHistoryOpen.value = false;
    showClearConfirmation.value = false;
    void scrollToBottom();
};

const formatSessionTime = (value: string | null) => {
    if (!value) {
        return 'Baru';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const sortSessions = () => {
    sessions.value.sort((left, right) => {
        const leftTime = new Date(
            left.last_message_at || left.created_at || 0,
        ).getTime();
        const rightTime = new Date(
            right.last_message_at || right.created_at || 0,
        ).getTime();

        return rightTime - leftTime;
    });
};

const upsertSession = (session: ChatSession) => {
    const index = sessions.value.findIndex((item) => item.id === session.id);

    if (index === -1) {
        sessions.value.unshift(session);
    } else {
        sessions.value[index] = { ...sessions.value[index], ...session };
    }

    sortSessions();
};

const fetchSessions = async () => {
    isLoadingSessions.value = true;

    try {
        const response = await axios.get(
            route('siswa.chatbot.sessions.index'),
            {
                params: {
                    classroom_id: props.classroomId,
                    phase_id: props.phaseId,
                },
            },
        );

        sessions.value = response.data.sessions || [];
    } catch (error) {
        console.error('Gagal mengambil daftar percakapan:', error);
        sessions.value = [];
    } finally {
        isLoadingSessions.value = false;
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

const addAiMessage = (logId: number, text: string, kind = 'ai') => {
    const messageId = `${kind}_${logId}`;
    const existing = messages.value.find((message) => message.id === messageId);

    if (existing) {
        existing.text = text;

        return;
    }

    messages.value.push({ id: messageId, sender: 'ai', text });
};

const finishPolling = (stillPending = false) => {
    const session = sessions.value.find(
        (item) => item.id === activeSessionId.value,
    );

    if (session) {
        session.has_pending = stillPending;
    }

    pendingLogId = null;
    isTyping.value = false;
    stopPolling();
    void scrollToBottom();
};

const pollChatStatus = async () => {
    if (pendingLogId === null || !isOpen.value || !activeSessionId.value) {
        return;
    }

    const logId = pendingLogId;
    const sessionId = activeSessionId.value;

    if (Date.now() - pollStartedAtMs > MAX_POLL_WINDOW_MS) {
        addAiMessage(
            logId,
            'Jawaban masih berada dalam antrean. Percakapan ini sudah tersimpan dan dapat dibuka kembali nanti.',
            'ai_delayed',
        );
        finishPolling(true);

        return;
    }

    try {
        const response = await axios.get(
            route('siswa.chatbot.status', { chatLog: logId }),
            { headers: { Accept: 'application/json' } },
        );
        const result = response.data;
        consecutivePollErrors = 0;

        if (activeSessionId.value !== sessionId) {
            return;
        }

        if (result.status === 'completed' && result.response) {
            addAiMessage(logId, result.response);
            finishPolling();

            return;
        }

        if (result.status === 'failed') {
            addAiMessage(
                logId,
                'Jawaban belum berhasil dibuat. Coba kirim pertanyaan ini kembali.',
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
                'Pemeriksaan jawaban berhenti sementara. Pesan tetap tersimpan di percakapan ini.',
                'ai_delayed',
            );
            finishPolling(true);

            return;
        }
    }

    pollAttempt += 1;
    startPolling();
};

const loadSession = async (sessionId: string) => {
    resetPollingState();
    activeSessionId.value = sessionId;
    isLoadingMessages.value = true;
    messages.value = [welcomeMessage()];

    try {
        const response = await axios.get(
            route('siswa.chatbot.sessions.show', {
                chatSession: sessionId,
            }),
            { headers: { Accept: 'application/json' } },
        );
        const logs: ChatLog[] = response.data.messages || [];
        const loadedMessages: ChatMessage[] = [welcomeMessage()];
        let latestPending: ChatLog | null = null;

        upsertSession(response.data.session);

        for (const log of logs) {
            loadedMessages.push({
                id: `user_${log.id}`,
                sender: 'user',
                text: log.prompt,
            });

            if (log.response) {
                loadedMessages.push({
                    id: `ai_${log.id}`,
                    sender: 'ai',
                    text: log.response,
                });
            } else if (log.status === 'failed') {
                loadedMessages.push({
                    id: `ai_failed_${log.id}`,
                    sender: 'ai',
                    text: 'Jawaban belum berhasil dibuat. Coba kirim pertanyaan ini kembali.',
                });
            } else {
                latestPending = log;
            }
        }

        messages.value = loadedMessages;

        if (latestPending) {
            const createdAtMs = latestPending.created_at
                ? new Date(latestPending.created_at).getTime()
                : Date.now();

            if (Date.now() - createdAtMs > MAX_POLL_WINDOW_MS) {
                addAiMessage(
                    latestPending.id,
                    'Jawaban masih berada dalam antrean. Percakapan ini sudah tersimpan dan dapat dibuka kembali nanti.',
                    'ai_delayed',
                );
            } else {
                pendingLogId = latestPending.id;
                pollStartedAtMs = createdAtMs;
                isTyping.value = true;
                startPolling();
            }
        }
    } catch (error: any) {
        if (error?.response?.status === 404) {
            sessions.value = sessions.value.filter(
                (session) => session.id !== sessionId,
            );
            startNewChat();
        } else {
            messages.value.push({
                id: `load_error_${Date.now()}`,
                sender: 'ai',
                text: 'Riwayat percakapan belum dapat dimuat. Coba buka kembali panel ini.',
            });
        }
    } finally {
        isLoadingMessages.value = false;
        isHistoryOpen.value = false;
        void scrollToBottom();
    }
};

const selectSession = (sessionId: string) => {
    void loadSession(sessionId);
};

const toggleHistory = async () => {
    isHistoryOpen.value = !isHistoryOpen.value;

    if (isHistoryOpen.value) {
        await fetchSessions();
    }
};

const toggleChat = async () => {
    isOpen.value = !isOpen.value;

    if (!isOpen.value) {
        stopPolling();

        return;
    }

    await fetchSessions();

    if (
        activeSessionId.value &&
        sessions.value.some((session) => session.id === activeSessionId.value)
    ) {
        await loadSession(activeSessionId.value);
    } else if (sessions.value.length > 0) {
        await loadSession(sessions.value[0].id);
    } else {
        startNewChat();
    }
};

const sendMessage = async () => {
    const userText = newMessage.value.trim();

    if (!userText || isTyping.value) {
        return;
    }

    newMessage.value = '';
    const optimisticId = `optimistic_${Date.now()}`;
    messages.value.push({ id: optimisticId, sender: 'user', text: userText });
    isTyping.value = true;
    void scrollToBottom();

    try {
        const response = await axios.post(route('siswa.chatbot.store'), {
            prompt: userText,
            session_id: activeSessionId.value,
            phase_id: props.phaseId,
            classroom_id: props.classroomId,
        });
        const sessionId = String(response.data.session_id);
        const timestamp = new Date().toISOString();
        activeSessionId.value = sessionId;

        upsertSession({
            id: sessionId,
            title: response.data.session_title || userText,
            is_legacy: false,
            has_pending: response.data.status === 'queued',
            last_message_at: timestamp,
            created_at: timestamp,
        });

        if (response.data.status === 'success' && response.data.response) {
            messages.value.push({
                id: `ai_${response.data.log_id || Date.now()}`,
                sender: 'ai',
                text: response.data.response,
            });
            isTyping.value = false;
            stopPolling();
            void scrollToBottom();

            return;
        }

        pendingLogId = Number(response.data.log_id);
        pollAttempt = 0;
        consecutivePollErrors = 0;
        pollStartedAtMs = Date.now();
        startPolling();
    } catch (error: any) {
        isTyping.value = false;
        messages.value = messages.value.filter(
            (message) => message.id !== optimisticId,
        );
        newMessage.value = userText;
        const errorMessage =
            error?.response?.data?.message ||
            'Pesan belum terkirim. Periksa koneksi, lalu coba lagi.';
        messages.value.push({
            id: `send_error_${Date.now()}`,
            sender: 'ai',
            text: errorMessage,
        });
        void scrollToBottom();
    }
};

const requestClearChat = () => {
    if (!activeSessionId.value) {
        startNewChat();

        return;
    }

    showClearConfirmation.value = true;
};

const clearActiveChat = async () => {
    if (!activeSessionId.value || isClearing.value) {
        return;
    }

    const sessionId = activeSessionId.value;
    isClearing.value = true;

    try {
        await axios.delete(
            route('siswa.chatbot.sessions.clear', {
                chatSession: sessionId,
            }),
            { headers: { Accept: 'application/json' } },
        );
        sessions.value = sessions.value.filter(
            (session) => session.id !== sessionId,
        );
        startNewChat();
    } catch {
        showClearConfirmation.value = false;
        messages.value.push({
            id: `clear_error_${Date.now()}`,
            sender: 'ai',
            text: 'Percakapan belum dapat dibersihkan. Coba lagi beberapa saat.',
        });
    } finally {
        isClearing.value = false;
    }
};

watch(
    () => [props.classroomId, props.phaseId],
    async () => {
        sessions.value = [];
        startNewChat();

        if (isOpen.value) {
            await fetchSessions();

            if (sessions.value.length > 0) {
                await loadSession(sessions.value[0].id);
            }
        }
    },
);

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <div
        class="chat-shell fixed right-4 bottom-4 z-[9999] flex flex-col items-end sm:right-6 sm:bottom-6"
    >
        <transition
            enter-active-class="transition-all duration-300 ease-out origin-bottom-right"
            enter-from-class="opacity-0 translate-y-6 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-200 ease-in origin-bottom-right"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-6 scale-95"
        >
            <div
                v-show="isOpen"
                class="relative mb-4 flex h-[min(620px,calc(100vh-7rem))] w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-[28px] border border-indigo-100 bg-white shadow-[0_24px_70px_rgba(30,41,59,0.22)] sm:w-[390px]"
            >
                <header
                    class="animate-gradient relative z-20 flex items-center justify-between overflow-hidden bg-gradient-to-r from-indigo-700 via-violet-600 to-indigo-700 bg-[length:200%_auto] px-4 py-3.5"
                >
                    <div
                        class="pointer-events-none absolute -top-8 left-10 h-20 w-20 rounded-full border border-white/15"
                        aria-hidden="true"
                    ></div>
                    <div class="relative z-10 flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/15 shadow-inner backdrop-blur-sm"
                        >
                            <BotIcon class="h-5 w-5 text-white" />
                        </div>
                        <div class="min-w-0">
                            <h3
                                class="truncate text-[14px] font-bold tracking-wide text-white"
                            >
                                AI Tutor Kimia
                            </h3>
                            <p
                                class="mt-0.5 truncate text-[10px] font-medium tracking-wide text-indigo-100"
                            >
                                {{ topicTitle || 'Pendamping belajar' }}
                            </p>
                        </div>
                    </div>

                    <div class="relative z-10 flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded-xl p-2 text-indigo-100 transition-colors hover:bg-white/15 hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                            :aria-label="
                                isHistoryOpen
                                    ? 'Kembali ke percakapan'
                                    : 'Buka riwayat percakapan'
                            "
                            :title="
                                isHistoryOpen ? 'Kembali' : 'Riwayat percakapan'
                            "
                            @click="toggleHistory"
                        >
                            <ChevronLeft
                                v-if="isHistoryOpen"
                                class="h-[18px] w-[18px]"
                            />
                            <History v-else class="h-[18px] w-[18px]" />
                        </button>
                        <button
                            type="button"
                            class="rounded-xl p-2 text-indigo-100 transition-colors hover:bg-white/15 hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                            aria-label="Mulai chat baru"
                            title="Chat baru"
                            @click="startNewChat"
                        >
                            <MessageSquarePlus class="h-[18px] w-[18px]" />
                        </button>
                        <button
                            type="button"
                            class="rounded-xl p-2 text-indigo-100 transition-colors hover:bg-white/15 hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                            aria-label="Tutup AI Tutor"
                            title="Tutup"
                            @click="toggleChat"
                        >
                            <X class="h-[18px] w-[18px]" />
                        </button>
                    </div>
                </header>

                <section
                    v-if="isHistoryOpen"
                    class="flex min-h-0 flex-1 flex-col bg-slate-50"
                    aria-label="Riwayat percakapan"
                >
                    <div class="border-b border-slate-200 bg-white px-5 py-4">
                        <p
                            class="text-[10px] font-bold tracking-[0.18em] text-indigo-500 uppercase"
                        >
                            Jejak belajar
                        </p>
                        <div class="mt-1 flex items-end justify-between gap-3">
                            <div>
                                <h4 class="text-base font-bold text-slate-900">
                                    Riwayat percakapan
                                </h4>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Setiap chat menyimpan konteksnya sendiri.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:outline-none"
                                @click="startNewChat"
                            >
                                Chat baru
                            </button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-3">
                        <div
                            v-if="isLoadingSessions"
                            class="flex h-full items-center justify-center text-slate-500"
                        >
                            <LoaderCircle class="h-5 w-5 animate-spin" />
                            <span class="ml-2 text-xs">Memuat riwayat...</span>
                        </div>

                        <div
                            v-else-if="sessions.length === 0"
                            class="flex h-full flex-col items-center justify-center px-8 text-center"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50"
                            >
                                <History class="h-5 w-5 text-indigo-500" />
                            </div>
                            <p
                                class="mt-3 text-sm font-semibold text-slate-700"
                            >
                                Belum ada percakapan
                            </p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-slate-500"
                            >
                                Mulai chat pertama untuk menyimpan jejak
                                belajarmu.
                            </p>
                        </div>

                        <div v-else class="space-y-2">
                            <button
                                v-for="session in sessions"
                                :key="session.id"
                                type="button"
                                class="group relative flex w-full items-start gap-3 rounded-2xl border bg-white px-3.5 py-3 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:outline-none"
                                :class="
                                    activeSessionId === session.id
                                        ? 'border-indigo-300 ring-1 ring-indigo-100'
                                        : 'border-slate-200'
                                "
                                @click="selectSession(session.id)"
                            >
                                <span
                                    class="mt-1.5 flex h-3 w-3 shrink-0 items-center justify-center rounded-full border-2 border-white shadow-[0_0_0_1px_rgba(99,102,241,0.25)]"
                                    :class="
                                        session.has_pending
                                            ? 'animate-pulse bg-amber-400'
                                            : 'bg-indigo-500'
                                    "
                                    aria-hidden="true"
                                ></span>
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="line-clamp-2 block text-[13px] leading-snug font-semibold text-slate-800"
                                    >
                                        {{ session.title }}
                                    </span>
                                    <span
                                        class="mt-1.5 flex items-center gap-1.5 text-[10px] font-medium text-slate-400"
                                    >
                                        <Clock3 class="h-3 w-3" />
                                        {{
                                            session.has_pending
                                                ? 'Menunggu jawaban'
                                                : formatSessionTime(
                                                      session.last_message_at ||
                                                          session.created_at,
                                                  )
                                        }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </section>

                <template v-else>
                    <div
                        class="flex h-10 shrink-0 items-center justify-between border-b border-slate-100 bg-white px-4"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full"
                                :class="
                                    activeSessionId
                                        ? 'bg-emerald-500'
                                        : 'bg-indigo-400'
                                "
                            ></span>
                            <span
                                class="truncate text-[10px] font-semibold tracking-wide text-slate-500"
                            >
                                {{
                                    activeSessionId
                                        ? sessions.find(
                                              (session) =>
                                                  session.id ===
                                                  activeSessionId,
                                          )?.title || 'Percakapan aktif'
                                        : 'Chat baru · konteks kosong'
                                }}
                            </span>
                        </div>
                        <button
                            v-if="activeSessionId"
                            type="button"
                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:outline-none"
                            aria-label="Bersihkan chat ini"
                            title="Bersihkan chat"
                            @click="requestClearChat"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div
                        ref="messagesContainer"
                        class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto scroll-smooth bg-[#F8FAFC] p-5"
                        aria-live="polite"
                    >
                        <div
                            v-if="isLoadingMessages"
                            class="flex flex-1 items-center justify-center text-slate-400"
                        >
                            <LoaderCircle class="h-5 w-5 animate-spin" />
                            <span class="ml-2 text-xs"
                                >Memuat percakapan...</span
                            >
                        </div>

                        <template v-else>
                            <div
                                v-for="msg in messages"
                                :key="msg.id"
                                class="flex w-full animate-in duration-300 fade-in slide-in-from-bottom-2"
                                :class="
                                    msg.sender === 'user'
                                        ? 'justify-end'
                                        : 'justify-start'
                                "
                            >
                                <div
                                    v-if="msg.sender === 'ai'"
                                    class="flex max-w-[88%] gap-2.5"
                                >
                                    <div
                                        class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-100 to-violet-100 shadow-sm"
                                    >
                                        <Bot class="h-4 w-4 text-indigo-600" />
                                    </div>
                                    <div
                                        class="rounded-2xl rounded-tl-sm border border-slate-200 bg-white p-3.5 text-[13.5px] text-slate-700 shadow-sm"
                                    >
                                        <div
                                            v-html="renderMarkdown(msg.text)"
                                            class="prose prose-sm prose-slate max-w-none break-words"
                                        ></div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="max-w-[85%] rounded-2xl rounded-tr-sm bg-gradient-to-br from-indigo-500 to-indigo-600 px-4 py-3 text-[13.5px] leading-relaxed whitespace-pre-wrap text-white shadow-md"
                                >
                                    {{ msg.text }}
                                </div>
                            </div>

                            <div
                                v-if="isTyping"
                                class="flex max-w-[85%] animate-in gap-2.5 duration-300 fade-in"
                            >
                                <div
                                    class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-100 to-violet-100 shadow-sm"
                                >
                                    <Bot class="h-4 w-4 text-indigo-600" />
                                </div>
                                <div
                                    class="flex items-center gap-1.5 rounded-2xl rounded-tl-sm border border-slate-200 bg-white px-4 py-3 shadow-sm"
                                    aria-label="AI Tutor sedang menyusun jawaban"
                                >
                                    <span
                                        class="h-2 w-2 animate-bounce rounded-full bg-indigo-400"
                                    ></span>
                                    <span
                                        class="h-2 w-2 animate-bounce rounded-full bg-indigo-400 [animation-delay:150ms]"
                                    ></span>
                                    <span
                                        class="h-2 w-2 animate-bounce rounded-full bg-indigo-400 [animation-delay:300ms]"
                                    ></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div
                        class="shrink-0 border-t border-slate-100 bg-white p-3.5 shadow-[0_-4px_12px_-8px_rgba(15,23,42,0.2)]"
                    >
                        <form
                            class="group relative flex items-center gap-2"
                            @submit.prevent="sendMessage"
                        >
                            <input
                                v-model="newMessage"
                                type="text"
                                maxlength="4000"
                                placeholder="Tanyakan konsep kimia..."
                                class="w-full rounded-2xl border border-transparent bg-slate-100 py-3.5 pr-12 pl-4 text-[13.5px] shadow-inner transition-all focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:outline-none"
                                :disabled="isTyping"
                                aria-label="Pertanyaan untuk AI Tutor"
                            />
                            <button
                                type="submit"
                                class="absolute top-1/2 right-2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md transition-all hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!newMessage.trim() || isTyping"
                                aria-label="Kirim pertanyaan"
                            >
                                <Send class="ml-0.5 h-4 w-4" />
                            </button>
                        </form>
                        <p
                            class="mt-2.5 text-center text-[9px] font-semibold tracking-wider text-slate-400"
                        >
                            AI DAPAT KELIRU · PERIKSA KEMBALI INFORMASI PENTING
                        </p>
                    </div>
                </template>

                <div
                    v-if="showClearConfirmation"
                    class="absolute inset-0 z-40 flex items-center justify-center bg-slate-950/35 p-5 backdrop-blur-[3px]"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="clear-chat-title"
                >
                    <div
                        class="w-full rounded-3xl border border-white/70 bg-white p-5 shadow-2xl"
                    >
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600"
                        >
                            <Trash2 class="h-5 w-5" />
                        </div>
                        <h4
                            id="clear-chat-title"
                            class="mt-4 text-base font-bold text-slate-900"
                        >
                            Bersihkan chat ini?
                        </h4>
                        <p
                            class="mt-1.5 text-xs leading-relaxed text-slate-500"
                        >
                            Percakapan akan hilang dari riwayatmu dan tidak lagi
                            dipakai sebagai konteks AI Tutor.
                        </p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button
                                type="button"
                                class="rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:outline-none"
                                :disabled="isClearing"
                                @click="showClearConfirmation = false"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:bg-rose-700 focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:opacity-60"
                                :disabled="isClearing"
                                @click="clearActiveChat"
                            >
                                <LoaderCircle
                                    v-if="isClearing"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                Bersihkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <div class="group relative">
            <span
                v-if="!isOpen"
                class="pointer-events-none absolute inset-0 animate-ping rounded-full bg-indigo-500 opacity-35"
                aria-hidden="true"
                style="animation-duration: 2.4s"
            ></span>
            <button
                type="button"
                class="relative flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-700 to-violet-600 text-white shadow-[0_10px_24px_rgba(79,70,229,0.36)] transition-all hover:-translate-y-1 hover:shadow-[0_14px_30px_rgba(79,70,229,0.42)] focus-visible:ring-4 focus-visible:ring-indigo-300 focus-visible:outline-none active:scale-95"
                :aria-label="isOpen ? 'Tutup AI Tutor' : 'Buka AI Tutor'"
                @click="toggleChat"
            >
                <X v-if="isOpen" class="h-6 w-6" />
                <BotIcon
                    v-else
                    class="h-6 w-6 transition-transform group-hover:scale-110 group-hover:rotate-6"
                />
                <span
                    v-if="!isOpen"
                    class="absolute top-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-400"
                    aria-hidden="true"
                ></span>
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes gradient {
    0%,
    100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.animate-gradient {
    animation: gradient 7s ease infinite;
}

:deep(.prose p) {
    margin-top: 0 !important;
    margin-bottom: 0.5rem !important;
    line-height: 1.6;
}

:deep(.prose p:last-child) {
    margin-bottom: 0 !important;
}

:deep(.prose a) {
    color: #4f46e5;
    text-decoration: underline;
    text-underline-offset: 2px;
}

:deep(.katex-display) {
    margin: 0.5em 0 !important;
    overflow-x: auto;
    overflow-y: hidden;
}

@media (prefers-reduced-motion: reduce) {
    .chat-shell *,
    .animate-gradient {
        scroll-behavior: auto !important;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
