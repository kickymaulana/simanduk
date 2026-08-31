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
    IconArrowsSplit2,
    IconBuildingCommunity,
    IconPower,
} from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    proses: {
        data: Array<{
            id: number;
            proses: string;
            urutan: number;
            is_active: boolean;
            jenis: string | null;
            departemen: { departemen: string };
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
            route("proses.index"),
            { search: value },
            { preserveState: true, replace: true },
        );
    }, 500);
});

const clearSearch = () => (search.value = "");

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};

const toggleActive = (id: number) => {
    router.post(route("proses.toggle_active", id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Master Proses" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader
                class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6"
            >
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconArrowsSplit2 class="size-6 text-primary" />
                    Manajemen Proses Produksi
                </CardTitle>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-72">
                        <IconSearch
                            class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            placeholder="Cari proses atau departemen..."
                            class="pl-10 pr-10"
                        />
                        <button
                            v-if="search"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2"
                        >
                            <IconX
                                class="size-4 text-muted-foreground hover:text-foreground"
                            />
                        </button>
                    </div>
                    <Button as-child class="shadow-md">
                        <Link :href="route('proses.create')">
                            <IconPlus class="mr-2 size-4" /> Tambah Proses
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-[80px] text-center"
                                    >Urutan</TableHead
                                >
                                <TableHead>Nama Proses</TableHead>
                                <TableHead>Jenis</TableHead>
                                <TableHead>Departemen</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="proses.data.length === 0">
                                <TableCell
                                    colspan="6"
                                    class="h-24 text-center text-muted-foreground italic"
                                >
                                    Data proses tidak ditemukan.
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="item in proses.data"
                                :key="item.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell
                                    class="text-center font-mono font-bold"
                                >
                                    <Badge
                                        variant="outline"
                                        class="rounded-full size-7 flex items-center justify-center p-0 mx-auto bg-primary/5"
                                    >
                                        {{ item.urutan }}
                                    </Badge>
                                </TableCell>
                                <TableCell
                                    class="font-bold tracking-wide text-primary"
                                >
                                    {{ item.proses }}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        v-if="item.jenis"
                                        :class="
                                            item.jenis === 'Body'
                                                ? 'bg-blue-100 text-blue-700 border-blue-200'
                                                : 'bg-amber-100 text-amber-700 border-amber-200'
                                        "
                                        variant="outline"
                                        class="font-bold"
                                    >
                                        {{ item.jenis }}
                                    </Badge>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground font-medium"
                                    >
                                        Semua
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <IconBuildingCommunity
                                            class="size-4 text-muted-foreground"
                                        />
                                        <span class="font-medium text-sm">{{
                                            item.departemen.departemen
                                        }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :class="
                                            item.is_active
                                                ? 'bg-green-100 text-green-700 border-green-200'
                                                : 'bg-slate-100 text-slate-500 border-slate-200'
                                        "
                                        variant="outline"
                                    >
                                        {{ item.is_active ? "Aktif" : "Nonaktif" }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8"
                                            :title="
                                                item.is_active
                                                    ? 'Nonaktifkan'
                                                    : 'Aktifkan'
                                            "
                                            @click="toggleActive(item.id)"
                                        >
                                            <IconPower
                                                class="size-4"
                                                :class="
                                                    item.is_active
                                                        ? 'text-green-600'
                                                        : 'text-slate-400'
                                                "
                                            />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    route('proses.edit', item.id)
                                                "
                                            >
                                                <IconPencil
                                                    class="size-4 text-muted-foreground hover:text-primary"
                                                />
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div
                    class="flex flex-col md:flex-row items-center justify-between gap-4 mt-6"
                >
                    <p class="text-xs text-muted-foreground italic font-medium">
                        Menampilkan {{ proses.from ?? 0 }} -
                        {{ proses.to ?? 0 }} dari {{ proses.total }} data proses
                    </p>
                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in proses.links" :key="k">
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
                                :class="{
                                    'bg-primary text-primary-foreground':
                                        link.active,
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
