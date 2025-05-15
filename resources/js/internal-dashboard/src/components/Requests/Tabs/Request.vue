<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableRow,
} from '@/components/ui/table'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import { Button } from '@/components/ui/button'
import { Icon } from '@iconify/vue'
import { JsonViewer } from "vue3-json-viewer"
import "vue3-json-viewer/dist/index.css";
import { bodyIsJson, copyToClipboard, isEmptyObject, toPhpArray } from '@/lib/utils'
import { nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useLocalStorage } from '@/lib/composables/useLocalStorage'
import { useColorMode } from '@vueuse/core'


const props = defineProps<{
    request: RequestData
}>()

const mode = useColorMode()

const requestHeadersVisible = useLocalStorage<boolean>('requestHeadersVisible', true)
const postParametersVisible = useLocalStorage<boolean>('postParametersVisible', true)
const accordionState = ref('requestHeaderOpen' as string);
const postParametersAccordionState = ref('postParametersOpen' as string);

const rowAccordion = reactive({} as Record<string, boolean>)



onMounted(async () => {
    await nextTick();

    if (requestHeadersVisible.value === false) {
        accordionState.value = ''
    }
    if (postParametersVisible.value === false) {
        postParametersAccordionState.value = ''
    }

    checkTruncatedRows();
    window.addEventListener('resize', checkTruncatedRows);
})

watch(() => props.request, async () => {
    await nextTick();

    checkTruncatedRows();
});

watch(accordionState, (value) => {
    if (value === 'requestHeaderOpen') {
        requestHeadersVisible.value = true;
    }
    else {
        requestHeadersVisible.value = false;
    }
});

watch(postParametersAccordionState, (value) => {
    if (value === 'postParametersOpen') {
        postParametersVisible.value = true;
    }
    else {
        postParametersVisible.value = false;
    }
});

const checkTruncatedRows = () => {
    Object.entries(props.request.post).forEach(([key, value]) => {
        const el = document.querySelector(`[data-truncate="post_${value.name}"]`);
        if (el) {
            const rowName = el.getAttribute('data-truncate')
            if (el.scrollWidth > el.clientWidth) {
                rowAccordion[rowName] = false;
            }
            else {
                delete rowAccordion[rowName];
            }
        }
    });

    Object.entries(props.request.headers).forEach(([key, value]) => {
        const el = document.querySelector(`[data-truncate="headers_${key}"]`);

        if (el) {
            const rowName = el.getAttribute('data-truncate')
            if (el.scrollWidth > el.clientWidth) {
                rowAccordion[rowName] = false;
            }
            else {
                delete rowAccordion[rowName];
            }
        }

    });
}

onUnmounted(() => {
    window.removeEventListener('resize', checkTruncatedRows);
})
</script>

<template>
    <div class="max-w-full">
        <div v-if="Object.keys(request.query).length > 0" class="mb-4">
            <div class="pt-4 font-medium text-base mb-2">Query parameters</div>
            <div class="flex justify-end">
                <Button @click="copyToClipboard(toPhpArray(request.query, 'queryParameters'))" variant="outline">
                    <Icon icon="radix-icons:copy" class="h-4 w-4 mr-2" />
                    Copy as PHP array
                </Button>
            </div>
            <Table class="max-w-full">
                <TableBody>
                    <TableRow v-for="[key, value] of Object.entries(request.query)" :key="key">
                        <TableCell class="w-2/5">
                            {{ key }}
                        </TableCell>

                        <TableCell class="w-3/5 break-all">
                            {{ value }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
        <Accordion type="single" collapsible v-model="postParametersAccordionState"
            v-if="request.post && !isEmptyObject(request.post)">
            <AccordionItem value="postParametersOpen">
                <AccordionTrigger>
                    <div class="flex relative z-10 justify-between items-center w-full pr-4">
                        Post Parameters
                    </div>
                </AccordionTrigger>
                <AccordionContent>
                    <div class="flex justify-end">
                        <Button @click="copyToClipboard(toPhpArray(request.post, 'postData'))" variant="outline">
                            <Icon icon="radix-icons:copy" class="h-4 w-4 mr-2" />
                            Copy as PHP array
                        </Button>
                    </div>
                    <Table class="table-fixed max-w-full ">
                        <TableBody>
                            <TableRow v-for="[key, value] of Object.entries(request.post)" :key="key">
                                <TableCell class="w-2/5 align-top">
                                    {{ value.name }}
                                </TableCell>

                                <TableCell class="pr-0">
                                    <div class="group w-[99%] relative flex items-center">
                                        <div class="pr-6 break-all" :data-truncate="'post_' + value.name"
                                            :class="{ 'truncate': !rowAccordion.hasOwnProperty('post_' + value.name) || rowAccordion['post_' + value.name] === false }">
                                            {{ value.value }}
                                        </div>
                                        <div>
                                            <button v-if="rowAccordion.hasOwnProperty('post_' + value.name)"
                                                @click="rowAccordion['post_' + value.name] = !rowAccordion['post_' + value.name]"
                                                class="opacity-100 group-hover:opacity-100 transition-150 absolute -top-0.5 -right-0.5 bg-white border rounded-md p-1">
                                                <Icon icon="radix-icons:chevron-down"
                                                    class="h-4 w-4 transform animate duration-150"
                                                    :class="{ 'rotate-180': rowAccordion['post_' + value.name] }" />
                                            </button>
                                        </div>
                                    </div>
                                </TableCell>

                            </TableRow>
                        </TableBody>
                    </Table>

                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <Accordion type="single" collapsible v-model="accordionState">
            <AccordionItem value="requestHeaderOpen">
                <AccordionTrigger>
                    <div class="flex relative z-10 justify-between items-center w-full pr-4">
                        Headers
                    </div>
                </AccordionTrigger>
                <AccordionContent>
                    <div class="flex justify-end">
                        <Button @click="copyToClipboard(toPhpArray(request.headers, 'headers'))" variant="outline">
                            <Icon icon="radix-icons:copy" class="h-4 w-4 mr-2" />
                            Copy as PHP array
                        </Button>
                    </div>
                    <Table class="max-w-full table-fixed">
                        <TableBody>
                            <TableRow v-for="[key, value] of Object.entries(request.headers)" :key="key">
                                <TableCell class="w-2/5">
                                    {{ key }}
                                </TableCell>

                                <TableCell class="pr-0">
                                    <div class="group w-[99%] relative flex items-center">
                                        <div class="pr-6 break-all" :data-truncate="'headers_' + key"
                                            :class="{ 'truncate': !rowAccordion.hasOwnProperty('headers_' + key) || rowAccordion['headers_' + key] === false }">
                                            {{ value }}
                                        </div>
                                        <div>
                                            <button v-if="rowAccordion.hasOwnProperty('headers_' + key)"
                                                @click="rowAccordion['headers_' + key] = !rowAccordion['headers_' + key]"
                                                class="opacity-100 group-hover:opacity-100 transition-150 absolute -top-0.5 -right-0.5 bg-white border rounded-md p-1">
                                                <Icon icon="radix-icons:chevron-down"
                                                    class="h-4 w-4 transform animate duration-150"
                                                    :class="{ 'rotate-180': rowAccordion['headers_' + key] }" />
                                            </button>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <div class="mt-4">
            <div class="pt-4 font-medium text-base">Body</div>


            <div v-if="request.body === null || request.body === undefined || request.body === ''">
                <span class="text-sm opacity-75 font-mono pt-2 inline-block">Request body is empty.</span>
            </div>

            <div v-else>
                <div class="flex justify-end">
                    <Button @click="copyToClipboard(request.body)" variant="outline">
                        <Icon icon="radix-icons:copy" class="h-4 w-4 mr-2" />
                        Copy
                    </Button>
                </div>
                <JsonViewer v-if="bodyIsJson(request)" :expand-depth="2" :value="JSON.parse(request.body ?? '')"
                    :class="{ 'jv-light': mode === 'light', 'jv-dark': mode === 'dark' }" />
                <pre v-else class="p-6 prettyprint break-all whitespace-pre-wrap">{{ request.body ?? '' }}
            </pre>
            </div>
        </div>
    </div>
</template>