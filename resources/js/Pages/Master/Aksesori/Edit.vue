<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps<{ aksesori: { id: number; part_number: string; description: string } }>();
const form = useForm({ part_number: props.aksesori.part_number, description: props.aksesori.description });
</script>

<template>
    <Head title="Edit Aksesori" />
    <div class="max-w-2xl p-4 md:p-8">
        <Card>
            <CardHeader><CardTitle>Edit Aksesori</CardTitle></CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="form.put(route('aksesori.update', aksesori.id))">
                    <div class="space-y-2">
                        <Label>PART NUMBER</Label>
                        <Input v-model="form.part_number" autofocus />
                        <p class="text-sm text-destructive">{{ form.errors.part_number }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label>DESCRIPTION</Label>
                        <Input v-model="form.description" />
                        <p class="text-sm text-destructive">{{ form.errors.description }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                        <Button variant="outline" as-child><Link :href="route('aksesori.index')">Batal</Link></Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
