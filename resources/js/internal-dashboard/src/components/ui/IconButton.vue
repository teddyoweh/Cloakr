<script setup lang="ts">
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger} from "@/components/ui/tooltip";
import {computed, FunctionalComponent} from "vue";
import KeyboardShortcut from "@/components/ui/KeyboardShortcut.vue";


const props = defineProps<{
    icon: FunctionalComponent
    tooltipText: string
    shortcut?: string
    bgClass?: string
}>()

const emit = defineEmits(['click'])

const backgroundClass = computed(() => {
    return props.bgClass ?? ' bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15'
})
</script>

<template>

    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger class="focus:outline-0">
                <button :class="backgroundClass" class="p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-800 duration-150 focus:outline-0" @click="emit('click')" type="button">
                    <component :is="icon" class="size-4"/>
                    <span class="sr-only">
                        {{ tooltipText }}
                    </span>
                </button>
            </TooltipTrigger>
            <TooltipContent class="flex items-center font-medium">
                <p>
                    {{ tooltipText }}
                </p>
                <keyboard-shortcut v-if="shortcut">
                    {{ shortcut }}
                </keyboard-shortcut>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
