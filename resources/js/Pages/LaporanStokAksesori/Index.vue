<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Head, Link, router } from "@inertiajs/vue3";
import { IconBoxSeam } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

interface Aksesori {
    id: number;
    part_number: string;
    description: string;
    stok: number;
    is_active: boolean;
}

interface Pergerakan {
    id: number;
    tipe: "MASUK" | "KELUAR_PACKING";
    quantity: number;
    stok_sebelum: number;
    stok_sesudah: number;
    keterangan: string | null;
    created_at: string;
    aksesori: Pick<Aksesori, "part_number" | "description">;
    packing: { qrcode_kardus: string } | null;
    user: { name: string };
}

const props = defineProps<{
    aksesoris: Aksesori[];
    riwayat: {
        data: Pergerakan[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { mulai: string; sampai: string; tipe: string | null; aksesori_id: number | null };
}>();

const mulai = ref(props.filters.mulai);
const sampai = ref(props.filters.sampai);
const tipe = ref(props.filters.tipe || "all");
const aksesoriId = ref(props.filters.aksesori_id ? String(props.filters.aksesori_id) : "all");

watch([mulai, sampai, tipe, aksesoriId], () => {
    router.get(route("laporan.stok.aksesori"), {
        mulai: mulai.value,
        sampai: sampai.value,
        tipe: tipe.value === "all" ? undefined : tipe.value,
        aksesori_id: aksesoriId.value === "all" ? undefined : aksesoriId.value,
    }, { preserveState: true, replace: true });
});

const formatWaktu = (value: string) => new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
}).format(new Date(value));

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Laporan Stok Aksesori" />
    <div class="space-y-6 p-4 md:p-8">
        <Card>
            <CardHeader class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <CardTitle class="flex items-center gap-2">
                    <IconBoxSeam class="size-6 text-primary" />
                    Laporan Stok Aksesori
                </CardTitle>
                <div class="grid w-full gap-2 sm:grid-cols-2 md:w-auto md:grid-cols-4">
                    <Input v-model="mulai" type="date" aria-label="Tanggal mulai" />
                    <Input v-model="sampai" type="date" aria-label="Tanggal selesai" />
                    <select v-model="tipe" class="rounded-md border px-3 py-2 text-sm">
                        <option value="all">Semua Tipe</option>
                        <option value="MASUK">MASUK</option>
                        <option value="KELUAR_PACKING">KELUAR_PACKING</option>
                    </select>
                    <select v-model="aksesoriId" class="rounded-md border px-3 py-2 text-sm">
                        <option value="all">Semua Aksesori</option>
                        <option v-for="item in aksesoris" :key="item.id" :value="String(item.id)">
                            {{ item.part_number }}
                        </option>
                    </select>
                </div>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="p-3 text-left">Part Number</th>
                                <th class="p-3 text-left">Description</th>
                                <th class="p-3 text-right">Saldo</th>
                                <th class="p-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in aksesoris" :key="item.id" class="border-t">
                                <td class="p-3 font-medium">{{ item.part_number }}</td>
                                <td class="p-3">{{ item.description }}</td>
                                <td class="p-3 text-right text-lg font-semibold">{{ item.stok }}</td>
                                <td class="p-3">{{ item.is_active ? "Aktif" : "Nonaktif" }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>Riwayat Pergerakan</CardTitle></CardHeader>
            <CardContent>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-[1100px] text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="p-3 text-left">Waktu</th>
                                <th class="p-3 text-left">Aksesori</th>
                                <th class="p-3 text-left">Tipe</th>
                                <th class="p-3 text-right">Quantity</th>
                                <th class="p-3 text-right">Stok Sebelum</th>
                                <th class="p-3 text-right">Stok Sesudah</th>
                                <th class="p-3 text-left">Keterangan</th>
                                <th class="p-3 text-left">Operator</th>
                                <th class="p-3 text-left">QR Packing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in riwayat.data" :key="item.id" class="border-t">
                                <td class="whitespace-nowrap p-3">{{ formatWaktu(item.created_at) }}</td>
                                <td class="p-3">
                                    <div class="font-medium">{{ item.aksesori.part_number }}</div>
                                    <div class="text-muted-foreground">{{ item.aksesori.description }}</div>
                                </td>
                                <td class="whitespace-nowrap p-3">{{ item.tipe }}</td>
                                <td class="p-3 text-right">{{ item.quantity }}</td>
                                <td class="p-3 text-right">{{ item.stok_sebelum }}</td>
                                <td class="p-3 text-right font-medium">{{ item.stok_sesudah }}</td>
                                <td class="p-3">{{ item.keterangan || "-" }}</td>
                                <td class="whitespace-nowrap p-3">{{ item.user.name }}</td>
                                <td class="whitespace-nowrap p-3 font-mono">{{ item.packing?.qrcode_kardus || "-" }}</td>
                            </tr>
                            <tr v-if="!riwayat.data.length">
                                <td colspan="9" class="p-8 text-center text-muted-foreground">Tidak ada riwayat pergerakan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col items-center justify-between gap-3 md:flex-row">
                    <p class="text-xs text-muted-foreground">
                        Menampilkan {{ riwayat.from ?? 0 }} - {{ riwayat.to ?? 0 }} dari {{ riwayat.total }} pergerakan
                    </p>
                    <nav class="flex items-center gap-1">
                        <template v-for="(link, index) in riwayat.links" :key="index">
                            <Button v-if="!link.url" variant="outline" size="sm" disabled v-html="cleanLabel(link.label)" />
                            <Button v-else as-child variant="outline" size="sm" :class="{ 'bg-primary text-primary-foreground': link.active }">
                                <Link :href="link.url" preserve-scroll v-html="cleanLabel(link.label)" />
                            </Button>
                        </template>
                    </nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
