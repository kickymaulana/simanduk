<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, nextTick, computed } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { toast } from "vue-sonner";
import axios from "axios";
import {
    IconScan,
    IconLoader2,
    IconInfoCircle,
    IconArrowBackUp,
    IconPackage,
    IconUser,
    IconClock,
    IconAlertCircle,
    IconCircleCheck,
    IconHistory,
} from "@tabler/icons-vue";

const qrInput = ref<HTMLInputElement | null>(null);
const isLoading = ref(false);
const isUndoing = ref(false);
const produk = ref<any>(null);
const qr = ref("");

const focusInput = () => qrInput.value?.focus();

onMounted(() => focusInput());

const histCount = computed(() => produk.value?.pengerjaan_produks?.length ?? 0);
const canUndo = computed(() => histCount.value >= 2 && !isUndoing.value);

const cari = async () => {
    const code = qr.value.trim().toUpperCase();
    if (!code || isLoading.value) return;

    isLoading.value = true;
    try {
        const res = await axios.post(route("koreksi.scan.cari"), { qr: code });
        produk.value = res.data.produk;
    } catch (err: any) {
        const msg = err.response?.data?.message || "Produk tidak ditemukan.";
        toast.error("Gagal", { description: msg });
        produk.value = null;
    } finally {
        isLoading.value = false;
        qr.value = "";
        nextTick(() => focusInput());
    }
};

const batalkan = async () => {
    if (!produk.value || !canUndo.value) return;
    if (!confirm(`Batalkan scan terakhir untuk produk ${produk.value.qrcode}?\nRiwayat scan terakhir akan dihapus dan produk dikembalikan ke posisi sebelumnya.`)) return;

    isUndoing.value = true;
    try {
        const res = await axios.post(route("koreksi.scan.batalkan", produk.value.id));
        produk.value = res.data.produk;
        toast.success(`Scan terakhir ${produk.value.qrcode} berhasil dibatalkan.`);
    } catch (err: any) {
        const msg = err.response?.data?.message || "Gagal membatalkan scan.";
        toast.error("Gagal", { description: msg });
    } finally {
        isUndoing.value = false;
    }
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <Head title="Koreksi Scan" />

    <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6" @click="focusInput">
        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-5">
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <IconHistory class="size-7 text-red-600" />
                    Koreksi Scan
                </h1>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Sidebar Input Scan -->
            <div class="lg:col-span-4 space-y-4">
                <Card class="border-2 border-red-100 shadow-md sticky top-6">
                    <CardHeader class="bg-red-50/50 pb-4">
                        <CardTitle class="text-[11px] font-black text-red-600 uppercase tracking-widest">
                            Cari QR Code
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <IconLoader2 v-if="isLoading" class="size-5 text-red-500 animate-spin" />
                                <IconScan v-else class="size-5 text-slate-400" />
                            </div>
                            <input
                                ref="qrInput"
                                v-model="qr"
                                type="text"
                                class="block w-full pl-10 pr-3 py-4 text-2xl font-mono font-black border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-red-100 focus:border-red-600 transition-all uppercase outline-none"
                                placeholder="......"
                                :disabled="isLoading"
                                @keyup.enter="cari"
                                autocomplete="off"
                            />
                        </div>
                        <div class="mt-6 flex items-start gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <IconInfoCircle class="size-5 text-slate-400 shrink-0 mt-0.5" />
                            <p class="text-[11px] text-slate-500 leading-relaxed font-medium">
                                Masukkan QR Code produk. Admin bisa melihat histori scan dan membatalkan scan terakhir untuk memperbaiki kesalahan.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Empty State -->
                <div v-if="!produk" class="text-center py-24">
                    <div class="flex flex-col items-center gap-2 text-slate-300">
                        <IconPackage class="size-12 opacity-20" />
                        <p class="text-sm font-medium italic opacity-50 tracking-wide">Cari produk untuk mulai koreksi.</p>
                    </div>
                </div>

                <template v-if="produk">
                    <!-- Product Info Card -->
                    <Card class="border-none shadow-sm ring-1 ring-slate-200">
                        <CardHeader class="pb-3">
                            <CardTitle class="text-xs font-black uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                                <IconPackage class="size-4" /> Informasi Produk
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="p-3 bg-slate-50 rounded-lg">
                                    <p class="text-[8px] font-black uppercase text-slate-500">QR Code</p>
                                    <p class="font-mono font-black text-lg text-blue-700">{{ produk.qrcode }}</p>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-lg">
                                    <p class="text-[8px] font-black uppercase text-slate-500">Jenis</p>
                                    <p class="font-black uppercase">{{ produk.jenis }}</p>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-lg">
                                    <p class="text-[8px] font-black uppercase text-slate-500">Status Akhir</p>
                                    <Badge
                                        :class="produk.status_akhir === 'OK' ? 'border-emerald-500 text-emerald-600 bg-emerald-50' : produk.status_akhir === 'Buang' ? 'border-red-500 text-red-600 bg-red-50' : 'border-amber-500 text-amber-600 bg-amber-50'"
                                        variant="outline"
                                        class="font-bold rounded-md"
                                    >
                                        {{ produk.status_akhir }}
                                    </Badge>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-lg">
                                    <p class="text-[8px] font-black uppercase text-slate-500">Posisi</p>
                                    <p class="font-bold text-sm">{{ produk.proses?.proses || '—' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- History -->
                    <Card class="border-none shadow-sm ring-1 ring-slate-200">
                        <CardHeader class="pb-3 flex flex-row items-center justify-between">
                            <CardTitle class="text-xs font-black uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                                <IconHistory class="size-4" /> Histori Scan ({{ histCount }})
                            </CardTitle>
                            <Button
                                v-if="canUndo"
                                variant="destructive"
                                size="sm"
                                :disabled="isUndoing"
                                @click.stop="batalkan"
                                class="font-black text-[10px] uppercase tracking-wider"
                            >
                                <IconArrowBackUp class="size-4 mr-1" />
                                {{ isUndoing ? 'Memproses...' : 'Batalkan Scan Terakhir' }}
                            </Button>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                            <th class="px-4 py-3">Proses</th>
                                            <th class="px-4 py-3">Operator</th>
                                            <th class="px-4 py-3 text-center">Status</th>
                                            <th class="px-4 py-3">Cacat</th>
                                            <th class="px-4 py-3 text-right">Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr
                                            v-for="(h, i) in produk.pengerjaan_produks"
                                            :key="h.id"
                                            :class="[i === 0 ? 'bg-red-50/50' : '', 'hover:bg-slate-50/50 transition-colors']"
                                        >
                                            <td class="px-4 py-3 font-bold text-slate-800">
                                                {{ h.proses?.proses || '—' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-1.5">
                                                    <IconUser class="size-3 text-slate-400" />
                                                    <span class="text-slate-700">{{ h.user?.name || '—' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <Badge
                                                    :class="h.status_kondisi === 'OK' ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : h.status_kondisi === 'Buang' ? 'bg-red-100 text-red-700 border-red-300' : 'bg-amber-100 text-amber-700 border-amber-300'"
                                                    variant="outline"
                                                    class="font-bold text-[10px] rounded-md"
                                                >
                                                    {{ h.status_kondisi }}
                                                </Badge>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div v-if="h.pengerjaan_cacats?.length" class="flex flex-wrap gap-1">
                                                    <Badge
                                                        v-for="c in h.pengerjaan_cacats"
                                                        :key="c.id"
                                                        variant="destructive"
                                                        class="text-[8px] font-black uppercase px-2 py-0"
                                                    >
                                                        {{ c.cacat?.cacat || '—' }}
                                                    </Badge>
                                                </div>
                                                <span v-else class="text-emerald-600 text-[10px] font-black uppercase flex items-center gap-1">
                                                    <IconCircleCheck class="size-3" /> OK
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-1 text-slate-500 text-[10px] font-medium">
                                                    <IconClock class="size-3" />
                                                    {{ new Date(h.created_at).toLocaleString('id-ID') }}
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Undo Button (bottom) -->
                    <div v-if="canUndo" class="flex justify-end">
                        <Button
                            variant="destructive"
                            :disabled="isUndoing"
                            @click="batalkan"
                            class="font-black text-xs uppercase tracking-wider"
                        >
                            <IconArrowBackUp class="size-4 mr-2" />
                            {{ isUndoing ? 'Memproses...' : 'Batalkan Scan Terakhir' }}
                        </Button>
                    </div>
                    <div v-else-if="histCount === 0" class="text-center py-8 text-slate-400 text-sm italic">
                        Belum ada histori scan.
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
input:focus {
    outline: none !important;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1) !important;
}
</style>