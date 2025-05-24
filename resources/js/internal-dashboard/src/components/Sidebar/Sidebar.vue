<script setup lang="ts">
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger} from "@/components/ui/tooltip";

import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from "@/components/ui/table";
import ResponseBadge from "@/components/ui/ResponseBadge.vue";
import Search from "@/components/ui/Search.vue";
import {computed, onMounted, ref} from "vue";
import {useLocalStorage} from "@/lib/composables/useLocalStorage.ts";
import ReconnectingWebSocket from "reconnecting-websocket";
import IconButton from "@/components/ui/IconButton.vue";
import TrashIcon from "@heroicons/vue/16/solid/TrashIcon";
import {Bars3Icon, BarsArrowUpIcon, SignalSlashIcon, SignalIcon, InformationCircleIcon} from "@heroicons/vue/16/solid";

const props = defineProps<{
    maxLogs: number,
    search: string,
    currentLog: CloakrLog | null
}>()

const emit = defineEmits(['set-log'])


const logs = ref([] as CloakrLog[]);
const highlightNextLog = ref(false as boolean);
const followRequests = useLocalStorage<boolean>('followLogs', true);
const listenForRequests = ref(true as boolean);
const search = ref('' as string)
const searchInput = ref()

onMounted(() => {
    connect();
    loadLogs();
});

const loadLogs = () => {
    fetch('/api/logs')
        .then((response) => {
            return response.json();
        })
        .then((data) => {
            logs.value = data;

            emit('set-log', logs.value[0]);
        });
}

const clearLogs = () => {
    fetch('/api/logs/clear');
    logs.value = []
    emit('set-log', null);
}

const toggleListenForRequests = () => {
    listenForRequests.value = !listenForRequests.value;
}

const toggleFollowRequests = () => {
    followRequests.value = !followRequests.value
}

const connect = () => {
    let conn = new ReconnectingWebSocket(`ws://${window.location.hostname}:${window.location.port}/socket`);

    conn.onmessage = (e) => {
        const request = JSON.parse(e.data);
        const index = logs.value.findIndex(log => log.id === request.id);
        if (index > -1) {
            logs.value[index] = request;
        } else {
            logs.value.unshift(request);
        }

        logs.value = logs.value.splice(0, props.maxLogs);

        if (highlightNextLog.value || followRequests.value) {
            emit('set-log', logs.value[0]);

            highlightNextLog.value = false;
        }
    };
}

const replay = (log: CloakrLog) => {
    highlightNextLog.value = true;
    fetch('/api/replay/' + log.id);
}

const nextLog = () => {
    const currentIndex = logs.value.findIndex(log => log.id === props.currentLog?.id);

    if (currentIndex === -1) {
        return;
    }

    const nextIndex = currentIndex + 1;
    if (nextIndex >= logs.value.length) {
        emit('set-log', logs.value[0]);
        return;
    }

    emit('set-log', logs.value[nextIndex]);
}

const previousLog = () => {
    const currentIndex = logs.value.findIndex(log => log.id === props.currentLog?.id);

    if (currentIndex === -1) {
        return;
    }

    const nextIndex = currentIndex - 1;
    if (nextIndex < 0) {
        emit('set-log', logs.value[0]);
        return;
    }

    emit('set-log', logs.value[nextIndex]);
}

const filteredLogs = computed(() => {
    const searchTerm = search.value ?? '';

    if (searchTerm === '') {
        return logs.value;
    }

    if (searchTerm.startsWith("/")) {
        return logs.value.filter(log => {
            return log.request.uri.indexOf(searchTerm) !== -1;
        })
    } else {
        return logs.value.filter((log) => {
            if (isSearchableResponse(log.response)) {
                return log.response.body.indexOf(searchTerm) !== -1;
            } else {
                return log.request.uri.indexOf(searchTerm) !== -1;
            }
        })
    }

})

const isSearchableResponse = (response: ResponseData): boolean => {
    if (response.headers && response.headers['Content-Type']) {
        const contentTypes = ["application/json", "application/ld-json", "text/plain"];
        return contentTypes.some(substring => response.headers['Content-Type'].includes(substring));
    }

    return false;
}

const focusSearch = () => {
    searchInput.value.focusSearch()
}

defineCloakr({replay, nextLog, previousLog, focusSearch, clearLogs, toggleFollowRequests});
</script>

<template>
    <div
        class="w-[400px] h-[calc(100vh-81px)] border-r border-gray-200 dark:border-gray-700 overflow-y-auto bg-white dark:bg-gray-900">

        <div class="pt-4 text-sm sticky bg-white dark:bg-gray-900 top-0 z-20">
            <h3 class="inline px-4 font-medium mr-2">Requests</h3> <span class="text-gray-400">{{ logs.length }}</span>

            <div class="flex flex-col lg:flex-row items-center px-4 items-center justify-between space-x-2 mt-4">


                <Search ref="searchInput" v-model="search"/>

                <div class="flex space-x-2 mt-2 lg:mt-0 justify-end w-full lg:w-auto pr-1 lg:pr-0">
                    <IconButton v-if="false"
                                @click="toggleListenForRequests"
                                :icon="!listenForRequests ? SignalSlashIcon : SignalIcon"
                                :bg-class="!listenForRequests ? 'bg-amber-600 hover:bg-amber-500 text-white hover:text-white' : undefined"
                                tooltip-text="Stop listening for requests"
                                shortcut="R"/>

                    <IconButton @click="toggleFollowRequests"
                                :icon="!followRequests ? Bars3Icon : BarsArrowUpIcon"
                                tooltip-text="Follow requests" shortcut="F"/>

                    <IconButton @click="clearLogs" :icon="TrashIcon" tooltip-text="Clear logs" shortcut="R"/>
                </div>

            </div>
            <Table class="mt-4">
                <TableHeader>
                    <TableRow>
                        <TableHead class="pr-0 w-[45px]">Status</TableHead>
                        <TableHead class="pr-0">
                            URL
                        </TableHead>
                        <TableHead class="text-right pr-4 pl-0">
                            Duration
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!listenForRequests" class="border-0 font-medium">
                        <TableCell class="p-0" colspan="3">
                            <div
                                class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-100 px-4 py-5 border-b border-dashed border-gray-200 dark:border-gray-700">
                                <div class="flex items-center">
                                    <InformationCircleIcon class="hidden lg:block size-4 mr-2"/>
                                    Requests listener is off
                                </div>
                                <button @click="listenForRequests = true" class="text-primary" type="button">
                                    Turn back on
                                </button>

                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

        </div>
        <Table>
            <TableBody>

                <TableRow v-for="request in filteredLogs" :key="request.id" @click="emit('set-log', request)"
                          class="border-l-4 border-l-transparent"
                          :class="{ 'bg-gray-50 border-l-primary dark:bg-gray-700': currentLog === request }">
                    <TableCell class="pr-0 align-top pl-2 lg:pl-4">
                        <ResponseBadge
                            :statusCode="request.response && request.response.status ? request.response.status : null"/>
                    </TableCell>

                    <TableCell class="align-top text-left pr-0 pl-2 lg:pl-4 flex flex-col items-start font-medium">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <div class="max-w-[155px] lg:max-w-[180px] truncate pt-0.5 text-gray-800 dark:text-white">
                                        <span class="text-gray-500 dark:text-gray-300">{{
                                                request.request.method
                                            }}</span>
                                        {{ request.request.uri }}
                                    </div>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {{ request.request.uri }}
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                        <TooltipProvider v-if="request.request.plugin">
                            <Tooltip>
                                <TooltipTrigger>
                                    <span class="text-xs">{{ request.request.plugin?.uiLabel }}</span>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {{ request.request.plugin?.uiLabel }} - {{ request.request.plugin?.plugin }}
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </TableCell>
                    <TableCell class=" text-right text-gray-500 dark:text-gray-300 pl-0 pr-4">
                        {{ request.duration?.toFixed(0) }}ms
                    </TableCell>
                </TableRow>

                <TableRow>
                    <TableCell class="text-center text-gray-400 py-8" colspan="3">
                        <span v-if="logs.length > 0">
                            No more requests to show
                        </span>
                        <span v-if="logs.length === 0">
                            No requests to show yet
                        </span>
                    </TableCell>
                </TableRow>


            </TableBody>
        </Table>
    </div>
</template>
