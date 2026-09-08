<script setup lang="ts">
import type { Component } from "vue";
import { computed, ref, watch } from "vue";
import { IconChevronDown } from "@tabler/icons-vue";
import { Link, usePage } from "@inertiajs/vue3";

import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from "@/components/ui/sidebar";

interface NavItem {
    title: string;
    url?: string;
    icon?: Component;
    root?: string;
    children?: NavItem[];
}

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const openGroups = ref<Record<string, boolean>>({});

const isActive = (item: NavItem) =>
    item.root ? page.component.startsWith(item.root) : false;

const hasActiveChild = (item: NavItem) =>
    item.children?.some((child) => isActive(child)) ?? false;

const activeGroups = computed(() =>
    props.items
        .filter((item) => item.children?.length && hasActiveChild(item))
        .map((item) => item.title),
);

watch(
    activeGroups,
    (groups) => {
        groups.forEach((title) => {
            openGroups.value[title] = true;
        });
    },
    { immediate: true },
);

const toggleGroup = (title: string) => {
    openGroups.value[title] = !openGroups.value[title];
};
</script>

<template>
    <SidebarGroup>
        <SidebarGroupContent class="flex flex-col gap-2">
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <template v-if="item.children?.length">
                        <SidebarMenuButton
                            :tooltip="item.title"
                            :is-active="hasActiveChild(item)"
                            class="data-[active=true]:bg-primary/10 data-[active=true]:text-primary"
                            @click="toggleGroup(item.title)"
                        >
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                            <IconChevronDown
                                class="ml-auto transition-transform"
                                :class="{ 'rotate-180': openGroups[item.title] }"
                            />
                        </SidebarMenuButton>
                        <SidebarMenuSub v-if="openGroups[item.title]">
                            <SidebarMenuSubItem
                                v-for="child in item.children"
                                :key="child.title"
                            >
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isActive(child)"
                                >
                                    <Link :href="child.url ?? '#'">
                                        <component
                                            :is="child.icon"
                                            v-if="child.icon"
                                        />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </template>
                    <SidebarMenuButton
                        v-else
                        :tooltip="item.title"
                        as-child
                        :is-active="isActive(item)"
                        class="data-[active=true]:bg-primary/10 data-[active=true]:text-primary"
                    >
                        <Link :href="item.url ?? '#'">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
