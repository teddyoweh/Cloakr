<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    statusCode: number | null,
    size?: string | null,
    reason?: string | null,
}>(), {
    size: 'xs'
})

const badgeColor = computed(() => {
    if (props.statusCode === null) {
        return 'bg-gray-100 dark:bg-gray-800 animate-pulse';
    }

    const startsWith = props.statusCode.toString().charAt(0);

    switch (startsWith) {
        case '2':
            return 'bg-lime-500'
        case '3':
            return 'bg-yellow-500'
        case '4':
            return 'bg-orange-500'
        case '5':
            return 'bg-red-500'
        default:
            return 'bg-gray-500'
    }
})


const reasonTextColor = computed(() => {
    if (props.statusCode === null) {
        return 'text-transparent';
    }

    const startsWith = props.statusCode.toString().charAt(0);

    switch (startsWith) {
        case '2':
            return 'text-lime-600'
        case '3':
            return 'text-yellow-600'
        case '4':
            return 'text-orange-600'
        case '5':
            return 'text-red-600'
        default:
            return 'text-gray-600'
    }
})

const badgeSize = computed(() => {
    switch (props.size) {
        case 'base':
            return 'text-base'
        case 'sm':
            return 'text-sm'
        default:
            return 'text-sm'
    }
})

</script>

<template>
    <div class="flex items-center space-x-2">
        <div class="w-[34px] h-[20px] md:w-[44px] md:h-[24px] text-xs md:text-sm rounded-xl flex justify-center text-white font-mono font-medium py-0.5 px-2" :class="[badgeColor, badgeSize]">
            <span v-if="statusCode" class="pt-px">{{ statusCode }}</span>
            <span v-else class="opacity-0">999</span>
        </div>
        <span v-if="reason" :class="reasonTextColor" class="font-bold text-sm mt-px">{{ reason }}</span>
    </div>
</template>
