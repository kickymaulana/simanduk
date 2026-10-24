<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm, Head, Link } from "@inertiajs/vue3";
import { computed, ref, onMounted, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    IconScan, IconLoader2, IconArrowLeft, IconCheck,
    IconAlertTriangle, IconTag, IconPalette, IconClock
} from "@tabler/icons-vue";

type Merk = {
    id: number;
    merk: string;
    kualitas: { id: number; kualitas: string };
};

const props = defineProps<{
    sesi: any;
    pilihan_cacat: Array<{ id: number; cacat: string }>;
    pilihan_merk: Merk[];
    pilihan_warna: Array<{ id: number; warna: string }>;
}>();

const STORAGE_KEY = "scan_checking_cacat_ids";
const nativeInput = ref<HTMLInputElement | null>(null);
const validCacatIds = new Set(props.pilihan_cacat.map((item) => item.id));
const savedCacatIds = (JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]") as number[])
    .filter((id) => validCacatIds.has(id));

localStorage.setItem(STORAGE_KEY, JSON.stringify(savedCacatIds));

const form = useForm({
    qr: "",
    cacat_ids: savedCacatIds,
    merk_id: null as number | null,
    warna_id: null as number | null,
});

const selectedMerk = computed(() => props.pilihan_merk.find((merk) => merk.id === form.merk_id));

const gradeLabel = (grade: string) => {
    if (grade.toUpperCase().includes("KW3")) return "KW3";
    if (grade.toUpperCase().includes("SG")) return "SG";
    if (grade.toUpperCase().includes("AB")) return "AB";
    if (grade.toUpperCase().includes("FG")) return "FG";
    return grade;
};

const gradeClass = (grade: string) => {
    const label = gradeLabel(grade);
    if (label === "FG") return "bg-green-100 text-green-700 border-green-200";
    if (label === "SG") return "bg-blue-100 text-blue-700 border-blue-200";
    if (label === "AB") return "bg-amber-100 text-amber-800 border-amber-200";
    if (label === "KW3") return "bg-red-100 text-red-700 border-red-200";
    return "bg-slate-100 text-slate-700 border-slate-200";
};

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

const resetCacat = () => {
    form.cacat_ids = [];
    localStorage.removeItem(STORAGE_KEY);
    focusInput();
};

const toggleCacat = (id: number) => {
    const idx = form.cacat_ids.indexOf(id);
    idx > -1 ? form.cacat_ids.splice(idx, 1) : form.cacat_ids.push(id);
    focusInput();
};

const handleScan = () => {
    if (!form.qr || form.processing) return;
    form.post(route("scan.checking.inproses_store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.qr = "";
            resetCacat();
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
    <Head title="Scan Checking In-Proses" />

    <div class="p-4 max-w-5xl mx-auto space-y-4" @click="focusInput">
        <div class="flex items-center justify-between bg-white p-2 rounded-lg shadow-sm border">
            <Link :href="route('scan.index')" class="flex items-center text-xs font-bold text-slate-500">
                <IconArrowLeft class="size-4 mr-1" /> KEMBALI
            </Link>
            <div class="flex gap-1">
                <Button as-child variant="outline" size="sm" class="h-8 text-[10px] border-blue-200 text-blue-600">
                    <Link :href="route('scan.checking')">MODE OK</Link>
                </Button>
                <Button size="sm" class="h-8 text-[10px] bg-orange-500 hover:bg-orange-600 shadow-md">IN PROSES</Button>
                <Button as-child variant="outline" size="sm" class="h-8 text-[10px] border-red-200 text-red-600">
                    <Link :href="route('scan.checking.mode', 'buang')">BUANG</Link>
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <div class="flex items-center bg-orange-50 border-2 border-orange-200 p-2 rounded-xl shadow-inner">
                    <IconScan class="size-6 text-orange-500 ml-2 mr-3" />
                    <input
                    ref="nativeInput"
                    v-model="form.qr"
                    :disabled="form.processing"
                    type="text"
                    class="flex-1 bg-transparent border-none focus:ring-0 text-xl font-black uppercase tracking-widest text-orange-700 placeholder:text-orange-200"
                    placeholder="SCAN QR IN-PROSES DISINI..."
                    @keyup.enter="handleScan"
                    @input="form.qr = form.qr.toUpperCase()"
                    @blur="focusInput"
                    autocomplete="off"
                />
                </div>
                <p v-if="form.errors.qr || form.errors.error || form.errors.cacat_ids || form.errors.merk_id || form.errors.jenis_mismatch" class="mt-2 text-sm text-red-600 text-center font-bold">
                    {{ form.errors.qr || form.errors.error || form.errors.cacat_ids || form.errors.merk_id || form.errors.jenis_mismatch }}
                </p>
            </div>
            <div class="bg-orange-900 text-white p-3 rounded-xl flex flex-col justify-center items-center shadow-lg border-b-4 border-orange-950">
                <span class="text-[9px] uppercase opacity-70 tracking-tighter font-bold">Sesi / Proses</span>
                <span v-if="sesi" class="font-black text-sm">
                    <IconClock class="inline size-3 mr-1" />{{ sesi.proses?.proses }} · {{ sesi.jenis }}
                </span>
                <span v-else class="font-black text-xs">Belum ada sesi aktif</span>
            </div>
        </div>

        <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
            <div class="bg-orange-600 px-3 py-1.5 flex items-center justify-between gap-2">
                <span class="flex items-center gap-2 text-[10px] font-bold text-white uppercase">
                    <IconAlertTriangle class="size-3" /> Cacat (Opsional)
                </span>
                <button type="button" @click="resetCacat" class="text-[10px] font-bold text-white underline">BERSIHKAN PILIHAN</button>
            </div>
            <div class="p-3 flex flex-wrap gap-2 max-h-[180px] overflow-y-auto">
                <button
                    v-for="item in pilihan_cacat" :key="item.id"
                    type="button"
                    @click="toggleCacat(item.id)"
                    :class="['px-4 py-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center gap-2',
                             form.cacat_ids.includes(item.id) ? 'bg-orange-500 border-orange-600 text-white shadow-md scale-105' : 'bg-slate-50 border-slate-100 text-slate-500']"
                >
                    <IconCheck v-if="form.cacat_ids.includes(item.id)" class="size-3" />
                    {{ item.cacat }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
                <div class="bg-orange-600 px-3 py-1.5 flex items-center gap-2">
                    <IconTag class="size-3 text-white" />
                    <span class="text-[10px] font-bold text-white uppercase">Pilih Merek</span>
                </div>
                <div class="p-3 grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <button
                        v-for="merk in pilihan_merk" :key="merk.id"
                        type="button"
                        @click="form.merk_id = merk.id; focusInput()"
                        :class="['min-h-20 px-4 py-3 rounded-xl border-2 transition-all flex flex-col items-center justify-center gap-2',
                                 form.merk_id === merk.id ? 'bg-orange-500 border-orange-600 text-white shadow-md scale-[1.02]' : 'bg-slate-50 border-slate-100 text-slate-700']"
                    >
                        <span class="text-base font-black">{{ merk.merk }}</span>
                        <span :class="['px-2 py-0.5 rounded-full border text-[10px] font-black', gradeClass(merk.kualitas.kualitas)]">
                            {{ gradeLabel(merk.kualitas.kualitas) }}
                        </span>
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

        <div class="bg-orange-50 border border-orange-200 p-3 rounded-xl text-xs text-orange-800">
            Merek: <strong>{{ selectedMerk?.merk || "Belum dipilih" }}</strong> · Grade:
            <strong>{{ selectedMerk ? gradeLabel(selectedMerk.kualitas.kualitas) : "-" }}</strong>
        </div>

        <div v-if="form.processing" class="fixed inset-0 bg-orange-900/40 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-white p-5 rounded-2xl shadow-2xl flex flex-col items-center border-t-4 border-orange-600">
                <IconLoader2 class="size-8 animate-spin text-orange-600 mb-2" />
                <span class="text-xs font-black uppercase tracking-widest text-orange-900">Memproses...</span>
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
