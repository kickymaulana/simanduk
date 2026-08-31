<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { ref } from "vue";
import { toast } from "vue-sonner";
import { IconSettings, IconLock, IconLockOpen, IconAlertTriangle } from "@tabler/icons-vue";

const props = defineProps<{
    cek_urutan_scan: boolean;
}>();

const isActive = ref(props.cek_urutan_scan);
const isToggling = ref(false);

const toggle = () => {
    if (isToggling.value) return;

    const newVal = !isActive.value;
    if (!newVal) {
        if (!confirm("Nonaktifkan fitur cek urutan scan?\n\nScan akan bisa dilakukan tanpa urutan proses. Hanya gunakan jika ada produk yang terblokir.")) return;
    }

    isToggling.value = true;
    router.post(route("pengaturan.toggle_cek_urutan"), { aktif: newVal }, {
        preserveScroll: true,
        onSuccess: () => {
            isActive.value = newVal;
            toast.success(newVal ? "Cek urutan scan AKTIF" : "Cek urutan scan NONAKTIF");
        },
        onError: () => {
            toast.error("Gagal mengubah pengaturan");
        },
        onFinish: () => {
            isToggling.value = false;
        },
    });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <Head title="Pengaturan" />

    <div class="max-w-3xl mx-auto p-4 md:p-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4 border-b pb-5">
            <IconSettings class="size-7 text-slate-700" />
            <h1 class="text-2xl font-bold text-slate-900">Pengaturan</h1>
        </div>

        <Card class="border-none shadow-sm ring-1 ring-slate-200">
            <CardHeader class="pb-3">
                <CardTitle class="text-sm font-black uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                    <IconLock class="size-4" /> Cek Urutan Proses di Scan
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                    <div class="space-y-1">
                        <Label class="font-bold text-base">Cek Urutan Scan</Label>
                        <p class="text-sm text-muted-foreground">
                            Saat aktif, produk hanya bisa discan jika sudah melalui semua proses aktif sebelumnya (sesuai urutan).
                        </p>
                    </div>
                    <Switch
                        :model-value="isActive"
                        :disabled="isToggling"
                        @update:model-value="toggle"
                        class="scale-125"
                    />
                </div>

                <div class="flex items-start gap-3 p-4 rounded-xl border"
                    :class="isActive ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
                    <IconLock v-if="isActive" class="size-6 text-emerald-600 shrink-0 mt-0.5" />
                    <IconLockOpen v-else class="size-6 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                        <p class="font-bold text-sm" :class="isActive ? 'text-emerald-800' : 'text-amber-800'">
                            {{ isActive ? 'Fitur terkunci' : 'Fitur terbuka' }}
                        </p>
                        <p class="text-xs mt-1" :class="isActive ? 'text-emerald-700' : 'text-amber-700'">
                            {{ isActive
                                ? 'Cek urutan scan aktif. Produk harus scan sesuai urutan proses.'
                                : 'Cek urutan scan nonaktif. Produk bisa scan tanpa urutan.' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl border border-blue-200">
                    <IconAlertTriangle class="size-5 text-blue-600 shrink-0 mt-0.5" />
                    <p class="text-xs text-blue-800 leading-relaxed">
                        Nonaktifkan fitur ini hanya jika ada produk yang terblokir karena tidak sesuai urutan (misal ada proses yang terlewat). Setelah produk selesai discan, segera aktifkan kembali.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>