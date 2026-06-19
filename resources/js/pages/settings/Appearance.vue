<script setup lang="ts">
import { h } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { edit } from '@/routes/appearance';
import { useTheme } from '@/composables/useTheme';

const { theme, updateTheme } = useTheme();

const themes = [
    {
        value: 'elegan',
        name: 'Elegan',
        description: 'Mewah, profesional dengan sentuhan Slate & Indigo.',
        previewClass: 'from-slate-900 to-indigo-600',
    },
    {
        value: 'genz',
        name: 'GenZ',
        description: 'Vibrant, berjiwa muda dengan gradasi pink neon & cyan.',
        previewClass: 'from-pink-500 to-cyan-400',
    },
    {
        value: 'classic',
        name: 'Classic',
        description: 'Hangat, retro dengan nuansa hijau forest & krem kertas.',
        previewClass: 'from-green-700 to-amber-500',
    }
] as const;

defineOptions({
    layout: (h, page) => {
        return h(
            AppLayout,
            {
                breadcrumbs: [
                    {
                        title: 'Appearance settings',
                        href: edit(),
                    },
                ],
            },
            () => h(SettingsLayout, null, () => page)
        );
    },
});
</script>

<template>
    <Head title="Appearance settings" />

    <h1 class="sr-only">Appearance settings</h1>

    <div class="space-y-6">
        <Card class="border-slate-200 shadow-sm rounded-xl bg-white">
            <CardHeader class="p-6">
                <CardTitle class="text-[17px] font-bold text-slate-800">Mode Tampilan</CardTitle>
                <CardDescription class="text-[13px] text-slate-500 mt-1">
                    Pilih skema pencahayaan aplikasi (Light, Dark, atau otomatis mengikuti sistem).
                </CardDescription>
            </CardHeader>
            <CardContent class="p-6 pt-0">
                <AppearanceTabs />
            </CardContent>
        </Card>

        <Card class="border-slate-200 shadow-sm rounded-xl bg-white">
            <CardHeader class="p-6">
                <CardTitle class="text-[17px] font-bold text-slate-800">Tema Hiasan & Warna</CardTitle>
                <CardDescription class="text-[13px] text-slate-500 mt-1">
                    Sesuaikan ornamen dekoratif dan nuansa warna utama aplikasi untuk Guru & Siswa.
                </CardDescription>
            </CardHeader>
            <CardContent class="p-6 pt-0">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button
                        v-for="item in themes"
                        :key="item.value"
                        @click="updateTheme(item.value)"
                        :class="[
                            'flex flex-col text-left p-4 rounded-2xl border transition-all duration-300 relative overflow-hidden',
                            theme === item.value
                                ? 'border-indigo-600 bg-indigo-50/10 shadow-xs ring-2 ring-indigo-600/20'
                                : 'border-slate-200 hover:border-slate-350 bg-white hover:bg-slate-50/50'
                        ]"
                    >
                        <!-- Gradient Color Preview Block -->
                        <div :class="['w-full h-12 rounded-lg bg-gradient-to-r mb-3', item.previewClass]"></div>
                        
                        <div class="flex items-center justify-between w-full mb-1">
                            <span class="font-bold text-[14px] text-slate-800">{{ item.name }}</span>
                            <span v-if="theme === item.value" class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-white text-[10px]">
                                <i class="pi pi-check"></i>
                            </span>
                        </div>
                        <p class="text-[12px] text-slate-550 leading-normal">{{ item.description }}</p>
                    </button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
