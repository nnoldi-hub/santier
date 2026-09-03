<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const props = defineProps({
    twoFactorEnabled: { type: Boolean, required: true },
});

const confirmingDisable = ref(false);
const passwordInput = ref(null);

const enableForm = useForm({ enabled: true });
const disableForm = useForm({ enabled: false, password: '' });

const enableTwoFactor = () => {
    enableForm.patch(route('two-factor.update'), { preserveScroll: true });
};

const confirmDisable = () => {
    confirmingDisable.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const disableTwoFactor = () => {
    disableForm.patch(route('two-factor.update'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => disableForm.reset('password'),
    });
};

const closeModal = () => {
    confirmingDisable.value = false;
    disableForm.clearErrors();
    disableForm.reset('password');
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Autentificare in doi factori
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Cand e activata, la autentificare ti se trimite si un cod pe email, pe langa parola.
                Poti bifa "Tine-ma conectat 30 de zile" pe un dispozitiv de incredere ca sa nu mai fie
                nevoie de cod (sau de parola, daca bifezi asta) in acea perioada.
            </p>
        </header>

        <div v-if="props.twoFactorEnabled" class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                Activata
            </span>
            <DangerButton @click="confirmDisable">Dezactiveaza</DangerButton>
        </div>
        <div v-else class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                Dezactivata
            </span>
            <PrimaryButton
                :class="{ 'opacity-25': enableForm.processing }"
                :disabled="enableForm.processing"
                @click="enableTwoFactor"
            >
                Activeaza
            </PrimaryButton>
        </div>

        <Modal :show="confirmingDisable" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Dezactivezi autentificarea in doi factori?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Introdu parola pentru a confirma. Toate dispozitivele de incredere memorate vor fi sterse.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Parola" class="sr-only" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="disableForm.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Parola"
                        @keyup.enter="disableTwoFactor"
                    />

                    <InputError :message="disableForm.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Renunta</SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': disableForm.processing }"
                        :disabled="disableForm.processing"
                        @click="disableTwoFactor"
                    >
                        Dezactiveaza
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
