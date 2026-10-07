<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { h } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: (_h, page) =>
        h(
            AuthLayout,
            {
                title: 'Lupa Password',
                description:
                    'Masukkan email Anda untuk mengajukan reset password ke Admin',
            },
            () => page,
        ),
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa Password" />

    <div
        v-if="status"
        class="mb-4 rounded-xl border border-green-100 bg-green-50 px-3 py-2 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">Alamat Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="email@contoh.com"
                    class="rounded-xl"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="h-11 w-full rounded-xl bg-blue-600 font-bold text-white transition-all hover:bg-blue-700"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Kirim Permintaan Reset
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Kembali ke halaman</span>
            <TextLink :href="login()">Log In</TextLink>
        </div>
    </div>
</template>
