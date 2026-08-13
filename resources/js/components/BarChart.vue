<script setup lang="ts">
import { VisXYContainer, VisStackedBar, VisAxis } from "@unovis/vue";

const props = defineProps<{
    data: Array<Record<string, any>>;
    labelKey: string;
    valueKey: string;
    color?: string;
    height?: number;
    suffix?: string;
}>();

const fmt = (v: number) => (props.suffix ? `${v}${props.suffix}` : `${v}`);
</script>

<template>
    <VisXYContainer
        :data="data"
        :height="height ?? 280"
        :margin="{ left: 8, right: 8, top: 8, bottom: 36 }"
    >
        <VisStackedBar
            :x="(_: any, i: number) => i"
            :y="(d: any) => d[valueKey]"
            :color="color ?? 'var(--primary)'"
            :bar-padding="0.25"
            :rounded-corners="true"
        />
        <VisAxis
            type="x"
            :tick-format="(i: number) => data[i]?.[labelKey] ?? ''"
            :num-ticks="Math.min(data.length || 1, 8)"
            :tick-line="false"
            :domain-line="false"
        />
        <VisAxis
            type="y"
            :num-ticks="4"
            :tick-line="false"
            :domain-line="false"
            :tick-format="(v: number) => fmt(v)"
        />
    </VisXYContainer>
</template>
