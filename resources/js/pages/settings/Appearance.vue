<script setup lang="ts">
import { h } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { edit } from '@/routes/appearance';

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
        <Card>
            <CardHeader>
                <CardTitle>Appearance Settings</CardTitle>
                <CardDescription>
                    Customize how the application looks on your device.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AppearanceTabs />
            </CardContent>
        </Card>
    </div>
</template>
