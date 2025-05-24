<!-- Search.vue -->
<script setup lang="ts">
import {defineEmits, type HTMLAttributes, ref} from 'vue'
import {useVModel} from '@vueuse/core'
import {MagnifyingGlassIcon} from "@heroicons/vue/16/solid"
import {cn, isDarwin} from '@/lib/utils'

const props = defineProps<{
    modelValue?: string
    class?: HTMLAttributes['class']
}>()


const input = ref()

const isMacOS = isDarwin()

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
})

const focusSearch = () => {
    input.value.focus()
}

defineCloakr({focusSearch})
</script>

<template>
    <div class="w-full">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2">
                <MagnifyingGlassIcon class="h-4 w-4 text-gray-400"/>
            </div>
            <input
                v-model="modelValue"
                ref="input"
                type="search"
                :class="cn(
          'h-8 w-full rounded-md bg-gray-100 dark:bg-white/10 pl-7 py-2 text-sm text-gray-800 dark:text-gray-200 focus-visible:border-transparent ring-offset-background placeholder:text-gray-400 placeholder:font-medium focus-visible:outline-none border border-transparent focus-visible:border-gray-300 dark:focus-visible:border-gray-700 disabled:cursor-not-allowed disabled:opacity-50',
          props.class,
          {'pr-11': !isMacOS, 'pr-7': isMacOS}
        )"
                placeholder="Search..."
            />
            <div
                class="pointer-events-none absolute inset-y-0 right-0 pr-1.5 flex items-center text-xs text-gray-400 font-medium">
                <template v-if="isMacOS">⌘K</template>
                <template v-else>Ctrl+K</template>
            </div>
        </div>
    </div>
</template>
