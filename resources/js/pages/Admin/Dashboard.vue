<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

// --- IMPORT KOMPONEN SHADCN-VUE ---
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

// --- TypeScript Interfaces ---
interface RecentUser {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    created_at: string;
}

interface ActiveClass {
    id: number;
    name: string;
    code: string;
    teacher: string;
    students: number;
    topics: number;
}

interface SystemHealth {
    db: string;
    queue: string;
}

defineProps<{
    stats: {
        total_users: number;
        total_guru: number;
        total_siswa: number;
        total_kelas: number;
        total_topik: number;
        total_ai_requests: number;
        ai_success_rate: number;
        recent_users: RecentUser[];
        active_classes: ActiveClass[];
        system_health: SystemHealth;
    };
}>();

// --- Polling otomatis: refresh data setiap 30 detik ---
let pollInterval: ReturnType<typeof setInterval>;

onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({ only: ['stats'] });
    }, 30000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <Head title="Dashboard Admin" />

    <div class="min-h-screen bg-[#F8FAFC] px-6 py-8 font-sans lg:px-10">
        <div
            class="mx-auto mb-8 flex max-w-7xl flex-col items-start justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h1 class="text-[26px] font-bold tracking-tight text-slate-900">
                    EduChem LC5E System
                </h1>
                <p class="mt-1 text-[14px] font-medium text-slate-500">
                    Dashboard Administrasi & Monitoring Sistem Periodik
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative hidden w-[250px] md:block">
                    <i
                        class="pi pi-search absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-400"
                    ></i>
                    <Input
                        type="text"
                        placeholder="Cari data..."
                        class="h-9 w-full rounded-lg border-slate-200 bg-white pl-9 text-[13px] shadow-sm placeholder:text-slate-400 focus-visible:ring-indigo-500"
                    />
                </div>

                <Button
                    variant="outline"
                    size="icon"
                    class="h-9 w-9 rounded-lg border-slate-200 bg-white shadow-sm transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                >
                    <i class="pi pi-bell text-[14px]"></i>
                </Button>

                <div
                    class="flex cursor-pointer items-center gap-3 rounded-full border border-slate-200 bg-white py-1.5 pr-4 pl-1.5 shadow-sm transition-colors hover:bg-slate-50"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white shadow-inner"
                    >
                        SA
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-[13px] leading-none font-bold text-slate-800"
                            >Super Admin</span
                        >
                        <span
                            class="mt-0.5 text-[9px] font-bold tracking-wider text-indigo-500"
                            >ROLE: ADMIN</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <div
            class="mx-auto mb-6 grid max-w-7xl grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3"
        >
            <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
                <CardContent class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="mb-2 text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                            >
                                Total Users
                            </p>
                            <h2 class="text-3xl font-extrabold text-slate-900">
                                {{ stats.total_users }}
                            </h2>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                        >
                            <i class="pi pi-users text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-[13px]">
                        <span
                            class="flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 font-semibold text-indigo-600"
                        >
                            <i class="pi pi-user text-[9px]"></i> {{ stats.total_guru }} Guru
                        </span>
                        <span
                            class="flex items-center gap-1 rounded bg-sky-50 px-1.5 py-0.5 font-semibold text-sky-600"
                        >
                            <i class="pi pi-id-card text-[9px]"></i> {{ stats.total_siswa }} Siswa
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
                <CardContent class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="mb-2 text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                            >
                                Total Kelas
                            </p>
                            <h2 class="text-3xl font-extrabold text-slate-900">
                                {{ stats.total_kelas }}
                            </h2>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                        >
                            <i class="pi pi-objects-column text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-[13px]">
                        <span
                            class="flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 font-semibold text-amber-600"
                        >
                            <i class="pi pi-book text-[9px]"></i> {{ stats.total_topik }} Topik
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card
                class="relative overflow-hidden rounded-xl border-none bg-gradient-to-br from-[#4F46E5] to-[#4338CA] shadow-sm"
            >
                <CardContent class="p-6">
                    <i
                        class="pi pi-share-alt absolute -right-3 -bottom-3 text-7xl text-white opacity-10"
                    ></i>
                    <div class="relative z-10 flex items-start justify-between">
                        <div>
                            <p
                                class="mb-2 text-[11px] font-bold tracking-wider text-indigo-100 uppercase"
                            >
                                AI LC5E Requests
                            </p>
                            <h2 class="text-3xl font-extrabold text-white">
                                {{ stats.total_ai_requests.toLocaleString('id-ID') }}
                            </h2>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-white backdrop-blur-sm"
                        >
                            <i class="pi pi-sparkles text-lg"></i>
                        </div>
                    </div>
                    <div
                        class="relative z-10 mt-4 flex items-center gap-2 text-sm"
                    >
                        <span
                            class="flex items-center gap-1.5 text-[13px] font-medium text-white"
                        >
                            <span
                                class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"
                            ></span>
                            {{ stats.ai_success_rate }}% Success Rate
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div
            class="mx-auto mb-6 grid max-w-7xl grid-cols-1 gap-5 xl:grid-cols-3"
        >
            <Card
                class="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm xl:col-span-2"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between border-b border-transparent p-6 pb-4"
                >
                    <CardTitle class="text-[17px] font-bold text-slate-800"
                        >User Management Terbaru</CardTitle
                    >
                    <Link :href="route('admin.users.index')">
                        <Button
                            variant="link"
                            class="h-auto p-0 font-semibold text-indigo-600 hover:text-indigo-700"
                            >Lihat Semua</Button
                        >
                    </Link>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader
                            class="border-y border-slate-200 bg-slate-50/80"
                        >
                            <TableRow class="border-none hover:bg-transparent">
                                <TableHead
                                    class="h-11 px-6 align-middle text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Nama Lengkap</TableHead
                                >
                                <TableHead
                                    class="h-11 align-middle text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Email</TableHead
                                >
                                <TableHead
                                    class="h-11 align-middle text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Role</TableHead
                                >
                                <TableHead
                                    class="h-11 align-middle text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Status</TableHead
                                >
                                <TableHead
                                    class="h-11 pr-6 text-right align-middle text-[11px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Bergabung</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="user in stats.recent_users"
                                :key="user.id"
                                class="border-b border-slate-100 transition-colors hover:bg-slate-50/60"
                            >
                                <TableCell
                                    class="px-6 py-4 text-[14px] font-semibold text-slate-900"
                                    >{{ user.name }}</TableCell
                                >
                                <TableCell
                                    class="text-[13px] font-medium text-slate-600"
                                    >{{ user.email }}</TableCell
                                >
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="
                                            user.role === 'GURU'
                                                ? 'border-indigo-200 bg-indigo-50 text-indigo-700'
                                                : user.role === 'ADMIN'
                                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                        "
                                        class="px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                                    >
                                        {{ user.role }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span
                                        class="flex items-center gap-1.5 text-[13px] font-medium text-slate-600"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                user.status === 'Active'
                                                    ? 'bg-emerald-500'
                                                    : 'bg-slate-300'
                                            "
                                        ></span>
                                        {{ user.status }}
                                    </span>
                                </TableCell>
                                <TableCell
                                    class="pr-6 text-right text-[13px] font-medium text-slate-500"
                                    >{{ user.created_at }}</TableCell
                                >
                            </TableRow>
                            <TableRow v-if="!stats.recent_users || stats.recent_users.length === 0">
                                <TableCell colspan="5" class="py-8 text-center text-sm text-slate-400">
                                    Belum ada pengguna terdaftar.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <div class="flex flex-col gap-5">
                <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
                    <CardHeader class="border-b border-slate-50 pb-4">
                        <CardTitle class="text-[16px] font-bold text-slate-800"
                            >System Health</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="space-y-4 pt-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg"
                                    :class="stats.system_health.db === 'online' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                >
                                    <i class="pi pi-database text-[14px]"></i>
                                </div>
                                <span
                                    class="text-[13px] font-semibold text-slate-700"
                                    >PostgreSQL</span
                                >
                            </div>
                            <Badge
                                variant="outline"
                                :class="stats.system_health.db === 'online'
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : 'border-rose-200 bg-rose-50 text-rose-700'"
                                class="text-[9px] font-bold tracking-wider uppercase"
                            >{{ stats.system_health.db === 'online' ? 'Online' : 'Offline' }}</Badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg"
                                    :class="stats.system_health.queue === 'online' ? 'bg-emerald-50 text-emerald-600' : stats.system_health.queue === 'warning' ? 'bg-orange-50 text-orange-600' : 'bg-rose-50 text-rose-600'"
                                >
                                    <i class="pi pi-bolt text-[14px]"></i>
                                </div>
                                <span
                                    class="text-[13px] font-semibold text-slate-700"
                                    >Job Queue</span
                                >
                            </div>
                            <Badge
                                variant="outline"
                                :class="stats.system_health.queue === 'online'
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : stats.system_health.queue === 'warning'
                                        ? 'border-orange-200 bg-orange-50 text-orange-700'
                                        : 'border-rose-200 bg-rose-50 text-rose-700'"
                                class="text-[9px] font-bold tracking-wider uppercase"
                            >{{ stats.system_health.queue === 'online' ? 'Online' : stats.system_health.queue === 'warning' ? 'Warning' : 'Offline' }}</Badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"
                                >
                                    <i
                                        class="pi pi-microchip-ai text-[14px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[13px] font-semibold text-slate-700"
                                    >Gemini API</span
                                >
                            </div>
                            <Badge
                                variant="outline"
                                :class="stats.total_ai_requests > 0
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : 'border-slate-200 bg-slate-50 text-slate-500'"
                                class="text-[9px] font-bold tracking-wider uppercase"
                            >{{ stats.total_ai_requests > 0 ? 'Connected' : 'No Data' }}</Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    class="rounded-xl border-slate-800 bg-slate-900 text-white shadow-sm"
                >
                    <CardContent class="p-6">
                        <h3
                            class="mb-4 text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                        >
                            AI Success Rate
                        </h3>
                        <div class="mb-3 flex items-baseline gap-2">
                            <span
                                class="text-4xl font-extrabold tracking-tight"
                                >{{ stats.ai_success_rate }}</span
                            >
                            <span class="text-lg font-bold text-emerald-400"
                                >%</span
                            >
                        </div>
                        <Progress
                            :model-value="stats.ai_success_rate"
                            class="h-2 bg-slate-800"
                            indicator-class="bg-indigo-500"
                        />
                        <p class="mt-4 text-[12px] font-medium text-slate-400">
                            {{ (100 - stats.ai_success_rate).toFixed(1) }}% failed dari
                            {{ stats.total_ai_requests.toLocaleString('id-ID') }} total requests
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div class="mx-auto max-w-7xl">
            <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
                <CardHeader class="border-b border-transparent pb-4">
                    <CardTitle class="text-[17px] font-bold text-slate-800"
                        >Class Monitoring (LC5E Progress)</CardTitle
                    >
                </CardHeader>
                <CardContent class="pt-2">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div
                            v-for="cls in stats.active_classes"
                            :key="cls.id"
                            class="rounded-xl border border-slate-100 bg-slate-50/50 p-5 transition-colors hover:bg-slate-50"
                        >
                            <div class="mb-4 flex items-start justify-between">
                                <div>
                                    <h4
                                        class="text-[15px] font-bold text-slate-900"
                                    >
                                        {{ cls.name }}
                                    </h4>
                                    <p
                                        class="mt-1 text-[13px] font-medium text-slate-500"
                                    >
                                        <i
                                            class="pi pi-user mr-1.5 text-[11px]"
                                        ></i>
                                        {{ cls.teacher }}
                                    </p>
                                </div>
                                <Badge
                                    variant="outline"
                                    class="border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-indigo-700 uppercase"
                                >{{ cls.code }}</Badge>
                            </div>
                            <div
                                class="flex gap-4 text-[12px] font-medium text-slate-600"
                            >
                                <span class="flex items-center gap-1.5">
                                    <i class="pi pi-users text-[10px] text-emerald-500"></i>
                                    {{ cls.students }} Siswa
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i class="pi pi-book text-[10px] text-amber-500"></i>
                                    {{ cls.topics }} Topik
                                </span>
                            </div>
                        </div>
                        <div
                            v-if="!stats.active_classes || stats.active_classes.length === 0"
                            class="col-span-2 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 p-8 text-center"
                        >
                            <i class="pi pi-inbox mb-3 text-3xl text-slate-300"></i>
                            <p class="text-sm font-medium text-slate-400">Belum ada kelas yang dibuat.</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
