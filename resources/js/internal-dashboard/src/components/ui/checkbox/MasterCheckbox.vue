<script setup lang="ts">
import {type HTMLAttributes, computed, ref, watch} from 'vue'
import { cn } from '@/lib/utils'
import {CheckIcon, MinusIcon} from "@heroicons/vue/16/solid";


const props = defineProps<{
    variant?: string
    class?: HTMLAttributes['class']
    state: 'checked' | 'unchecked' | 'indeterminate'
}>()

const currentState = ref(props.state as 'checked' | 'unchecked' | 'indeterminate');

watch(() => props.state, (newState) => {
    currentState.value = newState
})

const colors = computed(() => {
    if (props.variant === 'secondary') {
        return 'data-[state=checked]:bg-gray-500 text-white'
    }

    if(currentState.value === 'checked' || currentState.value === 'indeterminate') {
        return 'bg-primary border-transparent shadow-none text-white'
    }

    return 'border-gray-200 shadow-sm bg-white'
})

const emit = defineEmits(['apply-state'])

const click = () => {

    if (currentState.value === 'checked') {
        currentState.value = 'unchecked'
    } else if (currentState.value === 'unchecked' || currentState.value === 'indeterminate') {
        currentState.value = 'checked'
    }

    emit('apply-state', currentState.value)
}

</script>

<template>
    <div @click="click"
         :class="cn('peer size-[18px] shrink-0 rounded-sm border ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ' + colors,
        props.class)">
        <div class="flex h-full w-full items-center justify-center text-current">

                <CheckIcon v-if="currentState === 'checked'" class="size-[13px]" />
                <MinusIcon v-if="currentState === 'indeterminate'" class="size-[13px]" />
        </div>
    </div>
</template>
