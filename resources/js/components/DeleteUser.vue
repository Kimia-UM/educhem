<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
                class="space-y-2 rounded-lg border border-red-100 bg-red-50 p-4 text-sm text-red-600 dark:border-red-950/30 dark:bg-red-950/20 dark:text-red-400"
            >
                <p class="font-medium">Warning</p>
                <p class="leading-relaxed">
                    Please proceed with caution, this cannot be undone. Once
                    your account is deleted, all of its resources and data will
                    be permanently deleted.
                </p>
            </div>
        </CardContent>
        <CardFooter
            class="flex flex-col items-start justify-between gap-4 border-t border-destructive/10 bg-destructive/5 px-6 py-4 sm:flex-row sm:items-center dark:bg-destructive/10"
        >
            <span class="text-sm text-muted-foreground">
                This action requires password confirmation.
            </span>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                    >
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
                            <Label for="password" class="sr-only"
                                >Password</Label
                            >
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
                                    class="rounded-xl border border-slate-200 text-[13px] font-bold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                                class="rounded-xl bg-gradient-to-r from-rose-500 to-red-600 text-[13px] font-bold text-white shadow-md shadow-rose-100 hover:from-rose-600 hover:to-red-700 dark:shadow-none"
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
