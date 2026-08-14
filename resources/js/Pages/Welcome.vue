<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'

const user = computed(() => usePage().props.auth?.user ?? null)
</script>

<template>
    <Head title="Webtrah — Silsilah Keluarga Besar" />
    <PublicLayout>
        <!-- HERO -->
        <section class="relative overflow-hidden">
            <div
                class="mx-auto grid max-w-6xl items-center gap-12 px-5 pb-16 pt-14 sm:px-8 sm:pt-20 lg:grid-cols-[1.05fr,0.95fr]"
            >
                <div>
                    <p class="wt-label text-[--wt-gold]">
                        Silsilah Keluarga Besar
                    </p>
                    <h1
                        class="font-display mt-4 text-[40px] leading-[1.05] tracking-tight text-[--wt-ink] sm:text-[56px]"
                    >
                        Satu akar, banyak
                        <span
                            class="italic"
                            style="
                                font-family: 'Fraunces', serif;
                                color: var(--wt-root);
                            "
                            >cabang</span
                        >, satu catatan.
                    </h1>
                    <p
                        class="text-[--wt-ink]/75 mt-6 max-w-md text-[17px] leading-relaxed"
                    >
                        Webtrah menyimpan silsilah keluarga besar kita di satu
                        tempat — siapa keturunan siapa, kapan lahirnya, sampai
                        kapan menikahnya — supaya tidak hilang ditelan waktu,
                        dan mudah dicari kapan saja.
                    </p>

                    <div class="mt-9 flex flex-wrap items-center gap-4">
                        <Link
                            :href="user ? '/dashboard' : '/register'"
                            class="rounded-full bg-[--wt-indigo] px-6 py-3 text-[15px] font-medium text-white transition hover:brightness-110"
                        >
                            {{ user ? 'Ke Dashboard' : 'Mulai Sekarang' }}
                        </Link>
                        <Link
                            href="/about"
                            class="border-[--wt-ink]/15 hover:border-[--wt-ink]/40 rounded-full border px-6 py-3 text-[15px] font-medium text-[--wt-ink] transition"
                        >
                            Kenapa Dibuat?
                        </Link>
                    </div>

                    <div class="mt-12 flex items-center gap-8">
                        <div>
                            <p class="font-display text-2xl text-[--wt-ink]">
                                Alur Persetujuan
                            </p>
                            <p class="wt-label text-[--wt-ink]/45 mt-1">
                                Data Tetap Akurat
                            </p>
                        </div>
                        <div class="h-10 w-px bg-[--wt-line]"></div>
                        <div>
                            <p class="font-display text-2xl text-[--wt-ink]">
                                Pohon Interaktif
                            </p>
                            <p class="wt-label text-[--wt-ink]/45 mt-1">
                                Telusuri Generasi
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SIGNATURE: diagram akar/cabang -->
                <div class="relative h-[420px] sm:h-[480px]" aria-hidden="true">
                    <svg
                        viewBox="0 0 480 520"
                        class="h-full w-full"
                        fill="none"
                    >
                        <!-- batang utama -->
                        <path
                            d="M240 500 L240 340"
                            stroke="#3D5C3D"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 160"
                        />

                        <!-- generasi 1: cabang dari batang -->
                        <path
                            d="M240 340 C 200 300, 150 290, 110 240"
                            stroke="#3D5C3D"
                            stroke-width="2"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 130; animation-delay: 0.3s"
                        />
                        <path
                            d="M240 340 C 280 300, 330 290, 370 240"
                            stroke="#3D5C3D"
                            stroke-width="2"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 130; animation-delay: 0.3s"
                        />

                        <!-- generasi 2 -->
                        <path
                            d="M110 240 C 90 200, 70 180, 60 130"
                            stroke="#3D5C3D"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 110; animation-delay: 0.6s"
                        />
                        <path
                            d="M110 240 C 120 200, 140 175, 150 130"
                            stroke="#3D5C3D"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 110; animation-delay: 0.6s"
                        />
                        <path
                            d="M370 240 C 350 200, 330 180, 320 120"
                            stroke="#3D5C3D"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 120; animation-delay: 0.6s"
                        />
                        <path
                            d="M370 240 C 385 195, 400 175, 415 135"
                            stroke="#3D5C3D"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 110; animation-delay: 0.6s"
                        />

                        <!-- generasi 3: ranting kecil -->
                        <path
                            d="M60 130 C 55 100, 45 85, 40 55"
                            stroke="#3D5C3D"
                            stroke-width="1.2"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 80; animation-delay: 0.9s"
                        />
                        <path
                            d="M150 130 C 155 100, 165 85, 168 55"
                            stroke="#3D5C3D"
                            stroke-width="1.2"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 80; animation-delay: 0.9s"
                        />
                        <path
                            d="M320 120 C 315 90, 305 75, 300 50"
                            stroke="#3D5C3D"
                            stroke-width="1.2"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 75; animation-delay: 0.9s"
                        />
                        <path
                            d="M415 135 C 420 105, 428 88, 435 60"
                            stroke="#3D5C3D"
                            stroke-width="1.2"
                            stroke-linecap="round"
                            class="wt-branch"
                            style="--d: 80; animation-delay: 0.9s"
                        />

                        <!-- titik: leluhur (emas, akar) -->
                        <circle
                            cx="240"
                            cy="500"
                            r="7"
                            fill="#A9782F"
                            class="wt-node"
                            style="animation-delay: 1.1s"
                        />

                        <!-- titik: generasi 1 -->
                        <circle
                            cx="110"
                            cy="240"
                            r="5.5"
                            fill="#3D5C3D"
                            class="wt-node"
                            style="animation-delay: 1.2s"
                        />
                        <circle
                            cx="370"
                            cy="240"
                            r="5.5"
                            fill="#3D5C3D"
                            class="wt-node"
                            style="animation-delay: 1.2s"
                        />

                        <!-- titik: generasi 2 -->
                        <circle
                            cx="60"
                            cy="130"
                            r="4.5"
                            fill="#3D5C3D"
                            class="wt-node"
                            style="animation-delay: 1.35s"
                        />
                        <circle
                            cx="150"
                            cy="130"
                            r="4.5"
                            fill="#3D5C3D"
                            class="wt-node"
                            style="animation-delay: 1.35s"
                        />
                        <circle
                            cx="320"
                            cy="120"
                            r="4.5"
                            fill="#3D5C3D"
                            class="wt-node"
                            style="animation-delay: 1.35s"
                        />
                        <circle
                            cx="415"
                            cy="135"
                            r="4.5"
                            fill="#3D5C3D"
                            class="wt-node"
                            style="animation-delay: 1.35s"
                        />

                        <!-- titik: generasi 3, saat ini (indigo, hidup) -->
                        <circle
                            cx="40"
                            cy="55"
                            r="4"
                            fill="#4F46E5"
                            class="wt-node"
                            style="animation-delay: 1.5s"
                        />
                        <circle
                            cx="168"
                            cy="55"
                            r="4"
                            fill="#4F46E5"
                            class="wt-node"
                            style="animation-delay: 1.5s"
                        />
                        <circle
                            cx="300"
                            cy="50"
                            r="4"
                            fill="#4F46E5"
                            class="wt-node"
                            style="animation-delay: 1.5s"
                        />
                        <circle
                            cx="435"
                            cy="60"
                            r="4"
                            fill="#4F46E5"
                            class="wt-node"
                            style="animation-delay: 1.5s"
                        />
                    </svg>
                </div>
            </div>
        </section>

        <!-- KENAPA -->
        <section class="border-t border-[--wt-line]">
            <div class="mx-auto max-w-6xl px-5 py-16 sm:px-8 sm:py-20">
                <p class="wt-label text-[--wt-gold]">Kenapa Webtrah</p>
                <h2
                    class="font-display mt-3 max-w-xl text-[28px] text-[--wt-ink] sm:text-[34px]"
                >
                    Catatan keluarga sering hilang bukan karena dilupakan, tapi
                    karena tercecer.
                </h2>

                <div class="mt-12 grid gap-10 sm:grid-cols-3">
                    <div>
                        <div
                            class="border-[--wt-root]/30 flex h-9 w-9 items-center justify-center rounded-full border"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M8 1v14M1 8h14"
                                    stroke="#3D5C3D"
                                    stroke-width="1.4"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </div>
                        <h3 class="font-display mt-4 text-lg text-[--wt-ink]">
                            Satu Sumber Kebenaran
                        </h3>
                        <p
                            class="text-[--wt-ink]/70 mt-2 text-[15px] leading-relaxed"
                        >
                            Tidak lagi tersebar di catatan kertas, grup
                            WhatsApp, atau ingatan masing-masing — satu tempat,
                            selalu bisa diakses.
                        </p>
                    </div>
                    <div>
                        <div
                            class="border-[--wt-root]/30 flex h-9 w-9 items-center justify-center rounded-full border"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M3 8.5 6.5 12 13 4"
                                    stroke="#3D5C3D"
                                    stroke-width="1.4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                        <h3 class="font-display mt-4 text-lg text-[--wt-ink]">
                            Perubahan Diverifikasi
                        </h3>
                        <p
                            class="text-[--wt-ink]/70 mt-2 text-[15px] leading-relaxed"
                        >
                            Setiap tambah atau ubah data melalui alur
                            persetujuan — tidak ada yang bisa asal ubah data
                            anggota keluarga lain.
                        </p>
                    </div>
                    <div>
                        <div
                            class="border-[--wt-root]/30 flex h-9 w-9 items-center justify-center rounded-full border"
                        >
                            <svg
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <rect
                                    x="3"
                                    y="7"
                                    width="10"
                                    height="7"
                                    rx="1"
                                    stroke="#3D5C3D"
                                    stroke-width="1.4"
                                />
                                <path
                                    d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2"
                                    stroke="#3D5C3D"
                                    stroke-width="1.4"
                                />
                            </svg>
                        </div>
                        <h3 class="font-display mt-4 text-lg text-[--wt-ink]">
                            Dijaga Ketat
                        </h3>
                        <p
                            class="text-[--wt-ink]/70 mt-2 text-[15px] leading-relaxed"
                        >
                            Verifikasi 2 langkah untuk pengelola, backup
                            otomatis setiap hari — data keluarga tidak sekadar
                            disimpan, tapi dijaga.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA PENUTUP -->
        <section class="border-t border-[--wt-line]">
            <div
                class="mx-auto max-w-6xl px-5 py-16 text-center sm:px-8 sm:py-20"
            >
                <h2
                    class="font-display mx-auto max-w-lg text-[28px] text-[--wt-ink] sm:text-[36px]"
                >
                    Setiap nama, setiap cabang, layak dicatat.
                </h2>
                <div class="mt-8 flex justify-center gap-4">
                    <Link
                        :href="user ? '/dashboard' : '/register'"
                        class="rounded-full bg-[--wt-indigo] px-6 py-3 text-[15px] font-medium text-white transition hover:brightness-110"
                    >
                        {{ user ? 'Ke Dashboard' : 'Daftar Sekarang' }}
                    </Link>
                    <Link
                        v-if="!user"
                        href="/login"
                        class="border-[--wt-ink]/15 hover:border-[--wt-ink]/40 rounded-full border px-6 py-3 text-[15px] font-medium transition"
                    >
                        Sudah Punya Akun
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.wt-branch {
    stroke-dasharray: var(--d);
    stroke-dashoffset: var(--d);
    animation: wt-draw 1s ease-out forwards;
}

.wt-node {
    opacity: 0;
    transform-origin: center;
    animation: wt-pop 0.4s ease-out forwards;
}

@keyframes wt-draw {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes wt-pop {
    from {
        opacity: 0;
        transform: scale(0.3);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .wt-branch,
    .wt-node {
        animation: none;
        stroke-dashoffset: 0;
        opacity: 1;
    }
}
</style>
