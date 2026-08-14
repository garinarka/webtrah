<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const mobileOpen = ref(false)
// auth.user sudah otomatis ke-share di semua halaman (termasuk publik)
// lewat HandleInertiaRequests — null kalau belum login.
const user = computed(() => usePage().props.auth?.user ?? null)
</script>

<template>
    <div class="wt-public flex min-h-screen flex-col">
        <header
            class="bg-[--wt-paper]/90 sticky top-0 z-40 border-b border-[--wt-line] backdrop-blur"
        >
            <div
                class="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8"
            >
                <Link href="/" class="group flex items-center gap-2.5">
                    <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
                        <path
                            d="M13 24V13M13 13C13 13 7 13 7 8C7 4.5 9.5 2 13 2C16.5 2 19 4.5 19 8C19 13 13 13 13 13Z"
                            stroke="#3D5C3D"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle cx="13" cy="2" r="2" fill="#A9782F" />
                        <circle cx="7" cy="8" r="1.6" fill="#3D5C3D" />
                        <circle cx="19" cy="8" r="1.6" fill="#3D5C3D" />
                    </svg>
                    <span
                        class="font-display text-[19px] tracking-tight text-[--wt-ink]"
                        >Webtrah</span
                    >
                </Link>

                <nav class="hidden items-center gap-8 sm:flex">
                    <Link href="/" class="wt-nav-link">Beranda</Link>
                    <Link href="/about" class="wt-nav-link">Tentang</Link>

                    <template v-if="user">
                        <Link
                            href="/dashboard"
                            class="rounded-full bg-[--wt-indigo] px-4 py-2 text-sm font-medium text-white transition hover:brightness-110"
                        >
                            Ke Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="wt-nav-link">Masuk</Link>
                        <Link
                            href="/register"
                            class="rounded-full bg-[--wt-indigo] px-4 py-2 text-sm font-medium text-white transition hover:brightness-110"
                        >
                            Daftar
                        </Link>
                    </template>
                </nav>

                <button
                    class="text-[--wt-ink] sm:hidden"
                    @click="mobileOpen = !mobileOpen"
                    aria-label="Menu"
                >
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none">
                        <path
                            d="M3 6h16M3 11h16M3 16h16"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>
            </div>

            <div
                v-if="mobileOpen"
                class="flex flex-col gap-3 border-t border-[--wt-line] px-5 py-3 sm:hidden"
            >
                <Link href="/" class="wt-nav-link" @click="mobileOpen = false"
                    >Beranda</Link
                >
                <Link
                    href="/about"
                    class="wt-nav-link"
                    @click="mobileOpen = false"
                    >Tentang</Link
                >

                <template v-if="user">
                    <Link
                        href="/dashboard"
                        class="wt-nav-link font-medium text-[--wt-indigo]"
                        @click="mobileOpen = false"
                        >Ke Dashboard</Link
                    >
                </template>
                <template v-else>
                    <Link
                        href="/login"
                        class="wt-nav-link"
                        @click="mobileOpen = false"
                        >Masuk</Link
                    >
                    <Link
                        href="/register"
                        class="wt-nav-link font-medium text-[--wt-indigo]"
                        @click="mobileOpen = false"
                        >Daftar</Link
                    >
                </template>
            </div>
        </header>

        <main class="flex-1">
            <slot :user="user" />
        </main>

        <footer class="mt-auto border-t border-[--wt-line]">
            <div
                class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-4 px-5 py-10 sm:flex-row sm:items-center sm:px-8"
            >
                <p class="wt-label text-[--wt-ink]/50">
                    Webtrah — dijaga & dikembangkan mandiri untuk keluarga besar
                </p>
                <div class="flex items-center gap-6">
                    <Link
                        href="/about"
                        class="text-[--wt-ink]/60 text-sm hover:text-[--wt-ink]"
                        >Tentang</Link
                    >
                    <Link
                        v-if="user"
                        href="/dashboard"
                        class="text-[--wt-ink]/60 text-sm hover:text-[--wt-ink]"
                        >Ke Dashboard</Link
                    >
                    <Link
                        v-else
                        href="/login"
                        class="text-[--wt-ink]/60 text-sm hover:text-[--wt-ink]"
                        >Masuk</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
.wt-public {
    --wt-ink: #21301f;
    --wt-paper: #f7f4ec;
    --wt-root: #3d5c3d;
    --wt-gold: #a9782f;
    --wt-indigo: #4f46e5;
    --wt-line: #e4dfd1;
    background: var(--wt-paper);
    color: var(--wt-ink);
}

.wt-public .font-display {
    font-family: 'Fraunces', ui-serif, Georgia, serif;
}

.wt-public .wt-label {
    font-family: 'IBM Plex Mono', ui-monospace, monospace;
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 500;
}

.wt-public .wt-nav-link {
    font-size: 14px;
    color: var(--wt-ink);
    opacity: 0.7;
}

.wt-public .wt-nav-link:hover {
    opacity: 1;
}
</style>
