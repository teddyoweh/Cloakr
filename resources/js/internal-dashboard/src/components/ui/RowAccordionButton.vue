<script setup lang="ts">
import IconButton from '@/components/ui/IconButton.vue'
import { Icon } from '@iconify/vue'

defineProps({
    rotate: Boolean
})
</script>


<template>
    <icon-button class="opacity-100 group-hover:opacity-100 transition-150 absolute -top-0.5 -right-0.5">
        <Icon icon="radix-icons:chevron-down" class="h-4 w-4 transform animate duration-150"
            :class="{ 'rotate-180': rotate }" />
    </icon-button>
</template>