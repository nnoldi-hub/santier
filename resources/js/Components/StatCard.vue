<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">{{ label }}</p>
                <p class="text-3xl font-bold text-gray-800">{{ value }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-lg" :class="colorClasses.bg">
                <Icon :icon="icon" size="h-6 w-6" :class="colorClasses.text" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    icon: { type: [Object, Function], required: true },
    label: String,
    value: [String, Number],
    color: { type: String, default: 'blue' },
});

const colorMap = {
    blue: { bg: 'bg-blue-50', text: 'text-blue-600' },
    green: { bg: 'bg-emerald-50', text: 'text-emerald-600' },
    orange: { bg: 'bg-orange-50', text: 'text-[#F57C00]' },
    red: { bg: 'bg-rose-50', text: 'text-rose-600' },
};

const colorClasses = computed(() => colorMap[props.color] ?? colorMap.blue);
</script>
