<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Head, Link, router } from "@inertiajs/vue3";
import { IconPackage, IconSearch } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

interface Packing {
    id: number;
    qrcode_kardus: string;
    created_at: string;
    jenis: string;
    kualitas: string | null;
    merk: string | null;
    warna: string | null;
    gudang: string | null;
    operator: string | null;
    total_aksesori: number;
    accessories: Array<{
        part_number: string | null;
        description: string | null;
        quantity: number;
    }>;
}

const props = defineProps<{
    packings: {
        data: Packing[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { mulai: string; sampai: string; jenis: string | null; search: string };
}>();

const mulai = ref(props.filters.mulai);
const sampai = ref(props.filters.sampai);
const jenis = ref(props.filters.jenis || "all");
const search = ref(props.filters.search || "");
let timeout: ReturnType<typeof setTimeout>;

const load = () => router.get(route("laporan.scan.packing"), {
    mulai: mulai.value,
    sampai: sampai.value,
    jenis: jenis.value === "all" ? undefined : jenis.value,
    search: search.value || undefined,
}, { preserveState: true, replace: true });

watch([mulai, sampai, jenis], load);
watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(load, 400);
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
    <Head title="Laporan Scan Packing" />
    <div class="space-y-4 p-4 md:p-8">
        <Card>
            <CardHeader class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <CardTitle class="flex items-center gap-2">
                    <IconPackage class="size-6 text-primary" />
                    Laporan Scan Packing
                </CardTitle>
                <div class="grid w-full gap-2 sm:grid-cols-2 md:w-auto md:grid-cols-4">
                    <Input v-model="mulai" type="date" aria-label="Tanggal mulai" />
                    <Input v-model="sampai" type="date" aria-label="Tanggal selesai" />
                    <select v-model="jenis" class="rounded-md border px-3 py-2 text-sm">
                        <option value="all">Semua Jenis</option>
                        <option value="Body">Body</option>
                        <option value="Tangki">Tangki</option>
                    </select>
                    <div class="relative">
                        <IconSearch class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" class="pl-9" placeholder="Cari QR kardus" />
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-[1200px] text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="p-3 text-left">Waktu Packing</th>
                                <th class="p-3 text-left">QR Kardus</th>
                                <th class="p-3 text-left">Jenis</th>
                                <th class="p-3 text-left">Grade</th>
                                <th class="p-3 text-left">Merk</th>
                                <th class="p-3 text-left">Warna</th>
                                <th class="p-3 text-left">Gudang</th>
                                <th class="p-3 text-left">Operator</th>
                                <th class="p-3 text-right">Total Aksesori</th>
                                <th class="p-3 text-left">Detail Aksesori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in packings.data" :key="item.id" class="border-t align-top">
                                <td class="whitespace-nowrap p-3">{{ formatWaktu(item.created_at) }}</td>
                                <td class="whitespace-nowrap p-3 font-mono font-bold">{{ item.qrcode_kardus }}</td>
                                <td class="p-3">{{ item.jenis }}</td>
                                <td class="p-3">{{ item.kualitas || "-" }}</td>
                                <td class="p-3">{{ item.merk || "-" }}</td>
                                <td class="p-3">{{ item.warna || "-" }}</td>
                                <td class="p-3">{{ item.gudang || "-" }}</td>
                                <td class="p-3">{{ item.operator || "-" }}</td>
                                <td class="p-3 text-right font-semibold">{{ item.total_aksesori }}</td>
                                <td class="p-3">
                                    <div v-for="aksesori in item.accessories" :key="`${aksesori.part_number}-${aksesori.quantity}`">
                                        {{ aksesori.part_number }} - {{ aksesori.description }} ({{ aksesori.quantity }})
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!packings.data.length">
                                <td colspan="10" class="p-8 text-center text-muted-foreground">Tidak ada data packing.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col items-center justify-between gap-3 md:flex-row">
                    <p class="text-xs text-muted-foreground">
                        Menampilkan {{ packings.from ?? 0 }} - {{ packings.to ?? 0 }} dari {{ packings.total }} packing
                    </p>
                    <nav class="flex items-center gap-1">
                        <template v-for="(link, index) in packings.links" :key="index">
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
