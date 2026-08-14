<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PublicLayout from '@/layouts/PublicLayout.vue'

const user = computed(() => usePage().props.auth?.user ?? null)
</script>

<template>
    <Head title="Tentang — Webtrah" />
    <PublicLayout>
        <section class="mx-auto max-w-3xl px-5 pb-8 pt-14 sm:px-8 sm:pt-20">
            <p class="wt-label text-[--wt-gold]">Tentang Webtrah</p>
            <h1
                class="font-display mt-4 text-[32px] leading-[1.1] text-[--wt-ink] sm:text-[44px]"
            >
                Dibangun dari satu pertanyaan sederhana: kalau bukan sekarang,
                kapan lagi?
            </h1>
        </section>

        <section
            class="text-[--wt-ink]/85 mx-auto max-w-3xl space-y-6 px-5 py-8 text-[16px] leading-[1.75] sm:px-8"
        >
            <p>
                Silsilah keluarga besar kita selama ini tersebar — sebagian di
                ingatan orang tua dan kakek-nenek, sebagian di foto lawas yang
                tersimpan di HP salah satu anggota keluarga, sebagian lagi cuma
                pernah diceritakan lisan waktu kumpul keluarga. Tidak ada satu
                tempat yang bisa dibuka siapa saja, kapan saja, untuk tahu:
                siapa anak siapa, siapa menikah dengan siapa, cabang keluarga
                mana yang belum tercatat.
            </p>
            <p>
                Webtrah dibangun untuk jadi tempat itu. Bukan sekadar daftar
                nama, tapi pohon keluarga yang bisa ditelusuri, dengan alur
                persetujuan supaya data yang masuk sudah dicek dulu — tidak asal
                siapa saja bisa ubah data anggota keluarga lain begitu saja.
            </p>

            <div class="py-2">
                <div
                    class="rounded-2xl border border-[--wt-line] bg-white/50 p-6 sm:p-8"
                >
                    <p class="wt-label text-[--wt-root]">Cara Kerjanya</p>
                    <ul class="text-[--wt-ink]/80 mt-4 space-y-3 text-[15px]">
                        <li class="flex gap-3">
                            <span
                                class="font-display mt-0.5 italic text-[--wt-gold]"
                                >1</span
                            >
                            <span
                                >Anggota keluarga (moderator cabang) mengajukan
                                data baru atau perubahan.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="font-display mt-0.5 italic text-[--wt-gold]"
                                >2</span
                            >
                            <span
                                >Pengelola pusat meninjau dan menyetujui sebelum
                                data itu resmi tercatat.</span
                            >
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="font-display mt-0.5 italic text-[--wt-gold]"
                                >3</span
                            >
                            <span
                                >Pohon keluarga ter-update, bisa ditelusuri
                                siapa saja yang punya akun.</span
                            >
                        </li>
                    </ul>
                </div>
            </div>

            <p>
                Ini dibangun dan dirawat mandiri oleh salah satu anggota
                keluarga — bukan produk komersial, tapi proyek keluarga yang
                dikerjakan dengan standar yang sama seriusnya seperti aplikasi
                pada umumnya.
            </p>
        </section>

        <!-- KOMITMEN KEAMANAN -->
        <section class="border-t border-[--wt-line]">
            <div class="mx-auto max-w-3xl px-5 py-14 sm:px-8 sm:py-16">
                <p class="wt-label text-[--wt-gold]">
                    Data Keluarga, Dijaga Serius
                </p>
                <h2
                    class="font-display mt-3 text-[26px] text-[--wt-ink] sm:text-[30px]"
                >
                    Bukan cuma disimpan — dijaga.
                </h2>
                <p
                    class="text-[--wt-ink]/70 mt-4 max-w-xl text-[15px] leading-relaxed"
                >
                    Karena ini menyangkut data keluarga besar, bukan sekadar
                    catatan pribadi, Webtrah dibangun dengan beberapa lapis
                    perlindungan yang biasanya baru dipikirkan aplikasi besar:
                </p>

                <div class="mt-8 grid gap-x-10 gap-y-6 sm:grid-cols-2">
                    <div class="flex gap-3">
                        <div
                            class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[--wt-root]"
                        ></div>
                        <p
                            class="text-[--wt-ink]/75 text-[14.5px] leading-relaxed"
                        >
                            <span class="font-medium text-[--wt-ink]"
                                >Verifikasi 2 langkah</span
                            >
                            wajib untuk akun pengelola — password saja tidak
                            cukup.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <div
                            class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[--wt-root]"
                        ></div>
                        <p
                            class="text-[--wt-ink]/75 text-[14.5px] leading-relaxed"
                        >
                            <span class="font-medium text-[--wt-ink]"
                                >Backup otomatis</span
                            >
                            setiap hari, dengan riwayat simpanan berjenjang.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <div
                            class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[--wt-root]"
                        ></div>
                        <p
                            class="text-[--wt-ink]/75 text-[14.5px] leading-relaxed"
                        >
                            <span class="font-medium text-[--wt-ink]"
                                >Pemantauan otomatis</span
                            >
                            — kalau ada masalah di server, langsung ketahuan,
                            bukan menunggu laporan.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <div
                            class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[--wt-root]"
                        ></div>
                        <p
                            class="text-[--wt-ink]/75 text-[14.5px] leading-relaxed"
                        >
                            <span class="font-medium text-[--wt-ink]"
                                >Setiap perubahan diuji otomatis</span
                            >
                            sebelum dipakai, supaya fitur yang sudah jalan tidak
                            diam-diam rusak.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-[--wt-line]">
            <div
                class="mx-auto max-w-3xl px-5 py-14 text-center sm:px-8 sm:py-16"
            >
                <h2
                    class="font-display text-[26px] text-[--wt-ink] sm:text-[32px]"
                >
                    Yuk, mulai lengkapi pohon keluarga kita.
                </h2>
                <div class="mt-7 flex justify-center gap-4">
                    <Link
                        :href="user ? '/dashboard' : '/register'"
                        class="rounded-full bg-[--wt-indigo] px-6 py-3 text-[15px] font-medium text-white transition hover:brightness-110"
                    >
                        {{ user ? 'Ke Dashboard' : 'Daftar Sekarang' }}
                    </Link>
                    <Link
                        href="/"
                        class="border-[--wt-ink]/15 hover:border-[--wt-ink]/40 rounded-full border px-6 py-3 text-[15px] font-medium transition"
                    >
                        Kembali ke Beranda
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
