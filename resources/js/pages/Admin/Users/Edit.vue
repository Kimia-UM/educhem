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

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        roles: Array<{ name: string }>;
    };
    roles: Array<{ id: number; name: string }>;
}>();

// Set default value dropdown ke role pertama yang dimiliki user
const form = useForm({
    role: props.user.roles[0]?.name || 'SISWA',
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id), {
        onError: () => {
            toast.error('Gagal mengubah role', {
                description: 'Terjadi kesalahan saat menyimpan perubahan.',
            });
        },
    });
};
</script>

<template>
    <Head title="Ubah Role Pengguna" />

    <div class="min-h-screen bg-[#F8FAFC] px-6 py-8 font-sans lg:px-10">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-slate-900">
                    Ubah Role Pengguna
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
                        >Detail Pengguna</CardTitle
                    >
                    <CardDescription
                        >Ubah hak akses untuk pengguna <b>{{ user.name }}</b
                        >.</CardDescription
                    >
                </CardHeader>

                <CardContent class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                >Nama Lengkap</label
                            >
                            <input
                                type="text"
                                :value="user.name"
                                disabled
                                class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                >Email</label
                            >
                            <input
                                type="text"
                                :value="user.email"
                                disabled
                                class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                                >Pilih Role Baru
                                <span class="text-rose-500">*</span></label
                            >
                            <select
                                v-model="form.role"
                                required
                                class="w-full cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800 shadow-sm transition-colors outline-none focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.name"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>

                        <div class="flex justify-end pt-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-indigo-600 text-white shadow-sm hover:bg-indigo-700"
                            >
                                <i class="pi pi-save mr-2 text-xs"></i> Simpan
                                Perubahan
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
