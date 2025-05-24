<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import type { CheckboxRootEmits, CheckboxRootProps } from 'radix-vue'
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'radix-vue'
import { cn } from '@/lib/utils'
import {CheckIcon} from "@heroicons/vue/16/solid";

const props = defineProps<CheckboxRootProps & { class?: HTMLAttributes['class'], variant?: string }>()
const emits = defineEmits<CheckboxRootEmits>()

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props

    return delegated
})

const colors = computed(() => {
    if (delegatedProps.value.variant === 'secondary') {
        return 'data-[state=checked]:bg-gray-500 text-white'
    }

    return 'data-[state=checked]:bg-primary data-[state=checked]:border-transparent data-[state=checked]:shadow-none text-white'
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
    <CheckboxRoot v-bind="forwarded" :class="cn('peer size-[18px] bg-white shrink-0 rounded-sm border border-gray-200 shadow-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' + colors,
        props.class)">
        <CheckboxIndicator class="flex h-full w-full items-center justify-center text-current">
            <slot>
                <CheckIcon class="size-[13px]" />
            </slot>
        </CheckboxIndicator>
    </CheckboxRoot>
</template>
