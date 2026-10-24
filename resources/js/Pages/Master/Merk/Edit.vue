<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps<{ merk: { id: number; kualitas_id: number; merk: string }; kualitas: Array<{ id: number; kualitas: string }> }>();
const form = useForm({ kualitas_id: String(props.merk.kualitas_id), merk: props.merk.merk });
</script>
<template><Head title="Edit Merk" /><div class="p-4 md:p-8 max-w-2xl"><Card><CardHeader><CardTitle>Edit Merk</CardTitle></CardHeader><CardContent><form class="space-y-5" @submit.prevent="form.put(route('merk.update', merk.id))"><div class="space-y-2"><Label>Grade</Label><select v-model="form.kualitas_id" class="w-full rounded-md border p-2"><option v-for="item in kualitas" :key="item.id" :value="String(item.id)">{{ item.kualitas }}</option></select><p class="text-sm text-destructive">{{ form.errors.kualitas_id }}</p></div><div class="space-y-2"><Label>Nama Merk</Label><Input v-model="form.merk" /><p class="text-sm text-destructive">{{ form.errors.merk }}</p></div><div class="flex gap-2"><Button type="submit" :disabled="form.processing">Simpan</Button><Button variant="outline" as-child><Link :href="route('merk.index')">Batal</Link></Button></div></form></CardContent></Card></div></template>
