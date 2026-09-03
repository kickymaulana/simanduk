<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Card, CardContent } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

type Row = {
    tanggal: number;
    jenis: string;
    input: number;
    fg: number;
    ab: number;
    sg: number;
    reject: number;
    fg_persen: string;
    ab_persen: string;
    sg_persen: string;
    reject_persen: string;
};

const props = defineProps<{
    rows: Row[];
    summary: {
        input: number;
        fg: number;
        ab: number;
        sg: number;
        reject: number;
        fg_persen: string;
        ab_persen: string;
        sg_persen: string;
        reject_persen: string;
    };
    filter: {
        bulan: number;
        tahun: number;
        daftar_bulan: Record<number, string>;
        daftar_tahun: number[];
    };
}>();

const selectedBulan = ref<number>(props.filter.bulan);
const selectedTahun = ref<number>(props.filter.tahun);

const updateFilter = () => {
    router.get(
        `?bulan=${selectedBulan.value}&tahun=${selectedTahun.value}`,
        {},
        { preserveState: true, replace: true },
    );
};

watch([selectedBulan, selectedTahun], updateFilter);
</script>

<template>
    <Head title="Laporan Kualitas" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 uppercase italic">
                    Total Hasil Checking Kloset Duduk
                </h1>
                <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-[0.2em]">
                    Proses QC Visual & Dimensi · Rekapitulasi Hasil Checking per Hari
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="selectedBulan">
                    <SelectTrigger class="w-36 h-9 text-xs">
                        <SelectValue placeholder="Bulan" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="(label, value) in filter.daftar_bulan" :key="value" :value="value">
                            {{ label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="selectedTahun">
                    <SelectTrigger class="w-24 h-9 text-xs">
                        <SelectValue placeholder="Tahun" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="t in filter.daftar_tahun" :key="t" :value="t">
                            {{ t }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <Card class="border-none shadow-sm overflow-hidden">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-100 text-slate-700 text-xs font-bold border-b border-slate-300">
                                <TableHead class="w-10 text-center border-r border-slate-300">No</TableHead>
                                <TableHead class="w-16 text-center border-r border-slate-300">Tgl</TableHead>
                                <TableHead class="w-20 text-left border-r border-slate-300 pl-3">Item</TableHead>
                                <TableHead class="w-20 text-center border-r border-slate-300">Input/Pcs</TableHead>
                                <TableHead colspan="4" class="text-center border-r border-slate-300 border-l border-slate-300">Hasil Checking (Pcs)</TableHead>
                                <TableHead colspan="4" class="text-center">Hasil Checking (%)</TableHead>
                            </TableRow>
                            <TableRow class="bg-slate-50 text-slate-600 text-[10px] font-bold border-b border-slate-200">
                                <TableHead colspan="3" class="border-r border-slate-200"></TableHead>
                                <TableHead class="text-center border-r border-slate-200"></TableHead>
                                <TableHead class="text-center border-r border-slate-200">FG</TableHead>
                                <TableHead class="text-center border-r border-slate-200">AB</TableHead>
                                <TableHead class="text-center border-r border-slate-200">SG</TableHead>
                                <TableHead class="text-center border-r border-slate-200">Reject Buang</TableHead>
                                <TableHead class="text-center border-r border-slate-200">FG%</TableHead>
                                <TableHead class="text-center border-r border-slate-200">AB%</TableHead>
                                <TableHead class="text-center border-r border-slate-200">SG%</TableHead>
                                <TableHead class="text-center">Reject%</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in rows" :key="row.tanggal + '-' + row.jenis" class="border-t border-slate-200">
                                <TableCell class="w-10 text-center text-xs border-r border-slate-200"></TableCell>
                                <TableCell class="w-16 text-center font-bold text-sm border-r border-slate-200">
                                    {{ row.tanggal }}
                                </TableCell>
                                <TableCell class="w-20 text-left text-xs border-r border-slate-200 pl-3">
                                    {{ row.jenis }}
                                </TableCell>
                                <TableCell class="w-20 text-center text-xs font-mono font-bold border-r border-slate-200">
                                    {{ row.input }}
                                </TableCell>
                                <TableCell class="text-center text-xs font-mono border-r border-slate-200">{{ row.fg }}</TableCell>
                                <TableCell class="text-center text-xs font-mono border-r border-slate-200">{{ row.ab }}</TableCell>
                                <TableCell class="text-center text-xs font-mono border-r border-slate-200">{{ row.sg }}</TableCell>
                                <TableCell class="text-center text-xs font-mono border-r border-slate-200">{{ row.reject }}</TableCell>
                                <TableCell class="text-center text-xs font-mono border-r border-slate-200">{{ row.fg_persen }}</TableCell>
                                <TableCell class="text-center text-xs font-mono border-r border-slate-200">{{ row.ab_persen }}</TableCell>
                                <TableCell class="text-center text-xs font-mono border-r border-slate-200">{{ row.sg_persen }}</TableCell>
                                <TableCell class="text-center text-xs font-mono">{{ row.reject_persen }}</TableCell>
                            </TableRow>
                        </TableBody>
                        <tfoot>
                            <TableRow class="font-bold text-xs border-t-2 border-slate-300 bg-slate-50">
                                <TableCell colspan="3" class="border-r border-slate-300">Total</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.input }}</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.fg }}</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.ab }}</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.sg }}</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.reject }}</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.fg_persen }}</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.ab_persen }}</TableCell>
                                <TableCell class="text-center text-blue-700 border-r border-slate-300">{{ summary.sg_persen }}</TableCell>
                                <TableCell class="text-center text-blue-700">{{ summary.reject_persen }}</TableCell>
                            </TableRow>
                        </tfoot>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
