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
import { toast } from 'vue-sonner'; // Menggunakan Sonner Toast

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

// State Loading & Modals
const isRefreshing = ref(false);
const showNotifications = ref(false);
const selectedPeriod = ref('Minggu Ini');

// Form Gabung Kelas
const form = useForm({
    class_code: '',
});

const submitJoinClass = () => {
    form.post(route('siswa.classes.join'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('class_code');
            toast.success('Berhasil Bergabung!', {
                description: 'Anda telah berhasil masuk ke kelas baru.',
                position: 'bottom-right',
                icon: '✓'
            });
        },
        onError: (errors) => {
            if (errors.class_code) {
                toast.error('Gagal Bergabung', {
                    description: errors.class_code,
                    position: 'bottom-right',
                });
            } else {
                toast.error('Gagal Bergabung', {
                    description: 'Terjadi kesalahan saat mencoba bergabung.',
                    position: 'bottom-right',
                });
            }
        }
    });
};

const refreshDashboard = () => {
    isRefreshing.value = true;
    router.reload({
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

// Data Asli (Tanpa Dummy Fallback)
const chartData = computed(() => {
    const data = selectedPeriod.value === 'Minggu Ini' 
        ? props.chartDataWeek 
        : props.chartDataMonth;
    return data || [];
});

const recentActivities = computed(() => props.recentActivities || []);
const aiInsights = computed(() => props.aiInsights || []);

// Fungsi Helper Unovis
const x = (d: any, i: number) => i;
const y = (d: any) => d['Jam Belajar'];
const tickFormat = (i: number) => chartData.value[i]?.name || '';

const tooltipTemplate = (d: any) => `
    <div class="bg-white/95 backdrop-blur-sm border border-slate-200 shadow-lg rounded-xl px-3 py-2">
        <p class="text-[11px] font-medium text-slate-500 mb-0.5">${d.name}</p>
        <p class="text-[13px] font-black text-[var(--theme-primary)]">${d['Jam Belajar']} Jam</p>
    </div>
`;
</script>

<template>
    <Head title="Overview Siswa - EduChem" />

    <main
        :class="[theme === 'classic' ? 'font-serif' : 'font-sans']"
        class="relative flex min-h-screen w-full flex-1 flex-col bg-slate-50/50 transition-colors duration-500"
    >
        <!-- HEADER -->
        <header class="sticky top-0 z-20 flex h-[80px] items-center justify-between border-b border-[var(--theme-border)] bg-white/80 px-4 md:px-8 backdrop-blur-md transition-colors duration-500">
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-sm md:text-xl font-bold text-slate-800">
                        Overview Belajar, <span class="font-black text-[var(--theme-primary)]">{{ $page.props.auth.user.name }}</span> 👋
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-5">
                <!-- Refresh Button -->
                <button 
                    @click="refreshDashboard" 
                    :disabled="isRefreshing"
                    class="hidden md:flex items-center justify-center h-9 w-9 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition-all disabled:opacity-50"
                >
                    <i class="pi pi-refresh" :class="{ 'animate-spin': isRefreshing }"></i>
                </button>

                <!-- Notifications -->
                <div class="relative">
                    <button
                        @click="showNotifications = !showNotifications"
                        class="relative flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-700 focus:outline-none"
                    >
                        <i class="pi pi-bell text-lg"></i>
                        <span
                            v-if="unreadNotificationsCount && unreadNotificationsCount > 0"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-[9px] font-bold text-white shadow-sm"
                        >
                            {{ unreadNotificationsCount }}
                        </span>
                    </button>
                    <!-- Dropdown Notifikasi -->
                    <div
                        v-if="showNotifications"
                        class="absolute right-0 mt-2 w-80 rounded-2xl border border-[var(--theme-border)] bg-white p-4 shadow-xl z-30 transition-all duration-200"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-800"><i class="pi pi-bell mr-1"></i> Notifikasi</h4>
                            <button @click="showNotifications = false" class="text-[10px] text-slate-400 hover:text-slate-600">
                                <i class="pi pi-times"></i>
                            </button>
                        </div>
                        <div class="space-y-3 max-h-60 overflow-y-auto">
                            <div v-if="notifications && notifications.length > 0">
                                <div v-for="(notif, idx) in notifications" :key="idx" class="border-b border-slate-50 pb-2 last:border-0 last:pb-0">
                                    <p class="text-xs font-semibold text-slate-700 leading-normal">{{ notif.text }}</p>
                                    <span class="text-[10px] font-medium text-slate-400 mt-1 block">{{ notif.time }}</span>
                                </div>
                            </div>
                            <div v-else class="py-4 text-center">
                                <p class="text-xs text-slate-500">Tidak ada notifikasi baru.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="h-8 w-[1px] bg-slate-200"></div>
                
                <!-- User Profile -->
                <div class="flex cursor-pointer items-center gap-3">
                    <div class="hidden text-right md:block">
                        <p class="text-[13px] leading-tight font-bold text-slate-800">{{ $page.props.auth.user.name }}</p>
                        <p class="text-[11px] font-medium text-slate-500">{{ $page.props.auth.user.email }}</p>
                    </div>
                    <img
                        :src="`https://ui-avatars.com/api/?name=${encodeURIComponent($page.props.auth.user.name)}&background=${avatarBg}&color=fff`"
                        class="h-10 w-10 rounded-full border-2 border-white shadow-sm"
                        alt="Profile"
                    />
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                
                <!-- TOP SECTION: Welcome & Gabung Kelas -->
                <div class="mb-2 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div>
                        <h1 class="text-[26px] font-black tracking-tight text-slate-900">Dashboard Belajar</h1>
                        <p class="mt-1 text-[13px] font-medium text-slate-500">Pantau progres dan aktivitas belajar kimia Anda.</p>
                    </div>

                    <form @submit.prevent="submitJoinClass" class="flex flex-col items-end gap-1">
                        <div class="flex items-center gap-2 rounded-2xl border border-[var(--theme-border)] bg-white p-1.5 shadow-sm transition-colors duration-500 focus-within:border-[var(--theme-primary)] focus-within:ring-2 focus-within:ring-[var(--theme-primary)]/20">
                            <div class="relative w-full md:w-56">
                                <i class="pi pi-users absolute top-1/2 left-3 -translate-y-1/2 text-[13px] text-slate-400"></i>
                                <Input
                                    v-model="form.class_code"
                                    placeholder="KODE KELAS"
                                    maxlength="6"
                                    class="h-10 w-full rounded-xl border-none bg-slate-50 pr-3 pl-9 text-[13px] font-bold tracking-widest uppercase focus-visible:ring-0 placeholder:normal-case placeholder:tracking-normal"
                                />
                            </div>
                            <Button
                                type="submit"
                                :disabled="form.processing || form.class_code.length !== 6"
                                class="h-10 rounded-xl bg-[var(--theme-primary)] px-6 text-[13px] font-bold text-white shadow-md hover:bg-[var(--theme-primary-hover)] disabled:opacity-50 transition-all duration-300"
                            >
                                <i v-if="form.processing" class="pi pi-spin pi-spinner mr-2"></i>
                                <span>Gabung</span>
                            </Button>
                        </div>
                        <span v-if="form.errors.class_code" class="mr-2 text-[11px] font-bold text-rose-500">* {{ form.errors.class_code }}</span>
                    </form>
                </div>
 
                <!-- STATS CARDS -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Card 1: Kelas Aktif -->
                    <Card class="group relative overflow-hidden rounded-2xl border border-[var(--theme-border)] bg-gradient-to-br from-white to-slate-50/50 p-5 shadow-sm transition-all hover:shadow-md hover:border-blue-200 duration-300">
                        <div class="absolute -right-4 -top-4 rounded-full bg-blue-500/5 p-8 transition-transform group-hover:scale-110"></div>
                        <div class="mb-4 flex items-center justify-between relative z-10">
                            <span class="text-[13px] font-bold text-slate-500">Kelas Aktif</span>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white shadow-sm">
                                <i class="pi pi-book text-[18px]"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 relative z-10">
                            <span v-if="isRefreshing" class="inline-block h-8 w-16 animate-pulse rounded-md bg-slate-200"></span>
                            <span v-else>{{ stats?.kelas_aktif ?? 'N/A' }}</span>
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-slate-400 relative z-10">
                            <span class="text-emerald-500">{{ stats?.kelas_aktif_sub ?? 'N/A' }}</span> bulan ini
                        </p>
                    </Card>

                    <!-- Card 2: Nilai Awal -->
                    <Card class="group relative overflow-hidden rounded-2xl border border-[var(--theme-border)] bg-gradient-to-br from-white to-slate-50/50 p-5 shadow-sm transition-all hover:shadow-md hover:border-violet-200 duration-300">
                        <div class="absolute -right-4 -top-4 rounded-full bg-violet-500/5 p-8 transition-transform group-hover:scale-110"></div>
                        <div class="mb-4 flex items-center justify-between relative z-10">
                            <span class="text-[13px] font-bold text-slate-500">Nilai Awal</span>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 transition-colors group-hover:bg-violet-600 group-hover:text-white shadow-sm">
                                <i class="pi pi-file-edit text-[18px]"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 relative z-10">
                            <span v-if="isRefreshing" class="inline-block h-8 w-16 animate-pulse rounded-md bg-slate-200"></span>
                            <span v-else>{{ stats?.nilai_awal ?? 'N/A' }}</span>
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-slate-400 relative z-10">Rata-rata Pre-Test</p>
                    </Card>

                    <!-- Card 3: Nilai Akhir -->
                    <Card class="group relative overflow-hidden rounded-2xl border border-[var(--theme-border)] bg-gradient-to-br from-white to-slate-50/50 p-5 shadow-sm transition-all hover:shadow-md hover:border-emerald-200 duration-300">
                        <div class="absolute -right-4 -top-4 rounded-full bg-emerald-500/5 p-8 transition-transform group-hover:scale-110"></div>
                        <div class="mb-4 flex items-center justify-between relative z-10">
                            <span class="text-[13px] font-bold text-slate-500">Nilai Akhir</span>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white shadow-sm">
                                <i class="pi pi-check-circle text-[18px]"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 relative z-10">
                            <span v-if="isRefreshing" class="inline-block h-8 w-16 animate-pulse rounded-md bg-slate-200"></span>
                            <span v-else>{{ stats?.nilai_akhir ?? 'N/A' }}</span>
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-slate-400 relative z-10">Rata-rata Post-Test</p>
                    </Card>

                    <!-- Card 4: Peningkatan -->
                    <Card class="group relative overflow-hidden rounded-2xl border border-[var(--theme-border)] bg-gradient-to-br from-white to-slate-50/50 p-5 shadow-sm transition-all hover:shadow-md hover:border-rose-200 duration-300">
                        <div class="absolute -right-4 -top-4 rounded-full bg-rose-500/5 p-8 transition-transform group-hover:scale-110"></div>
                        <div class="mb-4 flex items-center justify-between relative z-10">
                            <span class="text-[13px] font-bold text-slate-500">Peningkatan</span>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-500 transition-colors group-hover:bg-rose-500 group-hover:text-white shadow-sm">
                                <i class="pi pi-chart-line text-[18px]"></i>
                            </div>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 relative z-10">
                            <span v-if="isRefreshing" class="inline-block h-8 w-16 animate-pulse rounded-md bg-slate-200"></span>
                            <span v-else>{{ stats?.peningkatan ?? 'N/A' }}</span>
                        </h3>
                        <p class="mt-2 text-[11px] font-bold text-slate-400 relative z-10">Selisih Post & Pre-Test</p>
                    </Card>
                </div>

                <!-- MAIN GRIDS -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    
                    <!-- KIRI: Chart & Kelas -->
                    <div class="space-y-6 lg:col-span-2">
                        
                        <!-- CHART DATA -->
                        <Card class="rounded-2xl border border-[var(--theme-border)] bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center justify-between">
                                <h3 class="text-[16px] font-extrabold text-slate-800"><i class="pi pi-chart-area mr-2 text-[var(--theme-primary)]"></i> Intensitas Belajar</h3>
                                <select v-model="selectedPeriod" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-[12px] font-bold text-slate-600 focus:ring-0 cursor-pointer shadow-sm hover:bg-slate-100 transition-colors">
                                    <option value="Minggu Ini">Minggu Ini</option>
                                    <option value="Bulan Ini">Bulan Ini</option>
                                </select>
                            </div>
 
                            <div class="mt-4 h-[250px] w-full">
                                <!-- Loading State -->
                                <div v-if="isRefreshing" class="h-full w-full animate-pulse rounded-xl bg-slate-100"></div>
                                
                                <!-- Empty State -->
                                <div v-else-if="chartData.length === 0" class="flex h-full w-full flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50">
                                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                        <i class="pi pi-chart-line text-xl text-slate-400"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-700">Belum Ada Data Aktivitas</h4>
                                    <p class="mt-1 text-center text-xs text-slate-500 max-w-xs">Data aktivitas belajar akan muncul setelah Anda mulai mengakses materi pembelajaran.</p>
                                </div>

                                <!-- Data State -->
                                <VisXYContainer v-else :data="chartData" :height="250">
                                    <VisArea :x="x" :y="y" :color="chartAreaColor" :opacity="0.15" />
                                    <VisLine :x="x" :y="y" :color="chartColor" :lineWidth="3" />
                                    <VisAxis type="x" :tickFormat="tickFormat" :gridLine="false" />
                                    <VisCrosshair :color="chartColor" :template="tooltipTemplate" />
                                    <VisTooltip />
                                </VisXYContainer>
                            </div>
                        </Card>
 
                        <!-- KELAS SAYA -->
                        <Card class="rounded-2xl border border-[var(--theme-border)] bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center justify-between">
                                <h3 class="text-[16px] font-extrabold text-slate-800"><i class="pi pi-book mr-2 text-[var(--theme-primary)]"></i> Daftar Kelas</h3>
                                <Link :href="route('siswa.classes.index')" class="text-[12px] font-bold text-[var(--theme-primary)] hover:underline">Lihat Semua</Link>
                            </div>
 
                            <!-- Loading State -->
                            <div v-if="isRefreshing" class="space-y-4">
                                <div v-for="i in 2" :key="i" class="h-20 w-full animate-pulse rounded-xl bg-slate-100"></div>
                            </div>

                            <!-- Empty State -->
                            <div v-else-if="!classrooms || classrooms.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 py-10">
                                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                    <i class="pi pi-users text-xl text-slate-400"></i>
                                </div>
                                <h4 class="text-sm font-bold text-slate-700">Belum Bergabung ke Kelas</h4>
                                <p class="mt-1 text-center text-xs text-slate-500 max-w-xs">Masukkan kode kelas pada kolom di atas untuk mulai mengikuti pembelajaran.</p>
                            </div>

                            <!-- Data State -->
                            <div v-else class="space-y-4">
                                <div
                                    v-for="kelas in classrooms"
                                    :key="kelas.id"
                                    class="group flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-[var(--theme-primary)]/40 hover:bg-slate-50"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[var(--theme-primary)]/80 to-[var(--theme-primary)] text-lg font-black text-white shadow-sm">
                                            {{ kelas.class_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <h4 class="text-[14px] font-bold text-slate-900 transition-colors group-hover:text-[var(--theme-primary)]">
                                                {{ kelas.class_name }}
                                            </h4>
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                                                <p class="text-[11px] font-semibold text-slate-500 flex items-center gap-1.5">
                                                    <i class="pi pi-file-edit text-[10px]"></i>
                                                    <span>Pre-Test:</span>
                                                    <span class="font-black px-1.5 py-0.5 bg-slate-100 rounded text-slate-700">
                                                        {{ kelas.pivot?.pre_test_score ?? 'N/A' }}
                                                    </span>
                                                </p>
                                                <div class="hidden sm:block h-3 w-[1px] bg-slate-300"></div>
                                                <p class="text-[11px] font-semibold text-[var(--theme-primary)] flex items-center gap-1.5">
                                                    <i class="pi pi-check-circle text-[10px]"></i>
                                                    <span>Post-Test:</span>
                                                    <span class="font-black px-1.5 py-0.5 bg-[var(--theme-primary)]/10 rounded text-[var(--theme-primary)]">
                                                        {{ kelas.pivot?.post_test_score ?? 'N/A' }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <Link :href="route('siswa.classes.show', kelas.id)">
                                        <Button variant="outline" class="h-9 rounded-xl border-slate-200 bg-white px-4 text-[12px] font-bold text-[var(--theme-primary)] shadow-sm transition-all hover:bg-[var(--theme-primary)] hover:text-white hover:border-transparent">
                                            Lanjutkan <i class="pi pi-arrow-right ml-1 text-[10px]"></i>
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </Card>
                    </div>
 
                    <!-- KANAN: Aktivitas & AI Insights -->
                    <div class="space-y-6">
                        
                        <!-- RIWAYAT AKTIVITAS -->
                        <Card class="rounded-2xl border border-[var(--theme-border)] bg-white p-6 shadow-sm">
                            <h3 class="mb-5 text-[16px] font-extrabold text-slate-800"><i class="pi pi-history mr-2 text-[var(--theme-primary)]"></i> Riwayat Aktivitas</h3>
                            
                            <!-- Loading State -->
                            <div v-if="isRefreshing" class="space-y-4">
                                <div v-for="i in 3" :key="i" class="flex items-center gap-4">
                                    <div class="h-10 w-10 shrink-0 animate-pulse rounded-full bg-slate-100"></div>
                                    <div class="space-y-2 w-full">
                                        <div class="h-3 w-3/4 animate-pulse rounded bg-slate-100"></div>
                                        <div class="h-2 w-1/2 animate-pulse rounded bg-slate-100"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else-if="recentActivities.length === 0" class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-50">
                                    <i class="pi pi-history text-xl text-slate-400"></i>
                                </div>
                                <h4 class="text-sm font-bold text-slate-700">Belum Ada Aktivitas</h4>
                                <p class="mt-1 text-xs text-slate-500">Aktivitas belajar Anda akan muncul di sini setelah mulai mengerjakan materi.</p>
                            </div>

                            <!-- Data State -->
                            <div v-else class="space-y-5">
                                <div v-for="activity in recentActivities" :key="activity.id" class="flex gap-4 items-start">
                                    <div class="mt-1">
                                        <div :class="`flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-slate-50 shadow-sm ${activity.color}`">
                                            <i :class="`pi ${activity.icon} text-[14px]`"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[13px] font-bold text-slate-800 leading-snug">{{ activity.title }}</p>
                                        <p class="mt-0.5 text-[12px] font-medium text-[var(--theme-primary)]">{{ activity.subject }}</p>
                                        <p class="mt-1.5 flex items-center text-[10px] font-bold text-slate-400">
                                            <i class="pi pi-clock mr-1.5 text-[10px]"></i>
                                            {{ activity.time }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <!-- AI INSIGHTS -->
                        <Card class="rounded-2xl border border-[var(--theme-border)] bg-gradient-to-b from-indigo-50/50 to-white p-6 shadow-sm">
                            <h3 class="mb-5 text-[16px] font-extrabold text-indigo-900"><i class="pi pi-sparkles mr-2 text-indigo-500"></i> Insight AI Tutor</h3>
                            
                            <!-- Loading State -->
                            <div v-if="isRefreshing" class="space-y-3">
                                <div v-for="i in 2" :key="i" class="h-16 w-full animate-pulse rounded-xl bg-indigo-50"></div>
                            </div>

                            <!-- Empty State -->
                            <div v-else-if="aiInsights.length === 0" class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100/50">
                                    <i class="pi pi-sparkles text-xl text-indigo-400"></i>
                                </div>
                                <h4 class="text-sm font-bold text-slate-700">Belum Ada Insight</h4>
                                <p class="mt-1 text-xs text-slate-500">Selesaikan aktivitas pembelajaran untuk mendapatkan rekomendasi belajar dari AI.</p>
                            </div>

                            <!-- Data State -->
                            <div v-else class="space-y-3">
                                <div v-for="(insight, idx) in aiInsights" :key="idx" class="rounded-xl bg-white border border-indigo-100 p-4 shadow-sm relative overflow-hidden">
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-400"></div>
                                    <p class="text-[12.5px] font-medium text-slate-700 leading-relaxed pl-2">{{ insight }}</p>
                                </div>
                            </div>
                        </Card>

                    </div>
                </div>
            </div>
        </div>
    </main>
</template>