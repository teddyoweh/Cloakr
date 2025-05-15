<script setup lang="ts">
import Header from '@/components/Header.vue'
import Logs from '@/components/Requests/Logs.vue'
import QrCodeModal from '@/components/QrCodeModal.vue'
import LogDetail from '@/components/Requests/LogDetail.vue'
import { exampleSubdomains, exampleUser } from './lib/devUtils';
import { Card } from './components/ui/card';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { isEmptyObject } from './lib/utils';
import EmptyState from './components/Requests/EmptyState.vue';
import { Button } from '@/components/ui/button'
import { Icon } from '@iconify/vue'

const props = defineProps<{
    pageData?: InternalDashboardPageData
}>();

const page: InternalDashboardPageData = {
    subdomains: props.pageData?.subdomains ?? exampleSubdomains(),
    user: props.pageData?.user ?? exampleUser(),
    max_logs: props.pageData?.max_logs ?? 100,
};

const currentLog = ref(null as CloakrLog | null)
const search = ref('' as string)
const logList = ref()
const qrCodeModal = ref()
const scrollY = ref(0 as number);

onMounted(() => {
    window.addEventListener('scroll', updateScroll);
    window.addEventListener('keydown', handleKeyDown);
});

const updateScroll = () => {
    scrollY.value = window.scrollY;
};

const setLog = (log: CloakrLog | null) => {
    currentLog.value = log;
}

const showQrCode = () => {
    qrCodeModal.value.show = true;
}

const showScrollUp = computed(() => {
    return scrollY.value > (window.innerHeight - 250);
})

const scrollUp = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}


const handleKeyDown = (event: KeyboardEvent) => {
    if(event.key === 'ArrowDown') {
        event.preventDefault();
        logList.value.nextLog()
    }
    if(event.key === 'ArrowUp') {
        event.preventDefault();
        logList.value.previousLog()
    }
}


onUnmounted(() => {
    window.removeEventListener('scroll', updateScroll);
});
</script>

<template>
    <div>
        <div v-if="!page.user.can_specify_subdomains"
            class="h-20 bg-pink-600 flex flex-col items-center justify-center text-white font-medium text-lg">
            <p>You are currently using the free version of Cloakr.</p>
            <p class="font-bold">
                <a href="https://cloakr.dev/get-pro" class="underline">Upgrade to Cloakr
                    Pro</a> to get access to our fast global network, custom domains, infinite tunnel duration and more.
            </p>
        </div>
        <div class="px-4 pb-16">
            <Header :subdomains="page.subdomains" @search-updated="search = $event" @show-qr-code="showQrCode" />


            <div
                class="flex flex-col md:flex-row items-start max-w-7xl mx-auto mt-8 space-y-4 md:space-y-0 md:space-x-4">
                <Logs ref="logList" :maxLogs="page.max_logs" :search="search" :currentLog="currentLog"
                    @set-log="setLog" />

                <Card class="p-4 w-full">
                    <EmptyState v-if="isEmptyObject(currentLog)" :subdomains="page.subdomains" />
                    <LogDetail v-else :log="currentLog" @replay="logList.replay" />
                </Card>
            </div>

            <Teleport to="body">
                <QrCodeModal ref="qrCodeModal" :subdomains="page.subdomains" />
            </Teleport>
        </div>

        <Button v-if="showScrollUp" @click="scrollUp" variant="outline"
            class="h-12 w-12 z-10 border rounded-md fixed bottom-4 right-4">
            <Icon icon="radix-icons:chevron-up" />
            <span class="sr-only">Scroll up</span>
        </Button>
    </div>
</template>