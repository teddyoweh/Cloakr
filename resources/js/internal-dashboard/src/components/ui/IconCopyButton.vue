<script setup lang="ts">
import {ref} from "vue";
import {ClipboardIcon} from "@heroicons/vue/16/solid";
import {CheckIcon} from "@heroicons/vue/16/solid";

defineProps<{
    tooltipText?: string
    shortcut?: string
    bgClass?: string
}>()

const clicked = ref(false as boolean)

const emit = defineEmits(['click'])

const click = () => {
    clicked.value = true
    emit('click')

    setTimeout(() => {
        clicked.value = false
    }, 1000)
}
</script>

<template>

    <button
        class="px-4 py-2 flex items-center space-x-2 text-sm text-gray-500 font-medium border border-gray-200  dark:border-[#606062] rounded-lg shadow-sm bg-white dark:bg-white/10 dark:hover:bg-white/15 hover:bg-gray-50 text-gray-400 dark:text-gray-300 hover:text-gray-800 duration-150 focus:outline-0"
        @click="click"
        type="button">
        <CheckIcon v-if="clicked" class="size-4 text-gray-400"/>
        <ClipboardIcon v-if="!clicked" class="size-4 text-gray-400"/>

        <div class="hidden lg:block">
            <slot>Copy</slot>
        </div>
    </button>

</template>
