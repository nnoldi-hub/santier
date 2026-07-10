<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificare email - Modulia" />

        <div class="mb-5 rounded-xl border border-[#F57C00]/20 bg-orange-50 px-4 py-3">
            <h1 class="text-base font-bold text-[#1A237E]">Verifica-ti adresa de email</h1>
            <p class="mt-1 text-sm text-gray-600">
                Multumim ca te-ai inregistrat! Apasa link-ul din emailul trimis ca sa-ti activezi contul.
                Daca nu l-ai primit, iti trimitem cu placere altul.
            </p>
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            Un nou link de verificare a fost trimis pe adresa de email folosita la inregistrare.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Retrimite email de verificare
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#F57C00] focus:ring-offset-2"
                    >Delogare</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
