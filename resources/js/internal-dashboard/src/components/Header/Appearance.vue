<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {MoonIcon} from "@heroicons/vue/16/solid";
import {SunIcon} from "@heroicons/vue/16/solid";
import {ComputerDesktopIcon} from "@heroicons/vue/16/solid";
import HeaderSingleIconButton from "@/components/ui/HeaderSingleIconButton.vue";
import {useColorMode} from "@vueuse/core";
import {computed} from "vue";


const { system, store } = useColorMode()

const colorMode = computed(() => store.value === 'auto' ? system.value : store.value)

const icon = computed(() => {
    if (colorMode.value === 'dark') {
        return MoonIcon
    }

    return SunIcon
})

const setAppearance = (mode: 'light' | 'dark' | 'auto') => {
    store.value = mode
}

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger>
            <HeaderSingleIconButton :icon="icon" tooltip-text="Appearance" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-[140px]">
            <DropdownMenuItem @click="setAppearance('light')" class="group">
                <SunIcon class="size-5 text-gray-400 group-hover:text-gray-800 dark:group-hover:text-gray-300"/>
                Light
            </DropdownMenuItem>
            <DropdownMenuItem @click="setAppearance('dark')" class="group">
                <MoonIcon class="size-5 text-gray-400 group-hover:text-gray-800 dark:group-hover:text-gray-300"/>
                Dark
            </DropdownMenuItem>
            <DropdownMenuItem @click="setAppearance('auto')" class="group">
                <ComputerDesktopIcon class="size-5 text-gray-400 group-hover:text-gray-800 dark:group-hover:text-gray-300"/>
                System
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
