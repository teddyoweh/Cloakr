<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <tr :class="cn('border-b dark:border-white/10 dark:hover:bg-transparent', props.class)">
        <slot />
    </tr>
</template>
