<template>
    <!-- Disabled: arata gri, nu navigheaza -->
    <span
        v-if="disabled"
        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 cursor-not-allowed select-none"
        title="In curand"
    >
        <span class="text-base opacity-50">{{ icon }}</span>
        <span class="opacity-50">{{ label }}</span>
        <span class="ml-auto text-xs bg-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Soon</span>
    </span>

    <!-- Active link -->
    <Link
        v-else
        :href="href"
        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
        :class="isActive
            ? 'bg-orange-500 text-white font-medium'
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
    >
        <span class="text-base">{{ icon }}</span>
        <span>{{ label }}</span>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    href:     { type: String, required: true },
    icon:     { type: String, default: '' },
    label:    { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

const page = usePage();
const isActive = computed(() =>
    !props.disabled && page.url.startsWith(props.href) && props.href !== '/'
);
</script>