<template>
    <!-- Disabled: arata gri, nu navigheaza -->
    <span
        v-if="disabled"
        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 cursor-not-allowed select-none"
        title="In curand"
    >
        <Icon v-if="icon" :icon="icon" size="h-5 w-5 shrink-0 opacity-50" />
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
        <Icon v-if="icon" :icon="icon" size="h-5 w-5 shrink-0" />
        <span>{{ label }}</span>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    href:     { type: String, required: true },
    icon:     { type: [Object, Function], default: null },
    label:    { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

const page = usePage();
const isActive = computed(() =>
    !props.disabled && page.url.startsWith(props.href) && props.href !== '/'
);
</script>