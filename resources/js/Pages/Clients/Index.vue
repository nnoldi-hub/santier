<template>
    <AppLayout title="Clienti">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Clienti</h2>
                <p class="text-sm text-gray-500 mt-1">{{ clients.total }} clienti in total</p>
            </div>
            <Link :href="route('clients.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Client nou
            </Link>
        </div>

        <EmptyState
            v-if="clients.data.length === 0"
            :icon="UsersIcon"
            title="Niciun client"
            description="Adauga primul client pentru a putea crea proiecte."
        >
            <Link :href="route('clients.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Adauga client
            </Link>
        </EmptyState>

        <!-- Clients table -->
        <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Nume</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Tip</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">CUI/CNP</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Contact</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Proiecte</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ client.name }}</div>
                            <div v-if="client.address" class="text-xs text-gray-400 truncate max-w-xs">{{ client.address }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                :class="client.type === 'company' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                                {{ client.type === 'company' ? '🏢 Firma' : '👤 Persoana' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ client.tax_id ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div v-if="client.phone" class="text-gray-600">📞 {{ client.phone }}</div>
                            <div v-if="client.email" class="text-gray-400 text-xs">{{ client.email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-700">{{ client.projects_count }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 justify-end">
                                <Link :href="route('clients.edit', client.id)" class="text-gray-400 hover:text-orange-500 text-xs px-2 py-1 border border-gray-200 rounded hover:border-orange-300 transition">
                                    Editeaza
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { UsersIcon } from '@heroicons/vue/24/outline';

defineProps({
    clients: Object,
});
</script>
