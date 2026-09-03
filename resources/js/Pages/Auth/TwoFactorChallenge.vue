<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    maskedEmail: { type: String, default: '' },
});

const page = usePage();

const form = useForm({
    code: '',
    remember_device: false,
});

const resendForm = useForm({});

const submit = () => {
    form.post(route('two-factor.store'));
};

const resend = () => {
    resendForm.post(route('two-factor.resend'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Verificare in doi factori" />

        <div class="mb-5 rounded-xl border border-[#F57C00]/20 bg-orange-50 px-4 py-3">
            <h1 class="text-base font-bold text-[#1A237E]">Verificare in doi factori</h1>
            <p class="mt-1 text-sm text-gray-600">
                Am trimis un cod pe {{ maskedEmail }}. Introduce-l mai jos pentru a continua.
            </p>
        </div>

        <div v-if="page.props.flash?.success" class="mb-4 text-sm font-medium text-green-600">
            {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash?.error" class="mb-4 text-sm font-medium text-red-600">
            {{ page.props.flash.error }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="code" value="Cod de verificare" />

                <TextInput
                    id="code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    class="mt-1 block w-full text-center text-2xl tracking-[0.5em]"
                    v-model="form.code"
                    required
                    autofocus
                    autocomplete="one-time-code"
                />

                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember_device" v-model:checked="form.remember_device" />
                    <span class="ms-2 text-sm text-gray-600">
                        Tine-ma conectat pe acest dispozitiv timp de 30 de zile
                    </span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <SecondaryButton
                    type="button"
                    :class="{ 'opacity-25': resendForm.processing }"
                    :disabled="resendForm.processing"
                    @click="resend"
                >
                    Retrimite codul
                </SecondaryButton>

                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Confirma
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
