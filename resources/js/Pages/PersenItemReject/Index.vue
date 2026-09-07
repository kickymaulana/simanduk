<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { IconChartBar } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

type Row = {
    item_reject: string;
    buang: number;
    buang_persen: string;
    in_proses: number;
    in_proses_persen: string;
    total: number;
};

const props = defineProps<{
    rows: Row[];
    summary: { buang: number; in_proses: number; total: number };
    filter: { mulai: string; sampai: string; cut_off_jam: number };
}>();

const mulai = ref(props.filter.mulai);
const sampai = ref(props.filter.sampai);
let timeout: ReturnType<typeof setTimeout>;

watch([mulai, sampai], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => router.get(route("persen.item.reject"), {
        mulai: mulai.value,
        sampai: sampai.value,
    }, { preserveState: true, replace: true }), 250);
});
</script>

<template>
    <Head title="Persen Item Reject" />
    <div class="flex flex-col gap-4 p-4 pt-4 md:p-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-black uppercase italic tracking-tight text-slate-900 dark:text-slate-100">Persen Item Reject</h1>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">QC Visual & Dimensi · Cut-off {{ filter.cut_off_jam }}:00</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <label class="font-bold">Mulai</label>
                <input v-model="mulai" type="date" class="h-9 rounded-md border bg-background px-3" />
                <label class="font-bold">Sampai</label>
                <input v-model="sampai" type="date" class="h-9 rounded-md border bg-background px-3" />
            </div>
        </div>

        <Card class="border-none shadow-sm">
            <CardHeader class="pb-3">
                <CardTitle class="flex items-center gap-2 text-xs font-black uppercase tracking-widest"><IconChartBar class="size-4 text-primary" /> Item Reject per Status</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-100 text-xs font-bold text-slate-700">
                                <TableHead>Item Reject</TableHead>
                                <TableHead class="text-center">Buang</TableHead>
                                <TableHead class="text-center">% Buang</TableHead>
                                <TableHead class="text-center">In Proses</TableHead>
                                <TableHead class="text-center">% In Proses</TableHead>
                                <TableHead class="text-center">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="rows.length === 0"><TableCell colspan="6" class="h-24 text-center text-muted-foreground">Tidak ada data reject.</TableCell></TableRow>
                            <TableRow v-for="row in rows" :key="row.item_reject">
                                <TableCell class="font-semibold">{{ row.item_reject }}</TableCell>
                                <TableCell class="text-center font-mono">{{ row.buang }}</TableCell>
                                <TableCell class="text-center font-mono">{{ row.buang_persen }}</TableCell>
                                <TableCell class="text-center font-mono">{{ row.in_proses }}</TableCell>
                                <TableCell class="text-center font-mono">{{ row.in_proses_persen }}</TableCell>
                                <TableCell class="text-center font-mono font-bold">{{ row.total }}</TableCell>
                            </TableRow>
                        </TableBody>
                        <tfoot>
                            <TableRow class="border-t-2 bg-slate-50 text-xs font-bold">
                                <TableCell>Total</TableCell>
                                <TableCell class="text-center text-blue-700">{{ summary.buang }}</TableCell>
                                <TableCell class="text-center text-blue-700">100%</TableCell>
                                <TableCell class="text-center text-blue-700">{{ summary.in_proses }}</TableCell>
                                <TableCell class="text-center text-blue-700">100%</TableCell>
                                <TableCell class="text-center text-blue-700">{{ summary.total }}</TableCell>
                            </TableRow>
                        </tfoot>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
