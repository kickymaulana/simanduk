<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    IconArrowLeft,
    IconDeviceFloppy,
    IconLoader2,
    IconDotsVertical,
    IconTrash,
} from "@tabler/icons-vue";
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps<{ cacat: { id: number; cacat: string; jenis: "Body" | "Tangki" } }>();
const form = useForm({ cacat: props.cacat.cacat, jenis: props.cacat.jenis });
</script>

<template>
    <Head title="Edit Cacat" />
    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button
                    variant="outline"
                    size="icon"
                    as-child
                    class="rounded-full"
                    ><Link :href="route('cacats.index')"
                        ><IconArrowLeft class="size-4" /></Link
                ></Button>
                <h2 class="text-3xl font-bold tracking-tight">Edit Cacat</h2>
            </div>
        </div>
        <div class="max-w-2xl">
            <Card class="border-none shadow-lg">
                <CardHeader
                    class="flex flex-row items-center justify-between border-b"
                >
                    <CardTitle class="text-primary text-lg"
                        >Update Data</CardTitle
                    >
                    <AlertDialog>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child
                                ><Button variant="ghost" size="icon"
                                    ><IconDotsVertical class="size-4" /></Button
                            ></DropdownMenuTrigger>
                            <DropdownMenuContent align="end"
                                ><AlertDialogTrigger as-child
                                    ><DropdownMenuItem class="text-destructive"
                                        ><IconTrash
                                            class="mr-2 size-4"
                                        />Hapus</DropdownMenuItem
                                    ></AlertDialogTrigger
                                ></DropdownMenuContent
                            >
                        </DropdownMenu>
                        <AlertDialogContent>
                            <AlertDialogHeader
                                ><AlertDialogTitle>Hapus Data?</AlertDialogTitle
                                ><AlertDialogDescription
                                    >Hapus permanen
                                    <strong>{{ props.cacat.cacat }}</strong
                                    >?</AlertDialogDescription
                                ></AlertDialogHeader
                            >
                            <AlertDialogFooter
                                ><AlertDialogCancel>Batal</AlertDialogCancel
                                ><AlertDialogAction
                                    @click="
                                        router.delete(
                                            route(
                                                'cacats.destroy',
                                                props.cacat.id,
                                            ),
                                        )
                                    "
                                    class="bg-destructive text-white"
                                    >Ya, Hapus</AlertDialogAction
                                ></AlertDialogFooter
                            >
                        </AlertDialogContent>
                    </AlertDialog>
                </CardHeader>
                <CardContent class="pt-6">
                    <form
                        @submit.prevent="
                            form.put(route('cacats.update', props.cacat.id))
                        "
                        class="space-y-6"
                    >
                        <div class="grid gap-2">
                            <Label for="cacat">Jenis Cacat</Label>
                            <Input
                                id="cacat"
                                v-model="form.cacat"
                                class=""
                            />
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
                            class="w-full bg-primary"
                            ><IconDeviceFloppy class="mr-2 size-4" />Simpan
                            Perubahan</Button
                        >
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
