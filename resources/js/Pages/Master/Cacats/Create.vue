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
} from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });
const form = useForm({ cacat: "", jenis: "Body" });
</script>

<template>
    <Head title="Tambah Cacat" />
    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" as-child class="rounded-full"
                ><Link :href="route('cacats.index')"
                    ><IconArrowLeft class="size-4" /></Link
            ></Button>
            <h2 class="text-3xl font-bold tracking-tight">
                Tambah Jenis Cacat
            </h2>
        </div>
        <div class="max-w-2xl">
            <Card class="border-none shadow-lg">
                <CardHeader
                    ><CardTitle class="text-primary"
                        >Master Data Cacat</CardTitle
                    ></CardHeader
                >
                <CardContent>
                    <form
                        @submit.prevent="form.post(route('cacats.store'))"
                        class="space-y-6"
                    >
                        <div class="grid gap-2">
                            <Label for="cacat">Jenis Cacat</Label>
                            <Input
                                id="cacat"
                                v-model="form.cacat"
                                placeholder="Contoh: Pecah Body"
                                class=""
                            />
                            <p
                                v-if="form.errors.cacat"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.cacat }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="jenis">Jenis Produk</Label>
                            <select id="jenis" v-model="form.jenis" class="h-10 rounded-md border bg-background px-3 text-sm">
                                <option value="Body">Body</option>
                                <option value="Tangki">Tangki</option>
                            </select>
                            <p v-if="form.errors.jenis" class="text-sm text-destructive">{{ form.errors.jenis }}</p>
                        </div>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-primary hover:bg-primary/90"
                        >
                            <IconLoader2
                                v-if="form.processing"
                                class="mr-2 animate-spin"
                            />
                            <IconDeviceFloppy v-else class="mr-2" /> Simpan Data
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
