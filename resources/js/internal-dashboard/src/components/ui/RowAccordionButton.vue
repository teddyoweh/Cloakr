<script setup lang="ts">
import {ChevronDownIcon} from "@heroicons/vue/16/solid";

defineProps({
    rotate: Boolean
})
</script>


<template>
    <button
        class="opacity-100 group-hover:opacity-100 transition-150 text-gray-400 absolute -top-0.5 -right-0.5 border p-px rounded-sm">
        <ChevronDownIcon class="size-4 transform animate duration-150" :class="{ 'rotate-180': rotate }"/>
    </button>
</template>
