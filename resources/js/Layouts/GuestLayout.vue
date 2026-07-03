<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-br from-[#F2F2F2] via-white to-[#E8EEFF] pt-6 sm:justify-center sm:pt-0"
    >
        <div class="text-center">
            <Link href="/">
                <div class="guest-logo-crop" aria-label="Modulia">
                    <img src="/brand/logo_modulia.png" alt="Modulia" class="guest-logo-crop__img" />
                </div>
            </Link>
            <p class="mt-3 text-xl font-extrabold tracking-wide text-[#2E2E2E]">MODULIA</p>
            <p class="text-sm text-gray-600">Șantierul devine clar.</p>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-5 shadow-md ring-1 ring-[#0057FF]/10 sm:max-w-md sm:rounded-2xl"
        >
            <slot />
        </div>
    </div>
</template>

<style scoped>
.guest-logo-crop {
    height: 72px;
    width: 320px;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 12px;
}

.guest-logo-crop__img {
    height: 100%;
    width: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
}
</style>
