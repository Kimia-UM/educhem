<script setup lang="ts">
import { h } from 'vue';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: (h, page) => {
        return h(
            AppLayout,
            {
                breadcrumbs: [
                    {
                        title: 'Profile settings',
                        href: edit(),
                    },
                ],
            },
            () => h(SettingsLayout, null, () => page)
        );
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>Profile Information</CardTitle>
                <CardDescription>
                    Update your account's profile information and email address.
                </CardDescription>
            </CardHeader>

            <Form
                v-bind="ProfileController.update.form()"
                @success="toast.success('Profil Diperbarui', { description: 'Perubahan profil Anda berhasil disimpan.', position: 'bottom-right' })"
                @error="toast.error('Gagal memperbarui profil')"
                v-slot="{ errors, processing }"
            >
                <CardContent class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-2 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="page.props.status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email address.
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="border-t bg-muted/10 px-6 py-4 flex items-center justify-end">
                    <Button :disabled="processing" data-test="update-profile-button">
                        Save
                    </Button>
                </CardFooter>
            </Form>
        </Card>

        <DeleteUser />
    </div>
</template>
