<script setup lang="ts">
import {type HTMLAttributes, computed} from 'vue'
import {
    DialogClose,
    DialogContent,
    type DialogContentEmits,
    type DialogContentProps,
    DialogOverlay,
    DialogPortal,
    useForwardPropsEmits,
} from 'radix-vue'
import {cn} from '@/lib/utils'
import {XMarkIcon} from "@heroicons/vue/16/solid";

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = computed(() => {
    const {class: _, ...delegated} = props

    return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
    <DialogPortal>
        <DialogOverlay
            class="fixed inset-0 z-50 bg-black/20 dark:bg-black/40 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
        />
        <DialogContent
            v-bind="forwarded"
            :class="
        cn(
          'fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border border-black/5 bg-[#F5F5F580] rounded-3xl p-2 backdrop-blur shadow-xl duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%]',
          props.class,
        )"
        >
            <div class="bg-white rounded-2xl py-8 dark:bg-gray-900">
                <slot/>
            </div>

            <DialogClose
                class="absolute right-6 top-6 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground"
            >
                <XMarkIcon class="size-4 text-gray-400"/>
                <span class="sr-only">Close</span>
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
