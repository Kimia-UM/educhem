<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <Card class="border-destructive/30 dark:border-destructive/30">
        <CardHeader>
            <CardTitle class="text-destructive">Delete Account</CardTitle>
            <CardDescription>
                Permanently delete your account and all of its resources.
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div
                class="space-y-2 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-950/30 dark:bg-red-950/20 text-red-600 dark:text-red-400 text-sm"
            >
                <p class="font-medium">Warning</p>
                <p class="leading-relaxed">
                    Please proceed with caution, this cannot be undone. Once your account is deleted, all of its resources and data will be permanently deleted.
                </p>
            </div>
        </CardContent>
        <CardFooter class="border-t border-destructive/10 bg-destructive/5 dark:bg-destructive/10 px-6 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <span class="text-sm text-muted-foreground">
                This action requires password confirmation.
            </span>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button">
                        Delete account
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                Are you sure you want to delete your account?
                            </DialogTitle>
                            <DialogDescription>
                                Once your account is deleted, all of its
                                resources and data will also be permanently
                                deleted. Please enter your password to confirm
                                you would like to permanently delete your
                                account.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Password</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                Delete account
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </CardFooter>
    </Card>
</template>
