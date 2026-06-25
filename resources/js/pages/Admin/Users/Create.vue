<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { toast } from 'vue-sonner';

const props = defineProps<{
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
        }
    });
};
</script>

<template>
    <Head title="Tambah Akun Baru" />

    <div class="min-h-screen bg-[#F8FAFC] py-8 px-6 lg:px-10 font-sans">
        <div class="max-w-3xl mx-auto">
            
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-slate-900">Tambah Akun Baru</h2>
                <Link :href="route('admin.users.index')">
                    <Button variant="outline" class="h-9 bg-white shadow-sm border-slate-200">
                        <i class="pi pi-arrow-left mr-2 text-xs"></i> Kembali
                    </Button>
                </Link>
            </div>

            <Card class="shadow-sm border-slate-200 rounded-xl bg-white overflow-hidden">
                <CardHeader class="border-b border-slate-100 bg-slate-50/50 p-6">
                    <CardTitle class="text-lg font-bold text-slate-800">Informasi Pengguna</CardTitle>
                    <CardDescription>Masukkan data detail untuk membuat akun pengguna baru di sistem.</CardDescription>
                </CardHeader>
                
                <CardContent class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.name"
                                type="text" 
                                required 
                                placeholder="Ketik nama lengkap..."
                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm" 
                                :class="{'border-rose-400 focus:ring-rose-500/20 focus:border-rose-500': form.errors.name}"
                            />
                            <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1 block font-medium">{{ form.errors.name }}</span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Email <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                required 
                                placeholder="name@example.com"
                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm"
                                :class="{'border-rose-400 focus:ring-rose-500/20 focus:border-rose-500': form.errors.email}"
                            />
                            <span v-if="form.errors.email" class="text-xs text-rose-500 mt-1 block font-medium">{{ form.errors.email }}</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kata Sandi <span class="text-rose-500">*</span></label>
                                <input 
                                    v-model="form.password"
                                    type="password" 
                                    required 
                                    placeholder="Minimal 8 karakter..."
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm"
                                    :class="{'border-rose-400 focus:ring-rose-500/20 focus:border-rose-500': form.errors.password}"
                                />
                                <span v-if="form.errors.password" class="text-xs text-rose-500 mt-1 block font-medium">{{ form.errors.password }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Kata Sandi <span class="text-rose-500">*</span></label>
                                <input 
                                    v-model="form.password_confirmation"
                                    type="password" 
                                    required 
                                    placeholder="Ulangi kata sandi..."
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Peran (Role) <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.role" 
                                required 
                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm cursor-pointer"
                                :class="{'border-rose-400 focus:ring-rose-500/20 focus:border-rose-500': form.errors.role}"
                            >
                                <option v-for="role in roles" :key="role.id" :value="role.name">
                                    {{ role.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.role" class="text-xs text-rose-500 mt-1 block font-medium">{{ form.errors.role }}</span>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <Button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm font-bold"
                            >
                                <i class="pi pi-user-plus mr-2 text-xs"></i> Buat Akun Pengguna
                            </Button>
                        </div>

                    </form>
                </CardContent>
            </Card>

        </div>
    </div>
</template>
