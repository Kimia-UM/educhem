<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { toast } from 'vue-sonner';

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
        }
    });
};
</script>

<template>
    <Head title="Ubah Role Pengguna" />

    <div class="min-h-screen bg-[#F8FAFC] py-8 px-6 lg:px-10 font-sans">
        <div class="max-w-3xl mx-auto">
            
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-slate-900">Ubah Role Pengguna</h2>
                <Link :href="route('admin.users.index')">
                    <Button variant="outline" class="h-9 bg-white shadow-sm border-slate-200">
                        <i class="pi pi-arrow-left mr-2 text-xs"></i> Kembali
                    </Button>
                </Link>
            </div>

            <Card class="shadow-sm border-slate-200 rounded-xl bg-white overflow-hidden">
                <CardHeader class="border-b border-slate-100 bg-slate-50/50 p-6">
                    <CardTitle class="text-lg font-bold text-slate-800">Detail Pengguna</CardTitle>
                    <CardDescription>Ubah hak akses untuk pengguna <b>{{ user.name }}</b>.</CardDescription>
                </CardHeader>
                
                <CardContent class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap</label>
                            <input type="text" :value="user.name" disabled class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-slate-500 cursor-not-allowed text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                            <input type="text" :value="user.email" disabled class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-slate-500 cursor-not-allowed text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Role Baru <span class="text-rose-500">*</span></label>
                            <select v-model="form.role" required class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-800 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors shadow-sm outline-none cursor-pointer">
                                <option v-for="role in roles" :key="role.id" :value="role.name">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                                <i class="pi pi-save mr-2 text-xs"></i> Simpan Perubahan
                            </Button>
                        </div>

                    </form>
                </CardContent>
            </Card>

        </div>
    </div>
</template>