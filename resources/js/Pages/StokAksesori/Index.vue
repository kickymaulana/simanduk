<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Head, useForm } from "@inertiajs/vue3";
import { IconPackageImport } from "@tabler/icons-vue";

interface Aksesori {
    id: number;
    part_number: string;
    description: string;
    stok: number;
    is_active: boolean;
}

interface Riwayat {
    id: number;
    tipe: "MASUK" | "KELUAR_PACKING";
    quantity: number;
    stok_sebelum: number;
    stok_sesudah: number;
    keterangan: string | null;
    created_at: string;
    aksesori: Pick<Aksesori, "part_number" | "description">;
    packing: { qrcode_kardus: string } | null;
    user: { name: string };
}

defineOptions({ layout: AuthenticatedLayout });
defineProps<{ aksesoris: Aksesori[]; riwayat: Riwayat[] }>();

const form = useForm({
    aksesori_id: "",
    quantity: 1,
    keterangan: "",
});

const submit = () => {
    if (!form.aksesori_id) {
        form.setError("aksesori_id", "Aksesori wajib dipilih.");
        return;
    }

    form.post(route("stok.aksesori.store", form.aksesori_id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const formatWaktu = (value: string) => new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
}).format(new Date(value));
</script>

<template>
    <Head title="Stok Aksesori" />
    <div class="space-y-6 p-4 md:p-8">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <IconPackageImport class="size-6 text-primary" />
                    Stok Aksesori
                </CardTitle>
            </CardHeader>
            <CardContent>
                <form class="grid gap-4 md:grid-cols-[2fr_1fr_2fr_auto] md:items-end" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="aksesori_id">Aksesori</Label>
                        <select id="aksesori_id" v-model="form.aksesori_id" class="w-full rounded-md border p-2">
                            <option value="">Pilih aksesori</option>
                            <option v-for="item in aksesoris" :key="item.id" :value="String(item.id)">
                                {{ item.part_number }} - {{ item.description }}
                            </option>
                        </select>
                        <p class="text-sm text-destructive">{{ form.errors.aksesori_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="quantity">Quantity Masuk</Label>
                        <Input id="quantity" v-model.number="form.quantity" type="number" min="1" step="1" />
                        <p class="text-sm text-destructive">{{ form.errors.quantity }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="keterangan">Keterangan</Label>
                        <Input id="keterangan" v-model="form.keterangan" maxlength="255" placeholder="Opsional" />
                        <p class="text-sm text-destructive">{{ form.errors.keterangan }}</p>
                    </div>
                    <Button type="submit" :disabled="form.processing">Tambah Stok</Button>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>Saldo Saat Ini</CardTitle></CardHeader>
            <CardContent>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="p-3 text-left">Part Number</th>
                                <th class="p-3 text-left">Description</th>
                                <th class="p-3 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in aksesoris" :key="item.id" class="border-t">
                                <td class="p-3 font-medium">{{ item.part_number }}</td>
                                <td class="p-3">{{ item.description }}</td>
                                <td class="p-3 text-right text-lg font-semibold">{{ item.stok }}</td>
                            </tr>
                            <tr v-if="!aksesoris.length">
                                <td colspan="3" class="p-8 text-center text-muted-foreground">Belum ada aksesori.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>Riwayat Pergerakan Terbaru</CardTitle></CardHeader>
            <CardContent>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="p-3 text-left">Waktu</th>
                                <th class="p-3 text-left">Aksesori</th>
                                <th class="p-3 text-left">Tipe</th>
                                <th class="p-3 text-right">Jumlah</th>
                                <th class="p-3 text-right">Sebelum</th>
                                <th class="p-3 text-right">Sesudah</th>
                                <th class="p-3 text-left">Operator</th>
                                <th class="p-3 text-left">QR Packing</th>
                                <th class="p-3 text-left">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in riwayat" :key="item.id" class="border-t">
                                <td class="whitespace-nowrap p-3">{{ formatWaktu(item.created_at) }}</td>
                                <td class="p-3">
                                    <div class="font-medium">{{ item.aksesori.part_number }}</div>
                                    <div class="text-muted-foreground">{{ item.aksesori.description }}</div>
                                </td>
                                <td class="whitespace-nowrap p-3">{{ item.tipe }}</td>
                                <td class="p-3 text-right">{{ item.quantity }}</td>
                                <td class="p-3 text-right">{{ item.stok_sebelum }}</td>
                                <td class="p-3 text-right font-medium">{{ item.stok_sesudah }}</td>
                                <td class="whitespace-nowrap p-3">{{ item.user.name }}</td>
                                <td class="whitespace-nowrap p-3">{{ item.packing?.qrcode_kardus || "-" }}</td>
                                <td class="p-3">{{ item.keterangan || "-" }}</td>
                            </tr>
                            <tr v-if="!riwayat.length">
                                <td colspan="9" class="p-8 text-center text-muted-foreground">Belum ada riwayat pergerakan stok.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
