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
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { IconTrash, IconCalendar } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    rows: Array<Record<string, number>>;
    prosesList: string[];
    total: Record<string, number>;
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
</script>

<template>
    <Head title="Laporan Produk Buang" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 uppercase italic">
                    Laporan Produk Buang
                </h1>
                <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-[0.2em]">
                    Rekapitulasi Produk Buang per Proses per Hari
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
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
            <CardHeader class="pb-2">
                <div class="flex items-center gap-2">
                    <IconCalendar class="size-4 text-primary" />
                    <CardTitle class="text-xs font-black uppercase tracking-widest">
                        Periode: {{ filter.daftar_bulan[filter.bulan] }} {{ filter.tahun }}
                    </CardTitle>
                </div>
            </CardHeader>
        </Card>

        <Card class="border-none shadow-sm overflow-hidden">
            <CardHeader class="pb-2">
                <div class="flex items-center gap-2">
                    <IconTrash class="size-4 text-red-500" />
                    <CardTitle class="text-xs font-black uppercase tracking-widest">Produk Buang per Proses</CardTitle>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-100 text-slate-700 text-xs font-bold border-b border-slate-300">
                                <TableHead class="w-16 text-center border-r border-slate-300">Tgl</TableHead>
                                <TableHead v-for="p in prosesList" :key="p" class="w-28 text-center border-r border-slate-300">
                                    {{ p }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in rows" :key="row.tanggal" class="border-t border-slate-200">
                                <TableCell class="w-16 text-center font-bold text-sm border-r border-slate-200">
                                    {{ row.tanggal }}
                                </TableCell>
                                <TableCell v-for="p in prosesList" :key="p" class="w-28 text-center border-r border-slate-200">
                                    <span class="text-xs font-mono font-medium">{{ row[p] ?? 0 }}</span>
                                </TableCell>
                            </TableRow>
                            <TableRow class="font-bold text-xs border-t-2 border-slate-300 bg-slate-50">
                                <TableCell class="w-16 border-r border-slate-300">Total</TableCell>
                                <TableCell v-for="p in prosesList" :key="p + '-s'" class="text-center text-red-600 border-r border-slate-300">
                                    {{ total[p] ?? 0 }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
