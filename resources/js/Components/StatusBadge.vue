<template>
    <span
        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
        :class="colors[status]"
    >
        {{ labels[status] }}
    </span>
</template>

<script setup>
defineProps({
    status: { type: String, required: true },
});

const labels = {
    draft: 'Ciorna',
    active: 'Activ',
    paused: 'Pauza',
    completed: 'Finalizat',
    cancelled: 'Anulat',
    open: 'Deschis',
    in_progress: 'In lucru',
    resolved: 'Rezolvat',
    rejected: 'Respins',
    todo: 'De facut',
    done: 'Gata',
};

const colors = {
    draft:      'bg-gray-100 text-gray-600',
    active:     'bg-green-100 text-green-700',
    paused:     'bg-yellow-100 text-yellow-700',
    completed:  'bg-blue-100 text-blue-700',
    cancelled:  'bg-red-100 text-red-600',
    open:       'bg-red-100 text-red-600',
    in_progress:'bg-yellow-100 text-yellow-700',
    resolved:   'bg-green-100 text-green-700',
    rejected:   'bg-gray-100 text-gray-600',
    todo:       'bg-gray-100 text-gray-600',
    done:       'bg-green-100 text-green-700',
};
</script>
