<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from "@/components/ui/card";
import {
    IconArrowLeft,
    IconDeviceFloppy,
    IconLoader2,
    IconBuildingCommunity,
    IconListNumbers,
} from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });

// Ambil data departemen dari props
const props = defineProps<{
    departemens: Array<{ id: number; departemen: string }>;
}>();

const form = useForm({
    departemen_id: "",
    proses: "",
    urutan: "",
    jenis: "",
});

const submit = () => {
    form.post(route("proses.store"));
};
</script>

<template>
    <Head title="Tambah Proses" />

    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div class="flex items-center gap-4">
            <Button
                variant="outline"
                size="icon"
                as-child
                class="rounded-full shadow-sm"
            >
                <Link :href="route('proses.index')">
                    <IconArrowLeft class="size-4" />
                </Link>
            </Button>
            <h2 class="text-3xl font-bold tracking-tight">
                Tambah Alur Proses
            </h2>
        </div>

        <div class="max-w-2xl">
            <Card class="border-none shadow-lg">
                <CardHeader>
                    <CardTitle class="text-primary"
                        >Form Master Proses</CardTitle
                    >
                    <CardDescription>
                        Tentukan departemen, nama proses, dan urutan
                        pengerjaannya.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="departemen_id">Departemen</Label>
                            <div class="relative">
                                <select
                                    id="departemen_id"
                                    v-model="form.departemen_id"
                                    class="flex h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring shadow-sm appearance-none"
                                    :class="{
                                        'border-destructive':
                                            form.errors.departemen_id,
                                    }"
                                >
                                    <option value="" disabled>
                                        Pilih Departemen
                                    </option>
                                    <option
                                        v-for="dept in departemens"
                                        :key="dept.id"
                                        :value="dept.id"
                                    >
                                        {{ dept.departemen }}
                                    </option>
                                </select>
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground"
                                >
                                    <IconBuildingCommunity class="size-4" />
                                </div>
                            </div>
                            <p
                                v-if="form.errors.departemen_id"
                                class="text-sm text-destructive font-medium italic"
                            >
                                {{ form.errors.departemen_id }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="proses">Nama Proses</Label>
                            <Input
                                id="proses"
                                v-model="form.proses"
                                placeholder="Contoh: PENGECEKAN FINAL"
                                class="h-11"
                                :class="{
                                    'border-destructive': form.errors.proses,
                                }"
                            />
                            <p
                                v-if="form.errors.proses"
                                class="text-sm text-destructive font-medium italic"
                            >
                                {{ form.errors.proses }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="urutan" class="flex items-center gap-2">
                                <IconListNumbers class="size-4" /> Urutan
                                Pengerjaan
                            </Label>
                            <Input
                                id="urutan"
                                type="number"
                                v-model="form.urutan"
                                placeholder="Masukkan angka urutan (1, 2, dst)"
                                class="h-11"
                                :class="{
                                    'border-destructive': form.errors.urutan,
                                }"
                            />
                            <p
                                v-if="form.errors.urutan"
                                class="text-sm text-destructive font-medium italic"
                            >
                                {{ form.errors.urutan }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="jenis">Jenis Produk (opsional)</Label>
                            <div class="relative">
                                <select
                                    id="jenis"
                                    v-model="form.jenis"
                                    class="flex h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring shadow-sm appearance-none"
                                >
                                    <option value="">
                                        Semua (Body & Tangki)
                                    </option>
                                    <option value="Body">Body</option>
                                    <option value="Tangki">Tangki</option>
                                </select>
                            </div>
                            <p
                                v-if="form.errors.jenis"
                                class="text-sm text-destructive font-medium italic"
                            >
                                {{ form.errors.jenis }}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-primary hover:bg-primary/90 shadow-md transition-all active:scale-95"
                        >
                            <IconLoader2
                                v-if="form.processing"
                                class="mr-2 animate-spin size-4"
                            />
                            <IconDeviceFloppy v-else class="mr-2 size-4" />
                            Simpan Alur Proses
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
