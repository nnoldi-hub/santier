<template>
    <AppLayout title="Administrare">
        <div class="space-y-6 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Operatori listati</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ metrics.users_total }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Firme cu plan platit</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ metrics.users_paid }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Firme in trial</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ metrics.users_on_trial }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">MRR estimat</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ formatMoney(metrics.monthly_mrr_estimate) }}</div>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Centru de administrare</div>
                        <h2 class="mt-2 text-2xl font-black text-slate-900">Alege zona pe care vrei sa o gestionezi</h2>
                        <p class="mt-2 text-sm text-slate-600">Abonamentele sunt prezentate in context de firma, iar utilizatorul afisat este operatorul principal din acel cont.</p>
                    </div>
                    <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                        <button type="button" @click="activeAdminTab = 'accounts'" class="rounded-xl px-4 py-2 text-sm font-semibold transition" :class="activeAdminTab === 'accounts' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">
                            Firme si abonamente
                        </button>
                        <button type="button" @click="activeAdminTab = 'documents'" class="rounded-xl px-4 py-2 text-sm font-semibold transition" :class="activeAdminTab === 'documents' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">
                            Documente emise
                        </button>
                    </div>
                </div>

                <div v-if="activeAdminTab === 'accounts'" class="mt-6 grid gap-4 xl:grid-cols-[1fr_1.1fr]">
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 border-b border-slate-200">Lista firme si operator principal</div>
                        <div class="max-h-[520px] overflow-auto divide-y divide-slate-100">
                            <button
                                v-for="user in users"
                                :key="user.id"
                                type="button"
                                class="w-full text-left px-4 py-3 transition hover:bg-slate-50"
                                :class="selectedUser?.id === user.id ? 'bg-orange-50' : 'bg-white'"
                                @click="selectUser(user)"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ user.tenant_name || 'Firma fara nume' }}</div>
                                        <div class="text-xs text-slate-500">Operator: {{ user.name }} · {{ user.email }}</div>
                                    </div>
                                    <span class="rounded-full px-2 py-1 text-[11px] font-semibold" :class="planTone(user.billing_plan)">
                                        {{ plans[user.billing_plan]?.label || user.billing_plan }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">
                                    Trial firma: {{ formatDate(user.billing_trial_ends_at) }} · Onboarding operator: {{ user.onboarding_completed_at ? 'finalizat' : 'in lucru' }}
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm font-semibold text-slate-700">Firma selectata</div>
                        <div v-if="selectedUser" class="mt-4 space-y-4">
                            <div>
                                <div class="text-lg font-bold text-slate-900">{{ selectedUser.tenant_name || 'Firma fara nume' }}</div>
                                <div class="text-sm text-slate-500">Operator principal: {{ selectedUser.name }} · {{ selectedUser.email }}</div>
                                <div v-if="selectedUser.tenant_slug" class="text-xs text-slate-400 mt-1">Slug firma: {{ selectedUser.tenant_slug }}</div>
                            </div>

                            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                                Editezi planul firmei din contextul tenant. Valorile de trial si abonament nu mai sunt tratate ca proprietati ale utilizatorului.
                            </div>

                            <form class="space-y-4" @submit.prevent="saveSubscription">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Plan firma</label>
                                    <select v-model="subscriptionForm.billing_plan" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2 text-sm">
                                        <option v-for="(plan, key) in plans" :key="key" :value="key">{{ plan.label }} - {{ formatMoney(plan.price) }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Trial firma pana la</label>
                                    <input v-model="subscriptionForm.billing_trial_ends_at" type="date" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2 text-sm" />
                                </div>

                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input v-model="subscriptionForm.onboarding_completed" type="checkbox" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500" />
                                    Onboarding complet
                                </label>

                                <button type="submit" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-60" :disabled="subscriptionForm.processing">
                                    Salveaza firma
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-6">
                    <section class="rounded-3xl border border-orange-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <div class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                                    <Icon :icon="DocumentTextIcon" size="h-3.5 w-3.5" />
                                    Configurare documente emise
                                </div>
                                <h2 class="mt-3 text-3xl font-black text-slate-900">Antet, logo, culoare si date de firma pentru documente profesionale</h2>
                                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                                    Aici setezi exact cum arata documentele emise din aplicatie: facturi, devize, oferte si procese verbale.
                                </p>
                            </div>
                            <a href="#documente-configurare" class="inline-flex items-center justify-center rounded-xl bg-[#1A237E] px-4 py-3 text-sm font-semibold text-white hover:bg-[#141b5c] transition">
                                Mergi la setari
                            </a>
                            <a href="/" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Vezi landing
                            </a>
                        </div>
                    </section>

                    <form id="documente-configurare" class="mt-5 space-y-4 max-w-xl scroll-mt-24" @submit.prevent="saveSettings">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Nume aplicatie</label>
                            <input v-model="settingsForm.app_name" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Nume companie</label>
                            <input v-model="settingsForm.company_name" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Telefon companie</label>
                            <input v-model="settingsForm.company_phone" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Adresa companie</label>
                            <input v-model="settingsForm.company_address" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Email suport</label>
                            <input v-model="settingsForm.support_email" type="email" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Email vanzari</label>
                            <input v-model="settingsForm.sales_email" type="email" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Social media</div>
                                <h3 class="mt-1 text-sm font-bold text-slate-900">Linkuri publice pentru pagina de prezentare</h3>
                            </div>
                            <div v-for="field in socialMediaFields" :key="field.key">
                                <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">{{ field.label }}</label>
                                <input v-model="settingsForm[field.key]" :type="field.type" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" :placeholder="field.placeholder" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Video prezentare landing</label>
                            <input v-model="settingsForm.landing_video_url" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="YouTube link sau URL direct .mp4/.webm/.ogg" />
                            <p class="mt-1 text-[11px] text-slate-500">Accepta YouTube, URL direct catre fisier video sau upload local.</p>
                            <p v-if="settingsForm.landing_video_url && !isLandingVideoUrlValid && !settingsForm.landing_video_file" class="mt-1 text-[11px] text-rose-600">
                                Link invalid. Foloseste YouTube sau URL direct .mp4/.webm/.ogg.
                            </p>
                            <div class="mt-2">
                                <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Sau incarca video</label>
                                <input type="file" accept="video/mp4,video/webm,video/ogg,.mp4,.webm,.ogg,.mov" @change="onVideoFileChange" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white" />
                                <p class="mt-1 text-[11px] text-slate-500">MP4, WebM, OGG sau MOV, maxim 100 MB.</p>
                            </div>
                            <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Preview video</span>
                                    <a href="/" target="_blank" rel="noopener" class="text-xs font-semibold text-slate-700 hover:underline">Deschide landing</a>
                                </div>
                                <div class="aspect-video overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                    <iframe
                                        v-if="previewLandingVideo.type === 'youtube'"
                                        class="h-full w-full"
                                        :src="previewLandingVideo.src"
                                        title="Preview video landing"
                                        loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen
                                    ></iframe>
                                    <video v-else-if="previewLandingVideo.type === 'file'" class="h-full w-full" controls preload="metadata">
                                        <source :src="previewLandingVideo.src" />
                                    </video>
                                    <div v-else class="h-full w-full flex items-center justify-center text-sm text-slate-500">
                                        Adauga URL sau incarca un video pentru preview.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Logo documente (URL)</label>
                            <input v-model="settingsForm.document_logo_url" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="https://.../logo.png" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Sau incarca logo</label>
                            <input type="file" accept="image/*" @change="onLogoFileChange" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white" />
                            <p class="mt-1 text-[11px] text-slate-500">PNG sau JPG, maxim 2 MB.</p>
                            <img v-if="logoPreview" :src="logoPreview" alt="Logo preview" class="mt-3 h-14 w-auto rounded-lg border border-slate-200 bg-white p-2" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Culoare branding documente</label>
                            <input v-model="settingsForm.document_brand_color" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="#f97316" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 mb-2">Zile trial</label>
                            <input v-model="settingsForm.trial_days" type="number" min="1" max="90" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        </div>

                        <label class="flex items-center gap-3 text-sm text-slate-700">
                            <input v-model="settingsForm.public_signup_enabled" type="checkbox" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500" />
                            Permite inscriere publica
                        </label>
                        <label class="flex items-center gap-3 text-sm text-slate-700">
                            <input v-model="settingsForm.demo_mode_enabled" type="checkbox" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500" />
                            Demo mode activ
                        </label>

                        <button type="submit" class="rounded-xl bg-[#1A237E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#141b5c] disabled:opacity-60" :disabled="settingsForm.processing">
                            Salveaza setarile
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    plans: { type: Object, required: true },
    settings: { type: Object, required: true },
    users: { type: Array, required: true },
    metrics: { type: Object, required: true },
});

const selectedUser = ref(props.users[0] || null);
const activeAdminTab = ref('documents');

const subscriptionForm = useForm({
    billing_plan: selectedUser.value?.billing_plan || 'free',
    billing_trial_ends_at: formatDateInput(selectedUser.value?.billing_trial_ends_at),
    onboarding_completed: Boolean(selectedUser.value?.onboarding_completed_at),
});

const settingsForm = useForm({
    app_name: props.settings.app_name || '',
    company_name: props.settings.company_name || '',
    company_phone: props.settings.company_phone || '',
    company_address: props.settings.company_address || '',
    support_email: props.settings.support_email || '',
    sales_email: props.settings.sales_email || '',
    social_facebook_url: props.settings.social_facebook_url || '',
    social_instagram_url: props.settings.social_instagram_url || '',
    social_linkedin_url: props.settings.social_linkedin_url || '',
    social_tiktok_url: props.settings.social_tiktok_url || '',
    social_youtube_url: props.settings.social_youtube_url || '',
    landing_video_url: props.settings.landing_video_url || '',
    landing_video_file: null,
    document_logo_url: props.settings.document_logo_url || '',
    document_logo_file: null,
    document_brand_color: props.settings.document_brand_color || '#f97316',
    trial_days: props.settings.trial_days || 14,
    public_signup_enabled: Boolean(props.settings.public_signup_enabled),
    demo_mode_enabled: Boolean(props.settings.demo_mode_enabled),
});

const logoPreview = ref(props.settings.document_logo_url || '');
const uploadedLandingVideoPreviewUrl = ref('');
const previewLandingVideo = computed(() => resolveLandingVideo(uploadedLandingVideoPreviewUrl.value || settingsForm.landing_video_url));
const isLandingVideoUrlValid = computed(() => isAllowedLandingVideoUrl(settingsForm.landing_video_url));
const socialMediaFields = [
    { key: 'social_facebook_url', label: 'Facebook', placeholder: 'https://facebook.com/modulia', type: 'url' },
    { key: 'social_instagram_url', label: 'Instagram', placeholder: 'https://instagram.com/modulia', type: 'url' },
    { key: 'social_linkedin_url', label: 'LinkedIn', placeholder: 'https://linkedin.com/company/modulia', type: 'url' },
    { key: 'social_tiktok_url', label: 'TikTok', placeholder: 'https://tiktok.com/@modulia', type: 'url' },
    { key: 'social_youtube_url', label: 'YouTube', placeholder: 'https://youtube.com/@modulia', type: 'url' },
];

const selectedPlanLabel = computed(() => selectedUser.value ? (props.plans?.[selectedUser.value.billing_plan]?.label || selectedUser.value.billing_plan) : '-');

function selectUser(user) {
    selectedUser.value = user;
    subscriptionForm.billing_plan = user.billing_plan || 'free';
    subscriptionForm.billing_trial_ends_at = formatDateInput(user.billing_trial_ends_at);
    subscriptionForm.onboarding_completed = Boolean(user.onboarding_completed_at);
}

function saveSubscription() {
    if (!selectedUser.value) {
        return;
    }

    subscriptionForm.patch(route('admin.users.subscription.update', selectedUser.value.id), {
        preserveScroll: true,
    });
}

function saveSettings() {
    settingsForm.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(route('admin.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
}

function onLogoFileChange(event) {
    const [file] = event.target.files || [];

    settingsForm.document_logo_file = file || null;

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
        return;
    }

    logoPreview.value = settingsForm.document_logo_url || '';
}

function onVideoFileChange(event) {
    const [file] = event.target.files || [];

    settingsForm.landing_video_file = file || null;

    if (file) {
        uploadedLandingVideoPreviewUrl.value = URL.createObjectURL(file);
        return;
    }

    uploadedLandingVideoPreviewUrl.value = '';
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('ro-RO');
}

function formatDateInput(value) {
    if (!value) {
        return '';
    }

    return new Date(value).toISOString().slice(0, 10);
}

function formatMoney(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function planTone(plan) {
    if (plan === 'enterprise') {
        return 'bg-slate-900 text-white';
    }

    if (plan === 'pro') {
        return 'bg-orange-100 text-orange-700';
    }

    if (plan === 'starter') {
        return 'bg-emerald-100 text-emerald-700';
    }

    return 'bg-gray-100 text-gray-600';
}

function normalizeVideoEmbedUrl(rawUrl) {
    const value = String(rawUrl || '').trim();

    if (!value) {
        return '';
    }

    if (value.includes('youtube-nocookie.com/embed/') || value.includes('youtube.com/embed/')) {
        return value;
    }

    try {
        const url = new URL(value);
        const host = url.hostname.replace('www.', '');

        if (host === 'youtu.be') {
            const videoId = url.pathname.split('/').filter(Boolean)[0];

            if (videoId) {
                return `https://www.youtube-nocookie.com/embed/${videoId}?rel=0`;
            }
        }

        if (host === 'youtube.com' || host === 'm.youtube.com') {
            const videoId = url.searchParams.get('v');

            if (videoId) {
                return `https://www.youtube-nocookie.com/embed/${videoId}?rel=0`;
            }
        }
    } catch (error) {
        return '';
    }

    return '';
}

function isAllowedLandingVideoUrl(rawUrl) {
    const value = String(rawUrl || '').trim();

    if (!value) {
        return true;
    }

    try {
        const url = new URL(value);
        const host = url.hostname.replace('www.', '');

        if (['youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com'].includes(host)) {
            return true;
        }

        const pathname = String(url.pathname || '').toLowerCase();

        return /\.(mp4|webm|ogg)(\?.*)?$/.test(pathname);
    } catch (error) {
        return /^\/(storage\/)?.*\.(mp4|webm|ogg)(\?.*)?$/i.test(value);
    }
}

function resolveLandingVideo(rawUrl) {
    const value = String(rawUrl || '').trim();

    if (!value) {
        return { type: 'none', src: '' };
    }

    const youtubeEmbedUrl = normalizeVideoEmbedUrl(value);

    if (youtubeEmbedUrl) {
        return { type: 'youtube', src: youtubeEmbedUrl };
    }

    if (isAllowedLandingVideoUrl(value)) {
        return { type: 'file', src: value };
    }

    return { type: 'none', src: '' };
}
</script>