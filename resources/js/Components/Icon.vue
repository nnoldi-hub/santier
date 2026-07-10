<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    size: { type: String, default: 'h-5 w-5' },
});
</script>

<template>
    <component :is="icon" :class="size" aria-hidden="true" />
</template>
