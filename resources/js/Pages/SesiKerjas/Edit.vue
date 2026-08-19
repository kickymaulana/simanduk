<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    IconArrowLeft,
    IconDeviceFloppy,
    IconLoader2,
    IconEdit,
    IconUsers,
    IconClock,
    IconSettings,
    IconCategory,
    IconTarget,
} from "@tabler/icons-vue";

const props = defineProps<{
    sesikerja: {
        id: number;
        shift_id: number;
        proses_id: number; // Tambahkan ini
        jenis: string;
        sesi_kerja_members: Array<{ user_id: number }>;
    };
    shifts: Array<{ id: number; shift: string }>;
    prosesList: Array<{ id: number; proses: string }>;
    users: Array<{ id: number; name: string }>;
}>();

defineOptions({ layout: AuthenticatedLayout });

const existingMemberIds = props.sesikerja.sesi_kerja_members.map((m) => m.user_id);

const form = useForm({
    shift_id: props.sesikerja.shift_id,
    proses_id: props.sesikerja.proses_id, // Masukkan data lama
    jenis: props.sesikerja.jenis,
    user_ids: existingMemberIds,
    target: props.sesikerja.target ?? "",
});

const submit = () => {
    form.put(route("sesikerjas.update", props.sesikerja.id));
};
</script>

<template>
    <Head title="Edit Sesi Kerja" />

    <div class="flex flex-col gap-6 p-4 md:p-8 pt-4">
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" as-child class="rounded-full shadow-sm">
                <Link :href="route('sesikerjas.index')">
                    <IconArrowLeft class="size-4" />
                </Link>
            </Button>
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-foreground">Edit Sesi Kerja</h2>
                <p class="text-muted-foreground text-sm">Perbarui informasi sesi kerja yang sudah berjalan.</p>
            </div>
        </div>

        <div class="max-w-5xl w-full">
            <Card class="border-none shadow-xl bg-card text-card-foreground">
                <CardHeader class="pb-4">
                    <CardTitle class="text-primary text-lg flex items-center gap-2">
                        <IconEdit class="size-5" />
                        Ubah Konfigurasi Sesi
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submit" class="space-y-8">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 bg-muted/20 rounded-xl border border-border/50">
                            <div class="grid gap-2">
                                <Label class="flex items-center gap-2 font-semibold">
                                    <IconClock class="size-4 text-primary" /> Shift
                                </Label>
                                <Select v-model="form.shift_id">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.shift_id }">
                                        <SelectValue placeholder="Pilih Shift" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="s in shifts" :key="s.id" :value="s.id">
                                            {{ s.shift }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.shift_id" class="text-[10px] text-destructive font-medium italic">
                                    {{ form.errors.shift_id }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="flex items-center gap-2 font-semibold">
                                    <IconSettings class="size-4 text-primary" /> Proses
                                </Label>
                                <Select v-model="form.proses_id">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.proses_id }">
                                        <SelectValue placeholder="Pilih Proses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="p in prosesList" :key="p.id" :value="p.id">
                                            {{ p.proses }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.proses_id" class="text-[10px] text-destructive font-medium italic">
                                    {{ form.errors.proses_id }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="flex items-center gap-2 font-semibold">
                                    <IconCategory class="size-4 text-primary" /> Jenis
                                </Label>
                                <Select v-model="form.jenis">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih Jenis" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Body">Body</SelectItem>
                                        <SelectItem value="Tangki">Tangki</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="flex items-center gap-2 font-semibold">
                                <IconTarget class="size-4 text-primary" /> Target Produk
                            </Label>
                            <Input
                                type="number"
                                v-model="form.target"
                                placeholder="Contoh: 50"
                                min="1"
                                class="w-full"
                            />
                            <p class="text-[10px] text-muted-foreground italic">
                                Opsional. Jumlah produk yang diharapkan selesai dalam sesi ini.
                            </p>
                            <p v-if="form.errors.target" class="text-[10px] text-destructive font-medium italic">
                                {{ form.errors.target }}
                            </p>
                        </div>

                        <div class="space-y-4">
                            <Label class="text-base font-bold flex items-center gap-2 px-1">
                                <IconUsers class="size-5 text-primary" />
                                Perbarui Anggota Tim
                            </Label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[300px] overflow-y-auto p-4 bg-muted/30 rounded-xl border shadow-inner">
                                <div
                                    v-for="user in users"
                                    :key="user.id"
                                    class="flex items-center space-x-3 p-3 bg-background border rounded-lg hover:border-primary/50 transition-all group cursor-pointer shadow-sm"
                                >
                                    <input
                                        type="checkbox"
                                        :id="'user-' + user.id"
                                        :value="user.id"
                                        v-model="form.user_ids"
                                        class="size-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer"
                                    />
                                    <label
                                        :for="'user-' + user.id"
                                        class="text-sm font-medium leading-none cursor-pointer w-full group-hover:text-primary transition-colors"
                                    >
                                        {{ user.name }}
                                    </label>
                                </div>
                            </div>
                            <p v-if="form.errors.user_ids" class="text-xs text-destructive italic mt-1">
                                {{ form.errors.user_ids }}
                            </p>
                        </div>

                        <div class="pt-4 flex flex-col md:flex-row gap-3">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full md:w-max md:px-12 bg-primary hover:bg-primary/90 h-12 text-base shadow-lg shadow-primary/20"
                            >
                                <IconLoader2
                                    v-if="form.processing"
                                    class="mr-2 animate-spin size-5"
                                />
                                <IconDeviceFloppy v-else class="mr-2 size-5" />
                                Simpan Perubahan
                            </Button>

                            <Button variant="ghost" as-child class="w-full md:w-max h-12">
                                <Link :href="route('sesikerjas.index')">Batal</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<style scoped>
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
