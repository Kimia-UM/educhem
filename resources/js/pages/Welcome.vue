<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { h, markRaw, ref, onMounted, onUpdated } from 'vue';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { Brain, FlaskConical, ListChecks, BookOpen, TrendingUp, Smartphone, Sparkles } from 'lucide-vue-next';

defineOptions({ layout: null });

const activeSection = ref('home');

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                activeSection.value = entry.target.id;
            }
        });
    }, { rootMargin: '-100px 0px -60% 0px' });

    ['home', 'stages', 'features', 'about'].forEach(id => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });

    AOS.init({
        duration: 800, 
        once: true,    
        offset: 100,   
    });
});

// Refresh AOS when component updates (useful for SPAs)
onUpdated(() => {
    AOS.refresh();
});

const scrollToSection = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const navItems = [
    { id: 'home',     label: 'Beranda' },
    { id: 'stages',   label: 'Tahapan Belajar' },
    { id: 'features', label: 'Fasilitas' },
    { id: 'about',    label: 'Tentang EduChem' },
];

const stages = [
    {
        title: 'Engage',
        desc: 'Fokuskan perhatian dan ketertarikanmu pada materi kimia.',
        icon: 'pi-lightbulb',
        bg: 'bg-blue-50', text: 'text-blue-600', color: 'blue',
        extra: () => h('div', { class: 'h-1.5 w-full overflow-hidden rounded-full bg-slate-100' },
            [h('div', { class: 'h-full w-3/4 bg-blue-500 rounded-full' })])
    },
    {
        title: 'Explore',
        desc: 'Jelajahi konsep lewat eksperimen & simulasi interaktif.',
        icon: 'pi-compass',
        bg: 'bg-emerald-50', text: 'text-emerald-600', color: 'emerald',
        extra: () => h('div', { class: 'flex gap-1.5' }, [
            h('div', { class: 'flex h-8 flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 text-emerald-300' }, [h('i', { class: 'pi pi-chart-bar text-xs' })]),
            h('div', { class: 'flex h-8 flex-1 items-center justify-center rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-400' }, [h('i', { class: 'pi pi-chart-line text-xs' })])
        ])
    },
    {
        title: 'Explain',
        desc: 'Jelaskan pemahamanmu dibimbing oleh AI Tutor.',
        icon: 'pi-comment',
        bg: 'bg-purple-50', text: 'text-purple-600', color: 'purple',
        extra: () => h('div', { class: 'space-y-1' }, [
            h('div', { class: 'h-1 w-full rounded-full bg-slate-100' }),
            h('div', { class: 'h-1 w-4/5 rounded-full bg-slate-100' })
        ])
    },
    {
        title: 'Elaborate',
        desc: 'Terapkan konsep pada tantangan baru & studi kasus nyata.',
        icon: 'pi-cog',
        bg: 'bg-amber-50', text: 'text-amber-600', color: 'amber',
        extra: () => h('div', { class: 'flex items-center gap-1.5 rounded-lg border border-amber-100 bg-amber-50/50 px-2 py-1.5 text-amber-700' }, [
            h('i', { class: 'pi pi-cog text-xs animate-spin-slow' }),
            h('span', { class: 'text-[10px] font-semibold' }, 'Penerapan Mandiri')
        ])
    },
    {
        title: 'Evaluate',
        desc: 'Uji pemahaman akhirmu dan lihat progres belajarmu.',
        icon: 'pi-check-circle',
        bg: 'bg-rose-50', text: 'text-rose-600', color: 'rose',
        extra: () => h('div', { class: 'flex items-center justify-between rounded-lg bg-rose-50 px-2 py-1.5' }, [
            h('span', { class: 'text-[10px] font-semibold text-rose-700' }, 'Skor Evaluasi'),
            h('span', { class: 'text-xs font-bold text-rose-600' }, '100%')
        ])
    },
];

const features = [
    { icon: markRaw(Brain),        bg: 'bg-indigo-50',  text: 'text-indigo-600',  title: 'AI Tutor Interaktif',      desc: 'Dapatkan bimbingan langsung dari AI saat menjelaskan konsep kimia dengan bahasa kamu sendiri.' },
    { icon: markRaw(FlaskConical), bg: 'bg-emerald-50', text: 'text-emerald-600', title: 'Simulasi & Eksperimen',     desc: 'Eksplorasi faktor-faktor laju reaksi melalui simulasi virtual yang interaktif dan visualisasi data.' },
    { icon: markRaw(ListChecks),   bg: 'bg-violet-50',  text: 'text-violet-600',  title: 'Evaluasi Adaptif',         desc: 'Soal evaluasi yang menyesuaikan tingkat pemahaman kamu secara real-time.' },
    { icon: markRaw(BookOpen),     bg: 'bg-amber-50',   text: 'text-amber-600',   title: 'Materi Terstruktur LC5E',  desc: 'Konten disusun mengikuti siklus belajar 5E yang terbukti efektif secara pedagogis.' },
    { icon: markRaw(TrendingUp),   bg: 'bg-blue-50',    text: 'text-blue-600',    title: 'Pelacakan Progres',        desc: 'Pantau kemajuan belajar di setiap tahap dan lihat perkembanganmu dari waktu ke waktu.' },
    { icon: markRaw(Smartphone),   bg: 'bg-rose-50',    text: 'text-rose-600',    title: 'Responsif & Ringan',       desc: 'Akses kapan saja dari perangkat apapun — laptop, tablet, maupun smartphone.' },
];
</script>

<template>
    <Head title="Selamat Datang di EduChem" />

    <div class="min-h-screen bg-slate-50 font-sans selection:bg-indigo-500 selection:text-white">

        <!-- ===== NAVBAR ===== -->
        <header :class="[
            'fixed inset-x-0 top-0 z-50 transition-all duration-300',
            activeSection === 'home'
                ? 'bg-indigo-700/80 backdrop-blur-md border-b border-white/10'
                : 'bg-white/90 backdrop-blur-md border-b border-slate-200/50 shadow-sm'
        ]">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-12" aria-label="Global">

                <!-- Logo -->
                <div class="flex lg:flex-1">
                    <Link href="#home" @click.prevent="scrollToSection('home')" class="-m-1.5 flex items-center gap-2.5 p-1.5">
                        <img
                            src="/assets/images/Logo1.png"
                            alt="EduChem Logo"
                            :class="['h-9 w-auto object-contain transition-all duration-300', activeSection === 'home' ? 'brightness-0 invert' : '']"
                        />
                    </Link>
                </div>

                <!-- Nav Links -->
                <div class="hidden md:flex items-center gap-8">
                    <Link 
                        v-for="item in navItems"
                        :key="item.id"
                        :href="`#${item.id}`"
                        @click.prevent="scrollToSection(item.id)"
                        :class="[
                            'text-sm transition-colors cursor-pointer relative pb-0.5',
                            activeSection === 'home'
                                ? activeSection === item.id ? 'text-white font-bold' : 'text-indigo-200 font-medium hover:text-white'
                                : activeSection === item.id ? 'text-indigo-600 font-bold' : 'text-slate-600 font-medium hover:text-indigo-600'
                        ]"
                    >
                        {{ item.label }}
                        <span
                            v-if="activeSection === item.id"
                            :class="['absolute -bottom-0.5 left-0 right-0 h-0.5 rounded-full', activeSection === 'home' ? 'bg-emerald-400' : 'bg-indigo-600']"
                        ></span>
                    </Link>
                </div>
                <!-- Auth Buttons -->
                <div class="flex flex-1 items-center justify-end gap-3">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route('dashboard')"
                            :class="['text-sm font-semibold transition-colors', activeSection === 'home' ? 'text-indigo-100 hover:text-white' : 'text-slate-700 hover:text-indigo-600']"
                        >
                            Dashboard <span aria-hidden="true">&rarr;</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            :class="['text-sm font-semibold transition-colors', activeSection === 'home' ? 'text-indigo-100 hover:text-white' : 'text-slate-700 hover:text-indigo-600']"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="route('register')"
                            :class="[
                                'rounded-full px-4 py-2 text-sm font-semibold transition-all shadow-sm',
                                activeSection === 'home' ? 'bg-white text-indigo-700 hover:bg-indigo-50' : 'bg-indigo-600 text-white hover:bg-indigo-700'
                            ]"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- ===== HERO ===== -->
        <section id="home" class="relative overflow-hidden pt-32 pb-16 sm:pt-40 sm:pb-24 lg:pb-32 bg-indigo-700">

            <!-- Pattern + Glow -->
            <div class="absolute inset-0 z-0" aria-hidden="true">
                <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="hex-pattern" x="0" y="0" width="60" height="52" patternUnits="userSpaceOnUse">
                            <polygon points="30,2 56,16 56,36 30,50 4,36 4,16" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="1.5"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#hex-pattern)" />
                </svg>
                <div class="absolute -top-20 -left-20 h-72 w-72 rounded-full bg-violet-600/40 blur-3xl"></div>
                <div class="absolute top-10 right-10 h-56 w-56 rounded-full bg-indigo-400/30 blur-3xl"></div>
                <div class="absolute bottom-0 right-1/3 h-64 w-64 rounded-full bg-sky-500/20 blur-3xl"></div>
            </div>

            <!-- Floating SVGs -->
            <div class="absolute top-16 right-12 lg:right-24 z-0 pointer-events-none opacity-20 animate-float">
                <svg class="w-16 h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 36 L12 52 C10 55 12 58 16 58 H48 C52 58 54 55 52 52 L42 36 Z" fill="white" opacity="0.5"/>
                    <path d="M28 8 H36 V18 L48.5 42 L52 52 C53.5 55 51.5 58 48 58 H16 C12.5 58 10.5 55 12 52 L15.5 42 L28 18 V8 Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M26 8 H38" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="28" cy="46" r="2" fill="white" opacity="0.8"/>
                    <circle cx="36" cy="42" r="3" fill="white" opacity="0.8"/>
                </svg>
            </div>
            <div class="absolute bottom-20 left-10 lg:left-24 z-0 pointer-events-none opacity-15 animate-float-delayed">
                <svg class="w-12 h-12" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line x1="16" y1="48" x2="32" y2="32" stroke="white" stroke-width="2"/>
                    <line x1="48" y1="48" x2="32" y2="32" stroke="white" stroke-width="2"/>
                    <line x1="32" y1="16" x2="32" y2="32" stroke="white" stroke-width="2"/>
                    <circle cx="32" cy="32" r="7" fill="white" opacity="0.4" stroke="white" stroke-width="2"/>
                    <circle cx="16" cy="48" r="5" fill="white" opacity="0.5"/>
                    <circle cx="48" cy="48" r="5" fill="white" opacity="0.5"/>
                    <circle cx="32" cy="16" r="4" fill="white" opacity="0.5"/>
                </svg>
            </div>

            <!-- Content -->
            <div class="mx-auto max-w-4xl px-6 lg:px-12 text-center relative z-10">
                <div data-aos="fade-up" class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-semibold text-white/90 backdrop-blur-sm">
                    <span class="flex h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                    Platform LMS Interaktif SMA
                </div>

                <h1 data-aos="fade-up" data-aos-delay="100" class="text-4xl leading-tight font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl mb-6">
                    Selamat Datang di
                    <span class="block mt-2 bg-gradient-to-r from-sky-300 via-indigo-200 to-emerald-300 bg-clip-text text-transparent animate-text-gradient pb-2">
                        Educhem-GenAI
                        <Sparkles class="inline-block w-8 h-8 text-sky-300 animate-pulse ml-1 align-middle relative -top-1" />
                    </span>
                </h1>

                <p data-aos="fade-up" data-aos-delay="200" class="mx-auto max-w-2xl text-base sm:text-lg leading-8 text-indigo-100 mb-10">
                    Tingkatkan pemahaman konsep <strong class="font-semibold text-white">Laju Reaksi</strong> melalui pendekatan
                    <strong class="font-semibold text-white">Learning Cycle 5E</strong>.
                    Belajar lebih interaktif, mendalam, dan menyenangkan bersama AI Tutor.
                </p>

                <div data-aos="fade-up" data-aos-delay="300" class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="w-full sm:w-auto rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-indigo-700 shadow-md hover:bg-indigo-50 transition-all"
                    >
                        Lanjutkan Belajar &rarr;
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('register')"
                            class="w-full sm:w-auto rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-indigo-700 shadow-md hover:bg-indigo-50 transition-all"
                        >
                            Mulai Sekarang &rarr;
                        </Link>
                        <Link
                            :href="route('login')"
                            class="w-full sm:w-auto rounded-full border border-white/30 bg-white/10 px-8 py-3.5 text-sm font-semibold text-white hover:bg-white/20 transition-all backdrop-blur-sm"
                        >
                            Sudah punya akun? Masuk
                        </Link>
                    </template>
                </div>

                <div data-aos="fade-up" data-aos-delay="400" class="mt-14 pt-8 border-t border-white/10 flex justify-center gap-10 sm:gap-16">
                    <div class="text-center">
                        <p class="text-3xl font-extrabold text-white">5</p>
                        <p class="text-xs sm:text-sm font-medium text-indigo-200 mt-1">Tahapan Belajar</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-extrabold text-emerald-300">AI</p>
                        <p class="text-xs sm:text-sm font-medium text-indigo-200 mt-1">Tutor Interaktif</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-extrabold text-sky-300">SMA</p>
                        <p class="text-xs sm:text-sm font-medium text-indigo-200 mt-1">Laju Reaksi</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== STAGES ===== -->
        <section id="stages" class="py-16 sm:py-24 bg-white border-t border-slate-100">
            <div class="mx-auto max-w-5xl px-6 lg:px-12">

                <div class="text-center mb-14" data-aos="fade-up">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600 mb-4">
                        <i class="pi pi-compass text-xs"></i>
                        Pendekatan Saintifik
                    </div>
                    <h2 class="text-3xl font-extrabold text-slate-900 sm:text-4xl mb-4">Siklus Belajar LC5E</h2>
                    <p class="text-slate-500 leading-relaxed max-w-xl mx-auto text-sm sm:text-base">
                        Model pembelajaran <strong class="text-slate-700">Learning Cycle 5E</strong> yang terbukti mampu meningkatkan nalar analitis dan pemahaman kimia secara bertahap.
                    </p>
                </div>

                <div class="relative">
                    <!-- Garis timeline -->
                    <div
                        class="absolute left-5 md:left-1/2 top-2 bottom-2 w-0.5 -translate-x-1/2 rounded-full"
                        style="background: linear-gradient(to bottom, #60a5fa 0%, #34d399 25%, #a78bfa 50%, #fbbf24 75%, #fb7185 100%)"
                    ></div>

                    <div class="space-y-6">
                        <template v-for="(stage, i) in stages" :key="i">

                            <!-- Mobile -->
                            <div class="flex items-start gap-5 md:hidden" data-aos="fade-up" :data-aos-delay="i * 100">
                                <div class="relative z-10 mt-0.5">
                                    <div :class="`h-10 w-10 shrink-0 rounded-full border-4 border-white shadow-md flex items-center justify-center ${stage.bg} ${stage.text}`">
                                        <i :class="`pi ${stage.icon} text-sm`"></i>
                                    </div>
                                </div>
                                <div class="flex-1 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                                    <span :class="`inline-block text-[10px] font-bold uppercase tracking-widest ${stage.text} mb-2`">Tahap {{ i + 1 }}</span>
                                    <h3 class="text-base font-extrabold text-slate-900 mb-1">{{ stage.title }}</h3>
                                    <p class="text-xs text-slate-500 leading-relaxed mb-3">{{ stage.desc }}</p>
                                    <component :is="stage.extra" />
                                </div>
                            </div>

                            <!-- Desktop zigzag -->
                            <div class="hidden md:grid md:grid-cols-[1fr_80px_1fr] items-center">

                                <!-- Kolom kiri (tahap genap: 2, 4) -->
                                <div class="flex justify-end pr-8">
                                    <div
                                        v-if="i % 2 !== 0"
                                        data-aos="fade-right"
                                        :data-aos-delay="i * 100"
                                        :class="`w-full max-w-xs rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:shadow-md transition-all duration-300 group hover:border-${stage.color}-200`"
                                    >
                                        <span :class="`inline-block text-[10px] font-bold uppercase tracking-widest ${stage.text} mb-3`">Tahap {{ i + 1 }}</span>
                                        <div class="flex items-start gap-3">
                                            <div :class="`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${stage.bg} ${stage.text} group-hover:scale-105 transition-transform`">
                                                <i :class="`pi ${stage.icon} text-base`"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-extrabold text-slate-900 mb-1">{{ stage.title }}</h3>
                                                <p class="text-xs text-slate-500 leading-relaxed">{{ stage.desc }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4"><component :is="stage.extra" /></div>
                                    </div>
                                </div>

                                <!-- Dot tengah -->
                                <div class="flex justify-center relative z-10" data-aos="zoom-in" :data-aos-delay="i * 100">
                                    <div :class="`h-12 w-12 rounded-full border-4 border-white shadow-lg flex items-center justify-center ${stage.bg} ${stage.text}`">
                                        <i :class="`pi ${stage.icon} text-base`"></i>
                                    </div>
                                </div>

                                <!-- Kolom kanan (tahap ganjil: 1, 3, 5) -->
                                <div class="flex justify-start pl-8">
                                    <div
                                        v-if="i % 2 === 0"
                                        data-aos="fade-left"
                                        :data-aos-delay="i * 100"
                                        :class="`w-full max-w-xs rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:shadow-md transition-all duration-300 group hover:border-${stage.color}-200`"
                                    >
                                        <span :class="`inline-block text-[10px] font-bold uppercase tracking-widest ${stage.text} mb-3`">Tahap {{ i + 1 }}</span>
                                        <div class="flex items-start gap-3">
                                            <div :class="`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${stage.bg} ${stage.text} group-hover:scale-105 transition-transform`">
                                                <i :class="`pi ${stage.icon} text-base`"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-extrabold text-slate-900 mb-1">{{ stage.title }}</h3>
                                                <p class="text-xs text-slate-500 leading-relaxed">{{ stage.desc }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4"><component :is="stage.extra" /></div>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== FEATURES ===== -->
        <section id="features" class="py-16 sm:py-24 bg-slate-50 border-t border-slate-200/60">
            <div class="mx-auto max-w-7xl px-6 lg:px-12">
                <div class="text-center mb-16" data-aos="fade-up">
                    <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-2">Kenapa EduChem?</p>
                    <h2 class="text-3xl font-extrabold text-slate-900 sm:text-4xl">Fasilitas Penunjang Belajarmu</h2>
                    <p class="mt-4 text-base text-slate-500 max-w-xl mx-auto">Kombinasi metode pembelajaran terbukti dan kecerdasan buatan untuk pengalaman belajar terbaik.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(feat, index) in features"
                        :key="feat.title"
                        data-aos="fade-up"
                        :data-aos-delay="index * 100"
                        class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm hover:shadow-md hover:border-indigo-100 transition-all duration-300 group"
                    >
                        <div :class="`h-12 w-12 rounded-xl flex items-center justify-center mb-5 ${feat.bg} ${feat.text} group-hover:scale-110 transition-transform`">
                            <component :is="feat.icon" class="h-6 w-6" :stroke-width="2" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">{{ feat.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== ABOUT ===== -->
        <section id="about" class="py-16 sm:py-24 bg-white border-t border-slate-100">
            <div class="mx-auto max-w-7xl px-6 lg:px-12">
                <div class="lg:grid lg:grid-cols-2 gap-12 items-center">

                    <div class="mb-10 lg:mb-0" data-aos="fade-right">
                        <h2 class="text-3xl font-extrabold text-slate-900 mb-5">Tentang EduChem-GenAI</h2>
                        <p class="text-slate-600 leading-relaxed mb-4 text-justify">
                            <strong>EduChem-GenAI</strong> adalah inovasi platform Learning Management System (LMS) yang dirancang khusus untuk memecahkan kesulitan siswa SMA dalam memahami materi kimia yang abstrak, khususnya pada topik Laju Reaksi.
                        </p>
                        <p class="text-slate-600 leading-relaxed text-justify">
                            Dengan mengintegrasikan Kecerdasan Buatan (AI) sebagai tutor personal 24/7, platform ini membimbing kamu mulai dari proses eksplorasi masalah, penyusunan eksperimen virtual, hingga evaluasi akhir — memastikan konsep kimia benar-benar dipahami, bukan sekadar dihafal.
                        </p>
                    </div>

                    <div data-aos="fade-left" class="rounded-3xl bg-gradient-to-br from-indigo-600 to-violet-600 p-8 sm:p-12 text-center shadow-xl relative overflow-hidden">
                        <div class="absolute inset-0 opacity-10">
                            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <pattern id="about-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                                        <circle cx="20" cy="20" r="1.5" fill="white"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" fill="url(#about-pattern)" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3 relative z-10">Siap Belajar Cara Baru?</h3>
                        <p class="text-indigo-100 text-sm sm:text-base mb-8 relative z-10">Dapatkan akses penuh ke materi LC5E, AI Tutor, dan evaluasi adaptif sekarang juga.</p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 relative z-10">
                            <Link
                                v-if="$page.props.auth?.user"
                                :href="route('dashboard')"
                                class="w-full sm:w-auto rounded-full bg-white px-8 py-3 text-sm font-semibold text-indigo-600 hover:bg-indigo-50 transition-all shadow-md"
                            >
                                Buka Dashboard &rarr;
                            </Link>
                            <template v-else>
                                <Link :href="route('register')" class="w-full sm:w-auto rounded-full bg-white px-8 py-3 text-sm font-semibold text-indigo-600 hover:bg-indigo-50 transition-all shadow-md">
                                    Daftar Gratis
                                </Link>
                                <Link :href="route('login')" class="w-full sm:w-auto rounded-full border border-white/30 bg-white/10 px-8 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all">
                                    Masuk
                                </Link>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer class="bg-slate-900 text-slate-400 py-8">
            <div class="mx-auto max-w-7xl px-6 lg:px-12 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <img src="/assets/images/Logo1.png" alt="EduChem Logo" class="h-7 w-auto object-contain brightness-0 invert opacity-70" />
                </div>
                <p class="text-xs text-center">© {{ new Date().getFullYear() }} EduChem. Platform Pembelajaran Kimia SMA Berbasis AI.</p>
                <div>
                    <p class="text-xs text-center">
                        Developed by <a href="https://instagram.com/scalenix.studio" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition-colors">Scalenix Studio</a>
                    </p>
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
html {
    scroll-behavior: smooth;
    scroll-padding-top: 80px;
}

@keyframes spin-slow { to { transform: rotate(360deg); } }
.animate-spin-slow { animation: spin-slow 8s linear infinite; }

@keyframes shine {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
.animate-text-gradient { background-size: 200% auto; animation: shine 6s linear infinite; }

@keyframes float-gentle {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-12px) rotate(6deg); }
}
@keyframes float-reverse {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(-8deg); }
}
.animate-float { animation: float-gentle 6s ease-in-out infinite; }
.animate-float-delayed { animation: float-reverse 8s ease-in-out infinite 2s; }
</style>