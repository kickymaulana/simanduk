<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed, nextTick, onMounted, ref } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { IconBox } from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });

type Merk = {
    id: number;
    kualitas_id: number;
    merk: string;
    kualitas: { id: number; kualitas: string };
};

type Item = { id: number; [key: string]: string | number };
type Aksesori = { id: number; part_number: string; description: string; stok: number };
type AccessorySelection = { aksesori_id: number; quantity: number };

const props = defineProps<{
    merks: Merk[];
    warnas: Item[];
    gudangs: Item[];
    aksesoris: Aksesori[];
}>();

const qrInput = ref<HTMLInputElement | null>(null);
const form = useForm({
    qrcode_kardus: "",
    jenis: "Body",
    merk_id: "",
    warna_id: "",
    gudang_id: "",
    accessories: [] as AccessorySelection[],
});

const selectedMerk = computed(() => props.merks.find(item => String(item.id) === String(form.merk_id)));
const selectedAccessory = (id: number) => form.accessories.find(item => item.aksesori_id === id);

const toggleAccessory = (id: number, checked: boolean) => {
    form.clearErrors("accessories");

    if (checked) {
        if (!selectedAccessory(id)) {
            form.accessories.push({ aksesori_id: id, quantity: 1 });
        }

        return;
    }

    form.accessories = form.accessories.filter(item => item.aksesori_id !== id);
};

const focusQr = () => nextTick(() => qrInput.value?.focus());
const showScanError = (message: string) => window.dispatchEvent(new CustomEvent("scan-packing-error", {
    detail: { message, qr: form.qrcode_kardus },
}));

const validateBeforeScan = () => {
    form.clearErrors();

    const requiredFields = [
        ["qrcode_kardus", form.qrcode_kardus, "QR kardus wajib dipindai."],
        ["merk_id", form.merk_id, "Pilih merk sebelum memindai QR."],
        ["warna_id", form.warna_id, "Pilih warna sebelum memindai QR."],
        ["gudang_id", form.gudang_id, "Pilih gudang sebelum memindai QR."],
    ] as const;

    requiredFields.forEach(([field, value, message]) => {
        if (!String(value).trim()) {
            form.setError(field, message);
        }
    });

    if (!form.accessories.length) {
        form.setError("accessories", "Pilih minimal satu aksesori sebelum memindai QR.");
    }

    if (form.accessories.some(item => !Number.isInteger(Number(item.quantity)) || Number(item.quantity) < 1)) {
        form.setError("accessories", "Quantity aksesori minimal 1.");
    }

    const melebihiStok = form.accessories.find(selection => {
        const aksesori = props.aksesoris.find(item => item.id === selection.aksesori_id);
        return aksesori && Number(selection.quantity) > aksesori.stok;
    });

    if (melebihiStok) {
        const aksesori = props.aksesoris.find(item => item.id === melebihiStok.aksesori_id)!;
        form.setError("accessories", `Quantity ${aksesori.part_number} melebihi stok tersedia (${aksesori.stok}).`);
    }

    return !form.hasErrors;
};

const submitFromScanner = () => {
    form.qrcode_kardus = form.qrcode_kardus.trim().toUpperCase();

    if (!validateBeforeScan()) {
        showScanError(Object.values(form.errors)[0] || "Data packing belum lengkap.");
        focusQr();
        return;
    }

    form.post(route("scan.packing.store"), {
        preserveScroll: true,
        onSuccess: () => {
            form.qrcode_kardus = "";
            form.clearErrors();
            focusQr();
        },
        onError: (errors) => {
            showScanError(Object.values(errors)[0] || "Data packing gagal disimpan.");
            focusQr();
        },
        onFinish: focusQr,
    });
};

onMounted(focusQr);
</script>

<template>
    <Head title="Scan Packing" />
    <div class="flex justify-center p-4 md:p-8">
        <Card class="w-full max-w-3xl">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <IconBox class="size-6 text-primary" />
                    Scan Packing
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Jenis</Label>
                            <select v-model="form.jenis" class="w-full rounded-md border p-2">
                                <option value="Body">BODY</option>
                                <option value="Tangki">TANGKI</option>
                            </select>
                            <p class="text-sm text-destructive">{{ form.errors.jenis }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label>Merk</Label>
                            <select v-model="form.merk_id" class="w-full rounded-md border p-2">
                                <option value="">Pilih merk</option>
                                <option v-for="item in merks" :key="item.id" :value="String(item.id)">{{ item.merk }}</option>
                            </select>
                            <p class="text-sm text-destructive">{{ form.errors.merk_id }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label>Grade</Label>
                            <div class="min-h-10 rounded-md border bg-muted px-3 py-2 text-sm">
                                {{ selectedMerk?.kualitas.kualitas || "Otomatis sesuai merk" }}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label>Warna</Label>
                            <select v-model="form.warna_id" class="w-full rounded-md border p-2">
                                <option value="">Pilih warna</option>
                                <option v-for="item in warnas" :key="item.id" :value="String(item.id)">{{ item.warna }}</option>
                            </select>
                            <p class="text-sm text-destructive">{{ form.errors.warna_id }}</p>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>Gudang</Label>
                            <select v-model="form.gudang_id" class="w-full rounded-md border p-2">
                                <option value="">Pilih gudang</option>
                                <option v-for="item in gudangs" :key="item.id" :value="String(item.id)">{{ item.gudang }}</option>
                            </select>
                            <p class="text-sm text-destructive">{{ form.errors.gudang_id }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <Label>Aksesori</Label>
                            <p class="text-sm text-muted-foreground">Pilih minimal satu aksesori dan sesuaikan quantity.</p>
                        </div>
                        <div class="space-y-2 rounded-md border p-3">
                            <div v-for="item in aksesoris" :key="item.id" class="grid grid-cols-[auto_1fr_6rem] items-center gap-3 rounded-md border p-3">
                                <input
                                    :id="`aksesori-${item.id}`"
                                    type="checkbox"
                                    class="size-4"
                                    :checked="Boolean(selectedAccessory(item.id))"
                                    :disabled="item.stok < 1"
                                    @change="toggleAccessory(item.id, ($event.target as HTMLInputElement).checked)"
                                />
                                <Label :for="`aksesori-${item.id}`" class="cursor-pointer">
                                    <span>{{ item.part_number }} - {{ item.description }}</span>
                                    <span class="block text-xs text-muted-foreground">Stok tersedia: {{ item.stok }}</span>
                                </Label>
                                <Input
                                    v-if="selectedAccessory(item.id)"
                                    v-model.number="selectedAccessory(item.id)!.quantity"
                                    type="number"
                                    min="1"
                                    :max="item.stok"
                                    :disabled="item.stok < 1"
                                    aria-label="Quantity aksesori"
                                />
                                <span v-else />
                            </div>
                            <p v-if="!aksesoris.length" class="py-4 text-center text-sm text-muted-foreground">Belum ada aksesori aktif.</p>
                        </div>
                        <p class="text-sm text-destructive">{{ form.errors.accessories }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="qrcode_kardus">QR Kardus</Label>
                        <Input
                            id="qrcode_kardus"
                            ref="qrInput"
                            v-model="form.qrcode_kardus"
                            autocomplete="off"
                            placeholder="Scan QR lalu tekan Enter"
                            :disabled="form.processing"
                            @keydown.enter.prevent="submitFromScanner"
                        />
                        <p class="text-sm text-destructive">{{ form.errors.qrcode_kardus }}</p>
                        <p v-if="form.errors.error" class="text-sm text-destructive">{{ form.errors.error }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
