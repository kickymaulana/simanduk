<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button"; // Pastikan import ini ada
import {
    IconCircleCheck,
    IconAlertCircle,
    IconUser,
    IconClock,
    IconPackage,
    IconHistory,
    IconTruck,
    IconBarcode,
    IconCalendar,
    IconArrowLeft,
    IconFlask // Icon untuk tombol kembali
} from "@tabler/icons-vue";
const props = defineProps<{
    produk: any;
    backUrl: string;
}>();

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <Head :title="'Detail ' + produk.qrcode" />

    <div class="p-4 md:p-8 max-w-7xl mx-auto">

        <div class="mb-6">
            <Button variant="ghost" size="sm" as-child class="p-0 hover:bg-transparent group">
                <Link :href="props.backUrl" class="flex items-center text-muted-foreground group-hover:text-primary transition-colors">
                    <IconArrowLeft class="size-4 mr-2 group-hover:-translate-x-1 transition-transform" />
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">Kembali ke Daftar</span>
                </Link>
            </Button>
        </div>

        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">Product Tracking System</p>
                <h1 class="text-4xl font-black italic uppercase tracking-tighter dark:text-white">
                    {{ produk.qrcode }}
                </h1>
            </div>
            <Badge variant="outline" class="w-fit font-black border-2 px-6 py-2 text-md uppercase italic bg-primary/5 border-primary/20 text-primary">
                Posisi: {{ produk.pengerjaan_produks[0]?.proses?.proses || 'N/A' }}
            </Badge>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-8">
                <Card class="border-none shadow-sm overflow-hidden bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800">
                    <div class="h-2 bg-primary"></div>
                    <CardHeader>
                        <CardTitle class="text-xs font-black uppercase tracking-widest text-muted-foreground italic flex items-center gap-2">
                            <IconPackage class="size-4" /> Informasi Unit
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="flex items-center gap-4 p-4 rounded-2xl bg-muted/30 dark:bg-slate-950/50 border border-dashed border-slate-300 dark:border-slate-700">
                            <IconBarcode class="size-8 text-slate-400" />
                            <div class="flex-1">
                                <p class="text-[9px] font-bold uppercase text-muted-foreground">Nomor Seri / Barcode</p>
                                <p class="text-lg font-black tracking-tight">{{ produk.qrcode }}</p>
                                <Badge v-if="produk.is_sample" variant="secondary" class="mt-1 font-black uppercase italic bg-amber-100 text-amber-700 hover:bg-amber-100">
                                    <IconFlask class="size-3 mr-1" /> Sampel · {{ produk.kode_sampel }}
                                </Badge>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                    <IconTruck class="size-4" />
                                </div>
                                <div>
                                    <p class="text-[8px] font-black uppercase text-muted-foreground">Proses Saat Ini</p>
                                    <p class="text-xs font-bold uppercase">{{ produk.proses?.proses || 'BELUM ADA PROSES' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-lg bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-600">
                                    <IconCalendar class="size-4" />
                                </div>
                                <div>
                                    <p class="text-[8px] font-black uppercase text-muted-foreground">Terdaftar Pada</p>
                                    <p class="text-xs font-bold uppercase">{{ new Date(produk.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                             <p class="text-[9px] font-black uppercase text-muted-foreground mb-2">Status Kualitas Terakhir</p>
                             <Badge v-if="produk.pengerjaan_produks[0]?.pengerjaan_cacats?.length > 0" variant="destructive" class="font-black uppercase italic">
                                Terdeteksi Cacat
                             </Badge>
                             <Badge v-else class="bg-green-500 hover:bg-green-600 font-black uppercase italic">
                                OK / Bagus
                             </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="lg:col-span-8">


                    <Card class="border-none shadow-sm dark:bg-slate-900/50 dark:ring-1 dark:ring-slate-800">
                    <CardHeader class="pb-1"> <CardTitle class="text-sm font-black uppercase flex items-center gap-2">
                            <IconHistory class="size-3 text-primary" /> Log Aktivitas Produksi
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="relative space-y-4 before:absolute before:inset-0 before:ml-4 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-primary before:via-slate-200 dark:before:via-slate-800 before:to-transparent">

                            <div v-for="(history, index) in produk.pengerjaan_produks" :key="history.id" class="relative flex items-start">
                                <div :class="[index === 0 ? 'bg-primary ring-4 ring-primary/20' : 'bg-slate-400']"
                                    class="absolute left-0 size-8 rounded-full border-4 border-white dark:border-slate-950 flex items-center justify-center z-10 shadow-sm transition-all">
                                    <IconCircleCheck v-if="history.pengerjaan_cacats.length === 0" class="size-4 text-white" />
                                    <IconAlertCircle v-else class="size-4 text-white" />
                                </div>

                                <div class="ml-10 bg-muted/30 dark:bg-slate-900/80 p-2 md:p-2 rounded-xl border border-transparent hover:border-primary/20 transition-all flex-1 shadow-sm">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-1 mb-1">
                                        <h4 class="font-black uppercase text-sm italic tracking-tight text-slate-800 dark:text-slate-200">
                                            {{ history.proses.proses }}
                                        </h4>
                                        <div class="flex items-center gap-1 bg-white/50 dark:bg-slate-800 px-2 py-0.5 rounded-full border dark:border-slate-700">
                                            <IconClock class="size-3 text-primary" />
                                            <span class="text-[9px] font-black text-slate-600 dark:text-slate-400 uppercase">
                                                {{ new Date(history.created_at).toLocaleString('id-ID') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 mb-2">
                                        <IconUser class="size-3 text-slate-400" />
                                        <p class="text-[9px] font-bold uppercase text-slate-500">
                                            Operator: <span class="text-slate-800 dark:text-slate-200">{{ history.user.name }}</span>
                                        </p>
                                    </div>

                                    <div v-if="history.pengerjaan_cacats.length > 0" class="p-2 bg-red-50 dark:bg-red-950/30 rounded-lg border border-red-100 dark:border-red-900/30">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="item in history.pengerjaan_cacats" :key="item.id" variant="destructive" class="text-[8px] font-black uppercase px-2 py-0 shadow-sm">
                                                {{ item.cacat.cacat }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <div v-else class="flex items-center gap-2 text-green-600 dark:text-green-500 text-[9px] font-black uppercase italic">
                                        <IconCircleCheck class="size-3" /> OK
                                    </div>
                                </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            </div>

        </div>
    </div>
</template>
