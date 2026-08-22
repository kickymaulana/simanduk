<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    IconArrowLeft,
    IconDeviceFloppy,
    IconLoader2,
    IconFlame,
} from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });

const form = useForm({
    oven: "",
});

const submit = () => {
    form.post(route("ovens.store"), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Tambah Oven" />
    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" as-child class="rounded-full">
                <Link :href="route('ovens.index')">
                    <IconArrowLeft class="size-4" />
                </Link>
            </Button>
            <h2 class="text-3xl font-bold tracking-tight">
                Tambah Oven
            </h2>
        </div>

        <div class="max-w-2xl">
            <Card class="border-none shadow-lg">
                <CardHeader>
                    <CardTitle class="text-primary flex items-center gap-2">
                        <IconFlame class="size-5" />
                        Master Data Oven
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="oven">Nama Oven</Label>
                            <Input
                                id="oven"
                                v-model="form.oven"
                                placeholder="Contoh: Oven 7, Oven 8, Roll Kiln"
                                class="uppercase"
                                :class="{ 'border-destructive': form.errors.oven }"
                                autofocus
                            />
                            <p v-if="form.errors.oven" class="text-sm text-destructive">
                                {{ form.errors.oven }}
                            </p>
                        </div>

                        <Button type="submit" :disabled="form.processing" class="w-full bg-primary hover:bg-primary/90 transition-all active:scale-[0.98]">
                            <IconLoader2 v-if="form.processing" class="mr-2 animate-spin" />
                            <IconDeviceFloppy v-else class="mr-2" />
                            Simpan Data Oven
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
