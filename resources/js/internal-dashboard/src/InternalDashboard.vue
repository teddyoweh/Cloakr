<script setup lang="ts">
import Header from '@/components/Header/Header.vue'
import QrCodeModal from '@/components/QrCodeModal.vue'
import ModifiedReplayModal from '@/components/ModifiedReplayModal.vue'
import LogDetail from '@/components/Requests/LogDetail.vue'
import {exampleSubdomains, exampleUser} from './lib/devUtils';
import {onMounted, ref} from 'vue';
import {isEmptyObject} from './lib/utils';
import EmptyState from './components/Requests/EmptyState.vue';
import Sidebar from "@/components/Sidebar/Sidebar.vue";

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
const header = ref()
const sidebar = ref()
const qrCodeModal = ref()
const modifiedReplayModal = ref()

onMounted(() => {
    window.addEventListener('keydown', setupKeybindings);
});

const setLog = (log: CloakrLog | null) => {
    currentLog.value = log;
}

const showQrCode = () => {
    qrCodeModal.value.show = true;
}

const showModifiedReplay = () => {
    modifiedReplayModal.value.show = true;
}


const setupKeybindings = (event: KeyboardEvent) => {
    const target = event.target as HTMLElement;

    if (
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.isContentEditable
    ) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        sidebar.value.nextLog()
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        sidebar.value.previousLog()
    } else if (event.key === 'o') {
        header.value.openSubdomainInNewTab()
    } else if (event.key === 'l') {
        header.value.copySubdomainToClipboard()
    } else if (event.key === 'c') {
        sidebar.value.clearLogs()
    } else if (event.key === 'f') {
        sidebar.value.toggleFollowRequests()
    } else if (event.key === 'q') {
        showQrCode()
    } else if (event.key === 'r' && !event.metaKey && !event.ctrlKey && currentLog.value) {
        sidebar.value.replay(currentLog.value)
    } else if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        sidebar.value.focusSearch()
    }

}

</script>

<template>
    <div class=" mx-auto h-screen overflow-hidden min-[2000px]:border-l min-[2000px]:border-r">
        <div v-if="!page.user.can_specify_subdomains"
             class="h-20 bg-pink-600 flex flex-col items-center justify-center text-white font-medium text-lg">
            <p>You are currently using the free version of Cloakr.</p>
            <p class="font-bold">
                <a href="https://cloakr.dev/get-pro" class="underline">Upgrade to Cloakr
                    Pro</a> to get access to our fast global network, custom domains, infinite tunnel duration and more.
            </p>
        </div>
        <div class=" h-full">
            <Header ref="header" :subdomains="page.subdomains" @search-updated="search = $event"
                    @show-qr-code="showQrCode"/>

            <div class="w-full flex items-start bg-white dark:bg-gray-900">
                <Sidebar ref="sidebar" :maxLogs="page.max_logs" :search="search" :currentLog="currentLog"
                         @set-log="setLog"/>
                <div class="relative w-11/12 h-[calc(100vh-81px)] overflow-y-auto">
                    <EmptyState v-if="isEmptyObject(currentLog)" :subdomains="page.subdomains"/>
                    <LogDetail v-else :log="currentLog" @replay="sidebar.replay" @modified-replay="showModifiedReplay"/>
                </div>
            </div>


            <Teleport to="body">
                <QrCodeModal ref="qrCodeModal" :subdomains="page.subdomains"/>
                <ModifiedReplayModal ref="modifiedReplayModal" :currentLog="currentLog"/>
            </Teleport>
        </div>
    </div>
</template>
