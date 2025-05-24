<script setup lang="ts">
import QrCode
    from '../QrCode.vue';

defineProps<{
    subdomains: string[]
}>()
</script>

<template>
    <div class="text-sm flex flex-1 items-center justify-center flex-col text-center  bg-gray-50 dark:bg-gray-800 h-full">


        <div class="border border-black/5 dark:border-white/10 bg-[#F5F5F580] dark:bg-white/30 rounded-3xl p-2 shadow-xl">
            <QrCode :subdomains="subdomains" class="max-w-[500px]"/>
        </div>

        <p class="font-medium text-gray-800 dark:text-white mt-10 mb-1">
            Waiting for incoming requests...
        </p>
        <p class="text-gray-500 dark:text-gray-400">
            Scan this QR code to open the site on a mobile device.
        </p>
    </div>
</template>
