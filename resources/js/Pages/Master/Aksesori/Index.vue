<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { IconPencil, IconPlus, IconPower } from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps<{ aksesoris: any; filters: { search?: string } }>();
const search = ref(props.filters.search || "");
let timeout: ReturnType<typeof setTimeout>;
watch(search, value => {
    clearTimeout(timeout);
    timeout = setTimeout(() => router.get(route("aksesori.index"), { search: value }, { preserveState: true, replace: true }), 400);
});
</script>

<template>
    <Head title="Master Aksesori" />
    <div class="p-4 md:p-8">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Master Aksesori</CardTitle>
                <Button as-child><Link :href="route('aksesori.create')"><IconPlus class="mr-2 size-4" />Tambah Aksesori</Link></Button>
            </CardHeader>
            <CardContent class="space-y-4">
                <Input v-model="search" class="max-w-sm" placeholder="Cari part number atau description..." />
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="p-3 text-left">PART NUMBER</th>
                                <th class="p-3 text-left">DESCRIPTION</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in aksesoris.data" :key="item.id" class="border-t">
                                <td class="p-3 font-medium">{{ item.part_number }}</td>
                                <td class="p-3">{{ item.description }}</td>
                                <td class="p-3 text-center"><Badge :variant="item.is_active ? 'default' : 'secondary'">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</Badge></td>
                                <td class="p-3 text-right">
                                    <Button variant="ghost" size="icon" @click="router.post(route('aksesori.toggle_active', item.id), {}, { preserveScroll: true })"><IconPower class="size-4" /></Button>
                                    <Button variant="ghost" size="icon" as-child><Link :href="route('aksesori.edit', item.id)"><IconPencil class="size-4" /></Link></Button>
                                </td>
                            </tr>
                            <tr v-if="!aksesoris.data.length"><td colspan="4" class="p-8 text-center text-muted-foreground">Belum ada data aksesori.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap gap-1">
                    <Button v-for="link in aksesoris.links" :key="link.label" size="sm" variant="outline" :disabled="!link.url" as-child>
                        <Link v-if="link.url" :href="link.url" v-html="link.label" />
                        <span v-else v-html="link.label" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
