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
    IconPlus,
    IconPencil,
    IconSearch,
    IconX,
    IconGavel,
} from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    aturanPenolakans: {
        data: Array<any>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    filters: { search: string; jenis: string; proses_pemeriksa: number | null; proses_buang: number | null; proses_toleransi: number | null };
    proses: { id: number; proses: string }[];
    counts: { total: number; body: number; tangki: number; pemeriksa: number; buang: number; toleransi: number };
}>();

const search = ref(props.filters.search || "");
const jenis = ref(props.filters.jenis || "semua");
const prosesPemeriksa = ref(String(props.filters.proses_pemeriksa || ""));
const prosesBuang = ref(String(props.filters.proses_buang || ""));
const prosesToleransi = ref(String(props.filters.proses_toleransi || ""));
let timeout: any;

watch([search, jenis, prosesPemeriksa, prosesBuang, prosesToleransi], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route("aturanpenolakans.index"),
            { search: search.value, jenis: jenis.value, proses_pemeriksa: prosesPemeriksa.value, proses_buang: prosesBuang.value, proses_toleransi: prosesToleransi.value },
            { preserveState: true, replace: true },
        );
    }, 500);
});

const clearSearch = () => {
    search.value = "";
};

// Helper Formatter agar label pagination rapi
const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Aturan Penolakan" />
    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader
                class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6"
            >
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconGavel class="size-6 text-primary" />
                    Aturan Penolakan Cacat
                </CardTitle>
                <div class="grid w-full grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3 xl:flex xl:w-auto xl:flex-wrap xl:items-center">
                    <div class="relative min-w-0 xl:w-72">
                        <IconSearch
                            class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            placeholder="Cari nama cacat..."
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
                    <select v-model="jenis" class="h-10 min-w-0 w-full rounded-md border bg-background px-3 text-sm xl:w-auto"><option value="semua">Semua Jenis</option><option value="Body">Body</option><option value="Tangki">Tangki</option></select>
                    <select v-model="prosesPemeriksa" class="h-10 min-w-0 w-full rounded-md border bg-background px-3 text-sm xl:w-auto"><option value="">Semua Pemeriksa</option><option v-for="item in proses" :key="item.id" :value="String(item.id)">{{ item.proses }}</option></select>
                    <select v-model="prosesToleransi" class="h-10 min-w-0 w-full rounded-md border bg-background px-3 text-sm xl:w-auto"><option value="">Semua Toleransi</option><option v-for="item in proses" :key="item.id" :value="String(item.id)">{{ item.proses }}</option></select>
                    <select v-model="prosesBuang" class="h-10 min-w-0 w-full rounded-md border bg-background px-3 text-sm xl:w-auto"><option value="">Semua Buang</option><option v-for="item in proses" :key="item.id" :value="String(item.id)">{{ item.proses }}</option></select>
                    <Button as-child class="bg-primary hover:bg-primary/90">
                        <Link :href="route('aturanpenolakans.create')">
                            <IconPlus class="mr-2 size-4" />Tambah
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="mb-4 grid grid-cols-2 gap-2 md:grid-cols-5">
                    <div class="rounded-md border p-3 text-center"><div class="text-xs text-muted-foreground">Total</div><div class="text-xl font-bold">{{ counts.total }}</div></div>
                    <div class="rounded-md border p-3 text-center"><div class="text-xs text-muted-foreground">Body</div><div class="text-xl font-bold">{{ counts.body }}</div></div>
                    <div class="rounded-md border p-3 text-center"><div class="text-xs text-muted-foreground">Tangki</div><div class="text-xl font-bold">{{ counts.tangki }}</div></div>
                    <div class="rounded-md border p-3 text-center"><div class="text-xs text-muted-foreground">Dep. Pemeriksa</div><div class="text-xl font-bold">{{ counts.pemeriksa }}</div></div>
                    <div class="rounded-md border p-3 text-center"><div class="text-xs text-muted-foreground">Dep. Buang</div><div class="text-xl font-bold">{{ counts.buang }}</div></div>
                    <div class="rounded-md border p-3 text-center"><div class="text-xs text-muted-foreground">Dep. Toleransi</div><div class="text-xl font-bold">{{ counts.toleransi }}</div></div>
                </div>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead>Jenis Cacat</TableHead>
                                <TableHead>Dep. Toleransi</TableHead>
                                <TableHead>Dep. Buang</TableHead>
                                <TableHead>Dep. Pemeriksa</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="aturanPenolakans.data.length === 0">
                                <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                    Data tidak ditemukan.
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="item in aturanPenolakans.data"
                                :key="item.id"
                            >
                                <TableCell class="font-bold text-primary">
                                    <div class="flex items-center gap-2">
                                        {{ item.cacat.cacat }}
                                        <Badge variant="outline">{{ item.cacat.jenis }}</Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ item.proses_toleransi.proses }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{{ item.proses_buang.proses }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge class="bg-lime-500 text-black hover:bg-lime-600">
                                        {{ item.proses_pemeriksa.proses }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('aturanpenolakans.edit', item.id)">
                                            <IconPencil class="size-4" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mt-6">
                    <p class="text-xs text-muted-foreground italic">
                        Menampilkan {{ aturanPenolakans.from ?? 0 }} -
                        {{ aturanPenolakans.to ?? 0 }} dari {{ aturanPenolakans.total }} data
                    </p>

                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in aturanPenolakans.links" :key="k">
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
                                class="text-xs px-3 h-8 transition-all"
                                :class="{
                                    'bg-primary text-primary-foreground hover:bg-primary/90 shadow-md': link.active,
                                }"
                            >
                                <Link
                                    :href="link.url"
                                    v-html="cleanLabel(link.label)"
                                />
                            </Button>
                        </template>
                    </nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
