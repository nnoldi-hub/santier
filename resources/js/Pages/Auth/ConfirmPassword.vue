<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirma parola - Modulia" />

        <div class="mb-5 rounded-xl border border-[#F57C00]/20 bg-orange-50 px-4 py-3">
            <h1 class="text-base font-bold text-[#1A237E]">Confirma parola</h1>
            <p class="mt-1 text-sm text-gray-600">Aceasta e o zona securizata - confirma parola inainte sa continui.</p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Parola" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Confirma
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
