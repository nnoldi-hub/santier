<template>
    <AppLayout title="Utilizatori firma">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Invita utilizator nou</h2>
                <p class="text-sm text-gray-500 mt-1">Adauga rapid un membru nou si atribuie rolul initial.</p>

                <form class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3" @submit.prevent="inviteMember">
                    <input v-model="inviteForm.name" type="text" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume" />
                    <input v-model="inviteForm.email" type="email" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="email@firma.ro" required />
                    <input v-model="inviteForm.department" type="text" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Departament" />
                    <select v-model="inviteForm.role_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="" disabled>Alege rol</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.label || role.name }} {{ role.is_global ? '(global)' : '(custom)' }}</option>
                    </select>
                    <button type="submit" class="bg-orange-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-orange-600 disabled:opacity-60" :disabled="inviteForm.processing">
                        Invita
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Utilizatori activi si suspendati</h3>
                    <span class="text-xs text-gray-500">{{ members.length }} inregistrari</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-600 uppercase tracking-wide">
                                <th class="px-4 py-3">Utilizator</th>
                                <th class="px-4 py-3">Rol</th>
                                <th class="px-4 py-3">Departament</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="member in members" :key="member.membership_id" class="border-t border-gray-100">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ member.name || 'Fara nume' }}</div>
                                    <div class="text-xs text-gray-500">{{ member.email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        class="border border-gray-300 rounded-lg px-2 py-1 text-xs"
                                        :value="member.roles?.[0]?.id || ''"
                                        @change="changeRole(member, $event.target.value)"
                                    >
                                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.label || role.name }}</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ member.department || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs rounded-full px-2 py-1 font-medium" :class="member.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                        {{ member.status === 'active' ? 'Activ' : 'Suspendat' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="text-xs rounded-lg px-3 py-1.5 border"
                                        :class="member.status === 'active' ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50'"
                                        @click="toggleStatus(member)"
                                    >
                                        {{ member.status === 'active' ? 'Suspenda' : 'Reactiveaza' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    members: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const inviteForm = useForm({
    name: '',
    email: '',
    department: '',
    role_id: '',
});

function inviteMember() {
    inviteForm.post(route('account.users.invite'), {
        preserveScroll: true,
        onSuccess: () => inviteForm.reset(),
    });
}

function toggleStatus(member) {
    const next = member.status === 'active' ? 'suspended' : 'active';

    router.patch(route('account.users.status.update', member.membership_id), {
        status: next,
    }, {
        preserveScroll: true,
    });
}

function changeRole(member, roleId) {
    if (!roleId) return;

    router.patch(route('account.users.role.update', member.membership_id), {
        role_id: Number(roleId),
    }, {
        preserveScroll: true,
    });
}
</script>
