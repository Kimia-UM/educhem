<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineOptions({
    layout: (h, page) => h(AuthLayout, {
        title: 'Reset Password',
        description: 'Status pengajuan reset password Anda',
    }, () => page),
});

const props = defineProps<{
    token: string;
    status: string;
    email: string;
}>();

const currentStatus = ref(props.status);
let pollingInterval: any = null;

const form = useForm({
    password: '',
    password_confirmation: '',
});

const startPolling = () => {
    pollingInterval = setInterval(async () => {
        try {
            const response = await axios.get(route('password-reset-request.status', { token: props.token }));
            if (response.data && response.data.status) {
                currentStatus.value = response.data.status;
                if (currentStatus.value !== 'pending') {
                    clearInterval(pollingInterval);
                }
            }
        } catch (e) {
            console.error('Error polling status:', e);
        }
    }, 4000); // Polling setiap 4 detik
};

onMounted(() => {
    if (currentStatus.value === 'pending') {
        startPolling();
    }
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

const submitNewPassword = () => {
    form.post(route('password-reset-request.reset', { token: props.token }), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="space-y-6">
        <!-- 1. FASE TUNGGU (PENDING) -->
        <div v-if="currentStatus === 'pending'" class="text-center space-y-6 py-4 animate-in fade-in duration-300">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/30 text-blue-600 dark:text-blue-400 shadow-inner">
                <i class="pi pi-spin pi-spinner text-2xl"></i>
            </div>
            
            <div class="space-y-2">
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Menunggu Persetujuan Admin</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Permintaan reset password untuk akun <strong class="text-slate-700 dark:text-slate-300">{{ email }}</strong> telah dikirimkan ke Admin.
                </p>
            </div>

            <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30 p-4 text-[12px] text-slate-500 dark:text-slate-400">
                <i class="pi pi-info-circle mr-1 text-blue-500"></i> Halaman ini akan otomatis beralih begitu Admin menyetujui permintaan Anda. Mohon jangan menutup halaman ini.
            </div>

            <div class="pt-2 text-center text-sm">
                <TextLink :href="login()">Batalkan & Kembali</TextLink>
            </div>
        </div>

        <!-- 2. FASE INPUT PASSWORD BARU (APPROVED) -->
        <div v-else-if="currentStatus === 'approved'" class="space-y-6 animate-in zoom-in-95 fade-in duration-300">
            <div class="text-center space-y-2 mb-2">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-50 dark:bg-green-950/30 border border-green-100 dark:border-green-900/30 text-green-600 dark:text-green-400 shadow-inner">
                    <i class="pi pi-check text-2xl"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900 dark:text-slate-100">Permintaan Disetujui!</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Silakan buat password baru Anda di bawah ini.</p>
            </div>

            <form @submit.prevent="submitNewPassword" class="space-y-4">
                <div class="grid gap-2">
                    <Label for="password">Password Baru</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="rounded-xl"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Konfirmasi Password Baru</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="rounded-xl"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="pt-2">
                    <Button
                        type="submit"
                        class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition-all shadow-md"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        Simpan Password Baru
                    </Button>
                </div>
            </form>
        </div>

        <!-- 3. FASE DITOLAK / KEDALUWARSA (REJECTED) -->
        <div v-else class="text-center space-y-6 py-4 animate-in fade-in duration-300">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 shadow-inner">
                <i class="pi pi-times text-2xl"></i>
            </div>
            
            <div class="space-y-2">
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Permintaan Tidak Valid</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    Pengajuan reset password ditolak oleh Admin atau sesi pengajuan Anda telah kedaluwarsa (lebih dari 30 menit).
                </p>
            </div>

            <div class="pt-2 flex flex-col gap-3">
                <Button
                    type="button"
                    variant="outline"
                    @click="router.visit(route('password.request'))"
                    class="h-11 rounded-xl font-bold w-full border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300"
                >
                    Ajukan Ulang
                </Button>
                <div class="text-center text-sm">
                    <TextLink :href="login()">Kembali ke Log In</TextLink>
                </div>
            </div>
        </div>
    </div>
</template>
