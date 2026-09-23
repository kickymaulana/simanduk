<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { IconChartBar } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps<{ proses: { id: number; proses: string }[]; kualitas: { id: number; kualitas: string }[]; rows: { nama: string; hari: Record<string, number>; total: number; persentase: number }[]; output: number; bulan: number; tahun: number; proses_id: number | null; kualitas_id: number | "belum_ditentukan"; days: number }>();
const bulan = ref(String(props.bulan));
const tahun = ref(String(props.tahun));
const prosesId = ref(String(props.proses_id ?? ""));
const kualitasId = ref(String(props.kualitas_id));
let timeout: ReturnType<typeof setTimeout>;
watch([bulan, tahun, prosesId, kualitasId], () => { clearTimeout(timeout); timeout = setTimeout(() => router.get(route("laporan.kerusakan.qc"), { bulan: bulan.value, tahun: tahun.value, proses_id: prosesId.value, kualitas_id: kualitasId.value }, { preserveState: true, replace: true }), 250); });
const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
</script>
<template>
    <Head title="Rincian Kerusakan QC" />
    <div class="flex flex-col gap-4 p-4 pt-4 md:p-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div><h1 class="text-2xl font-black uppercase tracking-tight">Rincian Kerusakan QC</h1><p class="text-xs text-muted-foreground">Data berdasarkan scan terakhir produk pada proses QC.</p></div>
            <div class="flex flex-wrap gap-2">
                <select v-model="bulan" class="h-9 rounded-md border bg-background px-3 text-sm"><option v-for="(name, index) in months" :key="index" :value="String(index + 1)">{{ name }}</option></select>
                <select v-model="tahun" class="h-9 rounded-md border bg-background px-3 text-sm"><option v-for="year in [tahun - 1, tahun, tahun + 1]" :key="year" :value="String(year)">{{ year }}</option></select>
                <select v-model="prosesId" class="h-9 rounded-md border bg-background px-3 text-sm"><option v-for="item in proses" :key="item.id" :value="String(item.id)">{{ item.proses }}</option></select>
                <select v-model="kualitasId" class="h-9 rounded-md border bg-background px-3 text-sm"><option value="belum_ditentukan">Belum ditentukan</option><option v-for="item in kualitas" :key="item.id" :value="String(item.id)">{{ item.kualitas }}</option></select>
            </div>
        </div>
        <Card class="border-none shadow-sm"><CardHeader class="pb-3"><CardTitle class="flex items-center gap-2 text-xs font-black uppercase tracking-widest"><IconChartBar class="size-4 text-primary" /> Rincian Kerusakan</CardTitle></CardHeader><CardContent class="p-0"><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="bg-muted/50"><th class="sticky left-0 bg-muted/50 px-3 py-2 text-left">Rincian Kerusakan</th><th v-for="day in days" :key="day" class="px-2 py-2 text-center">{{ day }}</th><th class="px-3 py-2 text-center">Total</th><th class="px-3 py-2 text-center">AVERAGE</th></tr></thead><tbody><tr v-if="rows.length === 0"><td :colspan="days + 3" class="h-24 text-center text-muted-foreground">Tidak ada data kerusakan.</td></tr><tr v-for="row in rows" :key="row.nama" class="border-b"><td class="sticky left-0 bg-background px-3 py-2 font-semibold">{{ row.nama }}</td><td v-for="day in days" :key="day" class="px-2 py-2 text-center font-mono">{{ row.hari[String(day)] || 0 }}</td><td class="px-3 py-2 text-center font-mono font-bold">{{ row.total }}</td><td class="px-3 py-2 text-center font-mono font-bold">{{ row.persentase.toFixed(2) }}%</td></tr></tbody><tfoot><tr class="border-t-2 bg-slate-50 font-bold"><td class="sticky left-0 bg-slate-50 px-3 py-2">Output</td><td v-for="day in days" :key="day" class="px-2 py-2 text-center font-mono">-</td><td class="px-3 py-2 text-center font-mono">{{ output }}</td><td class="px-3 py-2 text-center">100%</td></tr></tfoot></table></div></CardContent></Card>
    </div>
</template>
