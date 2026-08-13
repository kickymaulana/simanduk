<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import BarChart from "@/components/BarChart.vue";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    IconPlus,
    IconChartBar,
    IconCalendar,
} from "@tabler/icons-vue";

// Menerima data dari Controller
const props = defineProps<{
    filter: {
        bulan: number;
        tahun: number;
        daftar_bulan: Array<{ value: number; label: string }>;
        daftar_tahun: number[];
    };
    reports: any;
}>();

defineOptions({ layout: AuthenticatedLayout });

// --- Filter Bulan / Tahun ---
const selectedBulan = ref<number>(props.filter.bulan);
const selectedTahun = ref<number>(props.filter.tahun);

const updateFilter = () => {
    router.get(
        route("dashboard"),
        { bulan: selectedBulan.value, tahun: selectedTahun.value },
        {
            preserveState: true,
            replace: true,
            only: ["filter", "reports"],
        },
    );
};

watch([selectedBulan, selectedTahun], updateFilter);

const rs = computed(() => props.reports.rejectSummary);
const periodLabel = computed(
    () =>
        `${props.filter.daftar_bulan.find((b) => b.value === selectedBulan.value)?.label} ${selectedTahun.value}`,
);

const topWorstOperator = computed(() =>
    [...props.reports.rejectByOperator].sort((a, b) => b.persen - a.persen).slice(0, 10),
);

const rejectColor = (p: number) =>
    p >= 20 ? "bg-red-500" : p >= 10 ? "bg-amber-500" : "bg-green-500";
</script>

<template>
    <Head title="Dashboard SIMANDUK" />

    <div class="flex flex-col gap-6 p-4 md:p-8 pt-4 transition-colors duration-500">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 uppercase italic">
                    Dashboard SIMANDUK
                </h1>
                <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-[0.2em]">
                    SIstem Monitoring Alur Nilai Defect Unit Kloset Duduk
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2">
                    <Select v-model="selectedBulan">
                        <SelectTrigger class="w-36 h-9 text-xs">
                            <SelectValue placeholder="Bulan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="b in filter.daftar_bulan"
                                :key="b.value"
                                :value="b.value"
                            >
                                {{ b.label }}
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

                <Button
                    size="sm"
                    variant="outline"
                    class="font-black uppercase text-[10px] h-9 shadow-lg shadow-primary/20"
                    as-child
                    >
                    <Link :href="route('produk.index')" class="flex items-center px-4">
                        <IconChartBar class="mr-2 size-4" />
                        <span>Daftar Produk</span>
                    </Link>
                </Button>

                <Button
                    size="sm"
                    class="font-black uppercase text-[10px] h-9 shadow-lg shadow-primary/20"
                    as-child
                    >
                    <Link :href="route('scan.index')" class="flex items-center px-4">
                        <IconPlus class="mr-2 size-4" />
                        <span>Scan Produk</span>
                    </Link>
                </Button>
            </div>
        </div>

        <!-- ===================== LAPORAN REJECT & PRODUKSI ===================== -->
        <div class="flex items-center gap-2 pt-2">
            <IconCalendar class="size-5 text-primary" />
            <h2 class="text-lg font-black uppercase tracking-tight text-slate-800 dark:text-slate-100">
                Laporan Reject & Produksi
            </h2>
            <Badge variant="outline" class="text-[10px] font-black uppercase">{{ periodLabel }}</Badge>
        </div>

        <!-- 1. Reject Bulanan + Pareto -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="border-none shadow-sm dark:bg-slate-900/50 dark:ring-1 dark:ring-slate-800">
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs font-black uppercase tracking-widest dark:text-slate-200">
                        1. Ringkasan Reject Bulan Ini
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-muted/40 p-3">
                            <p class="text-[9px] font-black uppercase text-muted-foreground">Total Output</p>
                            <p class="text-2xl font-black">{{ rs.total }}</p>
                        </div>
                        <div class="rounded-xl bg-green-50 p-3">
                            <p class="text-[9px] font-black uppercase text-green-600">OK</p>
                            <p class="text-2xl font-black text-green-600">{{ rs.ok }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 p-3">
                            <p class="text-[9px] font-black uppercase text-amber-600">In Proses</p>
                            <p class="text-2xl font-black text-amber-600">{{ rs.in_proses }}</p>
                        </div>
                        <div class="rounded-xl bg-red-50 p-3">
                            <p class="text-[9px] font-black uppercase text-red-600">Buang</p>
                            <p class="text-2xl font-black text-red-600">{{ rs.buang }}</p>
                        </div>
                    </div>
                    <div class="rounded-xl bg-primary/10 p-4 text-center">
                        <p class="text-[9px] font-black uppercase text-muted-foreground">Persentase Reject</p>
                        <p class="text-4xl font-black text-primary italic">{{ rs.persen_reject }}%</p>
                        <p class="text-[10px] font-bold text-muted-foreground">{{ rs.reject }} dari {{ rs.total }} unit</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2 border-none shadow-sm dark:bg-slate-900/50 dark:ring-1 dark:ring-slate-800">
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs font-black uppercase tracking-widest dark:text-slate-200">
                        Pareto Top 10 Jenis Reject
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :data="reports.paretoCacat"
                        label-key="nama"
                        value-key="total"
                        color="rgb(239 68 68)"
                    />
                    <Table class="mt-2">
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="text-[10px] uppercase">Jenis Cacat</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">Jumlah</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">%</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">Kumulatif</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(p, i) in reports.paretoCacat" :key="i">
                                <TableCell class="text-xs font-semibold">{{ p.nama }}</TableCell>
                                <TableCell class="text-right font-bold">{{ p.total }}</TableCell>
                                <TableCell class="text-right">{{ p.persen }}%</TableCell>
                                <TableCell class="text-right text-primary font-bold">{{ p.kumulatif }}%</TableCell>
                            </TableRow>
                            <TableRow v-if="reports.paretoCacat.length === 0">
                                <TableCell colspan="4" class="text-center text-xs text-muted-foreground uppercase py-4">
                                    Tidak ada data reject
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- 2. Top Operator Reject -->
        <Card class="border-none shadow-sm dark:bg-slate-900/50 dark:ring-1 dark:ring-slate-800">
            <CardHeader class="pb-2">
                <CardTitle class="text-xs font-black uppercase tracking-widest dark:text-slate-200">
                    2. Top 10 Operator Reject (Buang + In Proses)
                </CardTitle>
            </CardHeader>
            <CardContent>
                <BarChart
                    :data="reports.topOperatorReject"
                    label-key="name"
                    value-key="total"
                    color="rgb(249 115 22)"
                />
                <Table class="mt-2">
                    <TableHeader>
                        <TableRow class="bg-muted/50">
                            <TableHead class="text-[10px] uppercase w-10">#</TableHead>
                            <TableHead class="text-[10px] uppercase">Operator</TableHead>
                            <TableHead class="text-[10px] uppercase">Departemen</TableHead>
                            <TableHead class="text-right text-[10px] uppercase">Reject</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(o, i) in reports.topOperatorReject" :key="i">
                            <TableCell class="text-muted-foreground font-bold">{{ i + 1 }}</TableCell>
                            <TableCell class="text-xs font-semibold">{{ o.name }}</TableCell>
                            <TableCell class="text-xs text-muted-foreground">{{ o.departemen }}</TableCell>
                            <TableCell class="text-right font-bold text-red-600">{{ o.total }}</TableCell>
                        </TableRow>
                        <TableRow v-if="reports.topOperatorReject.length === 0">
                            <TableCell colspan="4" class="text-center text-xs text-muted-foreground uppercase py-4">
                                Tidak ada data reject
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- 3. Total Output -->
        <Card class="border-none shadow-sm dark:bg-slate-900/50 dark:ring-1 dark:ring-slate-800">
            <CardHeader class="pb-2">
                <CardTitle class="text-xs font-black uppercase tracking-widest dark:text-slate-200">
                    3. Total Output per Bulan ({{ selectedTahun }})
                </CardTitle>
            </CardHeader>
            <CardContent>
                <BarChart
                    :data="reports.outputTrend"
                    label-key="bulan"
                    value-key="total"
                    color="rgb(34 197 94)"
                />
            </CardContent>
        </Card>

        <!-- 4. % Reject Departemen & Operator -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card class="border-none shadow-sm dark:bg-slate-900/50 dark:ring-1 dark:ring-slate-800">
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs font-black uppercase tracking-widest dark:text-slate-200">
                        4a. % Reject per Departemen
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :data="reports.rejectByDepartemen"
                        label-key="nama"
                        value-key="persen"
                        suffix="%"
                        color="rgb(168 85 247)"
                    />
                    <Table class="mt-2">
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="text-[10px] uppercase">Departemen</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">Total</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">Reject</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">%</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(d, i) in reports.rejectByDepartemen" :key="i">
                                <TableCell class="text-xs font-semibold">{{ d.nama }}</TableCell>
                                <TableCell class="text-right">{{ d.total }}</TableCell>
                                <TableCell class="text-right">{{ d.reject }}</TableCell>
                                <TableCell class="text-right">
                                    <span class="inline-flex items-center gap-1 font-bold">
                                        <span class="size-2 rounded-full" :class="rejectColor(d.persen)"></span>
                                        {{ d.persen }}%
                                    </span>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="reports.rejectByDepartemen.length === 0">
                                <TableCell colspan="4" class="text-center text-xs text-muted-foreground uppercase py-4">
                                    Tidak ada data
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card class="border-none shadow-sm dark:bg-slate-900/50 dark:ring-1 dark:ring-slate-800">
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs font-black uppercase tracking-widest dark:text-slate-200">
                        4b. % Reject per Operator (Top 10 Terburuk)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :data="topWorstOperator"
                        label-key="name"
                        value-key="persen"
                        suffix="%"
                        color="rgb(236 72 153)"
                    />
                    <Table class="mt-2">
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="text-[10px] uppercase">Operator</TableHead>
                                <TableHead class="text-[10px] uppercase">Departemen</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">Total</TableHead>
                                <TableHead class="text-right text-[10px] uppercase">%</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(o, i) in topWorstOperator" :key="i">
                                <TableCell class="text-xs font-semibold">{{ o.name }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ o.departemen }}</TableCell>
                                <TableCell class="text-right">{{ o.total }}</TableCell>
                                <TableCell class="text-right">
                                    <span class="inline-flex items-center gap-1 font-bold">
                                        <span class="size-2 rounded-full" :class="rejectColor(o.persen)"></span>
                                        {{ o.persen }}%
                                    </span>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="topWorstOperator.length === 0">
                                <TableCell colspan="4" class="text-center text-xs text-muted-foreground uppercase py-4">
                                    Tidak ada data
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
