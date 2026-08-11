<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { IconSearch, IconQrcode, IconArrowRight } from "@tabler/icons-vue";

const props = defineProps<{
    proses: Array<{
        id: number;
        proses: string;
        urutan: number;
        departemen: string;
        jumlah_belum_discan: number;
    }>;
    filters: {
        jenis?: string;
        date_start?: string;
        date_end?: string;
    };
}>();

defineOptions({ layout: AuthenticatedLayout });

const jenis = ref(props.filters.jenis || "all");
const dateStart = ref(props.filters.date_start || "");
const dateEnd = ref(props.filters.date_end || "");

const applyFilters = () => {
    router.get(route("qr.belum.discan"), {
        jenis: jenis.value === "all" ? undefined : jenis.value || undefined,
        date_start: dateStart.value || undefined,
        date_end: dateEnd.value || undefined,
    }, { preserveState: true, replace: true });
};

let timeout: ReturnType<typeof setTimeout>;
watch([dateStart, dateEnd], () => {
    clearTimeout(timeout);
    timeout = setTimeout(applyFilters, 400);
});
watch(jenis, () => applyFilters());

const totalBelum = props.proses.reduce((acc, p) => acc + p.jumlah_belum_discan, 0);
</script>

<template>
    <Head title="QR Belum Discan" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6">
                <div>
                    <CardTitle class="text-xl font-bold flex items-center gap-2">
                        <IconQrcode class="size-6 text-primary" />
                        QR Belum Discan per Proses
                    </CardTitle>
                    <p class="text-sm text-muted-foreground">
                        Produk yang telah selesai di proses sebelumnya tapi belum tercatat di proses ini —
                        kemungkinan kandidat QR lepas/rusak. Total: {{ totalBelum }}
                    </p>
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <div class="space-y-1">
                        <Label class="text-[10px] font-bold uppercase text-muted-foreground">Jenis</Label>
                        <Select v-model="jenis">
                            <SelectTrigger class="w-32">
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua</SelectItem>
                                <SelectItem value="Body">Body</SelectItem>
                                <SelectItem value="Tangki">Tangki</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-[10px] font-bold uppercase text-muted-foreground">Dari</Label>
                        <Input v-model="dateStart" type="date" class="w-40" />
                    </div>
                    <div class="space-y-1">
                        <Label class="text-[10px] font-bold uppercase text-muted-foreground">Sampai</Label>
                        <Input v-model="dateEnd" type="date" class="w-40" />
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-16">Urutan</TableHead>
                                <TableHead>Proses</TableHead>
                                <TableHead>Departemen</TableHead>
                                <TableHead class="text-center">QR Belum Discan</TableHead>
                                <TableHead class="text-right">Detail</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in proses" :key="p.id" class="hover:bg-muted/30">
                                <TableCell class="font-mono font-bold text-muted-foreground">{{ p.urutan }}</TableCell>
                                <TableCell class="font-semibold">{{ p.proses }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ p.departemen }}</TableCell>
                                <TableCell class="text-center">
                                    <span
                                        :class="p.jumlah_belum_discan > 0
                                            ? 'bg-amber-100 text-amber-700 border border-amber-200'
                                            : 'bg-muted text-muted-foreground border border-slate-200'"
                                        class="inline-block min-w-10 px-2 py-1 rounded-full text-sm font-bold"
                                    >
                                        {{ p.jumlah_belum_discan }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button as-child variant="ghost" size="sm" class="gap-1 text-primary">
                                        <Link :href="route('qr.belum.discan.show', p.id)">
                                            Lihat <IconArrowRight class="size-3.5" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>