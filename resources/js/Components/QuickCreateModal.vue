<template>
    <Modal :show="show" :max-width="maxWidth" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ title }}</h3>
            <div class="space-y-3">
                <slot />
            </div>
            <p v-if="error" class="text-red-500 text-xs mt-3">{{ error }}</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="$emit('close')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                    Anuleaza
                </button>
                <button type="button" @click="$emit('submit')" :disabled="processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-600 disabled:opacity-50">
                    {{ processing ? 'Se salveaza...' : 'Salveaza' }}
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    processing: { type: Boolean, default: false },
    error: { type: String, default: '' },
    maxWidth: { type: String, default: 'md' },
});

defineEmits(['close', 'submit']);
</script>
