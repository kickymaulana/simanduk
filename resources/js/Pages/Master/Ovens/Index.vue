<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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
import { IconPlus, IconPencil, IconSearch, IconX, IconTrash, IconFlame } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    ovens: {
        data: Array<{ id: number; oven: string; created_at: string }>;
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: { search: string };
}>();

const search = ref(props.filters.search || "");
let timeout: any;

watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route("ovens.index"),
            { search: value },
            { preserveState: true, replace: true },
        );
    }, 500);
});

const clearSearch = () => {
    search.value = "";
};

const deleteOven = (id: number) => {
    router.delete(route("ovens.destroy", id));
};

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Master Oven" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6">
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconFlame class="size-6 text-primary" />
                    Daftar Oven
                </CardTitle>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-72">
                        <IconSearch class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Cari nama oven..." class="pl-10 pr-10" />
                        <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <IconX class="size-4" />
                        </button>
                    </div>

                    <Button as-child class="bg-primary hover:bg-primary/90 shadow-md transition-all active:scale-95">
                        <Link :href="route('ovens.create')">
                            <IconPlus class="mr-2 size-4" />
                            Tambah Oven
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead>Nama Oven</TableHead>
                                <TableHead class="hidden md:table-cell text-center">Dibuat Pada</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="ovens.data.length === 0">
                                <TableCell colspan="3" class="h-24 text-center text-muted-foreground italic">
                                    Data oven tidak ditemukan.
                                </TableCell>
                            </TableRow>

                            <TableRow v-for="item in ovens.data" :key="item.id" class="hover:bg-muted/30 transition-colors">
                                <TableCell class="font-bold text-primary uppercase tracking-wide">
                                    {{ item.oven }}
                                </TableCell>
                                <TableCell class="hidden md:table-cell text-center text-muted-foreground text-sm">
                                    {{ new Date(item.created_at).toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" }) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" class="size-8 hover:text-primary transition-colors" as-child>
                                            <Link :href="route('ovens.edit', item.id)">
                                                <IconPencil class="size-4" />
                                            </Link>
                                        </Button>
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button variant="ghost" size="icon" class="size-8 hover:text-destructive transition-colors text-muted-foreground">
                                                    <IconTrash class="size-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Hapus Oven?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Oven <b>{{ item.oven }}</b> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Batal</AlertDialogCancel>
                                                    <AlertDialogAction class="bg-destructive text-white hover:bg-destructive/90" @click="deleteOven(item.id)">
                                                        Ya, Hapus
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mt-6">
                    <p class="text-xs text-muted-foreground italic font-medium">
                        Menampilkan {{ ovens.from ?? 0 }} - {{ ovens.to ?? 0 }} dari {{ ovens.total }} data oven
                    </p>

                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in ovens.links" :key="k">
                            <Button v-if="link.url === null" variant="outline" size="sm" disabled class="opacity-50 text-xs px-3 h-8" v-html="cleanLabel(link.label)" />
                            <Button v-else as-child variant="outline" size="sm" class="text-xs px-3 h-8 transition-all" :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-sm': link.active }">
                                <Link :href="link.url" v-html="cleanLabel(link.label)" />
                            </Button>
                        </template>
                    </nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
