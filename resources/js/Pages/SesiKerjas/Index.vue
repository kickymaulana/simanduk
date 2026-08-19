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
    IconSearch,
    IconClock,
    IconEye,
    IconPlayerStop,
    IconPlayerPlay,
    IconX,
    IconTarget,
} from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    sesikerjas: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    filters: { search: string };
    sesi_kerja_id: number | null;
}>();

const search = ref(props.filters.search || "");
let timeout: any;

// Watcher untuk pencarian dengan debounce 500ms
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route("sesikerjas.index"),
            { search: value },
            { preserveState: true, replace: true },
        );
    }, 500);
});

const clearSearch = () => {
    search.value = "";
};

const toggleSesi = (id: number) => {
    if (props.sesi_kerja_id === id) {
        router.delete(route("sesikerjas.nonaktif", id));
    } else {
        router.post(route("sesikerjas.aktifkan", id));
    }
};

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Sesi Kerja" />
    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader
                class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6"
            >
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconClock class="size-6 text-primary" />
                    Daftar Sesi Kerja
                </CardTitle>
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-72">
                        <IconSearch
                            class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            placeholder="Cari leader atau jenis..."
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
                    <Button
                        as-child
                        class="bg-primary hover:bg-primary/90 shadow-md transition-all active:scale-95"
                    >
                        <Link :href="route('sesikerjas.create')">
                            <IconPlus class="mr-2 size-4" />
                            Tambah
                        </Link>
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead>Leader</TableHead>
                                <TableHead>Anggota</TableHead>
                                <TableHead>Proses</TableHead>
                                <TableHead>Jenis</TableHead>
                                <TableHead>Shift</TableHead>
                                <TableHead>Total Scan</TableHead>
                                <TableHead>Target</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in sesikerjas.data"
                                :key="item.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell class="font-medium">
                                    {{ item.leader?.name }}
                                </TableCell>
                                <TableCell>
                                    <div
                                        class="flex flex-wrap gap-1 max-w-[200px]"
                                    >
                                        <Badge
                                            v-for="member in item.sesi_kerja_members"
                                            :key="member.id"
                                            variant="outline"
                                            class="text-[10px] px-2 py-0 bg-muted/50"
                                        >
                                            {{ member.user.name }}
                                        </Badge>
                                        <span
                                            v-if="
                                                item.sesi_kerja_members
                                                    .length === 0
                                            "
                                            class="text-xs text-muted-foreground italic"
                                        >
                                            Tanpa Anggota
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div class="size-2 rounded-full bg-primary animate-pulse" v-if="sesi_kerja_id === item.id"></div>
                                        <span class="font-semibold text-sm">
                                            {{ item.proses?.proses || '-' }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="
                                            item.jenis === 'Body'
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{ item.jenis }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground leading-relaxed">
                                    <p class="font-medium text-foreground/70">{{ item.shift.shift || "-" }}</p>
                                    <p class="italic text-[10px]">{{ item.tanggal || "-" }}</p>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            class="font-mono text-sm border-primary/50 text-primary"
                                        >
                                            {{ item.total_pengerjaan }}
                                        </Badge>
                                        <span
                                            class="text-xs text-muted-foreground uppercase italic font-medium"
                                            >Scan</span
                                        >
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            v-if="item.target"
                                            variant="outline"
                                            class="font-mono text-sm border-primary/50 text-primary"
                                        >
                                            <IconTarget class="mr-1 size-3" />
                                            {{ item.target }}
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="outline"
                                            class="font-mono text-sm border-muted text-muted-foreground"
                                        >
                                            <IconTarget class="mr-1 size-3" />
                                            —
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell
                                    class="text-right flex justify-end gap-2"
                                >
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'sesikerjas.show',
                                                    item.id,
                                                )
                                            "
                                        >
                                            <IconEye
                                                class="size-4 text-primary"
                                            />
                                        </Link>
                                    </Button>

                                    <Button
                                        @click="toggleSesi(item.id)"
                                        :variant="
                                            sesi_kerja_id === item.id
                                                ? 'destructive'
                                                : 'outline'
                                        "
                                        size="sm"
                                        class="w-32"
                                    >
                                        <template
                                            v-if="sesi_kerja_id === item.id"
                                        >
                                            <IconPlayerStop
                                                class="mr-2 size-4"
                                            />
                                            Nonaktifkan
                                        </template>
                                        <template v-else>
                                            <IconPlayerPlay
                                                class="mr-2 size-4 text-primary"
                                            />
                                            Aktifkan
                                        </template>
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="sesikerjas.data.length === 0">
                                <TableCell
                                    colspan="7"
                                    class="text-center py-10 text-muted-foreground italic"
                                >
                                    Data tidak ditemukan.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div
                    class="flex flex-col md:flex-row items-center justify-between gap-4 mt-6"
                >
                    <p class="text-xs text-muted-foreground italic font-medium">
                        Menampilkan {{ sesikerjas.from ?? 0 }} -
                        {{ sesikerjas.to ?? 0 }} dari
                        {{ sesikerjas.total }} sesi kerja
                    </p>

                    <nav class="flex items-center gap-1">
                        <template
                            v-for="(link, k) in sesikerjas.links"
                            :key="k"
                        >
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
                                    'bg-primary text-primary-foreground hover:bg-primary/90 shadow-sm':
                                        link.active,
                                }"
                            >
                                <Link
                                    :href="link.url"
                                    preserve-state
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
