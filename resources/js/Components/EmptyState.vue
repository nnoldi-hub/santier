<script setup>
import Icon from '@/Components/Icon.vue';

defineProps({
    icon: { type: [Object, Function], default: null },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
        <Icon v-if="icon" :icon="icon" size="h-10 w-10 mx-auto mb-4 text-gray-300" />
        <h3 class="text-lg font-semibold text-gray-700 mb-2">{{ title }}</h3>
        <p v-if="description" class="text-gray-400 text-sm" :class="{ 'mb-6': $slots.default }">{{ description }}</p>
        <slot />
    </div>
</template>
