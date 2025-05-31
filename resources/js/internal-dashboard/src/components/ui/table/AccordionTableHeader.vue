<script setup lang="ts">
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {TableCell, TableRow} from "@/components/ui/table/index.ts";

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()
</script>

<template>
    <thead :class="cn('[&_tr]:border-b  dark:border-[#606062]', props.class)">
    <TableRow class="text-gray-500 dark:text-gray-300  dark:border-[#727274] dark:hover:bg-transparent">
        <TableCell class="w-1/5">Key</TableCell>
        <TableCell class="pr-0">Value</TableCell>
    </TableRow>
    </thead>
</template>
