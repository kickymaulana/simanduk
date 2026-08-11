<script setup lang="ts">
import AppSidebar from "@/components/AppSidebar.vue"
import SiteHeader from "@/components/SiteHeader.vue"
import ScanSuccessOverlay from "@/components/ScanSuccessOverlay.vue"
import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar"
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'
import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import 'vue-sonner/style.css'

const page = usePage()
const flashSuccess = computed(() => page.props.flash.success)
console.log(flashSuccess.value)

// Watch perubahan pada flash message dari Laravel
watch(
  () => page.props.flash,
  (flash: any) => {
    if (flash?.success) {
      toast.success('Berhasil!', {
        description: flash.success,
      })
    }

    if (flash?.error) {
      toast.error('Terjadi Kesalahan', {
        description: flash.error,
      })
    }
  },
  { deep: true, immediate: true }
)
</script>

<template>
  <SidebarProvider
    :style="{
      '--sidebar-width': 'calc(var(--spacing) * 72)',
      '--header-height': 'calc(var(--spacing) * 12)',
    }"
  >
    <AppSidebar variant="inset" />

    <SidebarInset>
      <SiteHeader />

      <main class="flex flex-1 flex-col @container/main">
        <slot />
        <Toaster position="top-right"/>
      </main>

      <ScanSuccessOverlay />

    </SidebarInset>
  </SidebarProvider>
</template>
