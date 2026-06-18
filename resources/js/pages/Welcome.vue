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
                            <b>Predict, Observe, dan Explain</b>. Belajar
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
                    </div>

                    <div class="mt-16 hidden md:block lg:col-span-6 lg:mt-0">
                        <div class="relative mx-auto w-full max-w-lg">
                            <div
                                class="absolute top-1/2 left-1/2 h-[400px] w-[400px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-300/30 mix-blend-multiply blur-3xl"
                            ></div>

                            <div
                                class="relative z-10 ml-12 -rotate-3 transform rounded-2xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/50 transition-transform duration-300 hover:rotate-0"
                            >
                                <div class="mb-3 flex items-center gap-4">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                                    >
                                        <i
                                            class="pi pi-question-circle text-lg"
                                        ></i>
                                    </div>
                                    <div>
                                        <h4
                                            class="text-sm font-bold text-slate-800"
                                        >
                                            Tahap 1: Predict
                                        </h4>
                                        <p class="text-xs text-slate-500">
                                            Uji hipotesis awalmu
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="h-2 w-full overflow-hidden rounded-full bg-slate-100"
                                >
                                    <div class="h-full w-3/4 bg-blue-500"></div>
                                </div>
                            </div>

                            <div
                                class="relative z-20 translate-x-8 -translate-y-6 transform rounded-2xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/50 transition-transform duration-300 hover:-translate-y-8"
                            >
                                <div class="mb-3 flex items-center gap-4">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"
                                    >
                                        <i class="pi pi-eye text-lg"></i>
                                    </div>
                                    <div>
                                        <h4
                                            class="text-sm font-bold text-slate-800"
                                        >
                                            Tahap 2: Observe
                                        </h4>
                                        <p class="text-xs text-slate-500">
                                            Amati simulasi interaktif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div
                                        class="flex h-16 flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 text-emerald-300"
                                    >
                                        <i class="pi pi-chart-bar text-xl"></i>
                                    </div>
                                    <div
                                        class="flex h-16 flex-1 items-center justify-center rounded-lg border border-emerald-100 bg-emerald-50/50 text-emerald-400"
                                    >
                                        <i class="pi pi-chart-line text-xl"></i>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative z-30 mr-12 -translate-y-12 rotate-3 transform rounded-2xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-200/50 transition-transform duration-300 hover:rotate-0"
                            >
                                <div class="mb-3 flex items-center gap-4">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600"
                                    >
                                        <i class="pi pi-comment text-lg"></i>
                                    </div>
                                    <div>
                                        <h4
                                            class="text-sm font-bold text-slate-800"
                                        >
                                            Tahap 3: Explain
                                        </h4>
                                        <p class="text-xs text-slate-500">
                                            Pahami konsep bersama AI
                                        </p>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        class="h-2 w-full rounded-full bg-slate-100"
                                    ></div>
                                    <div
                                        class="h-2 w-4/5 rounded-full bg-slate-100"
                                    ></div>
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
