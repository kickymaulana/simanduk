<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    IconArrowLeft,
    IconEdit,
    IconClock,
    IconUser,
    IconCategory,
    IconCalendarTime,
    IconUsers,
    IconPackage,
    IconCheck,
    IconX,
    IconHistory,
    IconLoader,
    IconTrash,
    IconDotsVertical,
    IconArrowRight,
    IconClockCheck,
    IconTarget,
} from "@tabler/icons-vue";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

const props = defineProps<{
    sesikerja: any;
    stats: {
        total_scan: number;
        total_ok: number;
        total_in_proses: number;
        total_reject: number;
    };
    pengerjaan_unik: any[];
    targetProgress: {
        target: number;
        actual: number;
        persentase: number;
        sisa: number;
        status: string;
    } | null;
}>();

defineOptions({ layout: AuthenticatedLayout });

// Fungsi Hapus Sesi
const deleteSesi = () => {
    router.delete(route("sesikerjas.destroy", props.sesikerja.id));
};

// Format tanggal ke Indonesia
const formatDate = (dateString: string | null) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleString("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    });
};

// Durasi sesi berdasarkan created_at dan updated_at (actual)
const getActualDuration = () => {
    if (!props.sesikerja.created_at || !props.sesikerja.updated_at) return null;
    const start = new Date(props.sesikerja.created_at);
    const end = new Date(props.sesikerja.updated_at);
    const diffMs = end.getTime() - start.getTime();
    const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    return `${diffHrs} Jam ${diffMins} Menit`;
};
</script>

<template>
    <Head title="Detail Sesi Kerja" />

    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
            <div class="flex items-center gap-4">
                <Button
                    variant="outline"
                    size="icon"
                    as-child
                    class="rounded-full"
                >
                    <Link :href="route('sesikerjas.index')">
                        <IconArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">
                        Detail Sesi
                    </h2>
                    <p class="text-muted-foreground italic">
                        Monitor pengerjaan unit lengkap
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <AlertDialog>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="rounded-full"
                            >
                                <IconDotsVertical class="size-5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-40">
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="
                                        route('sesikerjas.edit', sesikerja.id)
                                    "
                                    class="flex items-center cursor-pointer"
                                >
                                    <IconEdit
                                        class="mr-2 size-4 text-primary"
                                    />
                                    <span>Edit Sesi</span>
                                </Link>
                            </DropdownMenuItem>
                            <AlertDialogTrigger as-child>
                                <DropdownMenuItem
                                    class="flex items-center cursor-pointer text-destructive focus:text-destructive"
                                >
                                    <IconTrash class="mr-2 size-4" />
                                    <span>Hapus Sesi</span>
                                </DropdownMenuItem>
                            </AlertDialogTrigger>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle
                                >Hapus Sesi Kerja?</AlertDialogTitle
                            >
                            <AlertDialogDescription>
                                Tindakan ini tidak dapat dibatalkan. Sesi ini
                                akan dihapus secara permanen dari sistem
                                **SISAMSUL**.
                                <span
                                    v-if="stats.total_scan > 0"
                                    class="block mt-2 font-bold text-destructive underline italic"
                                >
                                    Peringatan: Sesi ini sudah memiliki
                                    {{ stats.total_scan }} data pengerjaan!
                                </span>
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Batal</AlertDialogCancel>
                            <AlertDialogAction
                                @click="deleteSesi"
                                class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            >
                                Ya, Hapus
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <Card
                class="border-none shadow-md bg-blue-50/50 border-blue-100 text-blue-700"
            >
                <CardContent class="p-6 flex items-center gap-4">
                    <IconPackage class="size-8 opacity-70" />
                    <div>
                        <p class="text-xs font-bold uppercase opacity-70">
                            Total Scan
                        </p>
                        <p class="text-3xl font-bold">
                            {{ stats.total_scan }}
                            <span class="text-sm font-normal">Unit</span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card
                class="border-none shadow-md bg-green-50/50 border-green-100 text-green-700"
            >
                <CardContent class="p-6 flex items-center gap-4">
                    <IconCheck class="size-8 opacity-70" />
                    <div>
                        <p class="text-xs font-bold uppercase opacity-70">
                            Scan OK
                        </p>
                        <p class="text-3xl font-bold">
                            {{ stats.total_ok }}
                            <span class="text-sm font-normal">Unit</span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card
                class="border-none shadow-md bg-amber-50/50 border-amber-100 text-amber-700"
            >
                <CardContent class="p-6 flex items-center gap-4">
                    <IconLoader class="size-8 opacity-70 animate-spin-slow" />
                    <div>
                        <p class="text-xs font-bold uppercase opacity-70">
                            In Proses
                        </p>
                        <p class="text-3xl font-bold">
                            {{ stats.total_in_proses }}
                            <span class="text-sm font-normal">Unit</span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card
                class="border-none shadow-md bg-red-50/50 border-red-100 text-red-700"
            >
                <CardContent class="p-6 flex items-center gap-4">
                    <IconX class="size-8 opacity-70" />
                    <div>
                        <p class="text-xs font-bold uppercase opacity-70">
                            Reject / Buang
                        </p>
                        <p class="text-3xl font-bold">
                            {{ stats.total_reject }}
                            <span class="text-sm font-normal">Unit</span>
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Target Progress Card -->
        <div v-if="targetProgress" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card class="border-none shadow-md bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-primary text-lg">
                        <IconTarget class="size-5" /> Progress Target Sesi
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/50 rounded-xl p-4 border border-blue-100">
                                <p class="text-xs font-bold uppercase text-muted-foreground">Target</p>
                                <p class="text-3xl font-bold text-primary">{{ targetProgress.target }} Unit</p>
                            </div>
                            <div class="bg-white/50 rounded-xl p-4 border border-green-100">
                                <p class="text-xs font-bold uppercase text-muted-foreground">Actual (Total Scan)</p>
                                <p class="text-3xl font-bold text-green-600">{{ targetProgress.actual }} Unit</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase text-muted-foreground mb-2">Progress</p>
                            <div class="h-3 bg-blue-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="{
                                        'bg-green-500': targetProgress.status === 'target_tercapai' || targetProgress.status === 'baik',
                                        'bg-amber-500': targetProgress.status === 'sedang',
                                        'bg-red-500': targetProgress.status === 'rendah',
                                    }"
                                    :style="{ width: Math.min(targetProgress.persentase, 100) + '%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="bg-white/50 rounded-xl p-3 border border-green-100">
                                <p class="text-xs font-bold uppercase text-muted-foreground">Pencapaian</p>
                                <p class="font-bold text-green-600">
                                    {{ targetProgress.persentase }}%
                                    <span v-if="targetProgress.persentase >= 100" class="text-xs text-green-500"> (Target Tercapai!)</span>
                                </p>
                            </div>
                            <div class="bg-white/50 rounded-xl p-3 border border-red-100">
                                <p class="text-xs font-bold uppercase text-muted-foreground">Sisa</p>
                                <p class="font-bold text-red-600">
                                    {{ targetProgress.sisa }} Unit
                                    <span v-if="targetProgress.sisa === 0" class="text-xs text-green-500"> (Selesai)</span>
                                </p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-blue-100">
                            <p class="text-xs text-muted-foreground">
                                Status: 
                                <span class="font-semibold capitalize">
                                    {{ targetProgress.status === 'target_tercapai' ? 'Target Tercapai ✓' :
                                       targetProgress.status === 'baik' ? 'Baik (≥80%)' :
                                       targetProgress.status === 'sedang' ? 'Sedang (50-79%)' :
                                       'Rendah (<50%)' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card class="md:col-span-2 border-none shadow-lg">
                <CardHeader>
                    <CardTitle
                        class="flex items-center gap-2 text-primary text-lg"
                    >
                        <IconCalendarTime class="size-5" /> Informasi Sesi &
                        Shift
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <p
                                class="text-sm text-muted-foreground flex items-center gap-1"
                            >
                                <IconClockCheck class="size-4" /> Shift Terpilih
                            </p>
                            <p class="text-xl font-bold text-primary uppercase">
                                {{ sesikerja.shift?.shift || "NON-SHIFT" }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">
                                Tanggal Sesi
                            </p>
                            <p class="text-lg font-semibold">
                                {{ formatDate(sesikerja.tanggal_masuk) }}
                            </p>
                        </div>
                        <div
                            class="sm:col-span-2 mt-2 p-3 bg-primary/5 rounded-lg border-l-4 border-primary"
                        >
                            <p
                                class="text-sm font-medium flex items-center gap-2 text-primary"
                            >
                                <IconClock class="size-4" /> Durasi Aktual:
                                {{ getActualDuration() }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="flex flex-col gap-6">
                <Card class="border-none shadow-lg">
                    <CardContent class="p-6 space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span
                                class="text-muted-foreground flex items-center gap-2"
                                ><IconCategory class="size-4" /> Jenis</span
                            >
                            <Badge
                                variant="outline"
                                class="font-bold font-mono"
                                >{{ sesikerja.jenis }}</Badge
                            >
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span
                                class="text-muted-foreground flex items-center gap-2"
                                ><IconUser class="size-4" /> Leader Sesi</span
                            >
                            <span class="font-bold text-primary">{{
                                sesikerja.leader?.name
                            }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-lg">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base flex items-center gap-2">
                            <IconUsers class="size-4" /> Anggota Tim
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-1.5">
                            <Badge
                                v-for="member in sesikerja.sesi_kerja_members"
                                :key="member.id"
                                variant="secondary"
                                class="font-normal"
                            >
                                {{ member.user?.name }}
                            </Badge>
                            <p
                                v-if="!sesikerja.sesi_kerja_members?.length"
                                class="text-xs text-muted-foreground italic"
                            >
                                Tidak ada anggota tim tambahan.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Card class="border-none shadow-lg">
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0"
            >
                <CardTitle class="text-lg flex items-center gap-2">
                    <IconHistory class="size-5 text-primary" /> Riwayat Scan
                    Produk (Terbaru)
                </CardTitle>

                <Button
                    variant="ghost"
                    size="sm"
                    as-child
                    class="text-primary hover:text-primary hover:bg-primary/10"
                >
                    <Link
                        :href="route('sesikerjas.riwayat_scan', sesikerja.id)"
                        class="flex items-center gap-1"
                    >
                        Lihat Semua Riwayat
                        <IconArrowRight class="size-4" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-[180px]"
                                    >Waktu Scan</TableHead
                                >
                                <TableHead>QR Code</TableHead>
                                <TableHead>Proses Terakhir</TableHead>
                                <TableHead class="text-right"
                                    >Kondisi</TableHead
                                >
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="log in pengerjaan_unik"
                                :key="log.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell
                                    class="text-[11px] text-muted-foreground font-mono"
                                >
                                    {{ formatDate(log.created_at) }}
                                </TableCell>
                                <TableCell class="font-bold text-primary">{{
                                    log.produk?.qrcode
                                }}</TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="font-normal"
                                        >{{
                                            log.proses?.proses || "N/A"
                                        }}</Badge
                                    >
                                </TableCell>
                                <TableCell class="text-right">
                                    <Badge
                                        :variant="
                                            log.status_kondisi === 'OK'
                                                ? 'default'
                                                : log.status_kondisi === 'Buang'
                                                  ? 'destructive'
                                                  : 'secondary'
                                        "
                                        :class="
                                            log.status_kondisi === 'In Proses'
                                                ? 'bg-amber-500 text-white hover:bg-amber-600 border-none'
                                                : ''
                                        "
                                    >
                                        {{ log.status_kondisi }}
                                    </Badge>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!pengerjaan_unik.length">
                                <TableCell
                                    colspan="4"
                                    class="text-center py-12 text-muted-foreground italic font-medium"
                                >
                                    Belum ada aktivitas scan pada sesi ini.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
.animate-spin-slow {
    animation: spin 3s linear infinite;
}
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
