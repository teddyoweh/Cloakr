<script setup lang="ts">
import type {HTMLAttributes} from 'vue'
import {Primitive, type PrimitiveProps} from 'radix-vue'
import {type ButtonVariants, buttonVariants} from '.'
import {cn} from '@/lib/utils'

interface Props extends PrimitiveProps {
    variant?: ButtonVariants['variant']
    size?: ButtonVariants['size']
    class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
})
</script>

<template>
    <Primitive
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
        class="primary-button text-white dark:text-gray-800"
    >
        <slot/>
    </Primitive>
</template>

<style scoped>
.primary-button {
    box-shadow: 0px 0px 0px 1px rgba(202, 58, 100, 1) inset,
    0px 1px 0px 1px rgba(255, 255, 255, 0.25) inset,
    0px 0px 0px 1px rgba(202, 58, 100, 1) inset,
    0px 1px 2px 0px rgba(0, 0, 0, 0.15);
}
</style>
