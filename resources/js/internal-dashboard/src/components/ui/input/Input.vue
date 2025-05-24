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
  <input v-model="modelValue" :class="cn('flex w-full rounded-md bg-white dark:bg-white/10 border border-gray-800/15 px-2 py-2 shadow-sm text-sm text-gray-800 dark:text-gray-200 ring-offset-background placeholder:text-gray-400 placeholder:font-medium focus-visible:outline-none focus-visible:border-zinc-800/30 dark:focus-visible:border-gray-700 disabled:cursor-not-allowed disabled:opacity-50', props.class)">
</template>
