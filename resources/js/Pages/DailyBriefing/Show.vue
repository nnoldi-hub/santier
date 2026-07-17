<template>
    <AppLayout :title="'Memento zilnic - ' + project.name">
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <Link :href="route('projects.show', project.id)" class="text-gray-400 hover:text-gray-600 text-sm">← {{ project.name }}</Link>
                    <h2 class="mt-1 text-2xl font-black text-slate-900">Memento Zilnic</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ formatDate(briefing.date) }}</p>
                </div>
                <a :href="route('daily-briefing.pdf', project.id)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Export PDF
                </a>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                    :class="activeTab === tab.key ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div v-if="activeTab === 'astazi'" class="space-y-5">
                <div class="rounded-xl border p-4 flex items-center gap-3" :class="riskBannerClass(briefing.risk_level)">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shrink-0" :class="riskBadgeClass(briefing.risk_level)">
                        {{ riskEmoji(briefing.risk_level) }} {{ briefing.risk_label }}
                    </span>
                    <p class="text-sm font-medium">{{ briefing.summary }}</p>
                </div>

                <DailyBriefingSections :briefing="briefing" />
            </div>

            <div v-else-if="activeTab === 'istoric'" class="space-y-3">
                <p v-if="!history.length" class="text-sm text-gray-400">Niciun memento trimis pana acum pentru acest proiect.</p>
                <div v-for="entry in history" :key="entry.id" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <button
                        type="button"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left hover:bg-gray-50"
                        @click="toggleHistoryEntry(entry.id)"
                    >
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold" :class="riskBadgeClass(entry.risk_level)">
                                {{ riskEmoji(entry.risk_level) }}
                            </span>
                            <span class="text-sm font-medium text-gray-800">{{ formatDate(entry.briefing_date) }}</span>
                            <span class="text-xs text-gray-500">{{ entry.blockers_count }} blocaj(e) · {{ entry.recipients_count }} destinatari</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ openHistoryId === entry.id ? 'Ascunde' : 'Deschide' }}</span>
                    </button>
                    <div v-if="openHistoryId === entry.id" class="border-t border-gray-100 p-4">
                        <DailyBriefingSections :briefing="entry.snapshot" />
                    </div>
                </div>
            </div>

            <div v-else class="bg-white border border-gray-200 rounded-xl p-5 space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">Trimitere automata</h3>
                        <p class="text-xs text-gray-500">Trimite mementoul zilnic prin email si notificare in-app la ora setata.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-medium"
                        :class="form.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                        @click="form.enabled = !form.enabled"
                    >
                        {{ form.enabled ? 'Activat' : 'Dezactivat' }}
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ora trimiterii</label>
                    <input v-model="form.send_time" type="time" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nivel de detaliu</label>
                    <div class="flex gap-2">
                        <button
                            v-for="(label, key) in detailLevels"
                            :key="key"
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium"
                            :class="form.detail_level === key ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600'"
                            @click="form.detail_level = key"
                        >
                            {{ label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Canale</label>
                    <div class="flex flex-wrap gap-3 text-sm text-gray-700">
                        <label class="flex items-center gap-1.5">
                            <input v-model="form.channels.email" type="checkbox" />
                            Email
                        </label>
                        <label class="flex items-center gap-1.5">
                            <input v-model="form.channels.in_app" type="checkbox" />
                            Notificare in Modulia
                        </label>
                        <label class="flex items-center gap-1.5 text-gray-400">
                            <input type="checkbox" disabled />
                            WhatsApp (in curand)
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destinatari</label>
                    <div class="max-h-56 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100">
                        <label v-for="user in tenantUsers" :key="user.id" class="flex items-center gap-2 px-3 py-2 text-sm">
                            <input type="checkbox" :value="user.id" v-model="form.recipient_user_ids" />
                            <span>{{ user.name }} <span class="text-gray-400">({{ user.email }})</span></span>
                        </label>
                        <p v-if="!tenantUsers.length" class="text-sm text-gray-400 px-3 py-2">Niciun utilizator disponibil.</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="saveSettings"
                >
                    Salveaza setarile
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DailyBriefingSections from '@/Components/DailyBriefingSections.vue';

const props = defineProps({
    project: { type: Object, required: true },
    briefing: { type: Object, required: true },
    settings: { type: Object, required: true },
    detailLevels: { type: Object, default: () => ({}) },
    tenantUsers: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
});

const tabs = [
    { key: 'astazi', label: 'Astazi' },
    { key: 'istoric', label: 'Istoric' },
    { key: 'setari', label: 'Setari' },
];

const activeTab = ref('astazi');
const openHistoryId = ref(null);

function toggleHistoryEntry(id) {
    openHistoryId.value = openHistoryId.value === id ? null : id;
}

const form = useForm({
    enabled: props.settings.enabled,
    send_time: props.settings.send_time,
    recipient_user_ids: props.settings.recipient_user_ids,
    detail_level: props.settings.detail_level,
    channels: { ...props.settings.channels },
});

function saveSettings() {
    form.patch(route('daily-briefing.settings.update', props.project.id), { preserveScroll: true });
}

const riskBadges = {
    green: { emoji: '🟢', badge: 'bg-green-100 text-green-700', banner: 'border-green-200 bg-green-50 text-green-900' },
    orange: { emoji: '🟠', badge: 'bg-amber-100 text-amber-700', banner: 'border-amber-200 bg-amber-50 text-amber-900' },
    red: { emoji: '🔴', badge: 'bg-red-100 text-red-700', banner: 'border-red-200 bg-red-50 text-red-900' },
};

function riskEmoji(level) {
    return riskBadges[level]?.emoji ?? '⚪';
}

function riskBadgeClass(level) {
    return riskBadges[level]?.badge ?? 'bg-gray-100 text-gray-600';
}

function riskBannerClass(level) {
    return riskBadges[level]?.banner ?? 'border-gray-200 bg-gray-50 text-gray-800';
}

function formatDate(value) {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString('ro-RO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
</script>
