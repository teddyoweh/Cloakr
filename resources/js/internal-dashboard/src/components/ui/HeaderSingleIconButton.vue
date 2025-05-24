<script setup lang="ts">
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger} from "@/components/ui/tooltip";
import {FunctionalComponent} from "vue";
import KeyboardShortcut from "@/components/ui/KeyboardShortcut.vue";


defineProps<{
    icon: FunctionalComponent
    tooltipText: string,
    shortcut?: string|null
}>()

const emit = defineEmits(['click'])
</script>

<template>

    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger>
                <button class="p-2.5 border border-gray-200 dark:border-white/10 rounded-sm shadow-sm bg-white dark:bg-white/10 dark:hover:bg-white/15 hover:bg-gray-50 text-gray-400 dark:text-gray-300 hover:text-gray-800 duration-150 focus:outline-0" @click="emit('click')" type="button">
                    <component :is="icon" class="size-5"/>
                    <span class="sr-only">
                        {{ tooltipText }}
                    </span>
                </button>
            </TooltipTrigger>
            <TooltipContent class="font-medium">
                <p>
                    {{ tooltipText }}
                    <keyboard-shortcut v-if="shortcut">{{ shortcut }}</keyboard-shortcut>
                </p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
