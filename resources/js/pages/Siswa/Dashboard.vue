<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    VisXYContainer,
    VisArea,
    VisLine,
    VisAxis,
    VisCrosshair,
    VisTooltip,
} from '@unovis/vue';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTheme } from '@/composables/useTheme';

const { theme } = useTheme();

const chartColor = computed(() => {
    if (theme.value === 'elegan') return '#4f46e5';
    if (theme.value === 'genz') return '#d946ef';
    if (theme.value === 'classic') return '#15803d';
    return '#0D9488';
});

const chartAreaColor = computed(() => {
    if (theme.value === 'elegan') return '#818cf8';
    if (theme.value === 'genz') return '#f472b6';
    if (theme.value === 'classic') return '#4ade80';
    return '#2DD4BF';
});

const avatarBg = computed(() => {
    if (theme.value === 'elegan') return '4f46e5';
    if (theme.value === 'genz') return 'd946ef';
    if (theme.value === 'classic') return '15803d';
    return '0F5A53';
});

// Mengimpor mesin asli Shadcn Vue Chart secara langsung beserta komponen Tooltip

const props = defineProps<{
    classrooms?: Array<{
        id: number;
        class_name: string;
        class_code: string;
        description: string | null;
        topics_count: number;
        pivot?: {
            pre_test_score: number | null;
            post_test_score: number | null;
            is_evaluation_sent: boolean;
        };
    }>;
    stats?: {
        kelas_aktif: number;
        kelas_aktif_sub?: string;
        nilai_awal?: string | number;
        nilai_akhir?: string | number;
        peningkatan?: string | number;
        progress_rata_rata: number;
        progress_rata_rata_sub?: string;
        modul_selesai: number;
        modul_selesai_sub?: string;
        rata_rata_tes: string | number;
        rata_rata_tes_sub?: string;
    };
    chartDataWeek?: Array<{ name: string; 'Jam Belajar': number }>;
    chartDataMonth?: Array<{ name: string; 'Jam Belajar': number }>;
    recentActivities?: Array<{
        id: number | string;
        title: string;
        subject: string;
        time: string;
        icon: string;
        color: string;
    }>;
    aiInsights?: string[];
    notifications?: Array<{ text: string; time: string }>;
    unreadNotificationsCount?: number;
}>();

// Form Inertia untuk fitur Gabung Kelas
const form = useForm({
    class_code: '',
});

const submitJoinClass = () => {
    form.post(route('siswa.classes.join'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('class_code');
            alert('Berhasil bergabung ke kelas!');
        },
    });
};

// State filter grafik & notifikasi
const selectedPeriod = ref('Minggu Ini');
const showNotifications = ref(false);

const refreshDashboard = () => {
    router.reload({
        preserveState: true,
        preserveScroll: true,
    });
};

// Data Dinamis / Fallback untuk Grafik
const chartData = computed(() => {
    const data = selectedPeriod.value === 'Minggu Ini' 
        ? props.chartDataWeek 
        : props.chartDataMonth;
        
    return data && data.length > 0 ? data : (selectedPeriod.value === 'Minggu Ini' ? [
        { name: 'Sen', 'Jam Belajar': 0 },
        { name: 'Sel', 'Jam Belajar': 0 },
        { name: 'Rab', 'Jam Belajar': 0 },
        { name: 'Kam', 'Jam Belajar': 0 },
        { name: 'Jum', 'Jam Belajar': 0 },
        { name: 'Sab', 'Jam Belajar': 0 },
        { name: 'Min', 'Jam Belajar': 0 },
    ] : [
        { name: '01', 'Jam Belajar': 0 },
        { name: '05', 'Jam Belajar': 0 },
        { name: '10', 'Jam Belajar': 0 },
        { name: '15', 'Jam Belajar': 0 },
        { name: '20', 'Jam Belajar': 0 },
        { name: '25', 'Jam Belajar': 0 },
        { name: '30', 'Jam Belajar': 0 },
    ]);
});

// Fungsi helper mutlak untuk Unovis
const x = (d: any, i: number) => i;
const y = (d: any) => d['Jam Belajar'];
const tickFormat = (i: number) => chartData.value[i]?.name || '';

// Template desain untuk isi Tooltip saat di-hover
const tooltipTemplate = (d: any) => `
    <div class="bg-white/95 backdrop-blur-sm border border-slate-200 shadow-lg rounded-xl px-3 py-2">
        <p class="text-[11px] font-medium text-slate-500 mb-0.5">${d.name}</p>
        <p class="text-[13px] font-black text-[var(--theme-primary)]">${d['Jam Belajar']} Jam</p>
    </div>
`;

// Data Dinamis / Fallback untuk Aktivitas & AI
const recentActivities = computed(() => {
    return props.recentActivities && props.recentActivities.length > 0 ? props.recentActivities : [
        {
            id: 'dummy_1',
            title: 'Belum ada aktivitas baru',
            subject: 'Kimia Dasar X',
            time: 'Mulai sekarang',
            icon: 'pi-info-circle',
            color: 'text-slate-400',
        }
    ];
});

const aiInsights = computed(() => {
    return props.aiInsights && props.aiInsights.length > 0 ? props.aiInsights : [
        "Belum ada masukan AI. Selesaikan latihan esai atau jawaban singkat di Worksheet untuk mendapatkan feedback AI!",
        "Gunakan AI Tutor di pojok kanan bawah untuk bertanya kapan pun kamu bingung tentang konsep Kimia.",
        "Siklus belajar LC5E (Engage, Explore, Explain, Elaborate, Evaluate) membantumu memahami konsep secara mendalam."
    ];
});
</script>

<template>
    <Head title="Overview Siswa - EduChem" />

    <main
        :class="[theme === 'classic' ? 'font-serif' : 'font-sans']"
        class="relative flex min-h-screen w-full flex-1 flex-col transition-colors duration-500"
    >
        <header
            class="sticky top-0 z-20 flex h-[80px] items-center justify-between border-b border-[var(--theme-border)] bg-[var(--theme-card-bg)]/50 px-4 md:px-8 backdrop-blur-md transition-colors duration-500"
        >
            <div class="flex items-center">
                <p class="text-sm md:text-xl font-bold text-slate-800">
                    Selamat datang kembali, <span class="font-black text-[var(--theme-primary)]">{{ $page.props.auth.user.name }}</span>! 👋
                </p>
            </div>
            <div class="flex items-center gap-5">
                <div class="relative">
                    <button
                        @click="showNotifications = !showNotifications"
                        class="relative text-slate-400 transition-colors hover:text-slate-600 focus:outline-none"
                    >
                        <i class="pi pi-bell text-xl"></i>
                        <span
                            v-if="unreadNotificationsCount && unreadNotificationsCount > 0"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-[9px] font-bold text-white"
                            >{{ unreadNotificationsCount }}</span
                        >
                    </button>
                    <!-- Dropdown Notifikasi -->
                    <div
                        v-if="showNotifications"
                        class="absolute right-0 mt-2 w-80 rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-4 shadow-xl z-30 transition-all duration-200"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800">Notifikasi</h4>
                            <button
                                @click="showNotifications = false"
                                class="text-[10px] text-slate-400 hover:text-slate-600"
                            >
                                <i class="pi pi-times"></i>
                            </button>
                        </div>
                        <div class="space-y-3 max-h-60 overflow-y-auto">
                            <div
                                v-for="(notif, idx) in notifications"
                                :key="idx"
                                class="border-b border-slate-50 pb-2 last:border-0 last:pb-0"
                            >
                                <p class="text-xs font-semibold text-slate-700 leading-normal">{{ notif.text }}</p>
                                <span class="text-[10px] font-medium text-slate-400 mt-1 block">{{ notif.time }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="h-8 w-[1px] bg-slate-200"></div>
                <div class="flex cursor-pointer items-center gap-3">
                    <div class="hidden text-right md:block">
                        <p
                            class="text-[13px] leading-tight font-bold text-slate-800"
                        >
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-[11px] font-medium text-slate-500">
                            {{ $page.props.auth.user.email }}
                        </p>
                    </div>
                    <img
                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent($page.props.auth.user.name)}&background=${avatarBg}&color=fff`"
                        class="h-10 w-10 rounded-full border-2 border-white shadow-sm"
                        alt="Profile"
                    />
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <div
                    class="mb-2 flex flex-col justify-between gap-4 md:flex-row md:items-center"
                >
                    <div>
                        <h1
                            class="text-[26px] font-black tracking-tight text-slate-900"
                        >
                            Overview
                        </h1>
                        <p
                            class="mt-0.5 text-[13px] font-medium text-slate-500"
                        >
                            Sistem belajar kimia berbasis
                            Learning Cycle 5E (LC5E)
                        </p>
                    </div>

                    <form
                        @submit.prevent="submitJoinClass"
                        class="flex flex-col items-end gap-1"
                    >
                        <div
                            class="flex items-center gap-2 rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-1.5 shadow-sm transition-colors duration-500"
                        >
                            <div class="relative w-full md:w-48">
                                <i
                                    class="pi pi-key absolute top-1/2 left-3 -translate-y-1/2 text-[12px] text-slate-400"
                                ></i>
                                <Input
                                    v-model="form.class_code"
                                    placeholder="Kode Kelas (6 Digit)"
                                    maxlength="6"
                                    class="h-9 w-full rounded-xl border-none bg-slate-50 dark:bg-slate-900 pr-3 pl-8 text-[12px] font-bold tracking-widest uppercase focus-visible:ring-0"
                                />
                            </div>
                            <Button
                                type="submit"
                                :disabled="
                                    form.processing ||
                                    form.class_code.length !== 6
                                "
                                class="h-9 rounded-xl bg-[var(--theme-primary)] px-5 text-[12px] font-bold text-white shadow-sm hover:bg-[var(--theme-primary-hover)] disabled:opacity-50 transition-colors duration-300"
                            >
                                <span v-if="form.processing"
                                    ><i class="pi pi-spin pi-spinner"></i
                                ></span>
                                <span v-else>Gabung</span>
                            </Button>
                        </div>
                        <span
                            v-if="form.errors.class_code"
                            class="mr-2 text-[11px] font-bold text-rose-500"
                        >
                            * {{ form.errors.class_code }}
                        </span>
                    </form>
                </div>
 
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <Card
                        class="rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-5 shadow-xs transition-all hover:shadow-md hover:border-[var(--theme-primary)]/20 duration-300"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-[13px] font-bold text-slate-600 dark:text-slate-400"
                                >Kelas Aktif</span
                            >
                            <div
                                class="rounded-lg bg-[var(--theme-primary)]/10 p-2 text-[var(--theme-primary)]"
                            >
                                <i class="pi pi-file-text"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ stats?.kelas_aktif ?? 0 }}
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-emerald-600">
                            {{ stats?.kelas_aktif_sub ?? '+0' }} bulan ini
                        </p>
                    </Card>
                    <Card
                        class="rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-5 shadow-xs transition-all hover:shadow-md hover:border-[var(--theme-primary)]/20 duration-300"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-[13px] font-bold text-slate-600 dark:text-slate-400"
                                >Nilai Awal</span
                            >
                            <div
                                class="rounded-lg bg-violet-50 dark:bg-violet-950/30 p-2 text-violet-500 dark:text-violet-400"
                            >
                                <i class="pi pi-bookmark"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ stats?.nilai_awal ?? '-' }}
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-emerald-600">
                            Rata-rata Pre-Test
                        </p>
                    </Card>
                    <Card
                        class="rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-5 shadow-xs transition-all hover:shadow-md hover:border-[var(--theme-primary)]/20 duration-300"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-[13px] font-bold text-slate-600 dark:text-slate-400"
                                >Nilai Akhir</span
                            >
                            <div
                                class="rounded-lg bg-emerald-50 dark:bg-emerald-950/30 p-2 text-emerald-500 dark:text-emerald-400"
                            >
                                <i class="pi pi-check-circle"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ stats?.nilai_akhir ?? '-' }}
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-emerald-600">
                            Rata-rata Post-Test
                        </p>
                    </Card>
                    <Card
                        class="rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-5 shadow-xs transition-all hover:shadow-md hover:border-[var(--theme-primary)]/20 duration-300"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-[13px] font-bold text-slate-600 dark:text-slate-400"
                                >Peningkatan</span
                            >
                            <div
                                class="rounded-lg bg-rose-50 dark:bg-rose-950/30 p-2 text-rose-500 dark:text-rose-455"
                            >
                                <i class="pi pi-trending-up"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white">
                            {{ stats?.peningkatan ?? '-' }}
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-emerald-600">
                            Selisih Nilai Akhir & Awal
                        </p>
                    </Card>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <Card
                            class="rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-6 shadow-sm transition-colors duration-500"
                        >
                            <div class="mb-6 flex items-center justify-between">
                                <h3
                                    class="text-[16px] font-extrabold text-slate-800 dark:text-slate-200"
                                >
                                    Aktivitas Belajar Harian
                                </h3>
                                <select
                                    v-model="selectedPeriod"
                                    class="rounded-lg border border-[var(--theme-border)] bg-[var(--theme-card-bg)] px-3 py-1.5 text-[12px] font-medium text-slate-500 focus:ring-0 cursor-pointer transition-colors duration-550"
                                >
                                    <option value="Minggu Ini">Minggu Ini</option>
                                    <option value="Bulan Ini">Bulan Ini</option>
                                </select>
                            </div>
 
                            <div class="mt-4 h-[250px] w-full">
                                <VisXYContainer :data="chartData" :height="250">
                                    <VisArea
                                        :x="x"
                                        :y="y"
                                        :color="chartAreaColor"
                                        :opacity="0.2"
                                    />
                                    <VisLine
                                        :x="x"
                                        :y="y"
                                        :color="chartColor"
                                        :lineWidth="3"
                                    />
                                    <VisAxis
                                        type="x"
                                        :tickFormat="tickFormat"
                                        :gridLine="false"
                                    />
 
                                    <VisCrosshair
                                        :color="chartColor"
                                        :template="tooltipTemplate"
                                    />
                                    <VisTooltip />
                                </VisXYContainer>
                            </div>
                        </Card>
 
                        <Card
                            class="rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-6 shadow-sm transition-colors duration-500"
                        >
                            <div class="mb-6 flex items-center justify-between">
                                <h3
                                    class="text-[16px] font-extrabold text-slate-800 dark:text-slate-200"
                                  >
                                    Kelas Anda
                                </h3>
                                <Link
                                    :href="route('siswa.classes.index')"
                                    class="text-[12px] font-bold text-[var(--theme-primary)] hover:underline"
                                    >Lihat Semua</Link
                                >
                            </div>
 
                            <div
                                class="space-y-4"
                                v-if="classrooms && classrooms.length > 0"
                            >
                                <div
                                    v-for="kelas in classrooms"
                                    :key="kelas.id"
                                    class="group flex items-center justify-between rounded-xl border border-[var(--theme-border)]/60 p-4 transition-all hover:border-[var(--theme-primary)]/30 hover:bg-[var(--theme-primary)]/5 duration-350"
                                >
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[var(--theme-primary)]/70 to-[var(--theme-primary)] text-lg font-black text-white shadow-sm transition-colors duration-500"
                                        >
                                            {{ kelas.class_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <h4
                                                class="text-[14px] font-bold text-slate-900 dark:text-slate-100 transition-colors group-hover:text-[var(--theme-primary)]"
                                            >
                                                {{ kelas.class_name }}
                                            </h4>
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                                                <p
                                                    class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 flex items-center gap-1"
                                                >
                                                    <span>Nilai Awal:</span>
                                                    <span class="font-black px-1.5 py-0.2 bg-slate-105 dark:bg-slate-800 rounded text-slate-700 dark:text-slate-350">{{ kelas.pivot?.pre_test_score !== null && kelas.pivot?.pre_test_score !== undefined ? kelas.pivot.pre_test_score : '-' }}</span>
                                                </p>
                                                <div class="hidden sm:block h-3 w-[1px] bg-slate-200 dark:bg-slate-800"></div>
                                                <p
                                                    class="text-[11px] font-semibold text-[var(--theme-primary)] flex items-center gap-1"
                                                >
                                                    <span>Nilai Akhir:</span>
                                                    <span class="font-black px-1.5 py-0.2 bg-[var(--theme-primary)]/10 rounded text-[var(--theme-primary)]">{{ kelas.pivot?.post_test_score !== null && kelas.pivot?.post_test_score !== undefined ? kelas.pivot.post_test_score : '-' }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <Link :href="route('siswa.classes.show', kelas.id)">
                                        <Button
                                            variant="outline"
                                            class="h-9 rounded-xl border-[var(--theme-border)] px-4 text-[12px] font-bold text-[var(--theme-primary)] shadow-sm transition-all hover:bg-[var(--theme-primary)] hover:text-white"
                                        >
                                            Lanjutkan
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <p
                                    class="text-[13px] font-medium text-slate-500"
                                >
                                    Anda belum bergabung ke kelas manapun.
                                    Masukkan kode kelas di atas.
                                </p>
                            </div>
                        </Card>
                    </div>
 
                    <div class="space-y-6">
 
 
                        <Card
                            class="rounded-2xl border border-[var(--theme-border)] bg-[var(--theme-card-bg)] p-6 shadow-sm transition-colors duration-500"
                        >
                            <h3
                                class="mb-5 text-[16px] font-extrabold text-slate-800 dark:text-slate-200"
                            >
                                Riwayat Aktivitas
                            </h3>
                            <div class="space-y-5">
                                <div
                                    v-for="activity in recentActivities"
                                    :key="activity.id"
                                    class="flex gap-4"
                                >
                                    <div class="mt-1">
                                        <div
                                            :class="`flex h-8 w-8 items-center justify-center rounded-full border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shadow-sm ${activity.color}`"
                                        >
                                            <i
                                                :class="`pi ${activity.icon} text-[14px]`"
                                            ></i>
                                        </div>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[13px] font-bold text-slate-850 dark:text-slate-200"
                                        >
                                            {{ activity.title }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-[12px] font-medium text-slate-500"
                                        >
                                            {{ activity.subject }}
                                        </p>
                                        <p
                                            class="mt-1.5 text-[10px] font-bold text-slate-400"
                                        >
                                            <i
                                                class="pi pi-clock mr-1 text-[9px]"
                                            ></i>
                                            {{ activity.time }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
