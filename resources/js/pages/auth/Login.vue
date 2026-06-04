<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { ref } from 'vue';

defineOptions({
    layout: {
        title: 'Login - EduChem POE System',
        description: 'Login to your account',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// 1. SOLUSI: Tangkap instance form ke dalam variabel lokal.
// Ini mencegah data hilang/reset saat menekan tombol mata (re-render)
const loginForm = store.form();

const showPassword = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <Head title="Login - EduChem POE System" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <div class="flex min-h-screen flex-col justify-center bg-[#F0F4FF] py-12 font-sans sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-[420px]">
            <div class="border border-gray-100 bg-white px-6 py-8 shadow-sm sm:rounded-xl sm:px-10">
                
                <div class="mb-7 text-center">
                    <h1 class="mb-2 text-[28px] font-bold tracking-tight text-gray-900">
                        Login
                    </h1>
                    <p class="text-[15px] font-medium text-gray-500">
                        Sign in to your account
                    </p>
                </div>

                <Form
                    v-bind="loginForm"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-5"
                >
                    <div>
                        <label for="email" class="mb-1.5 block text-[14px] font-medium text-gray-700">Email</label>
                        <div class="mt-1">
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                v-model="loginForm.email"
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-[#4F46E5] focus:ring-[#4F46E5] focus:outline-none sm:text-[14px]"
                                placeholder="E.g. johndoe@email.com"
                            />
                        </div>
                        <InputError :message="errors.email" class="mt-1" />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-[14px] font-medium text-gray-700">Password</label>
                        <div class="relative mt-1">
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                v-model="loginForm.password"
                                class="block w-full appearance-none rounded-lg border border-gray-300 py-2.5 pr-10 pl-4 placeholder-gray-400 shadow-sm transition-colors focus:border-[#4F46E5] focus:ring-[#4F46E5] focus:outline-none sm:text-[14px]"
                                placeholder="Enter your password"
                            />
                            
                            <button
                                type="button"
                                @click.prevent="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center justify-center px-4 text-gray-400 transition-colors hover:text-gray-600 focus:outline-none"
                            >
                                <svg
                                    v-show="!showPassword"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg
                                    v-show="showPassword"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <InputError :message="errors.password" class="mt-1" />
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                v-model="loginForm.remember"
                                class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#4F46E5] focus:ring-[#4F46E5]"
                            />
                            <label for="remember" class="ml-2 block cursor-pointer text-[13px] text-gray-600">
                                Remember Me
                            </label>
                        </div>

                        <div class="text-sm">
                            <Link
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-[13px] font-semibold text-[#4F46E5] transition-colors hover:text-indigo-500"
                            >
                                Forgot Password?
                            </Link>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="flex w-full justify-center rounded-lg border border-transparent bg-[#4F46E5] px-4 py-2.5 text-[15px] font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:ring-2 focus:ring-[#4F46E5] focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                        >
                            <Spinner v-if="processing" class="mr-2 h-5 w-5 text-white" />
                            Login
                        </button>
                    </div>
                </Form>

                <div class="mt-8 text-center text-[14px]">
                    <span class="font-medium text-gray-500">Not registered yet? </span>
                    <Link :href="route('register')" class="font-semibold text-[#4F46E5] transition-colors hover:text-indigo-500">
                        Create an account ↗
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>