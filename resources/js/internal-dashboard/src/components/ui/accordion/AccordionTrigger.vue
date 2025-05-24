<script setup lang="ts">
import {type HTMLAttributes, computed} from 'vue'
import {
    AccordionHeader,
    AccordionTrigger,
    type AccordionTriggerProps,
} from 'radix-vue'
import {cn} from '@/lib/utils'
import {ChevronDownIcon} from "@heroicons/vue/16/solid";

const props = defineProps<AccordionTriggerProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
    const {class: _, ...delegated} = props

    return delegated
})
</script>

<template>
    <AccordionHeader class="flex">
        <AccordionTrigger
            v-bind="delegatedProps"
            :class="
        cn(
          'flex flex-1 items-center rounded-md justify-between bg-gray-50 dark:bg-white/10 py-2.5 pl-4 pr-2 font-medium transition-transform [&[data-state=open]>div>svg.close]:rotate-180 [&[data-state=open]>div.action]:opacity-100',
          props.class,
        )
      "
        >
            <div class="flex items-center space-x-2 text-sm text-gray-800 dark:text-white">
                <slot name="icon"></slot>

                <slot/>

                <ChevronDownIcon
                    class="h-4 w-4 shrink-0 transition-transform duration-200 close"
                />
            </div>
            <div class="opacity-0 action" @click.stop>
                <slot name="action">
                </slot>
            </div>
        </AccordionTrigger>
    </AccordionHeader>
</template>
