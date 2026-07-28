<template>
    <AppLayout title="Conversatie prospect">
        <div class="max-w-4xl mx-auto space-y-6">
            <div>
                <Link :href="route('pilot-invites.index')" class="text-sm text-[#1A237E] hover:underline">&larr; Inapoi la pipeline</Link>
            </div>

            <section class="rounded-3xl border border-orange-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900">{{ invite.company_name }}</h2>
                        <div class="mt-1 text-sm text-slate-600">
                            {{ invite.contact_name || 'Fara nume contact' }}
                            <span v-if="invite.contact_email"> · {{ invite.contact_email }}</span>
                            <span v-if="invite.contact_phone"> · {{ invite.contact_phone }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                            {{ labelStatus(invite.status) }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ labelStage(invite.commercial_stage) }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 font-semibold text-gray-800">Conversatie</h3>

                <div v-if="timeline.length === 0" class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500">
                    Inca nu exista niciun mesaj sau actiune inregistrata pentru acest prospect.
                </div>

                <div v-else class="space-y-3">
                    <div v-for="item in timeline" :key="item.id">
                        <div v-if="item.kind === 'message'" class="flex" :class="item.direction === 'outbound' ? 'justify-end' : 'justify-start'">
                            <div
                                class="max-w-[80%] rounded-2xl px-4 py-3 text-sm shadow-sm"
                                :class="item.direction === 'outbound' ? 'bg-[#1A237E] text-white' : 'bg-gray-100 text-gray-800'"
                            >
                                <div class="mb-1 flex items-center gap-2 text-[11px]" :class="item.direction === 'outbound' ? 'text-orange-100' : 'text-gray-500'">
                                    <span class="font-semibold">
                                        {{ item.direction === 'outbound' ? (item.actor_name || 'Echipa Modulia') : (item.from_name || item.from_email || invite.company_name) }}
                                    </span>
                                    <span>·</span>
                                    <span>{{ formatDateTime(item.occurred_at) }}</span>
                                </div>
                                <div v-if="item.subject" class="mb-1 text-xs font-semibold opacity-80">{{ item.subject }}</div>
                                <div class="whitespace-pre-line">{{ item.body }}</div>
                            </div>
                        </div>

                        <div v-else class="flex justify-center">
                            <div class="max-w-[90%] rounded-full bg-amber-50 px-3 py-1 text-[11px] text-amber-700">
                                <span class="font-semibold">{{ labelActionType(item.action_type) }}</span>
                                <span v-if="item.notes"> - {{ item.notes }}</span>
                                <span v-if="item.actor_name"> · {{ item.actor_name }}</span>
                                <span> · {{ formatDateTime(item.occurred_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-semibold text-gray-800">Trimite un mesaj</h3>
                <form @submit.prevent="sendMessage">
                    <textarea
                        v-model="form.body"
                        rows="4"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                        placeholder="Scrie raspunsul catre prospect..."
                    ></textarea>
                    <div v-if="form.errors.body" class="mt-1 text-xs text-rose-600">{{ form.errors.body }}</div>
                    <div class="mt-3 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Se trimite pe email catre {{ invite.contact_email || '-' }}.</p>
                        <button
                            type="submit"
                            :disabled="form.processing || !invite.contact_email"
                            class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Se trimite...' : 'Trimite' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { labelCommercialActionType, labelCommercialStage, labelCommercialStatus } from '@/Support/commercialLabels';

const props = defineProps({
    invite: { type: Object, required: true },
    timeline: { type: Array, default: () => [] },
});

const form = useForm({
    body: '',
});

function sendMessage() {
    form.post(route('pilot-invites.messages.store', props.invite.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function formatDateTime(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('ro-RO');
}

const labelStatus = labelCommercialStatus;
const labelStage = (stage) => labelCommercialStage(stage);
const labelActionType = (type) => labelCommercialActionType(type);
</script>
