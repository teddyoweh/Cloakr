<script setup lang="ts">

defineProps<{
    subdomains: string[]
}>()

</script>

<template>
    <div class="flex flex-col">

        <img
            src="https://image-charts.com/chart?chs=500x500&cht=qr&chl={{ subdomains[0] ?? '' | url_encode }}&choe=UTF-8&chf=bg,s,FFFFFF00" />

        <p class="text-base text-black dark:text-white text-sm text-center dark:mt-2">
            Use this QR code to quickly open the site on a mobile device.
        </p>
    </div>
</template>