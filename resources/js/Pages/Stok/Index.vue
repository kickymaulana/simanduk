<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
    IconBox,
    IconBuildingFactory,
    IconLayoutDashboard,
} from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    stok: Array<{
        id: number;
        departemen: string;
        total_produk: number;
        proses: Array<{
            id: number;
            proses: string;
            urutan: number;
            is_active: boolean;
            total_produk: number;
        }>;
    }>;
}>();

// Warna tema berdasarkan ID Departemen
const themes: { [key: number]: string } = {
    1: 'bg-blue-500',      // Casting
    2: 'bg-emerald-500',   // Solar
    3: 'bg-amber-500',     // Spray
    5: 'bg-orange-500',    // Oven
    18: 'bg-indigo-500',   // Packing
    24: 'bg-cyan-500',     // QC
    25: 'bg-rose-500',     // Repair
    0: 'bg-slate-500',     // Default
};

const getTheme = (id: number) => themes[id] || themes[id % 8] || themes[0];
</script>

<template>
    <Head title="Stok Produksi" />

    <div class="p-4 md:p-8 max-w-5xl mx-auto space-y-4">
        <div class="flex items-center justify-between pb-4 border-b">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-slate-900 dark:bg-primary rounded-lg text-white">
                    <IconLayoutDashboard class="size-6" />
                </div>
                <h1 class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">
                    Ringkasan Stok (per Departemen)
                </h1>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold text-muted-foreground uppercase">Total Produk Aktif</p>
                <p class="text-2xl font-black text-primary leading-none">
                    {{ stok.reduce((acc, d) => acc + d.total_produk, 0).toLocaleString() }}
                </p>
            </div>
        </div>

        <div v-if="stok.length === 0" class="text-center py-12 text-muted-foreground italic">
            Belum ada data stok.
        </div>

        <div v-for="dept in stok" :key="dept.id" class="grid grid-cols-1 gap-2">
            <Card class="border rounded-xl overflow-hidden shadow-sm">
                <CardHeader class="py-3 px-5 flex flex-row items-center justify-between space-y-0">
                    <CardTitle class="flex items-center gap-2 text-base font-black uppercase">
                        <div class="size-8 rounded-full text-white flex items-center justify-center font-black text-sm" :class="getTheme(dept.id)">
                            <IconBuildingFactory class="size-4" />
                        </div>
                        {{ dept.departemen }}
                    </CardTitle>
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 px-4 py-1.5 rounded-lg border">
                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ dept.total_produk || 0 }}</span>
                        <IconBox class="size-4 text-primary opacity-80" />
                    </div>
                </CardHeader>

                <CardContent class="py-3 px-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <div
                            v-for="p in dept.proses"
                            :key="p.id"
                            class="flex items-center justify-between p-2 px-3 rounded-lg bg-muted/40 dark:bg-slate-900/40 border"
                            :class="p.is_active ? '' : 'opacity-50'"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-[10px] font-black text-muted-foreground w-4">{{ p.urutan }}</span>
                                <span class="text-sm font-bold truncate">{{ p.proses }}</span>
                                <span v-if="!p.is_active" class="text-[9px] font-bold text-slate-400 uppercase">Nonaktif</span>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <span class="font-black text-sm">{{ p.total_produk || 0 }}</span>
                                <span class="text-[9px] font-bold text-muted-foreground uppercase">Unit</span>
                            </div>
                        </div>

                        <div v-if="dept.proses.length === 0" class="md:col-span-2 lg:col-span-3 text-xs text-muted-foreground italic">
                            Tidak ada proses di departemen ini.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <p class="text-center text-[10px] font-bold text-muted-foreground uppercase tracking-[0.3em] pt-6 opacity-40">
            Mark Dynamics Indonesia
        </p>
    </div>
</template>