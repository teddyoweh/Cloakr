<script setup lang="ts">


import {useColorMode} from "@vueuse/core";
import {onMounted, ref, watch} from "vue";

defineProps<{
    subdomains: string[]
}>()

const {store, system} = useColorMode();
const mode = ref(store.value);

onMounted(() => {
    if(store.value === 'auto') {
        mode.value = system.value
    }
})

watch(() => store.value, () => {
    if(store.value === 'auto') {
        mode.value = system.value
    }

    mode.value = store.value;
});
</script>

<template>
    <div>
        <div
            class="relative mx-auto qr-wrapper rounded-2xl overflow-hidden border-[20px] border-white dark:border-gray-900 box-border ">
            <img
                v-if="mode === 'light'"
                :src="'https://image-charts.com/chart?chs=170x170&cht=qr&chl=' + encodeURIComponent(subdomains[0] ?? '') + '&choe=UTF-8&chf=bg,s,FFFFFF00'"
                alt="QR Code"
                class="mix-blend-lighten"
            />
            <img
                v-if="mode === 'dark'"
                :src="'https://image-charts.com/chart?chs=170x170&cht=qr&chl=' + encodeURIComponent(subdomains[0] ?? '') + '&choe=UTF-8&chf=bg,s,FFFFFF00&icqrb=18181B&icqrf=ffffff'"
                alt="QR Code"
                class="mix-blend-darken"
            />
        </div>
    </div>
</template>


<style>
.qr-wrapper {
    width: 100%;
    height: 100%;
    background-image: linear-gradient(135deg, #D5039C 0%, #E79159 100%);
    background-repeat: no-repeat;
}

</style>
