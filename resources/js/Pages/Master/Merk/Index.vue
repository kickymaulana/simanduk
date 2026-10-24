<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { IconPlus, IconPencil, IconPower, IconSearch } from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps<{ merks: any; filters: { search?: string } }>();
const search = ref(props.filters.search || "");
let timeout: ReturnType<typeof setTimeout>;
watch(search, value => { clearTimeout(timeout); timeout = setTimeout(() => router.get(route("merk.index"), { search: value }, { preserveState: true, replace: true }), 400); });
</script>

<template>
    <Head title="Master Merk" />
    <div class="p-4 md:p-8"><Card><CardHeader class="flex flex-row items-center justify-between"><CardTitle>Master Merk</CardTitle><Button as-child><Link :href="route('merk.create')"><IconPlus class="mr-2 size-4" />Tambah Merk</Link></Button></CardHeader><CardContent class="space-y-4"><div class="relative max-w-sm"><IconSearch class="absolute left-3 top-3 size-4 text-muted-foreground" /><Input v-model="search" class="pl-9" placeholder="Cari merk..." /></div><div class="overflow-x-auto rounded-md border"><table class="w-full text-sm"><thead class="bg-muted"><tr><th class="p-3 text-left">Merk</th><th class="p-3 text-left">Grade</th><th class="p-3">Status</th><th class="p-3 text-right">Aksi</th></tr></thead><tbody><tr v-for="item in merks.data" :key="item.id" class="border-t"><td class="p-3 font-medium">{{ item.merk }}</td><td class="p-3">{{ item.kualitas.kualitas }}</td><td class="p-3 text-center"><Badge :variant="item.is_active ? 'default' : 'secondary'">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</Badge></td><td class="p-3 text-right"><Button variant="ghost" size="icon" @click="router.post(route('merk.toggle_active', item.id), {}, { preserveScroll: true })"><IconPower class="size-4" /></Button><Button variant="ghost" size="icon" as-child><Link :href="route('merk.edit', item.id)"><IconPencil class="size-4" /></Link></Button></td></tr><tr v-if="!merks.data.length"><td colspan="4" class="p-8 text-center text-muted-foreground">Belum ada data merk.</td></tr></tbody></table></div><div class="flex flex-wrap gap-1"><Button v-for="link in merks.links" :key="link.label" size="sm" variant="outline" :disabled="!link.url" as-child><Link v-if="link.url" :href="link.url" v-html="link.label" /><span v-else v-html="link.label" /></Button></div></CardContent></Card></div>
</template>
