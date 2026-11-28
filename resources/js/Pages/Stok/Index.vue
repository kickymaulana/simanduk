<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { IconLayoutDashboard } from "@tabler/icons-vue";
import { computed } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    stok: Array<{
        id: number;
        departemen: string;
        total_produk: number;
        proses: Array<{
            id: number;
            proses: string;
            total_produk: number;
        }>;
    }>;
}>();

const total = computed(() => props.stok.reduce((sum, dept) => sum + dept.total_produk, 0));
const formatStok = (value: number) => value.toLocaleString("id-ID");
</script>

<template>
    <Head title="Stok Produksi" />

    <div class="p-4 md:p-8 w-full space-y-4">
        <div class="flex items-center gap-3 pb-4 border-b">
            <div class="p-2 bg-slate-900 dark:bg-primary rounded-lg text-white">
                <IconLayoutDashboard class="size-6" />
            </div>
            <h1 class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">
                Ringkasan Stok per Departemen
            </h1>
        </div>

        <div class="overflow-x-auto rounded-xl border bg-white dark:bg-slate-950 shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-100 dark:bg-slate-900 uppercase text-xs font-bold">
                    <tr>
                        <th scope="col" class="px-4 py-3 border-b">Departemen</th>
                        <th scope="col" class="px-4 py-3 border-b">Proses</th>
                        <th scope="col" class="px-4 py-3 border-b text-right whitespace-nowrap">Stok (unit)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="stok.length === 0">
                        <td colspan="3" class="px-4 py-10 text-center text-muted-foreground italic">Belum ada data stok.</td>
                    </tr>
                    <template v-for="dept in stok" :key="dept.id">
                        <tr v-for="(proses, index) in dept.proses" :key="proses.id" class="border-b">
                            <th v-if="index === 0" scope="rowgroup" :rowspan="dept.proses.length" class="px-4 py-3 align-top font-bold">
                                {{ dept.departemen }}
                            </th>
                            <td class="px-4 py-3">{{ proses.proses }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatStok(proses.total_produk) }}</td>
                        </tr>
                        <tr class="border-b bg-slate-50 dark:bg-slate-900 font-bold">
                            <th colspan="2" scope="row" class="px-4 py-3">Subtotal {{ dept.departemen }}</th>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatStok(dept.total_produk) }}</td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-900 font-black">
                        <th colspan="2" scope="row" class="px-4 py-3">Total Produk Aktif</th>
                        <td class="px-4 py-3 text-right tabular-nums">{{ formatStok(total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-xs text-muted-foreground">Total mencakup produk bukan Buang pada proses aktif.</p>
    </div>
</template>
