<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: null });
</script>

<template>
    <Head title="Selamat Datang di EduChem" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/50 font-sans selection:bg-indigo-500 selection:text-white">
        
        <header class="absolute inset-x-0 top-0 z-50">
            <nav class="flex items-center justify-between p-6 lg:px-12 max-w-7xl mx-auto" aria-label="Global">
                <div class="flex lg:flex-1">
                    <a href="#" class="-m-1.5 p-1.5 flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 shadow-sm text-white font-bold text-lg">
                            E
                        </div>
                        <span class="font-bold text-xl tracking-tight text-slate-900">EduChem<span class="text-indigo-600">.</span></span>
                    </a>
                </div>
                
                <div class="flex flex-1 justify-end items-center gap-4">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route('dashboard')"
                            class="text-sm font-semibold leading-6 text-slate-700 hover:text-indigo-600 transition-colors"
                        >
                            Masuk ke Dashboard <span aria-hidden="true">&rarr;</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-semibold leading-6 text-slate-700 hover:text-indigo-600 transition-colors"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <div class="relative pt-24 pb-16 sm:pt-32 sm:pb-24 lg:pb-32 overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 lg:px-12">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                    
                    <div class="lg:col-span-6 text-center lg:text-left z-10">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 text-[13px] font-semibold mb-6">
                            <span class="flex h-2 w-2 rounded-full bg-indigo-600 animate-pulse"></span>
                            Platform LMS Interaktif SMA
                        </div>
                        
                        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl mb-6 leading-[1.15]">
                            Jelajahi Dunia Kimia dengan Metode <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-emerald-500">POE</span>
                        </h1>
                        
                        <p class="text-lg leading-8 text-slate-600 mb-8 max-w-2xl mx-auto lg:mx-0">
                            Tingkatkan pemahaman konsep Sistem Periodik Unsur melalui pendekatan pembelajaran <b>Predict, Observe, dan Explain</b>. Belajar menjadi lebih interaktif, mendalam, dan menyenangkan.
                        </p>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            <Link
                                v-if="$page.props.auth?.user"
                                :href="route('dashboard')"
                                class="w-full sm:w-auto rounded-full bg-indigo-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-700 hover:shadow-lg transition-all text-center"
                            >
                                Lanjutkan Belajar
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('register')"
                                    class="w-full sm:w-auto rounded-full bg-indigo-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-700 hover:shadow-lg transition-all text-center"
                                >
                                    Mulai Sekarang &rarr;
                                </Link>
                                <a
                                    href="#features"
                                    class="w-full sm:w-auto rounded-full bg-white border border-slate-200 px-8 py-3.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all text-center"
                                >
                                    Pelajari Metode
                                </a>
                            </template>
                        </div>

                        <div class="mt-10 flex items-center justify-center lg:justify-start gap-6 border-t border-slate-200/60 pt-6">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900">100%</h3>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">Sesuai Kurikulum</p>
                            </div>
                            <div class="h-10 w-px bg-slate-200"></div>
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900">Interaktif</h3>
                                <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mt-1">Simulasi & AI</p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-6 mt-16 lg:mt-0 hidden md:block">
                        <div class="relative w-full max-w-lg mx-auto">
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-indigo-300/30 blur-3xl rounded-full mix-blend-multiply"></div>
                            
                            <div class="relative z-10 bg-white p-6 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 transform -rotate-3 hover:rotate-0 transition-transform duration-300 ml-12">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <i class="pi pi-question-circle text-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">Tahap 1: Predict</h4>
                                        <p class="text-xs text-slate-500">Uji hipotesis awalmu</p>
                                    </div>
                                </div>
                                <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 w-3/4"></div>
                                </div>
                            </div>

                            <div class="relative z-20 bg-white p-6 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 transform translate-x-8 -translate-y-6 hover:-translate-y-8 transition-transform duration-300">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <i class="pi pi-eye text-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">Tahap 2: Observe</h4>
                                        <p class="text-xs text-slate-500">Amati simulasi interaktif</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="h-16 flex-1 bg-slate-50 rounded-lg border border-dashed border-slate-200 flex items-center justify-center text-emerald-300"><i class="pi pi-chart-bar text-xl"></i></div>
                                    <div class="h-16 flex-1 bg-emerald-50/50 rounded-lg border border-emerald-100 flex items-center justify-center text-emerald-400"><i class="pi pi-chart-line text-xl"></i></div>
                                </div>
                            </div>

                            <div class="relative z-30 bg-white p-6 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 transform rotate-3 -translate-y-12 mr-12 hover:rotate-0 transition-transform duration-300">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                        <i class="pi pi-comment text-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm">Tahap 3: Explain</h4>
                                        <p class="text-xs text-slate-500">Pahami konsep bersama AI</p>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <div class="h-2 w-full bg-slate-100 rounded-full"></div>
                                    <div class="h-2 w-4/5 bg-slate-100 rounded-full"></div>
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
</style>