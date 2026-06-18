<script setup lang="ts">
// Import ikon SVG premium dari Lucide
import axios from 'axios';
import katex from 'katex';
import { Sparkles, X, Bot, Send, BotIcon } from 'lucide-vue-next';
import { marked } from 'marked';
import { ref, onMounted, nextTick, onUnmounted } from 'vue';
import 'katex/dist/katex.min.css';

// Menerima judul materi untuk konteks AI dan id fase
const props = defineProps<{
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
let pollingInterval: any = null;

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
            mathBlocks.push(`<div class="my-3 overflow-x-auto">${rendered}</div>`);

            return `%%MATH_BLOCK_TOKEN_${mathBlocks.length - 1}%%`;
        } catch (e) {
            return match;
        }
    });

    // 2. Amankan Inline Math (Rumus kimia di dalam baris kalimat seperti $H_2O$)
    processedText = processedText.replace(/\$(.+?)\$/g, (match, math) => {
        try {
            const rendered = katex.renderToString(math, { displayMode: false });
            mathBlocks.push(rendered);

            return `%%MATH_BLOCK_TOKEN_${mathBlocks.length - 1}%%`;
        } catch (e) {
            return match;
        }
    });

    // 3. Render teks narasi utama ke format HTML via marked
    let finalHtml = marked.parse(processedText, { breaks: true }) as string;

    // 4. Kembalikan kode HTML KaTeX yang sudah matang ke posisinya masing-masing
    mathBlocks.forEach((renderedMath, index) => {
        finalHtml = finalHtml.split(`%%MATH_BLOCK_TOKEN_${index}%%`).join(renderedMath);
    });

    return finalHtml;
};

// Fungsi mengambil riwayat chat dari database
const fetchChats = async () => {
    try {
        const response = await axios.get(route('siswa.chatbot.index'));
        const logs = response.data;
        
        const newMessages: Array<any> = [
            { id: 'welcome', sender: 'ai', text: 'Halo! 👋 Aku adalah AI Tutor pendamping belajarmu. Ada yang bikin kamu bingung?' }
        ];

        let isWaiting = false;

        logs.forEach((log: any) => {
            // Masukkan pesan siswa
            newMessages.push({ id: `user_${log.id}`, sender: 'user', text: log.prompt });
            
            // Masukkan pesan AI (jika sudah dijawab oleh background worker)
            if (log.response) {
                newMessages.push({ id: `ai_${log.id}`, sender: 'ai', text: log.response });
            } else {
                // Cek apakah chat log sudah terlalu lama (misal > 45 detik) tapi belum ada respon.
                // Jika iya, berarti job AI di backend telah gagal/limit.
                const createdTimeStr = log.created_at;
                const utcTimeStr = (createdTimeStr.endsWith('Z') || createdTimeStr.includes('+')) 
                    ? createdTimeStr 
                    : createdTimeStr.replace(' ', 'T') + 'Z';
                
                const createdTime = new Date(utcTimeStr).getTime();
                const nowTime = new Date().getTime();
                const diffSeconds = (nowTime - createdTime) / 1000;

                if (diffSeconds > 45) {
                    newMessages.push({ 
                        id: `ai_failed_${log.id}`, 
                        sender: 'ai', 
                        text: 'Maaf, sepertinya terjadi gangguan koneksi atau batas kuota API terlampaui di server AI. Silakan kirim pesan baru atau coba sesaat lagi.' 
                    });
                } else {
                    isWaiting = true; // Menandakan ada pesan yang masih antre di antrean server
                }
            }
        });

        messages.value = newMessages;
        isTyping.value = isWaiting;

        // Manajemen Polling otomatis jika masih menunggu jawaban AI
        if (isWaiting && !pollingInterval) {
            startPolling();
        } else if (!isWaiting && pollingInterval) {
            stopPolling();
        }

    } catch (error) {
        console.error("Gagal mengambil riwayat chat:", error);
    }
};

const startPolling = () => {
    if (pollingInterval) {
return;
}

    pollingInterval = setInterval(async () => {
        await fetchChats();
        scrollToBottom();
    }, 3000); // Sinkronisasi data tiap 3 detik
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

const toggleChat = async () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        await fetchChats(); 
        scrollToBottom();
    }
};

const scrollToBottom = async () => {
    await nextTick();

    if (messagesContainer.value) {
        messagesContainer.value.scrollTo({
            top: messagesContainer.value.scrollHeight,
            behavior: 'smooth'
        });
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || isTyping.value) {
return;
}

    const userText = newMessage.value;
    newMessage.value = '';
    
    // Tampilkan langsung di layar secara instan agar terasa responsif
    messages.value.push({ id: Date.now(), sender: 'user', text: userText });
    isTyping.value = true;
    scrollToBottom();

    try {
        // Kirim post request menuju backend controller rute siswa
        await axios.post(route('siswa.chatbot.store'), {
            prompt: userText,
            topic_context: props.topicTitle || 'Materi Umum',
            phase_id: props.phaseId
        });

        // Jalankan pemantauan latar belakang
        startPolling();
    } catch (error) {
        isTyping.value = false;
        messages.value.push({ id: Date.now(), sender: 'ai', text: 'Maaf, terjadi gangguan jaringan saat mengirim pesan.' });
        scrollToBottom();
    }
};

onMounted(() => {
    fetchChats();
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
        
        <transition 
            enter-active-class="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] origin-bottom-right" 
            enter-from-class="opacity-0 translate-y-10 scale-50" 
            enter-to-class="opacity-100 translate-y-0 scale-100" 
            leave-active-class="transition-all duration-300 ease-in origin-bottom-right" 
            leave-from-class="opacity-100 translate-y-0 scale-100" 
            leave-to-class="opacity-0 translate-y-10 scale-50"
        >
            <div v-show="isOpen" class="mb-4 w-80 sm:w-[380px] bg-white rounded-3xl shadow-2xl border border-indigo-50 flex flex-col overflow-hidden">
                
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-[length:200%_auto] animate-gradient p-4 flex items-center justify-between shadow-md z-10 relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10 opacity-0 hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
                    
                    <div class="flex items-center gap-3 relative z-10">
                        <div class="bg-white/20 p-2.5 rounded-full backdrop-blur-sm shadow-inner group-hover:rotate-12 transition-transform duration-300">
                            <bot-icon class="w-5 h-5 text-white animate-pulse" />
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-[15px] tracking-wide">AI Tutor Pendamping</h3>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)] animate-pulse"></span>
                                <span class="text-indigo-50 text-[11px] font-medium tracking-wider">Online & Siap Membantu</span>
                            </div>
                        </div>
                    </div>
                    <button @click="toggleChat" class="relative z-10 text-indigo-100 hover:text-white transition-all bg-white/10 hover:bg-white/20 hover:rotate-90 p-2 rounded-xl duration-300">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div ref="messagesContainer" class="flex-1 p-5 overflow-y-auto bg-[#F8FAFC] min-h-[350px] max-h-[450px] flex flex-col gap-5 scroll-smooth">
                    
                    <div v-for="msg in messages" :key="msg.id" class="flex w-full animate-in fade-in slide-in-from-bottom-4 duration-500 ease-out" :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'">
                        
                        <div v-if="msg.sender === 'ai'" class="flex gap-2.5 max-w-[85%]">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex-shrink-0 flex items-center justify-center mt-1 border border-indigo-200 shadow-sm">
                                <Bot class="w-4 h-4 text-indigo-600" />
                            </div>
                            <div class="bg-white border border-slate-200 text-slate-700 p-4 rounded-2xl rounded-tl-sm text-[13.5px] shadow-sm hover:shadow-md transition-shadow">
                                <div v-html="renderMarkdown(msg.text)" class="prose prose-sm prose-slate max-w-none break-words"></div>
                            </div>
                        </div>

                        <div v-else class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white p-3.5 rounded-2xl rounded-tr-sm text-[13.5px] shadow-md max-w-[85%] leading-relaxed hover:shadow-lg transition-shadow transform hover:-translate-y-0.5 duration-300 whitespace-pre-wrap">
                            {{ msg.text }}
                        </div>
                    </div>

                    <div v-if="isTyping" class="flex gap-2.5 max-w-[85%] animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex-shrink-0 flex items-center justify-center mt-1 border border-indigo-200 shadow-sm">
                            <Bot class="w-4 h-4 text-indigo-600" />
                        </div>
                        <div class="bg-white border border-slate-200 py-3 px-4 rounded-2xl rounded-tl-sm shadow-sm flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 0ms"></span>
                            <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 150ms"></span>
                            <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 300ms"></span>
                        </div>
                    </div>
                </div>

                <div class="p-3.5 bg-white border-t border-slate-100 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]">
                    <form @submit.prevent="sendMessage" class="flex items-center gap-2 relative group">
                        <input 
                            v-model="newMessage" 
                            type="text" 
                            placeholder="Ketik pertanyaanmu di sini..." 
                            class="w-full bg-slate-100 border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl text-[13.5px] py-3.5 pl-4 pr-12 transition-all duration-300 shadow-inner"
                            :disabled="isTyping"
                        />
                        <button 
                            type="submit" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 active:scale-90 disabled:opacity-50 disabled:cursor-not-allowed group-focus-within:bg-indigo-700"
                            :disabled="!newMessage.trim() || isTyping"
                        >
                            <Send class="w-4 h-4 ml-0.5" :class="{'animate-pulse': newMessage.trim()}" />
                        </button>
                    </form>
                    <div class="text-center mt-2.5">
                        <span class="text-[9px] text-slate-400/80 font-semibold tracking-wider">AI DAPAT MELAKUKAN KESALAHAN. PERIKSA KEMBALI.</span>
                    </div>
                </div>

            </div>
        </transition>

        <div class="relative group">
            <span v-if="!isOpen" class="absolute inset-0 bg-indigo-500 rounded-full opacity-40 animate-ping" style="animation-duration: 2s;"></span>
            
            <button 
                @click="toggleChat"
                class="relative flex items-center justify-center w-14 h-14 bg-gradient-to-tr from-indigo-600 to-purple-600 text-white rounded-full shadow-[0_8px_16px_rgba(79,70,229,0.3)] hover:shadow-[0_12px_24px_rgba(79,70,229,0.4)] hover:-translate-y-1 transition-all duration-300 active:scale-95"
            >
                <X v-show="isOpen" class="w-6 h-6 transition-transform duration-500 rotate-90" />
                <bot-icon v-show="!isOpen" class="w-6 h-6 transition-transform duration-500 group-hover:rotate-12 group-hover:scale-110" />
                
                <span v-if="!isOpen" class="absolute top-0 right-0 w-3.5 h-3.5 bg-rose-500 border-2 border-white rounded-full"></span>
            </button>
        </div>

    </div>
</template>

<style scoped>
@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
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