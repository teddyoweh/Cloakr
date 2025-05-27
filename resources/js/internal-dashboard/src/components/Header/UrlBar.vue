<script setup lang="ts">
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger} from "@/components/ui/tooltip";
import {CheckIcon, LinkIcon} from "@heroicons/vue/16/solid";
import {ClipboardIcon} from "@heroicons/vue/16/solid";
import KeyboardShortcut from "@/components/ui/KeyboardShortcut.vue";
import {FunctionalComponent, ref} from "vue";

defineProps({
    subdomain: String
})

const copyUrlIcon = ref(ClipboardIcon as FunctionalComponent);

const emit = defineEmits(['copy'])

const copyUrl = () => {
    copyUrlIcon.value = CheckIcon
    emit('copy')

    setTimeout(() => {
        copyUrlIcon.value = ClipboardIcon
    }, 1000)
}
</script>


<template>
    <div
        class="w-full max-w-[380px] lg:max-w-[580px] flex items-stretch border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden duration-150">

        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger
                    class="w-full">

                    <a :href="subdomain"
                       class="flex flex-grow space-x-2 text-sm bg-gray-50 dark:bg-white/10 dark:hover:bg-white/15 hover:bg-gray-100 duration-150 px-4 py-2.5 border-r border-gray-200 dark:border-white/10 group">

                        <LinkIcon
                            class="size-5 text-gray-400 dark:text-white/50 group-hover:text-gray-800 dark:group-hover:text-white duration-150"/>
                        <span class="font-medium">
                            {{ subdomain }}
                        </span>
                    </a>

                </TooltipTrigger>
                <TooltipContent>
                    Open URL
                    <keyboard-shortcut>O</keyboard-shortcut>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>


        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger
                    @click="copyUrl"
                    class="bg-gray-50 dark:bg-white/10 dark:hover:bg-white/15 hover:bg-gray-100 duration-150 px-3 py-2.5 group duration-150">

                    <component :is="copyUrlIcon"
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
