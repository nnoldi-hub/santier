<template>
    <AppLayout title="Editeaza task etapa">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Editeaza task pe etapa</h2>
                    <p class="text-sm text-gray-500 mt-1">Actualizeaza progresul operational al etapei.</p>
                </div>
                <Link :href="route('stage-tasks.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi</Link>
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Titlu *</label>
                        <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Etapa *</label>
                        <select v-model="form.stage_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecteaza</option>
                            <option v-for="stage in stages" :key="stage.id" :value="stage.id">{{ stage.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Status *</label>
                        <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tip responsabil</label>
                        <select v-model="form.assignee_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Fara responsabil</option>
                            <option v-for="(label, key) in assigneeTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Responsabil</label>
                        <select v-model="form.assignee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecteaza</option>
                            <option v-for="option in currentAssignees" :key="option.id" :value="option.id">{{ option.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Deadline</label>
                        <input v-model="form.deadline" type="datetime-local" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Descriere</label>
                        <textarea v-model="form.description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza modificari' }}
                    </button>
                    <Link :href="route('stage-tasks.index')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Anuleaza</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    task: Object,
    stages: Array,
    statusLabels: Object,
    assigneeTypes: Object,
    users: Array,
    teams: Array,
    contractors: Array,
});

const form = useForm({
    stage_id: props.task.stage_id,
    title: props.task.title,
    description: props.task.description || '',
    assignee_type: props.task.assignee_type || '',
    assignee_id: props.task.assignee_id || '',
    deadline: props.task.deadline ? props.task.deadline.slice(0, 16) : '',
    status: props.task.status,
});

const currentAssignees = computed(() => {
    if (form.assignee_type === 'user') return props.users;
    if (form.assignee_type === 'team') return props.teams;
    if (form.assignee_type === 'contractor') return props.contractors;

    return [];
});

watch(() => form.assignee_type, () => {
    if (!currentAssignees.value.some((x) => x.id === Number(form.assignee_id))) {
        form.assignee_id = '';
    }
});

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('stage-tasks.update', props.task.id));
}
</script>
