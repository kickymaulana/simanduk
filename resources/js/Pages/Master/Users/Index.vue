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
import { Badge } from "@/components/ui/badge"; // Tambahkan Badge
import {
    IconUserPlus,
    IconSearch,
    IconX,
    IconEye,
    IconUsers,
} from "@tabler/icons-vue";
import { ref, watch } from "vue";

// 1. Definisikan Persistent Layout
defineOptions({ layout: AuthenticatedLayout });

// 2. Definisi Props
const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            username: string;
            email: string;
            departemen?: {
                departemen: string; // Sesuaikan key jika di database namanya 'departemen'
            };
            roles: Array<{
                id: number;
                name: string;
            }>;
            status?: string;
            created_at: string;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search: string;
    };
}>();

// 3. Logika Pencarian (Search)
const search = ref(props.filters.search || "");

let timeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route("users.index"),
            { search: value },
            { preserveState: true, replace: true },
        );
    }, 500);
});

const clearSearch = () => {
    search.value = "";
};

// 4. Helper Formatter
const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <Card class="border-none shadow-sm">
            <CardHeader
                class="flex flex-col md:flex-row items-start md:items-center justify-between space-y-4 md:space-y-0 pb-6"
            >
                <CardTitle class="text-xl font-bold flex items-center gap-2">
                    <IconUsers class="size-6 text-primary" />
                    Manajemen Pengguna
                </CardTitle>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-72">
                        <IconSearch
                            class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            placeholder="Cari user..."
                            class="pl-10 pr-10"
                        />
                        <button
                            v-if="search"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <IconX class="size-4" />
                        </button>
                    </div>

                    <Button as-child variant="outline" class="text-amber-600 border-amber-200 hover:bg-amber-50">
                        <Link :href="route('users.pending')">
                            <IconUserPlus class="mr-2 size-4" />
                            <span class="hidden sm:inline">Persetujuan</span>
                        </Link>
                    </Button>

                    <Button as-child class="bg-primary hover:bg-primary/90">
                        <Link :href="route('users.create')">
                            <IconUserPlus class="mr-2 size-4" />
                            <span class="hidden sm:inline">Tambah User</span>
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead>Nama</TableHead>
                                <TableHead>Username</TableHead>
                                <TableHead>Departemen</TableHead>
                                <TableHead>Jabatan/Role</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="users.data.length === 0">
                                <TableCell
                                    colspan="6"
                                    class="h-24 text-center text-muted-foreground"
                                >
                                    Data tidak ditemukan.
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell class="font-bold text-primary">{{
                                    user.name
                                }}</TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{{
                                        user.username
                                    }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="font-medium"
                                    >
                                        {{
                                            user.departemen?.departemen ??
                                            "Belum ada"
                                        }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            class="bg-lime-500 text-black hover:bg-lime-600 border-none capitalize text-[10px]"
                                        >
                                            {{ role.name }}
                                        </Badge>
                                        <span
                                            v-if="user.roles.length === 0"
                                            class="text-xs text-muted-foreground italic"
                                        >
                                            No Role
                                        </span>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        v-if="user.status === 'antri'"
                                        class="bg-amber-100 text-amber-700 border border-amber-200"
                                        variant="outline"
                                    >
                                        Antri
                                    </Badge>
                                    <Badge
                                        v-else-if="user.status === 'ditolak'"
                                        class="bg-red-100 text-red-700 border border-red-200"
                                        variant="outline"
                                    >
                                        Ditolak
                                    </Badge>
                                    <Badge
                                        v-else
                                        class="bg-green-100 text-green-700 border border-green-200"
                                        variant="outline"
                                    >
                                        Aktif
                                    </Badge>
                                </TableCell>

                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-8"
                                        as-child
                                    >
                                        <Link
                                            :href="route('users.show', user.id)"
                                        >
                                            <IconEye
                                                class="size-4 text-primary"
                                            />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div
                    class="flex flex-col md:flex-row items-center justify-between gap-4 mt-6"
                >
                    <p class="text-xs text-muted-foreground italic">
                        Menampilkan {{ users.from ?? 0 }} -
                        {{ users.to ?? 0 }} dari {{ users.total }} user
                    </p>

                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in users.links" :key="k">
                            <Button
                                v-if="link.url === null"
                                variant="outline"
                                size="sm"
                                disabled
                                class="opacity-50 text-xs px-3 h-8"
                                v-html="cleanLabel(link.label)"
                            />
                            <Button
                                v-else
                                as-child
                                variant="outline"
                                size="sm"
                                class="text-xs px-3 h-8 transition-all"
                                :class="{
                                    'bg-primary text-primary-foreground hover:bg-primary/90 shadow-md':
                                        link.active,
                                }"
                            >
                                <Link
                                    :href="link.url"
                                    v-html="cleanLabel(link.label)"
                                />
                            </Button>
                        </template>
                    </nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
