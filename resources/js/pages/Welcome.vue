<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: null });
</script>

<template>
    <Head title="Selamat Datang di EduChem" />

    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/50 font-sans selection:bg-indigo-500 selection:text-white"
    >
        <header class="absolute inset-x-0 top-0 z-50">
            <nav
                class="mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-12"
                aria-label="Global"
            >
                <div class="flex lg:flex-1">
                    <a href="#" class="-m-1.5 flex items-center gap-2.5 p-1.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-sm"
                        >
                            E
                        </div>
                        <span
                            class="text-xl font-bold tracking-tight text-slate-900"
                            >EduChem<span class="text-indigo-600">.</span></span
                        >
                    </a>
                </div>

                <div class="flex flex-1 items-center justify-end gap-4">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route('dashboard')"
                            class="text-sm leading-6 font-semibold text-slate-700 transition-colors hover:text-indigo-600"
                        >
                            Masuk ke Dashboard
                            <span aria-hidden="true">&rarr;</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm leading-6 font-semibold text-slate-700 transition-colors hover:text-indigo-600"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <div
            class="relative overflow-hidden pt-24 pb-16 sm:pt-32 sm:pb-24 lg:pb-32"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-12">
                <div class="items-center lg:grid lg:grid-cols-12 lg:gap-16">
                    <div class="z-10 text-center lg:col-span-6 lg:text-left">
                        <div
                            class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-[13px] font-semibold text-indigo-600"
                        >
                            <span
                                class="flex h-2 w-2 animate-pulse rounded-full bg-indigo-600"
                            ></span>
                            Platform LMS Interaktif SMA
                        </div>

                        <h1
                            class="mb-6 text-4xl leading-[1.15] font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl"
                        >
                            Jelajahi Dunia Kimia dengan Metode
                            <span
                                class="bg-gradient-to-r from-indigo-600 to-emerald-500 bg-clip-text text-transparent"
                                >LC5E</span
                            >
                        </h1>

                        <p
                            class="mx-auto mb-8 max-w-2xl text-lg leading-8 text-slate-600 lg:mx-0"
                        >
                            Tingkatkan pemahaman konsep Sistem Periodik Unsur
                            melalui pendekatan pembelajaran
                            <b>Learning Cycle 5E (Engage, Explore, Explain, Elaborate, Evaluate)</b>. Belajar
                            menjadi lebih interaktif, mendalam, dan
                            menyenangkan.
                        </p>

                        <div
                            class="flex flex-col items-center justify-center gap-4 sm:flex-row lg:justify-start"
                        >
                            <Link
                                v-if="$page.props.auth?.user"
                                :href="route('dashboard')"
                                class="w-full rounded-full bg-indigo-600 px-8 py-3.5 text-center text-sm font-semibold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-700 hover:shadow-lg sm:w-auto"
                            >
                                Lanjutkan Belajar
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('register')"
                                    class="w-full rounded-full bg-indigo-600 px-8 py-3.5 text-center text-sm font-semibold text-white shadow-md shadow-indigo-600/20 transition-all hover:bg-indigo-700 hover:shadow-lg sm:w-auto"
                                >
                                    Mulai Sekarang &rarr;
                                </Link>
                                <a
                                    href="#features"
                                    class="w-full rounded-full border border-slate-200 bg-white px-8 py-3.5 text-center text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 sm:w-auto"
                                >
                                    Pelajari Metode
                                </a>
                            </template>
                        </div>

                        <div
                            class="mt-10 flex items-center justify-center gap-6 border-t border-slate-200/60 pt-6 lg:justify-start"
                        >
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900">
                                    100%
                                </h3>
                                <p
                                    class="mt-1 text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Sesuai Kurikulum
                                </p>
                            </div>
                            <div class="h-10 w-px bg-slate-200"></div>
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900">
                                    Interaktif
                                </h3>
                                <p
                                    class="mt-1 text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Simulasi & AI
                                </p>
                            </div>
                        </div>

                        <!-- Mobile View: Vertical list of stages -->
                        <div class="mt-12 space-y-4 md:hidden text-left border-t border-slate-200/60 pt-8">
                            <h3 class="text-lg font-extrabold text-slate-900 mb-4 text-center">
                                5 Tahapan Belajar LC5E
                            </h3>
                            
                            <!-- 1. Engage -->
                            <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                        <i class="pi pi-lightbulb text-sm"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">1. Engage</h4>
                                </div>
                                <p class="text-xs text-slate-600">Fokuskan perhatian dan ketertarikanmu pada materi kimia.</p>
                            </div>

                            <!-- 2. Explore -->
                            <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                        <i class="pi pi-compass text-sm"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">2. Explore</h4>
                                </div>
                                <p class="text-xs text-slate-600">Jelajahi konsep melalui eksperimen & simulasi interaktif.</p>
                            </div>

                            <!-- 3. Explain -->
                            <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                        <i class="pi pi-comment text-sm"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">3. Explain</h4>
                                </div>
                                <p class="text-xs text-slate-600">Jelaskan pemahamanmu dibimbing oleh AI Tutor.</p>
                            </div>

                            <!-- 4. Elaborate -->
                            <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                        <i class="pi pi-cog text-sm"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">4. Elaborate</h4>
                                </div>
                                <p class="text-xs text-slate-600">Terapkan konsep pada tantangan baru & studi kasus nyata.</p>
                            </div>

                            <!-- 5. Evaluate -->
                            <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                        <i class="pi pi-check-circle text-sm"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900">5. Evaluate</h4>
                                </div>
                                <p class="text-xs text-slate-600">Uji pemahaman akhirmu dan lihat progres belajarmu.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-16 hidden md:block lg:col-span-6 lg:mt-0">
                        <div class="relative mx-auto w-full max-w-xl h-[620px]">
                            <!-- Glow background -->
                            <div
                                class="absolute top-1/2 left-1/2 h-[400px] w-[400px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-300/20 mix-blend-multiply blur-3xl"
                            ></div>

                            <!-- SVG Background Staircase Path -->
                            <svg class="absolute inset-0 z-0 w-full h-full select-none pointer-events-none" viewBox="0 0 500 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="stair-gradient" x1="0%" y1="100%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#3b82f6" />
                                        <stop offset="25%" stop-color="#10b981" />
                                        <stop offset="50%" stop-color="#8b5cf6" />
                                        <stop offset="75%" stop-color="#f59e0b" />
                                        <stop offset="100%" stop-color="#f43f5e" />
                                    </linearGradient>
                                    <filter id="glow-effect" x="-20%" y="-20%" width="140%" height="140%">
                                        <feGaussianBlur stdDeviation="6" result="blur" />
                                        <feComposite in="SourceGraphic" in2="blur" operator="over" />
                                    </filter>
                                </defs>
                                
                                <!-- Shadow/glowing duplicate of path for depth -->
                                <path d="M 125 510 H 190 V 405 H 255 V 300 H 320 V 195 H 385 V 90" stroke="url(#stair-gradient)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" opacity="0.15" filter="url(#glow-effect)" />
                                
                                <!-- Main staircase dashed path -->
                                <path d="M 125 510 H 190 V 405 H 255 V 300 H 320 V 195 H 385 V 90" stroke="url(#stair-gradient)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="8 6" />
                                
                                <!-- Glowing stage connecting points -->
                                <circle cx="125" cy="510" r="6" fill="#3b82f6" class="animate-pulse" />
                                <circle cx="190" cy="405" r="6" fill="#10b981" class="animate-pulse" />
                                <circle cx="255" cy="300" r="6" fill="#8b5cf6" class="animate-pulse" />
                                <circle cx="320" cy="195" r="6" fill="#f59e0b" class="animate-pulse" />
                                <circle cx="385" cy="90" r="6" fill="#f43f5e" class="animate-pulse" />
                            </svg>

                            <!-- TAHAP 1: ENGAGE -->
                            <div
                                class="absolute z-50 w-[230px] rounded-2xl border border-slate-100 bg-white/95 p-4 shadow-lg shadow-slate-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-blue-500/10 group"
                                style="bottom: 40px; left: 10px;"
                            >
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100"
                                    >
                                        <i class="pi pi-lightbulb text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Tahap 1
                                        </h4>
                                        <h3 class="text-sm font-extrabold text-slate-900 leading-tight">
                                            Engage
                                        </h3>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                                    Fokuskan perhatian dan ketertarikanmu pada materi kimia.
                                </p>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full w-3/4 bg-blue-500 rounded-full"></div>
                                </div>
                            </div>

                            <!-- TAHAP 2: EXPLORE -->
                            <div
                                class="absolute z-40 w-[230px] rounded-2xl border border-slate-100 bg-white/95 p-4 shadow-lg shadow-slate-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-emerald-500/10 group"
                                style="bottom: 145px; left: 75px;"
                            >
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-100"
                                    >
                                        <i class="pi pi-compass text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Tahap 2
                                        </h4>
                                        <h3 class="text-sm font-extrabold text-slate-900 leading-tight">
                                            Explore
                                        </h3>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                                    Jelajahi konsep melalui eksperimen & simulasi interaktif.
                                </p>
                                <div class="flex gap-2">
                                    <div class="flex h-10 flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 text-emerald-300 transition-colors group-hover:bg-emerald-50/50 group-hover:text-emerald-400">
                                        <i class="pi pi-chart-bar text-sm"></i>
                                    </div>
                                    <div class="flex h-10 flex-1 items-center justify-center rounded-lg border border-emerald-100 bg-emerald-50/50 text-emerald-400">
                                        <i class="pi pi-chart-line text-sm"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- TAHAP 3: EXPLAIN -->
                            <div
                                class="absolute z-30 w-[230px] rounded-2xl border border-slate-100 bg-white/95 p-4 shadow-lg shadow-slate-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-purple-200 hover:shadow-purple-500/10 group"
                                style="bottom: 250px; left: 140px;"
                            >
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-100"
                                    >
                                        <i class="pi pi-comment text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Tahap 3
                                        </h4>
                                        <h3 class="text-sm font-extrabold text-slate-900 leading-tight">
                                            Explain
                                        </h3>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                                    Jelaskan pemahamanmu dibimbing oleh AI Tutor.
                                </p>
                                <div class="space-y-1.5 py-1">
                                    <div class="h-1.5 w-full rounded-full bg-slate-100"></div>
                                    <div class="h-1.5 w-4/5 rounded-full bg-slate-100"></div>
                                </div>
                            </div>

                            <!-- TAHAP 4: ELABORATE -->
                            <div
                                class="absolute z-20 w-[230px] rounded-2xl border border-slate-100 bg-white/95 p-4 shadow-lg shadow-slate-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-amber-500/10 group"
                                style="bottom: 355px; left: 205px;"
                            >
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-100"
                                    >
                                        <i class="pi pi-cog text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Tahap 4
                                        </h4>
                                        <h3 class="text-sm font-extrabold text-slate-900 leading-tight">
                                            Elaborate
                                        </h3>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                                    Terapkan konsep pada tantangan baru & studi kasus nyata.
                                </p>
                                <div class="flex items-center gap-2 rounded-lg border border-amber-100 bg-amber-50/50 p-2 text-amber-700">
                                    <i class="pi pi-cog animate-spin-slow text-xs"></i>
                                    <span class="text-[10px] font-semibold">Penerapan Mandiri</span>
                                </div>
                            </div>

                            <!-- TAHAP 5: EVALUATE -->
                            <div
                                class="absolute z-10 w-[230px] rounded-2xl border border-slate-100 bg-white/95 p-4 shadow-lg shadow-slate-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-rose-500/10 group"
                                style="bottom: 460px; left: 270px;"
                            >
                                <div class="mb-3 flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 transition-colors group-hover:bg-rose-100"
                                    >
                                        <i class="pi pi-check-circle text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            Tahap 5
                                        </h4>
                                        <h3 class="text-sm font-extrabold text-slate-900 leading-tight">
                                            Evaluate
                                        </h3>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                                    Uji pemahaman akhirmu dan lihat progres belajarmu.
                                </p>
                                <div class="flex items-center justify-between rounded-lg bg-rose-50/60 px-2.5 py-1.5">
                                    <span class="text-[10px] font-semibold text-rose-700">Skor Evaluasi</span>
                                    <span class="text-xs font-bold text-rose-600">100%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Transisi halus untuk scroll */
html {
    scroll-behavior: smooth;
}

@keyframes climb-float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-8px) rotate(2deg);
    }
}

.animate-climb {
    animation: climb-float 3s ease-in-out infinite;
}

@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}
</style>
