<script setup lang="ts">
import {FunctionalComponent} from "vue";

defineProps<{
    icon: FunctionalComponent
    tooltipText?: string
    shortcut?: string
    bgClass?: string
}>()

const emit = defineEmits(['click'])
</script>

<template>

    <button
        class="px-4 py-2 flex items-center space-x-2 text-sm text-gray-500 font-medium border border-gray-200  dark:border-[#606062] rounded-lg shadow-sm bg-white dark:bg-white/10 dark:hover:bg-white/15 hover:bg-gray-50 text-gray-400 dark:text-gray-300 hover:text-gray-800 duration-150 focus:outline-0"
        @click="emit('click')"
        type="button">
        <component :is="icon" class="size-4 text-gray-400"/>
        <div class="hidden lg:block">
            <slot></slot>
        </div>
    </button>

</template>
