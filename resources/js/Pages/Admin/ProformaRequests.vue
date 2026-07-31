<template>
    <AppLayout title="Cereri proforma">
        <div class="max-w-6xl mx-auto space-y-6">
            <section class="rounded-3xl border border-orange-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                            Backoffice platforma
                        </div>
                        <h2 class="mt-3 text-3xl font-black text-slate-900">Cereri proforma</h2>
                        <p class="mt-2 max-w-3xl text-sm text-slate-600">
                            Toate cererile de factura proforma trimise catre prospecti. Cand confirmi plata prin transfer, marcheaza cererea ca platita.
                        </p>
                    </div>
                    <Link :href="route('admin.index')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Inapoi la Administrare
                    </Link>
                </div>
            </section>

            <div v-if="$page.props.flash?.success" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ $page.props.flash.success }}
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div v-if="requests.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    Nu exista cereri de proforma inca.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                            <tr>
                                <th class="px-5 py-3 text-left">Firma</th>
                                <th class="px-5 py-3 text-left">Contact</th>
                                <th class="px-5 py-3 text-left">Plan</th>
                                <th class="px-5 py-3 text-left">Discount</th>
                                <th class="px-5 py-3 text-left">Data cererii</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in requests" :key="item.id" class="hover:bg-slate-50/80">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ item.company_name }}</div>
                                    <div class="text-xs text-slate-500">CUI: {{ item.company_cui }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div>{{ item.contact_name }}</div>
                                    <div class="text-xs text-slate-500">{{ item.contact_email }} · {{ item.contact_phone }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div>{{ item.plan_label }}</div>
                                    <div class="text-xs text-slate-500">{{ item.interval }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">-{{ item.discount_pct }}%</td>
                                <td class="px-5 py-4 text-slate-700">{{ item.created_at }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold" :class="item.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                        {{ item.status === 'paid' ? 'Platita' : 'Trimisa' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col items-start gap-2">
                                        <button
                                            v-if="item.status !== 'paid'"
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                                            :disabled="markPaidForm.processing"
                                            @click="markPaid(item.id)"
                                        >
                                            Marcheaza platit
                                        </button>
                                        <Link
                                            v-if="item.converted_tenant_name"
                                            :href="route('admin.tenants.index', { search: item.converted_tenant_name })"
                                            class="text-xs font-semibold text-orange-700 hover:underline"
                                        >
                                            Activeaza planul pentru {{ item.converted_tenant_name }}
                                        </Link>
                                        <span v-else class="text-xs text-slate-400">Firma nu are inca cont creat</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: { type: Array, required: true },
});

const markPaidForm = useForm({});

function markPaid(id) {
    markPaidForm.patch(route('admin.proforma-requests.mark-paid', id), {
        preserveScroll: true,
    });
}
</script>
