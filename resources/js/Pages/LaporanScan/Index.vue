<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    IconCalendar,
    IconTarget,
    IconTrendingUp,
    IconRefreshCw,
    IconPrinter,
} from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    rows: Array<any>;
    prosesList: Array<string>;
    summary: {
        capaian: Record<string, number>;
        totalTarget: Record<string, number>;
        capaianPersen: Record<string, string>;
    };
    filter: {
        bulan: number;
        tahun: number;
        jenis: string | null;
        daftar_bulan: Record<number, string>;
        daftar_tahun: number[];
    };
}>();

const selectedBulan = ref<number>(props.filter.bulan);
const selectedTahun = ref<number>(props.filter.tahun);
const selectedJenis = ref<string>(props.filter.jenis ?? "all");

const updateFilter = () => {
    router.get(
        `?bulan=${selectedBulan.value}&tahun=${selectedTahun.value}&jenis=${selectedJenis.value}`,
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch([selectedBulan, selectedTahun, selectedJenis], updateFilter);

const getSummaryTextColor = (pctStr: string) => {
    const pct = parseFloat(pctStr.replace('%', ''));
    if (pct >= 100) return 'text-green-700 font-bold';
    if (pct >= 80) return 'text-green-700';
    if (pct >= 50) return 'text-amber-700';
    return 'text-red-700';
};
</script>

<template>
    <Head title="Laporan Scan" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 no-print">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 uppercase italic">
                    Laporan Scan
                </h1>
                <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-[0.2em]">
                    Rekapitulasi Target vs Actual per Proses per Hari
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-2">
                <Select v-model="selectedJenis">
                    <SelectTrigger class="w-32 h-9 text-xs">
                        <SelectValue placeholder="Jenis" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua</SelectItem>
                        <SelectItem value="body">Body</SelectItem>
                        <SelectItem value="tangki">Tangki</SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="selectedBulan">
                        <SelectTrigger class="w-36 h-9 text-xs">
                            <SelectValue placeholder="Bulan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="(label, value) in filter.daftar_bulan"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedTahun">
                        <SelectTrigger class="w-24 h-9 text-xs">
                            <SelectValue placeholder="Tahun" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="t in filter.daftar_tahun"
                                :key="t"
                                :value="t"
                            >
                                {{ t }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Button
                variant="outline"
                size="icon"
                @click="handlePrint"
                class="ml-2"
                title="Cetak Laporan"
            >
                <IconPrinter class="size-4" />
            </Button>

        </div>

        <Card class="border-none shadow-sm overflow-hidden">
            <CardHeader class="pb-2">
                <div class="flex items-center gap-2">
                    <IconCalendar class="size-4 text-primary" />
                    <CardTitle class="text-xs font-black uppercase tracking-widest">
                        Periode: {{ filter.daftar_bulan[filter.bulan] }} {{ filter.tahun }}
                    </CardTitle>
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-100 text-slate-700 text-xs font-bold border-b border-slate-300">
                                <TableHead class="w-16 text-center border-r border-slate-300">Tgl</TableHead>
                                <TableHead
                                    v-for="p in prosesList"
                                    :key="p"
                                    class="w-28 text-center border-r border-slate-300"
                                >
                                    {{ p }}
                                </TableHead>
                            </TableRow>
                            <TableRow class="bg-slate-50 text-slate-600 text-[9px] font-medium border-b border-slate-300">
                                <TableHead class="w-16 border-r border-slate-300"></TableHead>
                                <TableHead
                                    v-for="p in prosesList"
                                    :key="p + '-header'"
                                    class="w-28 text-center border-r border-slate-300"
                                >
                                    Actual / Target
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="row in rows"
                                :key="row.tanggal"
                                class="border-t border-slate-200"
                            >
                                <TableCell class="w-16 text-center font-bold text-sm border-r border-slate-200">
                                    {{ row.tanggal }}
                                </TableCell>
                                <TableCell
                                    v-for="p in prosesList"
                                    :key="p"
                                    class="w-28 text-center border-r border-slate-200"
                                >
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-xs font-mono font-medium">
                                            {{ row[p].actual }}
                                        </span>
                                        <span
                                            v-if="row[p].target > 0"
                                            class="text-[9px] text-muted-foreground font-mono"
                                        >
                                            / {{ row[p].target }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-[9px] text-muted-foreground"
                                        >
                                            —
                                        </span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>

<TableBody>
                            <TableRow class="font-bold text-xs border-t-2 border-slate-300">
                                <TableCell class="w-16 border-r border-slate-300">Capaian</TableCell>
                                <TableCell
                                    v-for="p in prosesList"
                                    :key="p + '-capaian'"
                                    class="text-center text-green-700 border-r border-slate-300"
                                >
                                    {{ summary.capaian[p] }}
                                </TableCell>
                            </TableRow>
                            <TableRow class="font-bold text-xs border-t border-slate-300">
                                <TableCell class="w-16 border-r border-slate-300">Total Target</TableCell>
                                <TableCell
                                    v-for="p in prosesList"
                                    :key="p + '-target'"
                                    class="text-center text-blue-700 border-r border-slate-300"
                                >
                                    {{ summary.totalTarget[p] }}
                                </TableCell>
                            </TableRow>
                            <TableRow class="font-bold text-xs border-t-2 border-slate-300">
                                <TableCell class="w-16 border-r border-slate-300">Capaian %</TableCell>
                                <TableCell
                                    v-for="p in prosesList"
                                    :key="p + '-persen'"
                                    class="text-center border-r border-slate-300"
                                    :class="getSummaryTextColor(summary.capaianPersen[p])"
                                >
                                    {{ summary.capaianPersen[p] }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>