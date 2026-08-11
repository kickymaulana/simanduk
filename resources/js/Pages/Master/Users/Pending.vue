<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "vue-sonner";
import { IconUserCheck, IconUserX, IconArrowLeft, IconUsers } from "@tabler/icons-vue";

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            username: string;
            email: string;
            departemen?: { departemen: string };
            created_at: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    roles: Array<{ id: number; name: string }>;
}>();

defineOptions({ layout: AuthenticatedLayout });

// Role yang dipilih per user saat approve
const roleMap = ref<Record<number, string>>({});

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};

const approve = (user: any) => {
    const role = roleMap.value[user.id];
    if (!role) {
        toast.error("Pilih role/jabatan terlebih dahulu.");
        return;
    }
    router.post(route("users.approve", user.id), { role });
};

const reject = (user: any) => {
    router.post(route("users.reject", user.id));
};
</script>

<template>
    <Head title="Persetujuan Pengguna" />

    <div class="flex flex-col gap-4 p-4 md:p-8 pt-4">
        <div class="flex items-center gap-3">
            <Button variant="outline" size="icon" as-child class="rounded-full shadow-sm">
                <Link :href="route('users.index')"><IconArrowLeft class="size-4" /></Link>
            </Button>
            <div>
                <h2 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                    <IconUsers class="size-6 text-primary" />
                    Persetujuan Pengguna
                </h2>
                <p class="text-sm text-muted-foreground">
                    User yang baru mendaftar dan menunggu persetujuan.
                </p>
            </div>
        </div>

        <Card class="border-none shadow-sm">
            <CardContent class="pt-6">
                <div class="rounded-lg border overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead>Nama</TableHead>
                                <TableHead>Username</TableHead>
                                <TableHead>Departemen</TableHead>
                                <TableHead>Role / Jabatan</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="users.data.length === 0">
                                <TableCell colspan="5" class="h-24 text-center text-muted-foreground italic">
                                    Tidak ada user yang menunggu persetujuan.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="user in users.data" :key="user.id" class="hover:bg-muted/30">
                                <TableCell class="font-bold text-primary">{{ user.name }}</TableCell>
                                <TableCell><Badge variant="secondary">{{ user.username }}</Badge></TableCell>
                                <TableCell>{{ user.departemen?.departemen ?? 'Belum ada' }}</TableCell>
                                <TableCell>
                                    <Select v-model="roleMap[user.id]">
                                        <SelectTrigger class="w-48">
                                            <SelectValue placeholder="Pilih role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="role in roles" :key="role.id" :value="String(role.id)">
                                                {{ role.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button size="sm" class="gap-1 bg-green-600 hover:bg-green-700" @click="approve(user)">
                                            <IconUserCheck class="size-4" /> Setujui
                                        </Button>
                                        <Button size="sm" variant="outline" class="gap-1 text-red-600 border-red-200 hover:bg-red-50" @click="reject(user)">
                                            <IconUserX class="size-4" /> Tolak
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="users.links.length > 3" class="flex items-center justify-center gap-1 pt-4">
                    <template v-for="(link, k) in users.links" :key="k">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="cleanLabel(link.label)"
                            class="px-3 py-1 text-xs border rounded-md"
                            :class="{ 'bg-primary text-white': link.active }"
                        />
                        <span v-else v-html="cleanLabel(link.label)" class="px-3 py-1 text-xs border rounded-md opacity-50" />
                    </template>
                </div>
            </CardContent>
        </Card>
    </div>
</template>