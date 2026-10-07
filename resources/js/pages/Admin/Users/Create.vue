<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';

defineProps<{
    roles: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'SISWA',
});

const submit = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
        onError: () => {
            toast.error('Gagal membuat akun', {
                description: 'Silakan periksa kembali data yang dimasukkan.',
            });
        },
    });
};
</script>

<template>
    <Head title="Tambah Akun Baru" />

    <div class="min-h-screen bg-[#F8FAFC] px-6 py-8 font-sans lg:px-10">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-slate-900">
                    Tambah Akun Baru
                </h2>
                <Link :href="route('admin.users.index')">
                    <Button
                        variant="outline"
                        class="h-9 border-slate-200 bg-white shadow-sm"
                    >
                        <i class="pi pi-arrow-left mr-2 text-xs"></i> Kembali
                    </Button>
                </Link>
            </div>

            <Card
                class="overflow-hidden rounded-xl border-slate-200 bg-white shadow-sm"
            >
                <CardHeader
                    class="border-b border-slate-100 bg-slate-50/50 p-6"
                >
                    <CardTitle class="text-lg font-bold text-slate-800"
                        >Informasi Pengguna</CardTitle
                    >
                    <CardDescription
                        >Masukkan data detail untuk membuat akun pengguna baru
                        di sistem.</CardDescription
                    >
                </CardHeader>

                <CardContent class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                >Nama Lengkap
                                <span class="text-rose-500">*</span></label
                            >
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Ketik nama lengkap..."
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm transition-all outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                :class="{
                                    'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20':
                                        form.errors.name,
                                }"
                            />
                            <span
                                v-if="form.errors.name"
                                class="mt-1 block text-xs font-medium text-rose-500"
                                >{{ form.errors.name }}</span
                            >
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                >Alamat Email
                                <span class="text-rose-500">*</span></label
                            >
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="name@example.com"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm transition-all outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                :class="{
                                    'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20':
                                        form.errors.email,
                                }"
                            />
                            <span
                                v-if="form.errors.email"
                                class="mt-1 block text-xs font-medium text-rose-500"
                                >{{ form.errors.email }}</span
                            >
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                    >Kata Sandi
                                    <span class="text-rose-500">*</span></label
                                >
                                <input
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="Minimal 8 karakter..."
                                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm transition-all outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    :class="{
                                        'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20':
                                            form.errors.password,
                                    }"
                                />
                                <span
                                    v-if="form.errors.password"
                                    class="mt-1 block text-xs font-medium text-rose-500"
                                    >{{ form.errors.password }}</span
                                >
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-slate-700"
                                    >Konfirmasi Kata Sandi
                                    <span class="text-rose-500">*</span></label
                                >
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    placeholder="Ulangi kata sandi..."
                                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm transition-all outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                >Pilih Peran (Role)
                                <span class="text-rose-500">*</span></label
                            >
                            <select
                                v-model="form.role"
                                required
                                class="w-full cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm transition-all outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                :class="{
                                    'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20':
                                        form.errors.role,
                                }"
                            >
                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.name"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                            <span
                                v-if="form.errors.role"
                                class="mt-1 block text-xs font-medium text-rose-500"
                                >{{ form.errors.role }}</span
                            >
                        </div>

                        <div class="flex justify-end pt-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-indigo-600 font-bold text-white shadow-sm hover:bg-indigo-700"
                            >
                                <i class="pi pi-user-plus mr-2 text-xs"></i>
                                Buat Akun Pengguna
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
