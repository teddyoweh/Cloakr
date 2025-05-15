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
import { bodyIsJson, copyToClipboard, toPhpArray } from '@/lib/utils'
import { useLocalStorage } from '@/lib/composables/useLocalStorage'
import { nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { useColorMode } from '@vueuse/core'


const props = defineProps<{
    response: ResponseData
}>()

const mode = useColorMode()

const responseHeadersVisible = useLocalStorage<boolean>('responseHeadersVisible', true)
const accordionState = ref('responseHeaderOpen' as string);
const responseView = useLocalStorage<string>('responseView', 'raw')

const rowAccordion = reactive({} as Record<string, boolean>)


onMounted(async () => {
    await nextTick();

    if (responseHeadersVisible.value === false) {
        accordionState.value = ''
    }

    checkTruncatedRows();
    window.addEventListener('resize', checkTruncatedRows);
})

watch(accordionState, (value) => {
    if (value === 'responseHeaderOpen') {
        responseHeadersVisible.value = true;
    }
    else {
        responseHeadersVisible.value = false;
    }
});

watch(() => props.response, async () => {
    await nextTick();

    checkTruncatedRows();
});


const checkTruncatedRows = () => {
    Object.entries(props.response.headers).forEach(([key, _]) => {
        const el = document.querySelector(`[data-truncate="headers_${key}"]`);
        if (el) {
            const rowName = el.getAttribute('data-truncate') ?? 'headers_' + key;

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
        <Accordion type="single" collapsible v-model="accordionState">
            <AccordionItem value="responseHeaderOpen">
                <AccordionTrigger>
                    <button class="flex relative z-10 justify-between items-center w-full pr-4">
                        Headers
                    </button>
                </AccordionTrigger>
                <AccordionContent>
                    <div class="flex justify-end">
                        <Button @click="copyToClipboard(toPhpArray(response.headers, 'headers'))" variant="outline">
                            <Icon icon="radix-icons:copy" class="h-4 w-4 mr-2" />
                            Copy as PHP array
                        </Button>
                    </div>
                    <Table class="max-w-full table-fixed">
                        <TableBody>
                            <TableRow v-for="[key, value] of Object.entries(response.headers)" :key="key">
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

            <div v-if="response.body === null || response.body === undefined || response.body === ''">
                <span class="text-sm opacity-75 font-mono pt-2 inline-block">Response body is empty.</span>
            </div>

            <div v-else>
                <div class="flex flex-row-reverse w-full justify-between mt-2">
                    <Button @click="copyToClipboard(response.body)" variant="outline">
                        <Icon icon="radix-icons:copy" class="h-4 w-4 mr-2" />
                        Copy
                    </Button>

                    <ToggleGroup type="single" v-model="responseView">
                        <ToggleGroupItem value="raw">
                            Raw
                        </ToggleGroupItem>
                        <ToggleGroupItem value="json" v-if="bodyIsJson(response)">
                            JSON
                        </ToggleGroupItem>
                        <ToggleGroupItem value="preview">
                            Preview
                        </ToggleGroupItem>
                    </ToggleGroup>

                </div>
                <pre v-if="responseView == 'raw'"
                    class="p-6 break-all whitespace-pre-wrap">{{ response.body ?? '' }}</pre>
                <JsonViewer v-if="responseView == 'json'" :expand-depth="2" :value="JSON.parse(response.body ?? '')" :class="{'jv-light': mode === 'light', 'jv-dark': mode === 'dark'}" />
                <iframe v-if="responseView == 'preview'" :srcdoc="response.body" style="height: 500px;"
                    class="border border-gray-200 dark:border-gray-700 rounded-md mt-4 w-full h-full"></iframe>
            </div>
        </div>
    </div>
</template>