<script setup lang="ts">

import {computed} from "vue";


const props = defineProps<{
    label: string,
    active: boolean,
    value: string,
}>();

const emit = defineEmits(['click']);

const backgroundColor = computed(() => {
    return props.active ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-200';
});
</script>

<template>
    <button @click="emit('click', value)" :class="backgroundColor" class="rounded-3xl text-sm font-semibold py-1 px-3">
        {{ label }}
    </button>
</template>
