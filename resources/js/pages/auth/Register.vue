<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Register - EduChem LC5E System',
        description: 'Enter your details below to create your account',
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// State untuk toggle mata password
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const togglePasswordConfirmation = () => {
    showPasswordConfirmation.value = !showPasswordConfirmation.value;
};

const submit = () => {
    // URL string langsung seperti yang Anda buat sebelumnya
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register - EduChem LC5E System" />

    <div
        class="flex min-h-screen flex-col justify-center bg-[#F0F4FF] py-12 font-sans sm:px-6 lg:px-8"
    >
        <div class="sm:mx-auto sm:w-full sm:max-w-[420px]">
            <div
                class="border border-gray-100 bg-white px-6 py-8 shadow-sm sm:rounded-xl sm:px-10"
            >
                <div class="mb-7 text-center">
                    <h1
                        class="mb-2 text-[28px] font-bold tracking-tight text-gray-900"
                    >
                        Create an account
                    </h1>
                    <p class="text-[15px] font-medium text-gray-500">
                        Enter your details below to get started
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Nama Field -->
                    <div>
                        <label
                            for="name"
                            class="mb-1.5 block text-[14px] font-medium text-gray-700"
                            >Full Name</label
                        >
                        <div class="mt-1">
                            <input
                                id="name"
                                type="text"
                                autocomplete="name"
                                required
                                autofocus
                                v-model="form.name"
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-[#4F46E5] focus:ring-[#4F46E5] focus:outline-none sm:text-[14px]"
                                placeholder="E.g. Budi Santoso"
                            />
                        </div>
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-[14px] font-medium text-gray-700"
                            >Email address</label
                        >
                        <div class="mt-1">
                            <input
                                id="email"
                                type="email"
                                autocomplete="email"
                                required
                                v-model="form.email"
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-[#4F46E5] focus:ring-[#4F46E5] focus:outline-none sm:text-[14px]"
                                placeholder="E.g. budi@siswa.com"
                            />
                        </div>
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-[14px] font-medium text-gray-700"
                            >Password</label
                        >
                        <div class="relative mt-1">
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                v-model="form.password"
                                class="block w-full appearance-none rounded-lg border border-gray-300 py-2.5 pr-10 pl-4 placeholder-gray-400 shadow-sm transition-colors focus:border-[#4F46E5] focus:ring-[#4F46E5] focus:outline-none sm:text-[14px]"
                                placeholder="Create a password"
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
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                                <svg
                                    v-show="showPassword"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                    />
                                </svg>
                            </button>
                        </div>
                        <InputError
                            :message="form.errors.password"
                            class="mt-1"
                        />
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-1.5 block text-[14px] font-medium text-gray-700"
                            >Confirm Password</label
                        >
                        <div class="relative mt-1">
                            <input
                                id="password_confirmation"
                                :type="
                                    showPasswordConfirmation
                                        ? 'text'
                                        : 'password'
                                "
                                autocomplete="new-password"
                                required
                                v-model="form.password_confirmation"
                                class="block w-full appearance-none rounded-lg border border-gray-300 py-2.5 pr-10 pl-4 placeholder-gray-400 shadow-sm transition-colors focus:border-[#4F46E5] focus:ring-[#4F46E5] focus:outline-none sm:text-[14px]"
                                placeholder="Confirm your password"
                            />

                            <button
                                type="button"
                                @click.prevent="togglePasswordConfirmation"
                                class="absolute inset-y-0 right-0 flex items-center justify-center px-4 text-gray-400 transition-colors hover:text-gray-600 focus:outline-none"
                            >
                                <svg
                                    v-show="!showPasswordConfirmation"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                                <svg
                                    v-show="showPasswordConfirmation"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                    />
                                </svg>
                            </button>
                        </div>
                        <InputError
                            :message="form.errors.password_confirmation"
                            class="mt-1"
                        />
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full justify-center rounded-lg border border-transparent bg-[#4F46E5] px-4 py-2.5 text-[15px] font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:ring-2 focus:ring-[#4F46E5] focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                        >
                            <Spinner
                                v-if="form.processing"
                                class="mr-2 h-5 w-5 text-white"
                            />
                            Register
                        </button>
                    </div>
                </form>

                <!-- Redirect to Login -->
                <div class="mt-8 text-center text-[14px]">
                    <span class="font-medium text-gray-500"
                        >Already have an account?
                    </span>
                    <!-- Menggunakan string literal '/login' agar aman -->
                    <Link
                        href="/login"
                        class="font-semibold text-[#4F46E5] transition-colors hover:text-indigo-500"
                    >
                        Log in ↗
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
