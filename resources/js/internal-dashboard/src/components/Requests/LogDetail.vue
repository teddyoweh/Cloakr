<script setup lang="ts">
import {Tabs, TabsContent, TabsList, TabsTrigger} from '@/components/ui/tabs'
import Request from './Tabs/Request.vue';
import Response from './Tabs/Response.vue';
import ResponseBadge from '../ui/ResponseBadge.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger
} from '@/components/ui/tooltip'
import {copyToClipboard} from '@/lib/utils';
import IconTextButton from "@/components/ui/IconTextButton.vue";
import {ArrowPathIcon} from "@heroicons/vue/16/solid";
import {EllipsisVerticalIcon} from "@heroicons/vue/16/solid";
import {ClipboardIcon} from "@heroicons/vue/16/solid";
import {ref} from "vue";

defineProps<{
    log: CloakrLog | null
}>()

const emit = defineEmits(['replay', 'modified-replay'])

const activeTab = ref('request' as 'request' | 'response')
</script>

<template>
    <div v-if="log" class="bg-white dark:bg-gray-900 pb-6 relative">
        <div class="sticky top-0 bg-white dark:bg-gray-900 z-20">
            <div class="px-6 pt-6 flex flex-col md:flex-row items-start justify-between">
                <div class="w-full">
                    <div class="flex flex-col-reverse md:flex-row items-start lg:items-center w-full ">

                        <div class="w-full">
                            <div class="flex flex-col-reverse lg:space-x-4 lg:flex-row items-start lg:items-center">

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <div class="font-medium truncate pt-0.5">
                                                <span class="dark:text-gray-300 text-sm">{{ log.request.method }}</span>
                                                {{ log.request.uri }}
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {{ log.request.uri }}
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <ResponseBadge v-if="log.response" :status-code="log.response.status"
                                               :reason="log.response.reason" class="mb-1 lg:mb-0 mt-px"/>
                            </div>

                            <div class="text-gray-500 dark:text-gray-300 font-normal text-sm flex flex-col lg:flex-row lg:items-center lg:space-x-2 mt-3">
                                <div>Received on {{ log.performed_at }}</div>
                                <div class="hidden lg:block">·</div>
                                <div>Took {{ log.duration }}ms to resolve</div>
                            </div>
                        </div>

                        <div class="flex items-center md:justify-end w-full mb-2 space-x-2">
                            <div class="flex">
                                <IconTextButton @click="emit('replay', log)" :icon="ArrowPathIcon"
                                                class="rounded-r-none">
                                    Replay
                                </IconTextButton>
                                <IconTextButton @click="emit('modified-replay', log)" :icon="EllipsisVerticalIcon"
                                                class="rounded-l-none border-l-0 px-0 pl-2.5 pr-1">
                                </IconTextButton>
                            </div>

                            <IconTextButton @click="copyToClipboard(log.request.curl)" :icon="ClipboardIcon">
                                Copy as cURL
                            </IconTextButton>
                        </div>
                    </div>
                </div>
            </div>
            <Tabs v-model="activeTab" default-value="request" class="w-full mt-4">
                <TabsList class="w-full px-6 sticky top-0">
                    <TabsTrigger value="request" class="">
                        Request
                    </TabsTrigger>
                    <TabsTrigger value="response" class="">
                        Response
                    </TabsTrigger>
                </TabsList>
            </Tabs>
        </div>
        <div>
            <Tabs v-model="activeTab" default-value="request">
                <TabsContent value="request">
                    <Request :request="log.request"/>
                </TabsContent>
                <TabsContent value="response">
                    <Response :response="log.response"/>
                </TabsContent>
            </Tabs>
        </div>
    </div>
</template>
