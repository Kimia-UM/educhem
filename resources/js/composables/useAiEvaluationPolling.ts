import axios from 'axios';
import { onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';

export type AiStatus =
    | 'legacy'
    | 'not_required'
    | 'queued'
    | 'processing'
    | 'completed'
    | 'failed';

export type AiStatusItem = {
    status: AiStatus;
    answer_version: number;
    feedback?: string | null;
    error_code?: string | null;
    updated_at?: string | null;
};

type PollingOptions = {
    phaseId: number;
    initialFeedbacks?: Record<number, string>;
    initialStatuses?: Record<number, AiStatusItem>;
};

const AUTO_POLL_WINDOW_MS = 3 * 60 * 1000;

export function useAiEvaluationPolling({
    phaseId,
    initialFeedbacks = {},
    initialStatuses = {},
}: PollingOptions) {
    const aiFeedbacks = ref<Record<number, string>>({ ...initialFeedbacks });
    const aiStatuses = ref<Record<number, AiStatusItem>>({
        ...initialStatuses,
    });
    const isWaitingForAI = ref<Record<number, boolean>>({});
    const expandedAIFeedbacks = ref<Record<number, boolean>>({});

    const pendingContentIds = new Set<number>();
    const pollDeadlines = new Map<number, number>();
    let pollTimer: ReturnType<typeof setTimeout> | null = null;
    let pollInFlight = false;
    let pollRound = 0;
    let consecutiveErrors = 0;

    const isAIFeedbackExpanded = (contentId: number) => {
        return expandedAIFeedbacks.value[contentId] !== false;
    };

    const toggleAIFeedback = (contentId: number) => {
        if (expandedAIFeedbacks.value[contentId] === undefined) {
            expandedAIFeedbacks.value[contentId] = false;
        } else {
            expandedAIFeedbacks.value[contentId] =
                !expandedAIFeedbacks.value[contentId];
        }
    };

    const clearPollTimer = () => {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    };

    const removePendingContent = (contentId: number) => {
        pendingContentIds.delete(contentId);
        pollDeadlines.delete(contentId);
        isWaitingForAI.value[contentId] = false;

        if (pendingContentIds.size === 0) {
            clearPollTimer();
            pollRound = 0;
        }
    };

    const nextPollDelay = () => {
        const baseDelay = pollRound < 3 ? 15_000 : 30_000;
        const jitter = Math.floor(Math.random() * 4_001) - 2_000;

        return Math.max(5_000, baseDelay + jitter);
    };

    const schedulePoll = (delay = nextPollDelay(), replace = false) => {
        if (pendingContentIds.size === 0) {
            return;
        }

        if (replace) {
            clearPollTimer();
        } else if (pollTimer) {
            return;
        }

        pollTimer = setTimeout(() => {
            pollTimer = null;
            void pollStatuses();
        }, delay);
    };

    const pauseAutomaticPolling = (message: string) => {
        clearPollTimer();
        pendingContentIds.forEach((contentId) => {
            isWaitingForAI.value[contentId] = false;
        });
        pendingContentIds.clear();
        pollDeadlines.clear();

        toast.warning(message, { id: 'ai-polling-paused' });
    };

    const pollStatuses = async () => {
        if (pollInFlight || pendingContentIds.size === 0) {
            return;
        }

        if (document.hidden) {
            schedulePoll(30_000);

            return;
        }

        const now = Date.now();
        const expiredContentIds = Array.from(pendingContentIds).filter(
            (contentId) => (pollDeadlines.get(contentId) ?? 0) <= now,
        );

        expiredContentIds.forEach(removePendingContent);

        if (expiredContentIds.length > 0) {
            toast.info(
                'Jawaban tetap tersimpan dan masih dapat diproses. Cek hasil AI kembali nanti.',
                { id: 'ai-poll-timeout' },
            );
        }

        const contentIds = Array.from(pendingContentIds);

        if (contentIds.length === 0) {
            return;
        }

        pollInFlight = true;
        pollRound += 1;

        try {
            const response = await axios.get(
                route('siswa.answers.ai-feedback-status', phaseId),
                {
                    params: { content_ids: contentIds },
                    headers: { Accept: 'application/json' },
                },
            );

            consecutiveErrors = 0;
            const items = (response.data?.items ?? {}) as Record<
                number,
                AiStatusItem
            >;

            contentIds.forEach((contentId) => {
                const item = items[contentId];

                if (!item) {
                    return;
                }

                aiStatuses.value[contentId] = item;

                if (item.status === 'completed') {
                    if (item.feedback) {
                        aiFeedbacks.value[contentId] = item.feedback;
                    }

                    expandedAIFeedbacks.value[contentId] = true;
                    removePendingContent(contentId);
                    toast.success('Evaluasi AI selesai!', {
                        id: `ai-finish-${contentId}`,
                    });
                } else if (
                    item.status === 'failed' ||
                    item.status === 'not_required' ||
                    item.status === 'legacy'
                ) {
                    removePendingContent(contentId);

                    if (item.status === 'failed') {
                        toast.error(
                            'Evaluasi AI belum berhasil. Jawaban tetap tersimpan.',
                            { id: `ai-failed-${contentId}` },
                        );
                    }
                }
            });
        } catch {
            consecutiveErrors += 1;

            if (consecutiveErrors >= 3) {
                pauseAutomaticPolling(
                    'Pemeriksaan otomatis dihentikan sementara agar server tetap stabil. Jawaban Anda sudah tersimpan.',
                );
            }
        } finally {
            pollInFlight = false;

            if (pendingContentIds.size > 0) {
                schedulePoll();
            }
        }
    };

    const trackAiEvaluation = (
        contentId: number,
        answerVersion: number,
        pollWindowMs = AUTO_POLL_WINDOW_MS,
    ) => {
        aiStatuses.value[contentId] = {
            status: 'queued',
            answer_version: answerVersion,
        };
        pendingContentIds.add(contentId);
        pollDeadlines.set(contentId, Date.now() + pollWindowMs);
        isWaitingForAI.value[contentId] = true;
        schedulePoll();
    };

    const stopTrackingAiEvaluation = (contentId: number) => {
        removePendingContent(contentId);
    };

    const checkAiResult = (contentId: number) => {
        const currentStatus = aiStatuses.value[contentId];

        if (!currentStatus) {
            return;
        }

        pendingContentIds.add(contentId);
        pollDeadlines.set(contentId, Date.now() + 60_000);
        isWaitingForAI.value[contentId] = true;
        consecutiveErrors = 0;
        schedulePoll(0, true);
    };

    const handleVisibilityChange = () => {
        if (!document.hidden && pendingContentIds.size > 0) {
            schedulePoll(1_000, true);
        }
    };

    onMounted(() => {
        Object.entries(aiStatuses.value).forEach(([contentId, item]) => {
            if (item.status === 'queued' || item.status === 'processing') {
                trackAiEvaluation(Number(contentId), item.answer_version);
            }
        });

        document.addEventListener('visibilitychange', handleVisibilityChange);
    });

    onUnmounted(() => {
        clearPollTimer();
        document.removeEventListener(
            'visibilitychange',
            handleVisibilityChange,
        );
    });

    return {
        aiFeedbacks,
        aiStatuses,
        checkAiResult,
        expandedAIFeedbacks,
        isAIFeedbackExpanded,
        isWaitingForAI,
        stopTrackingAiEvaluation,
        trackAiEvaluation,
        toggleAIFeedback,
    };
}
