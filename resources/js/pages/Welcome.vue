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
        <header class="fixed inset-x-0 top-0 z-50 transition-all duration-300 bg-white/90 backdrop-blur-md border-b border-slate-200/50 shadow-sm">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-12" aria-label="Global">

                <!-- Logo -->
                <div class="flex lg:flex-1">
                    <Link href="#home" @click.prevent="scrollToSection('home')" class="-m-1.5 flex items-center gap-2.5 p-1.5">
                        <img
                            src="/assets/images/Logo1.png"
                            alt="EduChem Logo"
                            class="h-9 w-auto object-contain transition-all duration-300"
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
                            activeSection === item.id ? 'text-blue-600 font-bold' : 'text-slate-600 font-medium hover:text-blue-600'
                        ]"
                    >
                        {{ item.label }}
                        <span
                            v-if="activeSection === item.id"
                            class="absolute -bottom-0.5 left-0 right-0 h-0.5 rounded-full bg-blue-600"
                        ></span>
                    </Link>
                </div>
                <!-- Auth Buttons -->
                <div class="flex flex-1 items-center justify-end gap-3">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route('dashboard')"
                            class="text-sm font-semibold transition-colors text-slate-700 hover:text-blue-600"
                        >
                            Dashboard <span aria-hidden="true">&rarr;</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-semibold transition-colors text-slate-700 hover:text-blue-600"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-full px-4 py-2 text-sm font-semibold transition-all shadow-sm bg-blue-600 text-white hover:bg-blue-700"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- ===== HERO ===== -->
        <section id="home" class="relative overflow-hidden pt-32 pb-16 sm:pt-40 sm:pb-24 lg:pb-32 bg-gradient-to-br from-emerald-400 to-blue-600">

            <!-- Hexagonal SVG Pattern -->
            <div class="absolute inset-0 z-0 pointer-events-none animate-hex-shimmer" aria-hidden="true">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="hex" width="28" height="48.4974" patternUnits="userSpaceOnUse" patternTransform="scale(1.5)">
                            <path d="M14 0L28 8.0829v16.1658L14 32.3316 0 24.2487V8.0829z M28 24.2487l14 8.0829v16.1658L28 56.5803l-14-8.0829V32.3316z M0 24.2487l-14 8.0829v16.1658L0 56.5803l14-8.0829V32.3316z" fill="none" stroke="white" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#hex)" />
                </svg>
            </div>

            <!-- Pattern + Glow -->
            <div class="absolute inset-0 z-0" aria-hidden="true">
                <div class="absolute -top-20 -left-20 h-72 w-72 rounded-full bg-emerald-600/40 blur-3xl"></div>
                <div class="absolute top-10 right-10 h-56 w-56 rounded-full bg-blue-400/30 blur-3xl"></div>
                <div class="absolute bottom-0 right-1/3 h-64 w-64 rounded-full bg-cyan-500/20 blur-3xl"></div>
            </div>

            <!-- Content -->
            <div class="mx-auto max-w-4xl px-6 lg:px-12 text-center relative z-10">
                <div data-aos="fade-up" class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-semibold text-white/90 backdrop-blur-sm">
                    <span class="flex h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                    Platform LMS Interaktif SMA
                </div>

                <h1 data-aos="fade-up" data-aos-delay="100" class="text-4xl leading-tight font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl mb-6">
                    Selamat Datang di
                    <span class="block mt-2 pb-2 font-black drop-shadow-[0_2px_10px_rgba(255,255,255,0.4)]">
                        <span class="text-blue-600">Educhem_</span><span class="text-yellow-300">Gen</span><span class="text-teal-400">AI</span>
                        <Sparkles class="inline-block w-8 h-8 text-yellow-200 animate-pulse ml-1 align-middle relative -top-1" />
                    </span>
                </h1>

                <p data-aos="fade-up" data-aos-delay="200" class="mx-auto max-w-2xl text-base sm:text-lg leading-8 text-slate-100 mb-10">
                    <span class="block font-semibold mb-2">Eksplorasi Dunia Kimia: Lebih Mendalam dan Menyenangkan bersama AI.</span>
                    Nikmati pengalaman belajar yang mendalam, interaktif, kontekstual dan menyenangkan dengan platform terintegrasi Generative AI.
                </p>

                <div data-aos="fade-up" data-aos-delay="300" class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="w-full sm:w-auto rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-blue-600 shadow-md hover:bg-blue-50 transition-all"
                    >
                        Lanjutkan Belajar &rarr;
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('register')"
                            class="w-full sm:w-auto rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-blue-600 shadow-md hover:bg-blue-50 transition-all"
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
                    <p class="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-2">Kenapa EduChem?</p>
                    <h2 class="text-3xl font-extrabold text-slate-900 sm:text-4xl">Fasilitas Penunjang Belajarmu</h2>
                    <p class="mt-4 text-base text-slate-500 max-w-xl mx-auto">Kombinasi metode pembelajaran terbukti dan kecerdasan buatan untuk pengalaman belajar terbaik.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(feat, index) in features"
                        :key="feat.title"
                        data-aos="fade-up"
                        :data-aos-delay="index * 100"
                        class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm hover:shadow-md hover:border-blue-100 transition-all duration-300 group"
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
                <div class="lg:grid lg:grid-cols-2 gap-16 items-center">

                    <!-- Left: Text Content -->
                    <div class="mb-10 lg:mb-0" data-aos="fade-right">
                        <!-- Badge -->
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-4 py-1.5 text-xs font-semibold text-blue-600 uppercase tracking-wider mb-5 border border-blue-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                            Tentang Platform
                        </span>

                        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-6 leading-tight">
                            Tentang <span class="bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-500 bg-clip-text text-transparent">Educhem_</span><span class="text-yellow-500">Gen</span><span class="bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-500 bg-clip-text text-transparent">AI</span>
                        </h2>

                        <p class="text-slate-600 leading-relaxed mb-4 text-justify text-[15px]">
                            <strong class="font-extrabold"><span class="bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-500 bg-clip-text text-transparent">Educhem_</span><span class="text-yellow-500">Gen</span><span class="bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-500 bg-clip-text text-transparent">AI</span></strong> adalah inovasi platform
                            <span class="font-semibold text-blue-600">Learning Management System (LMS)</span>
                            yang dirancang untuk memfasilitasi pembelajaran kimia bermakna melalui integrasi pendekatan
                            <span class="font-semibold text-emerald-600">Socioscientific Issues (SSI)</span>
                            dan <span class="font-semibold text-blue-600">Kecerdasan Buatan (AI)</span>.
                        </p>
                        <p class="text-slate-600 leading-relaxed mb-6 text-justify text-[15px]">
                            Platform ini menghadirkan isu-isu sosiosaintifik autentik dan kontekstual sebagai pintu masuk pembelajaran, mendorong siswa tidak hanya memahami konsep kimia secara konseptual, tetapi juga mengasah kemampuan berargumentasi, berpikir kritis, dan mempertimbangkan dimensi etis-sosial dari fenomena ilmiah di sekitar mereka.
                        </p>
                        <p class="text-slate-600 leading-relaxed mb-8 text-justify text-[15px]">
                            Dengan AI sebagai tutor personal <span class="font-semibold text-blue-600">24/7</span>, <span class="font-extrabold"><span class="bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-500 bg-clip-text text-transparent">Educhem_</span><span class="text-yellow-500">Gen</span><span class="bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-500 bg-clip-text text-transparent">AI</span></span> membimbing siswa secara adaptif mulai dari eksplorasi isu, penyusunan argumen berbasis bukti, eksperimen virtual, hingga refleksi dan evaluasi akhir — menjadikan proses belajar lebih <span class="font-semibold text-emerald-600">personal, interaktif</span>, dan relevan dengan kehidupan nyata.
                        </p>

                        <!-- Icon Bullet Points -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="flex items-center gap-2.5 rounded-xl bg-blue-50 px-4 py-3 border border-blue-100">
                                <div class="flex-shrink-0 h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </div>
                                <span class="text-xs font-semibold text-blue-700">Berbasis SSI</span>
                            </div>
                            <div class="flex items-center gap-2.5 rounded-xl bg-emerald-50 px-4 py-3 border border-emerald-100">
                                <div class="flex-shrink-0 h-8 w-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                </div>
                                <span class="text-xs font-semibold text-emerald-700">AI Adaptif</span>
                            </div>
                            <div class="flex items-center gap-2.5 rounded-xl bg-violet-50 px-4 py-3 border border-violet-100">
                                <div class="flex-shrink-0 h-8 w-8 rounded-lg bg-violet-100 flex items-center justify-center">
                                    <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <span class="text-xs font-semibold text-violet-700">Berpikir Kritis</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: CTA Card -->
                    <div data-aos="fade-left" class="rounded-3xl bg-gradient-to-br from-blue-600 via-blue-700 to-emerald-600 p-8 sm:p-10 shadow-2xl relative overflow-hidden">
                        <!-- Background pattern -->
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
                        <!-- Glow blobs -->
                        <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-emerald-400/30 blur-3xl"></div>
                        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-blue-400/30 blur-3xl"></div>

                        <div class="relative z-10">
                            <!-- Stats row -->


                            <div class="text-center pt-10 pb-10">
                                <h3 class="text-xl sm:text-2xl font-bold text-white mb-2">Siap Belajar Cara Baru?</h3>
                                <p class="text-blue-100 text-sm mb-7 leading-relaxed">
                                    Dapatkan akses penuh ke materi kimia, AI Tutor, dan evaluasi adaptif sekarang juga.
                                </p>
                                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                                    <Link
                                        v-if="$page.props.auth?.user"
                                        :href="route('dashboard')"
                                        class="w-full sm:w-auto rounded-full bg-white px-8 py-3 text-sm font-semibold text-blue-600 hover:bg-blue-50 transition-all shadow-md"
                                    >
                                        Buka Dashboard &rarr;
                                    </Link>
                                    <template v-else>
                                        <Link :href="route('register')" class="w-full sm:w-auto rounded-full bg-white px-8 py-3 text-sm font-semibold text-blue-600 hover:bg-blue-50 transition-all shadow-md">
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

                </div>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer class="bg-slate-900 text-slate-400 py-12 lg:py-16 border-t border-slate-800">
            <div class="mx-auto max-w-7xl px-6 lg:px-12">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-12 lg:gap-8 mb-12">
                    <!-- Column 1: Brand & About -->
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-2.5 mb-6">
                            <img src="/assets/images/Logo1.png" alt="EduChem Logo" class="h-12 w-auto object-contain brightness-0 invert opacity-90" />
                        </div>
                        <p class="text-sm leading-relaxed text-slate-400 mb-6 max-w-md">
                            Platform Learning Management System (LMS) inovatif terintegrasi AI, dirancang untuk memudahkan siswa SMA dalam memahami dan menguasai konsep kimia yang abstrak secara interaktif.
                        </p>
                        <!-- Social Media -->
                        <div class="flex items-center gap-4">
                            <a href="#" class="h-10 w-10 flex items-center justify-center rounded-full bg-slate-800 text-slate-400 hover:bg-blue-600 hover:text-white transition-all"><i class="pi pi-instagram"></i></a>
                            <a href="#" class="h-10 w-10 flex items-center justify-center rounded-full bg-slate-800 text-slate-400 hover:bg-blue-600 hover:text-white transition-all"><i class="pi pi-youtube"></i></a>
                            <a href="#" class="h-10 w-10 flex items-center justify-center rounded-full bg-slate-800 text-slate-400 hover:bg-blue-600 hover:text-white transition-all"><i class="pi pi-twitter"></i></a>
                        </div>
                    </div>

                    <!-- Column 2: Navigasi -->
                    <div>
                        <h4 class="text-white font-semibold mb-6 uppercase tracking-wider text-sm">Navigasi</h4>
                        <ul class="space-y-3">
                            <li><Link href="#home" @click.prevent="scrollToSection('home')" class="text-sm hover:text-blue-400 transition-colors">Beranda</Link></li>
                            <li><Link href="#stages" @click.prevent="scrollToSection('stages')" class="text-sm hover:text-blue-400 transition-colors">Tahapan Belajar</Link></li>
                            <li><Link href="#features" @click.prevent="scrollToSection('features')" class="text-sm hover:text-blue-400 transition-colors">Fasilitas</Link></li>
                            <li><Link href="#about" @click.prevent="scrollToSection('about')" class="text-sm hover:text-blue-400 transition-colors">Tentang EduChem</Link></li>
                            <li><a href="mailto:educhem.gen.ai@gmail.com" class="text-sm hover:text-blue-400 transition-colors">Pusat Bantuan</a></li>
                        </ul>
                    </div>

                    <!-- Column 3: Tim Pengembang -->
                    <div class="md:col-span-2">
                        <h4 class="text-white font-semibold mb-6 uppercase tracking-wider text-sm">Tim Pengembang</h4>
                        <ul class="space-y-3">
                            <li><a href="mailto:oktavia.sulistina.fmipa@um.ac.id" class="text-sm text-slate-400 hover:text-blue-400 transition-colors block">Dr. Oktavia Sulistina, S.Pd., M.Pd.</a></li>
                            <li><a href="mailto:m.muchson.fmipa@um.ac.id" class="text-sm text-slate-400 hover:text-blue-400 transition-colors block">M. Muchson, S.Pd., M.Pd., Ph.D.</a></li>
                            <li><a href="mailto:nur.setiawan.fmipa@um.ac.id" class="text-sm text-slate-400 hover:text-blue-400 transition-colors block">Nur Candra Eka Setiawan, S.Pd., M.Pd., Ph.D.</a></li>
                            <li><a href="mailto:arum.setyaningsih.fmipa@um.ac.id" class="text-sm text-slate-400 hover:text-blue-400 transition-colors block">Dr. Arum Setyaningsih, S.Pd., M.Pd.</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="pt-8 border-t border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-xs">© {{ new Date().getFullYear() }} EduChem-GenAI. Hak Cipta Dilindungi.</p>
                    <p class="text-xs flex items-center gap-1">
                        Developed with <i class="pi pi-heart-fill text-rose-500 text-xs mx-1"></i> by 
                        <a href="mailto:educhem.gen.ai@gmail.com" class="text-blue-400 hover:text-blue-300 font-medium transition-colors">EduChem_GenAI team</a>
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

@keyframes shimmer-mask {
    0% { -webkit-mask-position: -200% 0; mask-position: -200% 0; }
    100% { -webkit-mask-position: 200% 0; mask-position: 200% 0; }
}
.animate-hex-shimmer {
    -webkit-mask-image: linear-gradient(135deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0) 100%);
    mask-image: linear-gradient(135deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0) 100%);
    -webkit-mask-size: 200% 100%;
    mask-size: 200% 100%;
    animation: shimmer-mask 6s infinite linear;
}
</style>