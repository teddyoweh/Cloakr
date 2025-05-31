<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <div class="relative w-full overflow-auto rounded-lg border border-gray-200  dark:border-[#606062] shadow-sm" >
        <table :class="cn('w-full caption-bottom text-sm', props.class)" class="max-w-full table-fixed bg-white dark:bg-white/10">
            <slot />
        </table>
    </div>
</template>
