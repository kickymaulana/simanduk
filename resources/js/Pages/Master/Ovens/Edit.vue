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
    IconFlame,
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

const props = defineProps<{
    oven: { id: number; oven: string };
}>();

const form = useForm({
    oven: props.oven.oven,
});

const submit = () => {
    form.put(route("ovens.update", props.oven.id));
};
</script>

<template>
    <Head title="Edit Oven" />
    <div class="flex flex-col gap-6 p-4 md:p-8 pt-1">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child class="rounded-full">
                    <Link :href="route('ovens.index')">
                        <IconArrowLeft class="size-4" />
                    </Link>
                </Button>
                <h2 class="text-3xl font-bold tracking-tight">Edit Oven</h2>
            </div>
        </div>

        <div class="max-w-2xl">
            <Card class="border-none shadow-lg">
                <CardHeader class="flex flex-row items-center justify-between border-b py-4">
                    <CardTitle class="text-primary text-lg flex items-center gap-2">
                        <IconFlame class="size-5" />
                        Update Data Oven
                    </CardTitle>

                    <AlertDialog>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon">
                                    <IconDotsVertical class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <AlertDialogTrigger as-child>
                                    <DropdownMenuItem class="text-destructive focus:text-destructive">
                                        <IconTrash class="mr-2 size-4" />
                                        Hapus Oven
                                    </DropdownMenuItem>
                                </AlertDialogTrigger>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Hapus Data Oven?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Tindakan ini tidak dapat dibatalkan. Ini akan menghapus data oven
                                    <strong>{{ props.oven.oven }}</strong> secara permanen.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Batal</AlertDialogCancel>
                                <AlertDialogAction @click="router.delete(route('ovens.destroy', props.oven.id))" class="bg-destructive text-white hover:bg-destructive/90">
                                    Ya, Hapus
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </CardHeader>

                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="oven">Nama Oven</Label>
                            <Input
                                id="oven"
                                v-model="form.oven"
                                class="uppercase"
                                placeholder="Contoh: Oven 7"
                                :class="{ 'border-destructive': form.errors.oven }"
                            />
                            <p v-if="form.errors.oven" class="text-sm text-destructive">
                                {{ form.errors.oven }}
                            </p>
                        </div>

                        <Button type="submit" :disabled="form.processing" class="w-full bg-primary hover:bg-primary/90 transition-all active:scale-[0.98]">
                            <IconLoader2 v-if="form.processing" class="mr-2 animate-spin" />
                            <IconDeviceFloppy v-else class="mr-2 size-4" />
                            Simpan Perubahan
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
