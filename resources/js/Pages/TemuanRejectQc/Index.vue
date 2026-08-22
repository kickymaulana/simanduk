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
import {
    IconSearch,
    IconX,
    IconClipboardList,
    IconEye,
} from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    logs: {
        data: Array<{
            id: number;
            id_pengerjaan_cacat: number;
            pengerjaan_produk: {
                produk: {
                    qrcode: string;
                    jenis: string;
                    kualitas: { kualitas: string } | null;
                    warna: { warna: string } | null;
                };
            };
            master_cacat: { cacat: string };
            user_scan_id: number;
            created_at: string;
        }>;
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: { search: string };
}>();

const search = ref(props.filters.search || "");
let timeout: any;

watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route("temuan.reject.qc"),
            { search: value },
            { preserveState: true, replace: true },
        );
    }, 500);
});

const clearSearch = () => {
    search.value = "";
};

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <Head title="Temuan Reject QC" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader
                class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6"
            >
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconClipboardList class="size-6 text-primary" />
                    Temuan Reject QC
                </CardTitle>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-72">
                        <IconSearch
                            class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            placeholder="Cari produk atau jenis cacat..."
                            class="pl-10 pr-10"
                        />
                        <button
                            v-if="search"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <IconX class="size-4" />
                        </button>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-16">ID</TableHead>
                                <TableHead>Waktu Scan</TableHead>
                                <TableHead>Produk</TableHead>
                                <TableHead>Jenis</TableHead>
                                <TableHead>Kualitas</TableHead>
                                <TableHead>Jenis Cacat</TableHead>
                                <TableHead>Penemu</TableHead>
                                <TableHead>Proses</TableHead>
                                <TableHead>Penanggung Jawab</TableHead>
                                <TableHead>Proses Png Jawab</TableHead>
                                <TableHead class="text-right">Detail</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="logs.data.length === 0">
                                <TableCell
                                    colspan="11"
                                    class="h-24 text-center text-muted-foreground italic"
                                >
                                    Tidak ada riwayat temuan reject.
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="item in logs.data"
                                :key="item.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell class="font-medium text-muted-foreground">
                                    #{{ item.id_pengerjaan_cacat }}
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ item.created_at }}
                                </TableCell>
                                <TableCell class="font-semibold">
                                    {{ item.pengerjaan_produk?.produk?.qrcode || 'N/A' }}
                                </TableCell>
                                <TableCell class="text-sm">
                                    <Badge variant="outline" :class="item.pengerjaan_produk?.produk?.jenis === 'Tangki' ? 'text-orange-600 border-orange-200' : 'text-blue-600 border-blue-200'">
                                        {{ item.pengerjaan_produk?.produk?.jenis || '-' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ item.pengerjaan_produk?.produk?.kualitas?.kualitas || '-' }}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="destructive" class="font-medium">
                                        {{ item.cacat?.cacat }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ item.user_scan.name }}
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ item.proses_scan.proses }}
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ item.user_pj.name }}
                                </TableCell>
                                <TableCell class="text-sm">
                                    {{ item.proses_pj.proses }}
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

                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mt-6">
                    <p class="text-xs text-muted-foreground italic font-medium">
                        Menampilkan {{ logs.from ?? 0 }} - {{ logs.to ?? 0 }} dari {{ logs.total }} temuan
                    </p>

                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in logs.links" :key="k">
                            <Button
                                v-if="link.url === null"
                                variant="outline"
                                size="sm"
                                disabled
                                class="opacity-50 text-xs px-3 h-8"
                                v-html="cleanLabel(link.label)"
                            />
                            <Button
                                v-else
                                as-child
                                variant="outline"
                                size="sm"
                                class="text-xs px-3 h-8"
                                :class="{ 'bg-primary text-primary-foreground': link.active }"
                            >
                                <Link :href="link.url" v-html="cleanLabel(link.label)" />
                            </Button>
                        </template>
                    </nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
