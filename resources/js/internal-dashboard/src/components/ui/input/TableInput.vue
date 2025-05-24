<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { useVModel } from '@vueuse/core'
import { cn } from '@/lib/utils'

const props = defineProps<{
    defaultValue?: string | number
    modelValue?: string | number
    class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
})
</script>

<template>
    <input v-model="modelValue" :class="cn('h-8 rounded-md w-full disabled:bg-transparent disabled:text-gray-400 dark:bg-transparent focus:bg-gray-100 dark:focus:bg-white/10 focus:outline-none px-2.5 py-2 -ml-2', props.class)">
</template>
