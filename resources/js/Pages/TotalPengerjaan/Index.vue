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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { Calendar } from "@/components/ui/calendar";
import {
    IconSearch,
    IconUsers,
    IconX,
    IconTrophy,
    IconCalendar,
    IconFilterOff,
    IconClock,
    IconPrinter,
} from "@tabler/icons-vue";
import { ref, watch, onMounted, computed } from "vue";
import {
    DateFormatter,
    getLocalTimeZone,
    parseDate,
} from "@internationalized/date";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    rekap: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    } | Array<any>;
    departemens: Array<{ id: number; departemen: string }>;
    filters: {
        search?: string;
        date_start?: string;
        date_end?: string;
        departemen_id?: string;
    };
    isPrint: boolean;
}>();

// Computed to handle both paginated and array data
const rekapData = computed(() => Array.isArray(props.rekap) ? props.rekap : props.rekap.data);
const rekapFrom = computed(() => Array.isArray(props.rekap) ? 1 : props.rekap.from);
const rekapTo = computed(() => Array.isArray(props.rekap) ? props.rekap.length : props.rekap.to);
const rekapTotal = computed(() => Array.isArray(props.rekap) ? props.rekap.length : props.rekap.total);
const rekapLinks = computed(() => Array.isArray(props.rekap) ? [] : props.rekap.links);

// Auto-print when in print mode
onMounted(() => {
    if (props.isPrint) {
        window.print();
    }
});

const df = new DateFormatter("id-ID", { dateStyle: "medium" });

const splitDateTime = (
    dateTimeStr: string | undefined,
    defaultTime: string,
) => {
    if (!dateTimeStr) return { date: undefined, time: defaultTime };
    const parts = dateTimeStr.split(" ");
    return {
        date: parts[0] ? parseDate(parts[0]) : undefined,
        time: parts[1] ? parts[1].substring(0, 5) : defaultTime,
    };
};

const initialStart = splitDateTime(props.filters.date_start, "00:00");
const initialEnd = splitDateTime(props.filters.date_end, "23:59");

const search = ref(props.filters.search || "");
const departemenId = ref(props.filters.departemen_id || "");
const dateStart = ref(initialStart.date);
const timeStart = ref(initialStart.time);
const dateEnd = ref(initialEnd.date);
const timeEnd = ref(initialEnd.time);

let timeout: any;

const updateFilters = () => {
    const fullDateStart = dateStart.value
        ? `${dateStart.value.toString()} ${timeStart.value}:00`
        : undefined;
    const fullDateEnd = dateEnd.value
        ? `${dateEnd.value.toString()} ${timeEnd.value}:59`
        : undefined;

    router.get(
        route("total.pengerjaan.user"),
        {
            search: search.value,
            departemen_id:
                departemenId.value && departemenId.value !== "all"
                    ? departemenId.value
                    : undefined,
            date_start: fullDateStart,
            date_end: fullDateEnd,
        },
        {
            preserveState: true,
            replace: true,
            only: ["rekap", "filters"],
        },
    );
};

watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => updateFilters(), 500);
});

watch([dateStart, timeStart, dateEnd, timeEnd], () => {
    updateFilters();
});

watch(departemenId, () => {
    updateFilters();
});

const clearSearch = () => {
    search.value = "";
};

const resetFilters = () => {
    search.value = "";
    departemenId.value = "";
    dateStart.value = undefined;
    timeStart.value = "00:00";
    dateEnd.value = undefined;
    timeEnd.value = "23:59";
};

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};

const handlePrintAll = () => {
    const fullDateStart = dateStart.value
        ? `${dateStart.value.toString()} ${timeStart.value}:00`
        : "";
    const fullDateEnd = dateEnd.value
        ? `${dateEnd.value.toString()} ${timeEnd.value}:59`
        : "";
    const depId = departemenId.value && departemenId.value !== "all"
        ? departemenId.value
        : "";

    const params = new URLSearchParams({
        print: "all",
        search: search.value,
        date_start: fullDateStart,
        date_end: fullDateEnd,
        departemen_id: depId,
    });

    const url = `total-pengerjaan-user?${params.toString()}`;
    const win = window.open(url, "_blank");
    if (win) {
        win.focus();
    }
};

const formatPeriode = () => {
    if (!props.filters.date_start && !props.filters.date_end) return "Semua Periode";
    const start = props.filters.date_start ? props.filters.date_start.split(" ")[0] : "";
    const end = props.filters.date_end ? props.filters.date_end.split(" ")[0] : "";
    if (start && end) return `${start} s/d ${end}`;
    if (start) return `Mulai ${start}`;
    if (end) return `Sampai ${end}`;
    return "Semua Periode";
};

const getDepartemenLabel = () => {
    if (!props.filters.departemen_id) return "Semua Departemen";
    const dep = props.departemens.find(d => d.id.toString() === props.filters.departemen_id);
    return dep ? dep.departemen : "Unknown";
};
</script>

<template>
    <Head :title="props.isPrint ? 'Cetak Rekap Produk Per User' : 'Rekap Produk Per User'" />

    <!-- PRINT LAYOUT -->
    <div v-if="props.isPrint" class="print-only p-6">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold uppercase">Rekap Produk Per Personel</h1>
            <p class="text-sm text-muted-foreground">SIMANDUK - Sistem Monitoring Alur Nilai Defect Unit Kloset Duduk</p>
            <div class="mt-2 text-xs text-muted-foreground">
                <span>Periode: {{ formatPeriode() }}</span>
                <span class="mx-2">|</span>
                <span>Departemen: {{ getDepartemenLabel() }}</span>
                <span class="mx-2">|</span>
                <span>Search: {{ props.filters.search || "-" }}</span>
                <span class="mx-2">|</span>
                <span>Dicetak: {{ new Date().toLocaleString("id-ID") }}</span>
            </div>
        </div>

        <div class="rounded-lg border overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow class="bg-muted/50">
                        <TableHead class="w-[60px] text-center text-xs uppercase font-bold tracking-wider">Rank</TableHead>
                        <TableHead class="text-xs uppercase font-bold tracking-wider">Nama Personel</TableHead>
                        <TableHead class="text-xs uppercase font-bold tracking-wider">Departemen</TableHead>
                        <TableHead class="text-center text-xs uppercase font-bold tracking-wider text-green-600">OK</TableHead>
                        <TableHead class="text-center text-xs uppercase font-bold tracking-wider text-amber-600">In Proses</TableHead>
                        <TableHead class="text-center text-xs uppercase font-bold tracking-wider text-red-600">Buang</TableHead>
                        <TableHead class="text-center text-xs uppercase font-bold tracking-wider">Total Output</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow
                        v-for="(item, index) in rekapData"
                        :key="item.user.id"
                        class="border-t border-slate-200"
                    >
                        <TableCell class="text-center font-bold text-muted-foreground">{{ rekapFrom + index }}</TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2">
                                <div class="size-7 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs border border-primary/20">
                                    {{ item.user.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <span class="font-semibold text-slate-700 tracking-tight">{{ item.user.name }}</span>
                            </div>
                        </TableCell>
                        <TableCell class="text-slate-600">{{ item.user.departemen || "—" }}</TableCell>
                        <TableCell class="text-center"><span class="font-bold text-green-600">{{ item.total_ok }}</span></TableCell>
                        <TableCell class="text-center"><span class="font-bold text-amber-600">{{ item.total_proses }}</span></TableCell>
                        <TableCell class="text-center"><span class="font-bold text-red-600">{{ item.total_buang }}</span></TableCell>
                        <TableCell class="text-center font-bold">{{ item.total_pengerjaan }} Produk</TableCell>
                    </TableRow>
                    <TableRow v-if="rekapData.length === 0" class="text-center">
                        <TableCell colspan="7" class="py-8 text-muted-foreground">Tidak ada data</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="mt-6 text-xs text-right text-muted-foreground">
            Total: {{ rekapTotal }} personel
        </div>
    </div>

    <!-- NORMAL LAYOUT -->
    <div v-else class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader
                class="flex flex-col lg:flex-row items-start lg:items-center justify-between space-y-4 lg:space-y-0 pb-6"
            >
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconUsers class="size-6 text-primary" />
                    Pencapaian Kerja Per Personel
                </CardTitle>

                <div class="flex flex-col md:flex-row items-center gap-3 w-full lg:w-auto">
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full md:w-[200px] justify-start text-left font-normal h-12"
                                >
                                    <IconCalendar class="mr-2 size-4 text-muted-foreground" />
                                    <div class="flex flex-col items-start">
                                        <span class="text-[10px] uppercase font-bold text-muted-foreground leading-none mb-1">Mulai</span>
                                        <span class="text-xs">
                                            {{
                                                dateStart
                                                    ? df.format(
                                                          dateStart.toDate(
                                                              getLocalTimeZone(),
                                                          ),
                                                      )
                                                    : "Pilih Tanggal"
                                            }}
                                            <span class="text-primary font-medium">({{ timeStart }})</span>
                                        </span>
                                    </div>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="dateStart" />
                                <div class="p-3 border-t bg-muted/20 flex items-center gap-2">
                                    <IconClock class="size-4 text-muted-foreground" />
                                    <span class="text-xs font-medium">Jam:</span>
                                    <Input type="time" v-model="timeStart" class="h-8 py-1" />
                                </div>
                            </PopoverContent>
                        </Popover>

                        <span class="text-muted-foreground text-xs font-bold">s/d</span>

                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full md:w-[200px] justify-start text-left font-normal h-12"
                                >
                                    <IconCalendar class="mr-2 size-4 text-muted-foreground" />
                                    <div class="flex flex-col items-start">
                                        <span class="text-[10px] uppercase font-bold text-muted-foreground leading-none mb-1">Selesai</span>
                                        <span class="text-xs">
                                            {{
                                                dateEnd
                                                    ? df.format(
                                                          dateEnd.toDate(
                                                              getLocalTimeZone(),
                                                          ),
                                                      )
                                                    : "Pilih Tanggal"
                                            }}
                                            <span class="text-primary font-medium">({{ timeEnd }})</span>
                                        </span>
                                    </div>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="end">
                                <Calendar v-model="dateEnd" />
                                <div class="p-3 border-t bg-muted/20 flex items-center gap-2">
                                    <IconClock class="size-4 text-muted-foreground" />
                                    <span class="text-xs font-medium">Jam:</span>
                                    <Input type="time" v-model="timeEnd" class="h-8 py-1" />
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <div class="relative w-full md:w-64">
                            <IconSearch class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Cari nama..."
                                class="pl-10 pr-10 h-10"
                            />
                            <button
                                v-if="search"
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                            >
                                <IconX class="size-4" />
                            </button>
                        </div>

                        <Select v-model="departemenId">
                            <SelectTrigger class="w-full md:w-48 h-10">
                                <SelectValue placeholder="Pilih Departemen" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Departemen</SelectItem>
                                <SelectItem
                                    v-for="d in departemens"
                                    :key="d.id"
                                    :value="d.id"
                                >
                                    {{ d.departemen }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Button
                            variant="outline"
                            size="icon"
                            @click="handlePrintAll"
                            class="ml-2"
                            title="Cetak Semua Data"
                        >
                            <IconPrinter class="size-4" />
                        </Button>

                        <Button
                            v-if="search || departemenId || dateStart || dateEnd"
                            variant="ghost"
                            size="icon"
                            @click="resetFilters"
                            class="text-red-500 hover:bg-red-50"
                        >
                            <IconFilterOff class="size-5" />
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-[80px] text-center text-xs uppercase font-bold tracking-wider">Rank</TableHead>
                                <TableHead class="text-xs uppercase font-bold tracking-wider">Nama Personel</TableHead>
                                <TableHead class="text-xs uppercase font-bold tracking-wider">Departemen</TableHead>
                                <TableHead class="text-center text-xs uppercase font-bold tracking-wider text-green-600">OK</TableHead>
                                <TableHead class="text-center text-xs uppercase font-bold tracking-wider text-amber-600">In Proses</TableHead>
                                <TableHead class="text-center text-xs uppercase font-bold tracking-wider text-red-600">Buang</TableHead>
                                <TableHead class="text-center text-xs uppercase font-bold tracking-wider">Total Output</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="(item, index) in rekapData"
                                :key="item.user.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell class="text-center font-bold text-muted-foreground">{{ rekapFrom + index }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs border border-primary/20">
                                            {{ item.user.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <span class="font-semibold text-slate-700 tracking-tight">{{ item.user.name }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-slate-600">{{ item.user.departemen || "—" }}</TableCell>
                                <TableCell class="text-center"><span class="font-bold text-green-600">{{ item.total_ok }}</span></TableCell>
                                <TableCell class="text-center"><span class="font-bold text-amber-600">{{ item.total_proses }}</span></TableCell>
                                <TableCell class="text-center"><span class="font-bold text-red-600">{{ item.total_buang }}</span></TableCell>
                                <TableCell class="text-center">
                                    <Badge class="bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 px-4 py-1">
                                        <IconTrophy class="size-3.5 mr-1.5" />
                                        {{ item.total_pengerjaan }} Produk
                                    </Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mt-6">
                    <p class="text-xs text-muted-foreground font-medium">
                        Menampilkan {{ rekapFrom }} - {{ rekapTo }} dari
                        {{ rekapTotal }} personel
                    </p>
                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in rekapLinks" :key="k">
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
                                    'bg-primary text-white border-primary': link.active,
                                }"
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