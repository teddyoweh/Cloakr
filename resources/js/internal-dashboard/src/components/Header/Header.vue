<script setup lang="ts">
import {QrCodeIcon} from '@heroicons/vue/16/solid'
import HeaderSingleIconButton from "@/components/ui/HeaderSingleIconButton.vue";
import {onMounted, ref, watch} from 'vue'
import Appearance from "@/components/Header/Appearance.vue";
import UrlBar from "@/components/Header/UrlBar.vue";
import {copyToClipboard, openInNewTab} from "@/lib/utils.ts";

const props = defineProps<{
    subdomains: string[]
}>()

const emit = defineEmits(['search-updated', 'show-qr-code'])

const search = ref('' as string)
const currentSubdomain = ref('' as string)

watch(() => search.value, () => {
    emit('search-updated', search.value)
})

onMounted(() => {
    if (props.subdomains.length > 0) {
        currentSubdomain.value = props.subdomains[0]
    }
})

const openSubdomainInNewTab = () => {
    openInNewTab(currentSubdomain.value)
}

const copySubdomainToClipboard = () => {
    copyToClipboard(currentSubdomain.value)
}

defineCloakr({
    copySubdomainToClipboard,
    openSubdomainInNewTab
})


</script>


<template>
    <div>
        <div
            class="py-4 px-4 md:px-6 flex flex-col md:flex-row md:items-center justify-between space-y-3 md:space-y-0 dark:bg-gray-900">

            <a href="https://cloakr.dev" target="_blank" class="inline-flex items-center self-start">
                <img src="https://beyondco.de/apps/icons/cloakr.png" alt="cloakr.dev" class="h-8 lg:h-10">
                <div class="ml-4 ">
                    <p class="text-lg lg:text-2xl tracking-tight font-bold">Cloakr</p>
                    <p class="text-xs text-gray-400">by Beyond Code</p>
                </div>
            </a>


            <div class="flex   space-x-4 lg:w-3/5 justify-between">
                <UrlBar
                    :subdomain="currentSubdomain"
                    @open-in-new-tab="openSubdomainInNewTab"
                    @copy-to-clipboard="copySubdomainToClipboard"
                />

                <div class="flex items-center">
                    <HeaderSingleIconButton @click="emit('show-qr-code')" :icon="QrCodeIcon"
                                            shortcut="Q"
                                            tooltip-text="Show QR Code"/>

                    <div class="w-px h-6 bg-gray-200 dark:bg-white/20 mx-2 md:mx-4"></div>

                    <Appearance/>
                </div>
            </div>

        </div>

        <div class="border-b border-gray-200 dark:border-gray-700"></div>
    </div>
</template>
