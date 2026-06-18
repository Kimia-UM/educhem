<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <Card v-if="canManageTwoFactor">
        <CardHeader>
            <CardTitle>Two-Factor Authentication</CardTitle>
            <CardDescription>
                Add additional security to your account using two-factor authentication.
            </CardDescription>
        </CardHeader>

        <CardContent class="space-y-4">
            <div
                v-if="!twoFactorEnabled"
                class="flex flex-col items-start justify-start space-y-4"
            >
                <p class="text-sm text-muted-foreground leading-relaxed">
                    When you enable two-factor authentication, you will be prompted
                    for a secure pin during login. This pin can be retrieved from a
                    TOTP-supported application on your phone.
                </p>
            </div>

            <div v-else class="flex flex-col items-start justify-start space-y-4">
                <p class="text-sm text-muted-foreground leading-relaxed">
                    You will be prompted for a secure, random pin during login,
                    which you can retrieve from the TOTP-supported application on
                    your phone.
                </p>

                <TwoFactorRecoveryCodes />
            </div>
        </CardContent>

        <CardFooter class="border-t bg-muted/10 px-6 py-4 flex items-center justify-end">
            <div v-if="!twoFactorEnabled">
                <Button v-if="hasSetupData" @click="showSetupModal = true">
                    <ShieldCheck class="mr-2 h-4 w-4" />Continue setup
                </Button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Enable 2FA
                    </Button>
                </Form>
            </div>

            <div v-else>
                <Form v-bind="disable.form()" #default="{ processing }">
                    <Button
                        variant="destructive"
                        type="submit"
                        :disabled="processing"
                    >
                        Disable 2FA
                    </Button>
                </Form>
            </div>
        </CardFooter>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </Card>
</template>
