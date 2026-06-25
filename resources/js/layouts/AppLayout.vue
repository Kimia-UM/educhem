<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner'; // Cukup import komponennya saja
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();

watch(
    () => page.props.flash,
    (flash: any) => {
        if (!flash) return;
        
        if (flash.success) {
            toast.success(flash.success, {
                duration: 5000,
            });
        }
        if (flash.error) {
            toast.error(flash.error, {
                duration: 6000,
            });
        }
        if (flash.toast) {
            const { type, message } = flash.toast;
            if (type && message && typeof (toast as any)[type] === 'function') {
                (toast as any)[type](message, {
                    duration: 5000,
                });
            }
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>

</template>