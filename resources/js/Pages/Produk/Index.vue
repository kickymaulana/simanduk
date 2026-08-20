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
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    IconBox,
    IconSearch,
    IconX,
    IconQrcode,
    IconShoppingCart,
    IconEye,
    IconFlask,
} from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    produks: {
        data: Array<{
            id: number;
            qrcode: string;
            nama: string;
            jenis: string;
            status_akhir: string;
            sudah_scan: string;
            is_sample: boolean;
            kode_sampel: string | null;
            proses?: {
                proses: string;
            };
            created_at: string;
        }>;
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search: string;
    };
}>();

const search = ref(props.filters.search || "");

// Debounce search untuk scan barcode
let timeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route("produk.index"),
            { search: value },
            { preserveState: true, replace: true }
        );
    }, 500);
});

const clearSearch = () => { search.value = ""; };

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Data Produk" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6">
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconBox class="size-6 text-primary" />
                    Data Produk (Master)
                </CardTitle>

                <div class="relative w-full md:w-96">
                    <IconSearch class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Scan QR Produk atau cari proses..."
                        class="pl-10 pr-10"
                    />
                    <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <IconX class="size-4" />
                    </button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead>QR Code</TableHead>
                                <TableHead>Jenis</TableHead>
                                <TableHead>Sampel</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Proses</TableHead>
                                <TableHead>Scan</TableHead>
                                <TableHead>Tanggal Input</TableHead>
                                <TableHead>Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="produks.data.length === 0">
                                <TableCell colspan="8" class="h-24 text-center text-muted-foreground">
                                    Data produk tidak ditemukan.
                                </TableCell>
                            </TableRow>

                            <TableRow v-for="item in produks.data" :key="item.id" class="hover:bg-muted/30">
                                <TableCell class="font-mono font-bold text-primary">
                                    <div class="flex items-center gap-2">
                                        <IconQrcode class="size-4 text-muted-foreground" />
                                        {{ item.qrcode }}
                                    </div>
                                </TableCell>
                                <TableCell>{{ item.jenis }}</TableCell>
                                <TableCell>
                                    <Badge v-if="item.is_sample" variant="secondary" class="font-black uppercase italic bg-amber-100 text-amber-700 hover:bg-amber-100">
                                        <IconFlask class="size-3" /> {{ item.kode_sampel }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground text-xs italic">-</span>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="item.status_akhir === 'OK' ? 'bg-green-500' : 'bg-red-500'">
                                        {{ item.status_akhir }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div v-if="item.proses" class="flex flex-col gap-1">
                                        <div class="flex items-center gap-1 text-sm font-bold">
                                            <IconShoppingCart class="size-3" />
                                            {{ item.proses.proses }}
                                        </div>
                                        <span class="text-xs text-muted-foreground">{{ item.proses?.proses ?? '-' }}</span>
                                    </div>
                                    <span v-else class="text-muted-foreground text-xs italic">Belum Ada Proses</span>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" :class="item.sudah_scan === 'Sudah' ? 'text-green-600 border-green-200' : 'text-orange-600 border-orange-200'">
                                        {{ item.sudah_scan }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">
                                    {{ item.created_at }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button variant="ghost" class="size-10" as-child>
                                        <Link :href="route('produk.show', item.id)">
                                            <IconEye class="size-5 text-primary" />
                                        </Link>
                                    </Button>
                                </TableCell>

                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="produks.total > 0" class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-muted-foreground">
                        Data <span class="font-medium text-foreground">{{ produks.from }}</span> - {{ produks.to }} dari {{ produks.total }}
                    </div>
                    <div class="flex items-center gap-1">
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
                </div>
            </CardContent>
        </Card>
    </div>
</template>
