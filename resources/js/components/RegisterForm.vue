<script setup lang="ts">
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps<{
    class?: string
    departemens: Array<{ id: number, nama: string }>
}>()

const form = useForm({
    name: '',
    username: '',
    departemen_id: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <div :class="cn('flex flex-col gap-6', props.class)">
        <Card class="bg-white/40 dark:bg-black/30 backdrop-blur-3xl border-primary/20 dark:border-white/5 shadow-2xl rounded-2xl">
            <CardHeader class="text-center">
                <CardTitle class="text-2xl font-bold text-foreground">Daftar Akun</CardTitle>
                <CardDescription class="text-foreground/70 font-medium">
                    Silakan lengkapi data untuk bergabung dengan Sisamcus.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <FieldGroup class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <Field class="md:col-span-2">
                            <FieldLabel for="name" class="font-semibold text-primary dark:text-primary-foreground/90">
                                Nama Lengkap
                            </FieldLabel>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Contoh: Kicky Maulana"
                                class="bg-white/70 dark:bg-black/20 border-border focus:border-primary focus:ring-primary h-11"
                                required
                            />
                            <p v-if="form.errors.name" class="text-destructive text-xs mt-1 italic">{{ form.errors.name }}</p>
                        </Field>

                        <Field class="md:col-span-2">
                            <FieldLabel for="username" class="font-semibold text-primary dark:text-primary-foreground/90">
                                Username
                            </FieldLabel>
                            <Input
                                id="username"
                                v-model="form.username"
                                type="text"
                                placeholder="Gunakan nama tanpa spasi"
                                class="bg-white/70 dark:bg-black/20 border-border focus:border-primary focus:ring-primary h-11"
                                required
                            />
                            <p v-if="form.errors.username" class="text-destructive text-xs mt-1 italic">{{ form.errors.username }}</p>
                        </Field>

                        <Field>
                            <FieldLabel for="departemen" class="font-semibold text-primary dark:text-primary-foreground/90">
                                Departemen
                            </FieldLabel>
                            <select
                                id="departemen"
                                v-model="form.departemen_id"
                                class="flex h-11 w-full rounded-md border border-border bg-white/70 dark:bg-black/20 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                required
                            >
                                <option value="" disabled class="dark:bg-slate-900">Pilih Departemen</option>
                                <option v-for="dept in departemens" :key="dept.id" :value="dept.id" class="dark:bg-slate-900">
                                    {{ dept.departemen }}
                                </option>
                            </select>
                            <p v-if="form.errors.departemen_id" class="text-destructive text-xs mt-1 italic">{{ form.errors.departemen_id }}</p>
                        </Field>

                        <Field>
                            <FieldLabel for="password" class="font-semibold text-primary dark:text-primary-foreground/90">
                                Password
                            </FieldLabel>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="bg-white/70 dark:bg-black/20 border-border focus:border-primary focus:ring-primary h-11"
                                required
                            />
                        </Field>

                        <Field>
                            <FieldLabel for="password_confirmation" class="font-semibold text-primary dark:text-primary-foreground/90">
                                Konfirmasi
                            </FieldLabel>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="bg-white/70 dark:bg-black/20 border-border focus:border-primary focus:ring-primary h-11"
                                required
                            />
                            <p v-if="form.errors.password" class="text-destructive text-xs mt-1 italic">{{ form.errors.password }}</p>
                        </Field>

                        <Field class="md:col-span-2 mt-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full font-bold shadow-xl transition-all duration-300 hover:bg-accent hover:text-accent-foreground text-lg h-12 rounded-xl active:scale-95"
                            >
                                {{ form.processing ? "MEMPROSES..." : "DAFTAR SEKARANG" }}
                            </Button>
                        </Field>
                    </FieldGroup>
                </form>

                <div class="mt-6 text-center text-sm font-medium text-foreground/80">
                    Sudah punya akun?
                    <Link :href="route('login')" class="font-bold text-primary underline underline-offset-4 hover:text-accent transition-colors">
                        Masuk ke Simanduk
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
