<script setup lang="ts">
import RegisterForm from "@/components/RegisterForm.vue"
import { Button } from "@/components/ui/button"
import { useDark, useToggle } from '@vueuse/core'
import { IconSun, IconMoon } from '@tabler/icons-vue'
import { Head } from '@inertiajs/vue3'
import bgImage from "../../images/bg-login.png";


defineProps<{
  departemens: Array<{ id: number, departemen: string }>,
}>()

const isDark = useDark()
const toggleDark = useToggle(isDark)
</script>

<template>
  <Head title="Register - Sisamcus" />

  <div
    class="relative flex min-h-svh w-full items-center justify-center p-6 md:p-10 bg-cover bg-center bg-no-repeat transition-all duration-500"
    :style="{ backgroundImage: `url(${bgImage})` }"
  >
    <div class="absolute inset-0 bg-white/30 dark:bg-black/70 backdrop-blur-[2px] transition-colors duration-500"></div>

    <div class="absolute right-4 top-4 md:right-10 md:top-10 z-20">
      <Button variant="outline" size="icon" @click="toggleDark()" class="bg-background/50 backdrop-blur-md">
        <IconSun v-if="isDark" class="size-5 text-yellow-400" />
        <IconMoon v-else class="size-5 text-fuchsia-600" />
        <span class="sr-only">Toggle Theme</span>
      </Button>
    </div>

    <div class="relative z-10 w-full max-w-2xl">
      <RegisterForm
        :departemens="departemens"
        class="shadow-2xl border-none"
      />
    </div>

    <div class="absolute bottom-4 text-xs text-muted-foreground z-10">
      &copy; 2026 PT Mark Dynamics Indonesia Tbk. All rights reserved.
    </div>
  </div>
</template>
