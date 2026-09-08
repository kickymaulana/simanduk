<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { IconCalendar, IconTrash } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    rows: Array<{ tanggal: number; jenis: string; columns: Record<string, number> }>;
    columns: Array<{ key: string; label: string }>;
    totals: Record<string, Record<string, number>>;
    filter: {
        bulan: number;
        tahun: number;
        daftar_bulan: Record<number, string>;
        daftar_tahun: number[];
    };
}>();

const selectedBulan = ref<number>(props.filter.bulan);
const selectedTahun = ref<number>(props.filter.tahun);

watch([selectedBulan, selectedTahun], () => {
    router.get(
        `?bulan=${selectedBulan.value}&tahun=${selectedTahun.value}`,
        {},
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Data Reject Sanitary" />
    <div class="flex flex-col gap-4 p-4 pt-4 md:p-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-black uppercase italic tracking-tight text-slate-900 dark:text-slate-100">
                    Data Reject Sanitary
                </h1>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
                    Rekap reject berdasarkan proses dan jenis produk
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="selectedBulan">
                    <SelectTrigger class="h-9 w-36 text-xs"><SelectValue placeholder="Bulan" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="(label, value) in filter.daftar_bulan" :key="value" :value="value">{{ label }}</SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="selectedTahun">
                    <SelectTrigger class="h-9 w-24 text-xs"><SelectValue placeholder="Tahun" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="year in filter.daftar_tahun" :key="year" :value="year">{{ year }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <Card class="overflow-hidden border-none shadow-sm">
            <CardHeader class="pb-2">
                <div class="flex items-center gap-2">
                    <IconCalendar class="size-4 text-primary" />
                    <CardTitle class="text-xs font-black uppercase tracking-widest">
                        Periode: {{ filter.daftar_bulan[filter.bulan] }} {{ filter.tahun }}
                    </CardTitle>
                </div>
            </CardHeader>
        </Card>

        <Card class="overflow-hidden border-none shadow-sm">
            <CardHeader class="pb-2">
                <div class="flex items-center gap-2">
                    <IconTrash class="size-4 text-red-500" />
                    <CardTitle class="text-xs font-black uppercase tracking-widest">Reject per Proses</CardTitle>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-b border-slate-300 bg-slate-100 text-xs font-bold text-slate-700">
                                <TableHead class="w-16 border-r border-slate-300 text-center">Tgl</TableHead>
                                <TableHead class="w-24 border-r border-slate-300 text-center">Jenis</TableHead>
                                <TableHead v-for="column in columns" :key="column.key" class="min-w-36 border-r border-slate-300 text-center">
                                    <div>{{ column.label }}</div>
                                    <div class="text-[10px] font-normal uppercase tracking-widest text-muted-foreground">Qty / %</div>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in rows" :key="`${row.tanggal}-${row.jenis}`" class="border-t border-slate-200">
                                <TableCell class="border-r border-slate-200 text-center font-bold">{{ row.tanggal }}</TableCell>
                                <TableCell class="border-r border-slate-200 text-center text-xs font-bold">{{ row.jenis }}</TableCell>
                                 <TableCell v-for="column in columns" :key="column.key" class="border-r border-slate-200 text-center font-mono text-xs">
                                    <div class="flex flex-col items-center leading-tight">
                                        <span>{{ (row.columns?.[column.key] ?? row[column.key]) ?? 0 }}</span>
                                        <span class="text-[10px] text-muted-foreground">{{ row.output_casting ? (((row.columns?.[column.key] ?? row[column.key]) ?? 0) / row.output_casting * 100).toFixed(2).replace('.', ',') + '%' : '0,00%' }}</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow class="border-t-2 border-slate-300 bg-slate-50 text-xs font-bold">
                                <TableCell colspan="2" class="border-r border-slate-300">Total Body</TableCell>
                                <TableCell v-for="column in columns" :key="`${column.key}-body-total`" class="border-r border-slate-300 text-center text-red-600">
                                    <div class="flex flex-col items-center leading-tight">
                                        <span>{{ totals.Body?.[column.key] ?? 0 }}</span>
                                        <span class="text-[10px] text-muted-foreground">{{ totals.Body?.output_casting ? (((totals.Body?.[column.key] ?? 0) / totals.Body.output_casting) * 100).toFixed(2).replace('.', ',') + '%' : '0,00%' }}</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow class="border-t border-slate-200 bg-slate-50 text-xs font-bold">
                                <TableCell colspan="2" class="border-r border-slate-300">Total Tangki</TableCell>
                                <TableCell v-for="column in columns" :key="`${column.key}-tangki-total`" class="border-r border-slate-300 text-center text-red-600">
                                    <div class="flex flex-col items-center leading-tight">
                                        <span>{{ totals.Tangki?.[column.key] ?? 0 }}</span>
                                        <span class="text-[10px] text-muted-foreground">{{ totals.Tangki?.output_casting ? (((totals.Tangki?.[column.key] ?? 0) / totals.Tangki.output_casting) * 100).toFixed(2).replace('.', ',') + '%' : '0,00%' }}</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
