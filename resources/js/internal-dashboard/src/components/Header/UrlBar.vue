<script setup lang="ts">
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger} from "@/components/ui/tooltip";
import {LinkIcon} from "@heroicons/vue/16/solid";
import {ClipboardIcon} from "@heroicons/vue/16/solid";
import KeyboardShortcut from "@/components/ui/KeyboardShortcut.vue";

defineProps({
    subdomain: String
})

const emit = defineEmits(['copy', 'open-in-new-tab'])
</script>


<template>
    <div
        class="w-full max-w-[380px] lg:max-w-[580px] flex items-stretch border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden duration-150">

        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger
                    @click="emit('open-in-new-tab')"
                    class="flex flex-grow space-x-2 text-sm bg-gray-50 dark:bg-white/10 dark:hover:bg-white/15 hover:bg-gray-100 duration-150 px-4 py-2.5 border-r border-gray-200 dark:border-white/10 group">

                    <LinkIcon
                        class="size-5 text-gray-400 dark:text-white/50 group-hover:text-gray-800 dark:group-hover:text-white duration-150"/>
                    <span class="font-medium">
                        {{ subdomain }}
                    </span>

                </TooltipTrigger>
                <TooltipContent>
                    Open URL in a new tab
                    <keyboard-shortcut>O</keyboard-shortcut>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>


        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger
                    class="bg-gray-50 dark:bg-white/10 dark:hover:bg-white/15 hover:bg-gray-100 duration-150 px-3 py-2.5 group duration-150">

                    <ClipboardIcon
                        @click="emit('copy')"
                        class="size-4 text-gray-400 dark:text-white/50 group-hover:text-gray-800 dark:group-hover:text-white duration-150"/>

                </TooltipTrigger>
                <TooltipContent>
                    Copy link
                    <keyboard-shortcut>L</keyboard-shortcut>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
