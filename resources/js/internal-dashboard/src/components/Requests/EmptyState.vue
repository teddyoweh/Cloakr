<script setup lang="ts">
import QrCode
 from '../QrCode.vue';
defineProps<{
    subdomains: string[]
}>()
</script>

<template>
    <div class="min-h-[500px] flex flex-1 py-32 items-center flex-col text-center">
        <p class="font-bold text-pink-600">
            Waiting for incoming requests...
        </p>

        <QrCode :subdomains="subdomains" class="max-w-[500px]"/>
    </div>
</template>