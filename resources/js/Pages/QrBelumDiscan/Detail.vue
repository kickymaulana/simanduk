<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { IconArrowLeft, IconSearch, IconQrcode, IconX } from "@tabler/icons-vue";

const props = defineProps<{
    proses: any;
    produks: {
        data: Array<any>;
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        jenis?: string;
        date_start?: string;
        date_end?: string;
        search?: string;
    };
}>();

defineOptions({ layout: AuthenticatedLayout });

const jenis = ref(props.filters.jenis || "all");
const dateStart = ref(props.filters.date_start || "");
const dateEnd = ref(props.filters.date_end || "");
const search = ref(props.filters.search || "");

const go = (overrides = {}) => {
    router.get(route("qr.belum.discan.show", props.proses.id), {
        jenis: jenis.value === "all" ? undefined : jenis.value || undefined,
        date_start: dateStart.value || undefined,
        date_end: dateEnd.value || undefined,
        search: search.value || undefined,
        ...overrides,
    }, { preserveState: true, replace: true });
};

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};

let timeout: ReturnType<typeof setTimeout>;
watch([dateStart, dateEnd], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => go(), 400);
});
watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => go(), 500);
});
watch(jenis, () => go());
</script>

<template>
    <Head :title="'QR Belum Discan — ' + proses.proses" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <div class="flex items-center gap-3">
            <Button variant="outline" size="icon" as-child class="rounded-full shadow-sm">
                <Link :href="route('qr.belum.discan')"><IconArrowLeft class="size-4" /></Link>
            </Button>
            <div>
                <h2 class="text-2xl font-bold tracking-tight">
                    QR Belum Discan — {{ proses.proses }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ proses.departemen?.departemen ?? '-' }} · Urutan {{ proses.urutan }}
                </p>
            </div>
        </div>

        <Card class="border-none shadow-sm">
            <CardHeader class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-4">
                <CardTitle class="text-sm font-bold flex items-center gap-2 text-muted-foreground">
                    <IconQrcode class="size-4 text-primary" />
                    Daftar kandidat ({{ produks.total }} item)
                </CardTitle>

                <div class="flex flex-wrap items-end gap-2">
                    <div class="relative">
                        <IconSearch class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Cari QR..." class="pl-9 w-44" />
                    </div>
                    <div class="space-y-1">
                        <Label class="text-[10px] font-bold uppercase text-muted-foreground">Jenis</Label>
                        <Select v-model="jenis">
                            <SelectTrigger class="w-28">
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
                                <TableHead>QR Code</TableHead>
                                <TableHead>Jenis</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Mesin</TableHead>
                                <TableHead>Mould</TableHead>
                                <TableHead>Asal Slip</TableHead>
                                <TableHead>Update Terakhir</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="produks.data.length === 0">
                                <TableCell colspan="7" class="h-24 text-center text-muted-foreground italic">
                                    Tidak ada produk yang belum discan pada rentang ini.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="item in produks.data" :key="item.id" class="hover:bg-muted/30">
                                <TableCell class="font-mono font-bold text-primary">{{ item.qrcode }}</TableCell>
                                <TableCell>{{ item.jenis }}</TableCell>
                                <TableCell>
                                    <Badge :class="item.status_akhir === 'OK' ? 'bg-green-500' : 'bg-red-500'">
                                        {{ item.status_akhir }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ item.nomor_mesin ?? '-' }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ item.nomor_mould ?? '-' }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ item.asal_slip ?? '-' }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ item.updated_at }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="produks.links.length > 3" class="flex items-center justify-center gap-1 pt-4">
                    <template v-for="(link, k) in produks.links" :key="k">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="cleanLabel(link.label)"
                            class="px-3 py-1 text-xs border rounded-md"
                            :class="{ 'bg-primary text-white': link.active }"
                            preserve-scroll
                        />
                        <span v-else v-html="cleanLabel(link.label)" class="px-3 py-1 text-xs border rounded-md opacity-50" />
                    </template>
                </div>
            </CardContent>
        </Card>
    </div>
</template>