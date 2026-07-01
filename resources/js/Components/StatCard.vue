<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">{{ label }}</p>
                <p class="text-3xl font-bold text-gray-800">{{ value }}</p>
            </div>
            <div class="text-4xl opacity-80">{{ icon }}</div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    icon: String,
    label: String,
    value: [String, Number],
    color: { type: String, default: 'blue' },
});
</script>
