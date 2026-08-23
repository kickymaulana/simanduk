<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
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
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { IconSearch, IconX, IconTractor } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    produks: {
        data: Array<any>;
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: { search: string; jenis: string | null };
}>();

const search = ref(props.filters.search || "");
const jenis = ref(props.filters.jenis || "all");
let timeout: any;

watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => go(), 500);
});
watch(jenis, () => go());

const go = () => {
    router.get(route("laporan.trace.mesin"), {
        search: search.value || undefined,
        jenis: jenis.value === "all" ? undefined : jenis.value || undefined,
    }, { preserveState: true, replace: true });
};

const clearSearch = () => { search.value = ""; };

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Laporan Trace Mesin" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 uppercase italic">
                    Laporan Trace Mesin
                </h1>
                <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-[0.2em]">
                    Penelusuran Mesin & Parameter per Proses per Produk
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="jenis">
                    <SelectTrigger class="w-32 h-9 text-xs">
                        <SelectValue placeholder="Jenis" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua</SelectItem>
                        <SelectItem value="Body">Body</SelectItem>
                        <SelectItem value="Tangki">Tangki</SelectItem>
                    </SelectContent>
                </Select>
                <div class="relative">
                    <IconSearch class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari QR produk..." class="pl-10 pr-10 w-64" />
                    <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                        <IconX class="size-4" />
                    </button>
                </div>
            </div>
        </div>

        <Card class="border-none shadow-sm overflow-hidden">
            <CardHeader class="pb-2">
                <div class="flex items-center gap-2">
                    <IconTractor class="size-4 text-primary" />
                    <CardTitle class="text-xs font-black uppercase tracking-widest">
                        Mesin & Parameter per Proses ({{ produks.total }} produk)
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-100 text-slate-700 text-xs font-bold border-b border-slate-300">
                                <TableHead class="border-r border-slate-300">QR Code</TableHead>
                                <TableHead class="w-32 border-r border-slate-300">Tgl QC Visual</TableHead>
                                <TableHead class="w-28 border-r border-slate-300">Grade</TableHead>
                                <TableHead class="border-r border-slate-300">Item Reject</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">No. Mesin</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">No. Mold</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Asal Slip</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Shift</TableHead>
                                <TableHead class="w-32 border-r border-slate-300">Solar (Waktu)</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Spray No. Mesin</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Spray Warna</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Visco</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Oven Susun</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Oven Bongkar</TableHead>
                                <TableHead class="w-24 border-r border-slate-300">Temp</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="produks.data.length === 0">
                                <TableCell colspan="15" class="h-24 text-center text-muted-foreground italic">
                                    Tidak ada produk.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="item in produks.data" :key="item.id" class="border-t border-slate-200">
                                <TableCell class="font-mono font-bold text-primary border-r border-slate-200">{{ item.qrcode }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.tgl_qc_visual || '-' }}</TableCell>
                                <TableCell class="border-r border-slate-200">
                                    <span class="text-xs font-semibold">{{ item.grade || '-' }}</span>
                                </TableCell>
                                <TableCell class="border-r border-slate-200">
                                    <div v-if="item.item_reject.length" class="flex flex-wrap gap-1 max-w-[180px]">
                                        <Badge v-for="r in item.item_reject" :key="r" variant="destructive" class="text-[8px] font-bold uppercase px-2 py-0">
                                            {{ r }}
                                        </Badge>
                                    </div>
                                    <span v-else class="text-xs text-green-600 font-semibold">OK</span>
                                </TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.casting.mesin || '-' }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.casting.mould || '-' }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.casting.slip || '-' }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.casting.shift || '-' }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.solar.waktu || '-' }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.spray.mesin || '-' }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.spray.warna || '-' }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground italic border-r border-slate-200">{{ item.spray.visco || '-' }}</TableCell>
                                <TableCell class="text-xs border-r border-slate-200">{{ item.oven.susun || '-' }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground italic border-r border-slate-200">{{ item.oven.bongkar || '-' }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground italic border-r border-slate-200">{{ item.oven.temp || '-' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="produks.total > 0" class="flex flex-col md:flex-row items-center justify-between gap-4 p-4">
                    <p class="text-xs text-muted-foreground italic font-medium">
                        Menampilkan {{ produks.from ?? 0 }} - {{ produks.to ?? 0 }} dari {{ produks.total }} produk
                    </p>
                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in produks.links" :key="k">
                            <Button v-if="link.url === null" variant="outline" size="sm" disabled class="opacity-50 text-xs px-3 h-8" v-html="cleanLabel(link.label)" />
                            <Button v-else as-child variant="outline" size="sm" class="text-xs px-3 h-8" :class="{ 'bg-primary text-primary-foreground': link.active }">
                                <Link :href="link.url" preserve-scroll v-html="cleanLabel(link.label)" />
                            </Button>
                        </template>
                    </nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
