<template>
    <AppLayout :title="team.name">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('teams.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Echipe</Link>
                    <h2 class="text-xl font-semibold text-gray-800">{{ team.name }}</h2>
                    <span :class="team.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="text-xs px-2 py-0.5 rounded-full">
                        {{ team.active ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>
                <Link :href="route('teams.edit', team.id)" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Editeaza
                </Link>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Detalii echipa</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div>
                        <div class="text-xs text-gray-400">Specialitate</div>
                        <div class="font-medium text-gray-700">{{ team.specialty || 'Nespecificat' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Lider</div>
                        <div class="font-medium text-gray-700">{{ team.leader?.name || 'Nesetat' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Membri</div>
                        <div class="font-medium text-gray-700">{{ team.members?.length || 0 }}</div>
                    </div>
                </div>
                <p v-if="team.notes" class="text-sm text-gray-600 mt-4 border-t border-gray-100 pt-3">{{ team.notes }}</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">+ Adauga membru</h3>
                <form @submit.prevent="addMember" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Utilizator *</label>
                        <select v-model="memberForm.user_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                            <option value="">— Selecteaza —</option>
                            <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                        </select>
                        <p v-if="memberForm.errors.user_id" class="text-red-500 text-xs mt-1">{{ memberForm.errors.user_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Rol</label>
                        <input v-model="memberForm.role" type="text" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" placeholder="ex: instalator" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tarif/ora</label>
                        <input v-model="memberForm.hourly_rate" type="number" min="0" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" placeholder="0" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data intrare</label>
                        <input v-model="memberForm.joined_at" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                    </div>
                    <div class="md:col-span-3 flex items-end">
                        <button type="submit" :disabled="memberForm.processing" class="bg-orange-500 text-white px-4 py-1.5 rounded text-sm hover:bg-orange-600 disabled:opacity-50">
                            {{ memberForm.processing ? '...' : 'Adauga membru' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Membri echipa</h3>
                <div v-if="!team.members || team.members.length === 0" class="text-sm text-gray-400 py-4">
                    Echipa nu are membri inca.
                </div>
                <div v-else class="space-y-2">
                    <div v-for="member in team.members" :key="member.id" class="flex items-center justify-between border border-gray-100 rounded-lg p-3">
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ member.user?.name || 'Utilizator necunoscut' }}</div>
                            <div class="text-xs text-gray-500">
                                {{ member.role || 'Fara rol' }}
                                <span v-if="member.hourly_rate"> · {{ formatCurrency(member.hourly_rate) }}/ora</span>
                            </div>
                        </div>
                        <button @click="removeMember(member)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">
                            Elimina
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    team: Object,
    users: Array,
});

const memberForm = useForm({
    user_id: '',
    role: '',
    hourly_rate: '',
    joined_at: '',
});

function addMember() {
    memberForm.post(route('teams.members.store', props.team.id), {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
}

function removeMember(member) {
    if (confirm(`Elimini membrul "${member.user?.name || ''}" din echipa?`)) {
        router.delete(route('teams.members.destroy', [props.team.id, member.id]));
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(value);
}
</script>
