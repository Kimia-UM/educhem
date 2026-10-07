import { check, sleep } from 'k6';
import http from 'k6/http';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = (__ENV.BASE_URL || 'https://staging.educhem.id').replace(
    /\/$/,
    '',
);
const MODE = __ENV.MODE || 'web';
const STUDENT_COUNT = Number(__ENV.STUDENT_COUNT || '50');
const MAX_VUS = Number(__ENV.MAX_VUS || '50');
const EMAIL_PREFIX = __ENV.EMAIL_PREFIX || 'load.student';
const EMAIL_DOMAIN = __ENV.EMAIL_DOMAIN || 'load.educhem.test';
const PASSWORD = __ENV.K6_PASSWORD;
const WEB_STAGE_10 = __ENV.WEB_STAGE_10 || '1m';
const WEB_STAGE_25 = __ENV.WEB_STAGE_25 || '1m';
const WEB_STAGE_50 = __ENV.WEB_STAGE_50 || '2m';
const WEB_HOLD_50 = __ENV.WEB_HOLD_50 || '5m';
const WEB_RAMP_DOWN = __ENV.WEB_RAMP_DOWN || '1m';

const CLASSROOM_ID = requiredInteger('CLASSROOM_ID');
const TOPIC_ID = requiredInteger('TOPIC_ID');
const WEB_PHASE_ID = MODE === 'web' ? requiredInteger('WEB_PHASE_ID') : 0;
const MCQ_CONTENT_ID = MODE === 'web' ? requiredInteger('MCQ_CONTENT_ID') : 0;
const ESSAY_CONTENT_ID =
    MODE === 'web' ? requiredInteger('ESSAY_CONTENT_ID') : 0;
const AI_PHASE_ID = ['ai', 'chat'].includes(MODE)
    ? requiredInteger('AI_PHASE_ID')
    : 0;
const AI_CONTENT_ID = MODE === 'ai' ? requiredInteger('AI_CONTENT_ID') : 0;
const CHAT_VUS = Number(__ENV.CHAT_VUS || '50');
const CHAT_RUN_ID = __ENV.CHAT_RUN_ID || 'manual';
const CHAT_RAMP_SECONDS = Number(__ENV.CHAT_RAMP_SECONDS || '10');

if (!PASSWORD) {
    throw new Error(
        'K6_PASSWORD is required. Keep it in the shell environment, not in Git.',
    );
}

if (!['web', 'ai', 'chat'].includes(MODE)) {
    throw new Error('MODE must be "web", "ai", or "chat".');
}

if (
    (MODE === 'web' && STUDENT_COUNT < MAX_VUS) ||
    (MODE === 'chat' && STUDENT_COUNT < CHAT_VUS)
) {
    throw new Error(
        'STUDENT_COUNT must be greater than or equal to the configured VUs.',
    );
}

const flowErrors = new Counter('flow_errors');
const worksheetDuration = new Trend('worksheet_duration', true);
const answerSaveDuration = new Trend('answer_save_duration', true);
const aiStatusDuration = new Trend('ai_status_duration', true);
const chatSubmitDuration = new Trend('chat_submit_duration', true);
const chatStatusDuration = new Trend('chat_status_duration', true);
const chatTotalDuration = new Trend('chat_total_duration', true);
const chatDirectResult = new Rate('chat_direct_result');
const chatQueuedResult = new Rate('chat_queued_result');

const sharedThresholds = {
    checks: ['rate>0.99'],
    flow_errors: ['count<1'],
    http_req_failed: ['rate<0.01'],
};

export const options =
    MODE === 'chat'
        ? {
              scenarios: {
                  hybrid_chat: {
                      executor: 'per-vu-iterations',
                      vus: CHAT_VUS,
                      iterations: 1,
                      maxDuration: '5m',
                      exec: 'chatHybrid',
                  },
              },
              thresholds: {
                  ...sharedThresholds,
                  chat_submit_duration: ['p(95)<15000'],
                  chat_status_duration: ['p(95)<2000'],
                  chat_total_duration: ['p(95)<90000'],
              },
          }
        : MODE === 'ai'
          ? {
                scenarios: {
                    ai_queue: {
                        executor: 'per-vu-iterations',
                        vus: Number(__ENV.AI_VUS || '5'),
                        iterations: 1,
                        maxDuration: '5m',
                        exec: 'aiQueue',
                    },
                },
                thresholds: {
                    ...sharedThresholds,
                    ai_status_duration: ['p(95)<2000'],
                    answer_save_duration: ['p(95)<3000'],
                },
            }
          : {
                scenarios: {
                    web_load: {
                        executor: 'ramping-vus',
                        startVUs: 0,
                        stages: [
                            {
                                duration: WEB_STAGE_10,
                                target: Math.min(10, MAX_VUS),
                            },
                            {
                                duration: WEB_STAGE_25,
                                target: Math.min(25, MAX_VUS),
                            },
                            { duration: WEB_STAGE_50, target: MAX_VUS },
                            { duration: WEB_HOLD_50, target: MAX_VUS },
                            { duration: WEB_RAMP_DOWN, target: 0 },
                        ],
                        gracefulRampDown: '30s',
                        exec: 'webLoad',
                    },
                },
                thresholds: {
                    ...sharedThresholds,
                    answer_save_duration: ['p(95)<3000'],
                    worksheet_duration: ['p(95)<2500'],
                },
            };

let webFlowCompleted = false;

export function webLoad() {
    const worksheetUrl = `${BASE_URL}/siswa/classes/${CLASSROOM_ID}/topics/${TOPIC_ID}/phases/${WEB_PHASE_ID}`;

    if (webFlowCompleted) {
        const steadyStateResponse = http.get(worksheetUrl, {
            tags: { name: 'GET worksheet steady state' },
        });
        worksheetDuration.add(steadyStateResponse.timings.duration);

        if (
            !check(steadyStateResponse, {
                'steady-state worksheet opens': (response) =>
                    response.status === 200,
            })
        ) {
            recordError(
                `Steady-state worksheet failed for VU ${__VU}: ${steadyStateResponse.status}`,
            );
        }

        sleep(randomSeconds(3, 7));

        return;
    }

    webFlowCompleted = true;
    const user = credentialsForVirtualUser();
    const csrfToken = login(user);

    if (!csrfToken) {
        return;
    }

    const worksheetResponse = http.get(worksheetUrl, {
        tags: { name: 'GET worksheet' },
    });
    worksheetDuration.add(worksheetResponse.timings.duration);

    if (
        !check(worksheetResponse, {
            'worksheet opens': (response) => response.status === 200,
        })
    ) {
        recordError(
            `Worksheet failed for ${user.email}: ${worksheetResponse.status}`,
        );

        return;
    }

    for (let attempt = 1; attempt <= 3; attempt += 1) {
        if (
            !saveAnswer(WEB_PHASE_ID, MCQ_CONTENT_ID, 'H2O', csrfToken, 'mcq')
        ) {
            return;
        }

        sleep(randomSeconds(1, 3));
    }

    for (let attempt = 1; attempt <= 2; attempt += 1) {
        if (
            !saveAnswer(
                WEB_PHASE_ID,
                ESSAY_CONTENT_ID,
                `Air merupakan senyawa yang tersusun dari hidrogen dan oksigen. VU ${__VU}, revisi ${attempt}.`,
                csrfToken,
                'essay',
            )
        ) {
            return;
        }

        sleep(randomSeconds(2, 5));
    }

    const verifyResponse = http.get(worksheetUrl, {
        tags: { name: 'GET worksheet after autosave' },
    });
    worksheetDuration.add(verifyResponse.timings.duration);

    if (
        !check(verifyResponse, {
            'saved worksheet reopens': (response) => response.status === 200,
        })
    ) {
        recordError(
            `Worksheet verification failed for ${user.email}: ${verifyResponse.status}`,
        );
    }
}

export function aiQueue() {
    const user = credentialsForVirtualUser();
    const csrfToken = login(user);

    if (!csrfToken) {
        return;
    }

    const worksheetUrl = `${BASE_URL}/siswa/classes/${CLASSROOM_ID}/topics/${TOPIC_ID}/phases/${AI_PHASE_ID}`;
    const worksheetResponse = http.get(worksheetUrl, {
        tags: { name: 'GET AI worksheet' },
    });

    if (
        !check(worksheetResponse, {
            'AI worksheet opens': (response) => response.status === 200,
        })
    ) {
        recordError(
            `AI worksheet failed for ${user.email}: ${worksheetResponse.status}`,
        );

        return;
    }

    if (
        !saveAnswer(
            AI_PHASE_ID,
            AI_CONTENT_ID,
            `Atom adalah unit dasar unsur, sedangkan molekul terdiri dari atom-atom yang berikatan. Jawaban VU ${__VU}.`,
            csrfToken,
            'ai',
            202,
        )
    ) {
        return;
    }

    const maxPollAttempts = Number(__ENV.AI_POLL_ATTEMPTS || '12');

    for (let attempt = 1; attempt <= maxPollAttempts; attempt += 1) {
        sleep(15);

        const statusResponse = http.get(
            `${BASE_URL}/siswa/phases/${AI_PHASE_ID}/ai-feedback-status?content_ids%5B%5D=${AI_CONTENT_ID}`,
            {
                headers: jsonHeaders(csrfToken),
                tags: { name: 'GET AI feedback status' },
            },
        );
        aiStatusDuration.add(statusResponse.timings.duration);

        if (
            !check(statusResponse, {
                'AI status request succeeds': (response) =>
                    response.status === 200,
            })
        ) {
            recordError(
                `AI status failed for ${user.email}: ${statusResponse.status}`,
            );

            return;
        }

        const item = statusResponse.json(`items.${AI_CONTENT_ID}`);

        if (item?.status === 'completed') {
            check(item, {
                'AI feedback is present': (value) => Boolean(value.feedback),
            });

            return;
        }

        if (item?.status === 'failed') {
            recordError(`AI job reported failure for ${user.email}.`);

            return;
        }
    }

    recordError(
        `AI result timed out for ${user.email}; the answer may still be queued.`,
    );
}

export function chatHybrid() {
    // Keep all VUs active concurrently, but spread their initial login over a
    // short window so the test models a class opening chat rather than an
    // artificial single-millisecond MySQL connection spike.
    if (CHAT_RAMP_SECONDS > 0 && CHAT_VUS > 1) {
        sleep(((__VU - 1) / (CHAT_VUS - 1)) * CHAT_RAMP_SECONDS);
    }

    const user = credentialsForVirtualUser();
    const csrfToken = login(user);

    if (!csrfToken) {
        return;
    }

    const startedAt = Date.now();
    const prompt = `Apa perbedaan atom dan ion? Jawab maksimal dua kalimat. Kode pengujian ${CHAT_RUN_ID}-${String(__VU).padStart(3, '0')}.`;
    const submitResponse = http.post(
        `${BASE_URL}/siswa/chatbot`,
        JSON.stringify({
            prompt,
            topic_context: 'Struktur atom',
            phase_id: AI_PHASE_ID,
        }),
        {
            headers: jsonHeaders(csrfToken),
            redirects: 0,
            tags: { name: 'POST hybrid chatbot' },
        },
    );
    chatSubmitDuration.add(submitResponse.timings.duration);

    const submitted = check(submitResponse, {
        'chatbot accepts request': (response) =>
            response.status === 200 || response.status === 202,
    });

    if (!submitted) {
        chatDirectResult.add(false);
        chatQueuedResult.add(false);
        recordError(
            `Chatbot submission failed for ${user.email}: ${submitResponse.status}`,
        );

        return;
    }

    const body = submitResponse.json();

    if (submitResponse.status === 200) {
        chatDirectResult.add(true);
        chatQueuedResult.add(false);
        chatTotalDuration.add(Date.now() - startedAt);

        if (
            !check(body, {
                'direct chatbot response is present': (value) =>
                    value.status === 'success' && Boolean(value.response),
            })
        ) {
            recordError(`Direct chatbot response is empty for ${user.email}.`);
        }

        return;
    }

    chatDirectResult.add(false);
    chatQueuedResult.add(true);
    const logId = Number(body.log_id);

    if (!Number.isInteger(logId) || logId < 1) {
        recordError(`Queued chatbot response has no log ID for ${user.email}.`);

        return;
    }

    const delays = [2, 3, 5, 8, 15];
    const maxPollAttempts = Number(__ENV.CHAT_POLL_ATTEMPTS || '16');

    for (let attempt = 0; attempt < maxPollAttempts; attempt += 1) {
        sleep(delays[Math.min(attempt, delays.length - 1)]);

        const statusResponse = http.get(
            `${BASE_URL}/siswa/chatbot/${logId}/status`,
            {
                headers: jsonHeaders(csrfToken),
                tags: { name: 'GET chatbot status' },
            },
        );
        chatStatusDuration.add(statusResponse.timings.duration);

        if (
            !check(statusResponse, {
                'chatbot status request succeeds': (response) =>
                    response.status === 200,
            })
        ) {
            recordError(
                `Chatbot status failed for ${user.email}: ${statusResponse.status}`,
            );

            return;
        }

        const result = statusResponse.json();

        if (result.status === 'completed') {
            chatTotalDuration.add(Date.now() - startedAt);

            if (
                !check(result, {
                    'queued chatbot response is present': (value) =>
                        Boolean(value.response),
                })
            ) {
                recordError(
                    `Queued chatbot response is empty for ${user.email}.`,
                );
            }

            return;
        }

        if (result.status === 'failed') {
            recordError(`Chatbot job reported failure for ${user.email}.`);

            return;
        }
    }

    recordError(
        `Chatbot result timed out for ${user.email}; the message may still be queued.`,
    );
}

function login(user) {
    const loginPage = http.get(`${BASE_URL}/login`, {
        redirects: 0,
        tags: { name: 'GET login' },
    });

    if (
        !check(loginPage, {
            'login page opens': (response) => response.status === 200,
        })
    ) {
        recordError(`Login page failed for ${user.email}: ${loginPage.status}`);

        return null;
    }

    const csrfToken = csrfCookie();

    if (!csrfToken) {
        recordError(`No XSRF token was issued for ${user.email}.`);

        return null;
    }

    const loginResponse = http.post(
        `${BASE_URL}/login`,
        JSON.stringify({
            email: user.email,
            password: user.password,
            remember: false,
        }),
        {
            headers: jsonHeaders(csrfToken),
            redirects: 0,
            tags: { name: 'POST login' },
        },
    );
    const loggedIn = check(loginResponse, {
        'student login succeeds': (response) =>
            [200, 204, 302, 303].includes(response.status),
    });

    if (!loggedIn) {
        recordError(`Login failed for ${user.email}: ${loginResponse.status}`);

        return null;
    }

    return csrfCookie() || csrfToken;
}

function saveAnswer(
    phaseId,
    contentId,
    answerText,
    csrfToken,
    kind,
    expectedStatus = 200,
) {
    const response = http.post(
        `${BASE_URL}/siswa/phases/${phaseId}/answers`,
        JSON.stringify({
            content_id: contentId,
            answer_text: answerText,
        }),
        {
            headers: jsonHeaders(csrfToken),
            redirects: 0,
            tags: { name: `POST answer (${kind})` },
        },
    );
    answerSaveDuration.add(response.timings.duration, { kind });

    const saved = check(response, {
        [`${kind} answer saves`]: (result) => result.status === expectedStatus,
    });

    if (!saved) {
        recordError(`${kind} answer failed for VU ${__VU}: ${response.status}`);
    }

    return saved;
}

function csrfCookie() {
    const cookies = http.cookieJar().cookiesForURL(BASE_URL);
    const token = cookies['XSRF-TOKEN']?.[0];

    return token ? decodeURIComponent(token) : null;
}

function jsonHeaders(csrfToken) {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': csrfToken,
    };
}

function credentialsForVirtualUser() {
    const number = ((__VU - 1) % STUDENT_COUNT) + 1;

    return {
        email: `${EMAIL_PREFIX}.${String(number).padStart(3, '0')}@${EMAIL_DOMAIN}`,
        password: PASSWORD,
    };
}

function requiredInteger(name) {
    const value = Number(__ENV[name]);

    if (!Number.isInteger(value) || value < 1) {
        throw new Error(`${name} must be provided as a positive integer.`);
    }

    return value;
}

function randomSeconds(minimum, maximum) {
    return minimum + Math.random() * (maximum - minimum);
}

function recordError(message) {
    flowErrors.add(1);
    console.error(message);
}
