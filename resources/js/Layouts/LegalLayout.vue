<template>
    <Head :title="title" />

    <div class="min-h-screen bg-slate-50 text-slate-800">
        <header class="border-b border-slate-200 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <img src="/brand/logo_modulia.png" alt="Modulia" class="h-8 w-8 object-contain" />
                    <span class="font-bold text-slate-900">Modulia</span>
                </Link>
                <Link href="/" class="text-sm text-[var(--brand-blue,#0057FF)] hover:underline">Inapoi la pagina principala</Link>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
            <slot />
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 text-xs text-slate-400">
                © 2026 Modulia - Șantierul devine clar.
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Modulia' },
});
</script>
