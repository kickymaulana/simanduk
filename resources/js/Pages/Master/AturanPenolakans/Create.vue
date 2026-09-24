<script setup lang="ts">
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import {
    IconArrowLeft,
    IconDeviceFloppy,
    IconLoader2,
    IconCheck,
    IconSelector,
} from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{ cacats: any[]; proses: any[] }>();

const form = useForm({
    cacat_id: "",
    proses_toleransi: "",
    proses_buang: "",
    proses_pemeriksa: "",
});

// State untuk Popover Combobox
const openCacat = ref(false);

// Computed untuk label Cacat yang dipilih
const selectedCacatLabel = computed(() => {
    const cacat = props.cacats.find((c) => c.id.toString() === form.cacat_id);
    return cacat ? `${cacat.cacat} — ${cacat.jenis}` : "Pilih Jenis Cacat...";
});

const submit = () => {
    form.post(route('aturanpenolakans.store'));
};
</script>

<template>
    <Head title="Tambah Aturan" />
    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" as-child class="rounded-full">
                <Link :href="route('aturanpenolakans.index')">
                    <IconArrowLeft class="size-4" />
                </Link>
            </Button>
            <h2 class="text-3xl font-bold tracking-tight">Buat Aturan Baru</h2>
        </div>

        <div class="max-w-4xl">
            <Card class="border-none shadow-lg">
                <CardHeader class="border-b">
                    <CardTitle class="text-primary text-lg">
                        Konfigurasi Alur Penolakan
                    </CardTitle>
                </CardHeader>

                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                            <!-- BARIS 1: JENIS CACAT (SEARCHABLE COMBOBOX) -->
                            <div class="grid gap-2 md:col-span-3">
                                <Label>Pilih Jenis Cacat</Label>
                                <Popover v-model:open="openCacat">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            :aria-expanded="openCacat"
                                            class="w-full justify-between font-normal h-10"
                                            :class="{ 'border-destructive': form.errors.cacat_id }"
                                        >
                                            {{ selectedCacatLabel }}
                                            <IconSelector class="ml-2 size-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-[--radix-popover-trigger-width] p-0">
                                        <Command>
                                            <CommandInput placeholder="Ketik untuk mencari..." />
                                            <CommandList>
                                                <CommandEmpty>Jenis cacat tidak ditemukan.</CommandEmpty>
                                                <CommandGroup>
                                                    <CommandItem
                                                        v-for="c in cacats"
                                                        :key="c.id"
                                                        :value="`${c.cacat} ${c.jenis}`"
                                                        @select="() => {
                                                            form.cacat_id = c.id.toString();
                                                            openCacat = false;
                                                        }"
                                                    >
                                                        <IconCheck
                                                            :class="cn(
                                                                'mr-2 size-4',
                                                                form.cacat_id === c.id.toString() ? 'opacity-100' : 'opacity-0'
                                                            )"
                                                        />
                                                        {{ c.cacat }} — {{ c.jenis }}
                                                    </CommandItem>
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                                <p v-if="form.errors.cacat_id" class="text-xs text-destructive italic">
                                    {{ form.errors.cacat_id }}
                                </p>
                            </div>

                            <!-- BARIS 2 KOLOM 1: DEPARTEMEN TOLERANSI -->
                            <div class="grid gap-2">
                                <Label>Departemen Toleransi</Label>
                                <Select v-model="form.proses_toleransi">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.proses_toleransi }">
                                        <SelectValue placeholder="Pilih Departemen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="d in proses" :key="d.id" :value="d.id.toString()">
                                            {{ d.proses }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.proses_toleransi" class="text-xs text-destructive italic">
                                    {{ form.errors.proses_toleransi }}
                                </p>
                            </div>

                            <!-- BARIS 2 KOLOM 2: DEPARTEMEN BUANG -->
                            <div class="grid gap-2">
                                <Label>Departemen Buang (Reject)</Label>
                                <Select v-model="form.proses_buang">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.proses_buang }">
                                        <SelectValue placeholder="Pilih Departemen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="d in proses" :key="d.id" :value="d.id.toString()">
                                            {{ d.proses }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.proses_buang" class="text-xs text-destructive italic">
                                    {{ form.errors.proses_buang }}
                                </p>
                            </div>

                            <!-- BARIS 2 KOLOM 3: DEPARTEMEN PEMERIKSA -->
                            <div class="grid gap-2">
                                <Label>Departemen Pemeriksa</Label>
                                <Select v-model="form.proses_pemeriksa">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.proses_pemeriksa }">
                                        <SelectValue placeholder="Pilih Departemen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="d in proses" :key="d.id" :value="d.id.toString()">
                                            {{ d.proses }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.proses_pemeriksa" class="text-xs text-destructive italic">
                                    {{ form.errors.proses_pemeriksa }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-4 border-t">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-primary h-11"
                            >
                                <IconLoader2 v-if="form.processing" class="mr-2 animate-spin" />
                                <IconDeviceFloppy v-else class="mr-2 size-4" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
