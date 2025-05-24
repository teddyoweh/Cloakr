<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { AccordionItem, type AccordionItemProps, useForwardProps } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<AccordionItemProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <AccordionItem
    v-bind="forwardedProps"
    :class="cn('bg-gray-50 dark:bg-white/10 border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden', props.class)"
  >
    <slot />
  </AccordionItem>
</template>
