<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm, Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, watch } from "vue";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import {
    IconScan, IconLoader2, IconArrowLeft, IconCheck,
    IconAlertTriangle, IconSettings, IconPalette, IconClock
} from "@tabler/icons-vue";

const props = defineProps<{
    sesi: any;
    pilihan_cacat: Array<{ id: number; cacat: string }>;
    pilihan_kualitas: Array<{ id: number; kualitas: string }>;
    pilihan_warna: Array<{ id: number; warna: string }>;
}>();

const STORAGE_KEY = "scan_checking_buang_cacat_ids";
const nativeInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    qr: "",
    cacat_ids: JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]") as number[],
    kualitas_id: null as number | null,
    warna_id: null as number | null,
});

watch(() => form.cacat_ids, (newVal) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
}, { deep: true });

const focusInput = (attempt = 0) => {
    if (nativeInput.value) {
        nativeInput.value.focus();
        if (document.activeElement !== nativeInput.value && attempt < 5) {
            setTimeout(() => focusInput(attempt + 1), 80 * (attempt + 1));
        }
    } else if (attempt < 5) {
        setTimeout(() => focusInput(attempt + 1), 80 * (attempt + 1));
    }
};

onMounted(() => focusInput());
watch(() => form.processing, (proc) => { if (!proc) focusInput(); });

const toggleCacat = (id: number) => {
    const idx = form.cacat_ids.indexOf(id);
    idx > -1 ? form.cacat_ids.splice(idx, 1) : form.cacat_ids.push(id);
    focusInput();
};

const handleScan = () => {
    if (!form.qr || form.processing) return;
    if (form.cacat_ids.length === 0) {
        toast.error("Wajib pilih minimal satu jenis cacat!");
        focusInput();
        return;
    }
    form.post(route("scan.checking.buang_store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.qr = "";
            focusInput();
        },
        onError: (err) => {
            // Overlay tengah (ScanSuccessOverlay) menampilkan error
            form.reset("qr");
            focusInput();
        },
    });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <Head title="Scan Checking Buang" />

    <div class="p-4 max-w-5xl mx-auto space-y-4" @click="focusInput">
        <div class="flex items-center justify-between bg-white p-2 rounded-lg shadow-sm border">
            <Link :href="route('scan.index')" class="flex items-center text-xs font-bold text-slate-500">
                <IconArrowLeft class="size-4 mr-1" /> KEMBALI
            </Link>
            <div class="flex gap-1">
                <Button as-child variant="outline" size="sm" class="h-8 text-[10px] border-blue-200 text-blue-600">
                    <Link :href="route('scan.checking')">MODE OK</Link>
                </Button>
                <Button as-child variant="outline" size="sm" class="h-8 text-[10px] border-orange-200 text-orange-600">
                    <Link :href="route('scan.checking.mode', 'inproses')">IN PROSES</Link>
                </Button>
                <Button size="sm" class="h-8 text-[10px] bg-red-600 hover:bg-red-700 shadow-md">BUANG</Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 flex items-center bg-red-50 border-2 border-red-200 p-2 rounded-xl shadow-inner">
                <IconScan class="size-6 text-red-500 ml-2 mr-3" />
                <input
                    ref="nativeInput"
                    v-model="form.qr"
                    :disabled="form.processing"
                    type="text"
                    class="flex-1 bg-transparent border-none focus:ring-0 text-xl font-black uppercase tracking-widest text-red-700 placeholder:text-red-200"
                    placeholder="SCAN QR BUANG DISINI..."
                    @keyup.enter="handleScan"
                    @input="form.qr = form.qr.toUpperCase()"
                    @blur="focusInput"
                    autocomplete="off"
                />
            </div>
            <div class="bg-red-900 text-white p-3 rounded-xl flex flex-col justify-center items-center shadow-lg border-b-4 border-red-950">
                <span class="text-[9px] uppercase opacity-70 tracking-tighter font-bold">Sesi / Proses</span>
                <span v-if="sesi" class="font-black text-sm">
                    <IconClock class="inline size-3 mr-1" />{{ sesi.proses?.proses }} · {{ sesi.jenis }}
                </span>
                <span v-else class="font-black text-xs">Belum ada sesi aktif</span>
            </div>
        </div>

        <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
            <div class="bg-red-600 px-3 py-1.5 flex items-center gap-2">
                <IconAlertTriangle class="size-3 text-white" />
                <span class="text-[10px] font-bold text-white uppercase">Cacat (WAJIB MINIMAL 1)</span>
            </div>
            <div class="p-3 flex flex-wrap gap-2 max-h-[180px] overflow-y-auto">
                <button
                    v-for="item in pilihan_cacat" :key="item.id"
                    type="button"
                    @click="toggleCacat(item.id)"
                    :class="['px-4 py-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center gap-2',
                             form.cacat_ids.includes(item.id) ? 'bg-red-600 border-red-700 text-white shadow-md scale-105' : 'bg-slate-50 border-slate-100 text-slate-500']"
                >
                    <IconCheck v-if="form.cacat_ids.includes(item.id)" class="size-3" />
                    {{ item.cacat }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
                <div class="bg-blue-600 px-3 py-1.5 flex items-center gap-2">
                    <IconSettings class="size-3 text-white" />
                    <span class="text-[10px] font-bold text-white uppercase">Tentukan Kualitas</span>
                </div>
                <div class="p-3 flex flex-wrap gap-2">
                    <button
                        v-for="k in pilihan_kualitas" :key="k.id"
                        type="button"
                        @click="form.kualitas_id = (form.kualitas_id === k.id ? null : k.id); focusInput()"
                        :class="['px-5 py-3 rounded-xl text-xs font-bold border-2 transition-all',
                                 form.kualitas_id === k.id ? 'bg-blue-600 border-blue-700 text-white shadow-md scale-105' : 'bg-slate-50 border-slate-100 text-slate-500']"
                    >
                        {{ k.kualitas }}
                    </button>
                </div>
            </div>

            <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
                <div class="bg-blue-800 px-3 py-1.5 flex items-center gap-2">
                    <IconPalette class="size-3 text-white" />
                    <span class="text-[10px] font-bold text-white uppercase">Tentukan Warna</span>
                </div>
                <div class="p-3 flex flex-wrap gap-2">
                    <button
                        v-for="w in pilihan_warna" :key="w.id"
                        type="button"
                        @click="form.warna_id = (form.warna_id === w.id ? null : w.id); focusInput()"
                        :class="['px-5 py-3 rounded-xl text-xs font-bold border-2 transition-all',
                                 form.warna_id === w.id ? 'bg-blue-800 border-blue-900 text-white shadow-md scale-105' : 'bg-slate-50 border-slate-100 text-slate-500']"
                    >
                        {{ w.warna }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="form.processing" class="fixed inset-0 bg-red-900/40 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-white p-5 rounded-2xl shadow-2xl flex flex-col items-center border-t-4 border-red-600">
                <IconLoader2 class="size-8 animate-spin text-red-600 mb-2" />
                <span class="text-xs font-black uppercase tracking-widest text-red-900">Memproses...</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
input:focus {
    outline: none !important;
}
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}
</style>
