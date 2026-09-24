<script setup lang="ts">
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
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
    IconDotsVertical,
    IconTrash,
    IconCheck,
    IconSelector,
    IconLoader2
} from "@tabler/icons-vue";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{ aturan: any; cacats: any[]; proses: any[] }>();

const form = useForm({
    cacat_id: props.aturan.cacat_id.toString(),
    proses_toleransi: props.aturan.proses_toleransi.toString(),
    proses_buang: props.aturan.proses_buang.toString(),
    proses_pemeriksa: props.aturan.proses_pemeriksa.toString(),
});

// State untuk Popover Combobox
const openCacat = ref(false);

// Computed untuk menampilkan label cacat yang sedang dipilih (berdasarkan props awal atau perubahan form)
const selectedCacatLabel = computed(() => {
    const cacat = props.cacats.find((c) => c.id.toString() === form.cacat_id);
    return cacat ? `${cacat.cacat} — ${cacat.jenis}` : "Pilih Jenis Cacat...";
});

const submit = () => {
    form.put(route('aturanpenolakans.update', props.aturan.id));
};
</script>

<template>
    <Head title="Edit Aturan" />
    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div class="flex items-center justify-between max-w-4xl">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child class="rounded-full">
                    <Link :href="route('aturanpenolakans.index')">
                        <IconArrowLeft class="size-4" />
                    </Link>
                </Button>
                <h2 class="text-3xl font-bold tracking-tight">Edit Aturan</h2>
            </div>
        </div>

        <div class="max-w-4xl">
            <Card class="border-none shadow-lg">
                <CardHeader class="flex flex-row items-center justify-between border-b">
                    <CardTitle class="text-primary">Update Konfigurasi</CardTitle>

                    <AlertDialog>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon">
                                    <IconDotsVertical class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <AlertDialogTrigger as-child>
                                    <DropdownMenuItem class="text-destructive">
                                        <IconTrash class="mr-2 size-4" />
                                        Hapus Aturan
                                    </DropdownMenuItem>
                                </AlertDialogTrigger>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Hapus Aturan Ini?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Tindakan ini permanen dan tidak dapat dibatalkan.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Batal</AlertDialogCancel>
                                <AlertDialogAction
                                    @click="router.delete(route('aturanpenolakans.destroy', props.aturan.id))"
                                    class="bg-destructive text-white hover:bg-destructive/90"
                                >
                                    Ya, Hapus
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </CardHeader>

                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Grid Container -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                            <!-- Baris 1: Jenis Cacat (SEARCHABLE COMBOBOX) -->
                            <div class="grid gap-2 md:col-span-3">
                                <Label>Jenis Cacat</Label>
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
                                            <CommandInput placeholder="Cari jenis cacat..." />
                                            <CommandList>
                                                <CommandEmpty>Data tidak ditemukan.</CommandEmpty>
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

                            <!-- Baris 2 Kolom 1: Departemen Toleransi -->
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
                            </div>

                            <!-- Baris 2 Kolom 2: Departemen Buang -->
                            <div class="grid gap-2">
                                <Label>Departemen Buang</Label>
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
                            </div>

                            <!-- Baris 2 Kolom 3: Departemen Pemeriksa -->
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
                            </div>
                        </div>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-primary h-11"
                        >
                            <IconLoader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                            <IconDeviceFloppy v-else class="mr-2 size-4" />
                            Update Aturan
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
