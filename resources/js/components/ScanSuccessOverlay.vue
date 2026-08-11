<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from "vue";
import { usePage } from "@inertiajs/vue3";
import { IconCheck, IconCircleCheck } from "@tabler/icons-vue";

const page = usePage();

const show = ref(false);
const qr = ref("");
const mode = ref("");
const counter = ref(0);
let timer: ReturnType<typeof setTimeout> | null = null;

const hide = () => {
    show.value = false;
    if (timer) clearTimeout(timer);
};

watch(
    () => (page.props as any).flash,
    (flash: any) => {
        if (flash?.scan_qr) {
            qr.value = flash.scan_qr;
            mode.value = flash.scan_mode || "Berhasil";
            counter.value = (page.props as any).scan_counter ?? 0;
            show.value = true;

            if (timer) clearTimeout(timer);
            timer = setTimeout(hide, 2000);
        }
    },
    { deep: true }
);

onBeforeUnmount(() => {
    if (timer) clearTimeout(timer);
});
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-200"
        leave-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-[999] flex flex-col items-center justify-center bg-white/95 backdrop-blur-sm"
        >
            <div class="flex flex-col items-center gap-6 text-center">
                <div
                    class="flex items-center justify-center size-36 rounded-full bg-green-100 text-green-600 animate-in zoom-in"
                >
                    <IconCheck class="size-24" />
                </div>

                <h1 class="text-6xl font-black uppercase tracking-tight text-green-600">
                    {{ mode }}
                </h1>

                <p class="font-mono text-4xl font-bold tracking-widest text-slate-800">
                    {{ qr }}
                </p>

                <div class="flex items-center gap-2 text-2xl font-bold text-slate-500">
                    <IconCircleCheck class="size-7 text-green-500" />
                    Scan ke-{{ counter }} (sesi ini)
                </div>
            </div>
        </div>
    </Transition>
</template>
