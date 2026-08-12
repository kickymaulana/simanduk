<script setup lang="ts">
import { ref, watch, computed, onBeforeUnmount } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const show = ref(false);
const isError = ref(false);
const qr = ref("");
const mode = ref("");
const counter = ref(0);
const message = ref("");
let timer: ReturnType<typeof setTimeout> | null = null;

const hide = () => {
    show.value = false;
    if (timer) clearTimeout(timer);
};

// Sukses scan → muncul dari flash.scan_qr
watch(
    () => (page.props as any).flash,
    (flash: any) => {
        if (flash?.scan_qr) {
            isError.value = false;
            qr.value = flash.scan_qr;
            mode.value = flash.scan_mode || "Berhasil";
            counter.value = (page.props as any).scan_counter ?? 0;
            show.value = true;

            if (timer) clearTimeout(timer);
            timer = setTimeout(hide, 2200);
        }
    },
    { deep: true }
);

// Gagal scan → muncul dari errors (qr/error) saat berada di halaman scan
watch(
    () => (page.props as any).errors,
    (errors: any) => {
        const err = errors?.qr || errors?.error;
        if (err && page.url.includes("/scan/")) {
            isError.value = true;
            message.value = err;
            qr.value = "";
            show.value = true;

            if (timer) clearTimeout(timer);
            timer = setTimeout(hide, 2200);
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
            class="fixed inset-0 z-[999] flex items-center justify-center bg-black/40 backdrop-blur-sm"
        >
            <div class="bg-white rounded-3xl shadow-2xl px-12 py-10 text-center relative animate-in zoom-in duration-300">
                <!-- SweetAlert2-style checkmark (sukses) -->
                <div v-if="!isError" class="swal-icon swal-success relative mx-auto size-20 mb-4">
                    <span class="swal-line swal-line-tip"></span>
                    <span class="swal-line swal-line-long"></span>
                    <div class="swal-placeholder rounded-full"></div>
                </div>

                <!-- SweetAlert2-style error (gagal) -->
                <div v-else class="swal-icon swal-error relative mx-auto size-20 mb-4">
                    <span class="swal-x-mark">
                        <span class="swal-line swal-line-left"></span>
                        <span class="swal-line swal-line-right"></span>
                    </span>
                    <div class="swal-placeholder rounded-full"></div>
                </div>

                <div class="flex flex-col gap-1">
                    <h1 class="text-4xl font-black uppercase tracking-tight" :class="isError ? 'text-red-600' : 'text-green-600'">
                        {{ isError ? "Gagal" : mode }}
                    </h1>
                    <p v-if="qr" class="font-mono text-2xl font-bold tracking-widest text-slate-800">
                        {{ qr }}
                    </p>
                    <p class="text-base font-semibold" :class="isError ? 'text-red-500' : 'text-slate-500'">
                        {{ isError ? message : `Scan ke-${counter} (sesi ini)` }}
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.swal-icon.swal-success {
    width: 80px;
    height: 80px;
    border: 4px solid #4ade80;
    border-radius: 50%;
    border-right-color: transparent;
    animation: swal-animateSuccessCircle 0.3s ease-in-out;
}

.swal-icon.swal-error {
    width: 80px;
    height: 80px;
    border: 4px solid #f87171;
    border-radius: 50%;
    border-right-color: transparent;
    animation: swal-animateErrorCircle 0.3s ease-in-out;
}

.swal-success .swal-placeholder,
.swal-error .swal-placeholder {
    width: 80px;
    height: 80px;
    position: absolute;
    top: -4px;
    left: -4px;
    border-radius: 50%;
}

.swal-success .swal-placeholder {
    border: 4px solid rgba(74, 222, 128, 0.2);
}

.swal-error .swal-placeholder {
    border: 4px solid rgba(248, 113, 113, 0.2);
}

.swal-success .swal-line,
.swal-error .swal-line {
    position: absolute;
    height: 5px;
    border-radius: 2px;
    display: inline-block;
}

.swal-success .swal-line {
    background-color: #22c55e;
}

.swal-error .swal-line {
    background-color: #ef4444;
}

.swal-success .swal-line-tip {
    width: 24px;
    left: 14px;
    top: 42px;
    transform: rotate(45deg);
    animation: swal-animateSuccessTip 0.75s;
}

.swal-success .swal-line-long {
    width: 48px;
    right: 7px;
    top: 34px;
    transform: rotate(-45deg);
    animation: swal-animateSuccessLong 0.75s;
}

.swal-error .swal-x-mark {
    position: absolute;
    top: 22px;
    left: 22px;
    width: 34px;
    height: 34px;
}

.swal-error .swal-line-left {
    width: 34px;
    left: 0;
    top: 15px;
    transform: rotate(45deg);
    animation: swal-animateErrorLine 0.6s;
}

.swal-error .swal-line-right {
    width: 34px;
    left: 0;
    top: 15px;
    transform: rotate(-45deg);
    animation: swal-animateErrorLine 0.6s;
}

@keyframes swal-animateSuccessCircle {
    0% {
        transform: rotate(-100deg) scale(0.5);
    }
    100% {
        transform: rotate(0deg) scale(1);
    }
}

@keyframes swal-animateErrorCircle {
    0% {
        transform: rotate(-100deg) scale(0.5);
    }
    100% {
        transform: rotate(0deg) scale(1);
    }
}

@keyframes swal-animateSuccessTip {
    0% {
        width: 0;
        left: 1px;
        top: 20px;
    }
    54% {
        width: 0;
        left: 1px;
        top: 20px;
    }
    70% {
        width: 48px;
        left: -6px;
        top: 30px;
    }
    84% {
        width: 18px;
        left: 18px;
        top: 41px;
    }
    100% {
        width: 24px;
        left: 14px;
        top: 42px;
    }
}

@keyframes swal-animateSuccessLong {
    0% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    65% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    84% {
        width: 54px;
        right: 0;
        top: 33px;
    }
    100% {
        width: 48px;
        right: 7px;
        top: 34px;
    }
}

@keyframes swal-animateErrorLine {
    0% {
        transform: translateY(0) rotate(45deg) scale(0);
        opacity: 0;
    }
    50% {
        transform: translateY(0) rotate(45deg) scale(1);
        opacity: 1;
    }
    100% {
        transform: translateY(0) rotate(45deg) scale(1);
        opacity: 1;
    }
}
</style>