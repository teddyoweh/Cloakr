<script setup lang="ts">
import {
    AccordionTable, AccordionTableRow,
    TableBody,
    TableCell,
} from '@/components/ui/table'
import {Accordion, AccordionContent, AccordionItem, AccordionTrigger} from '@/components/ui/accordion'
import RowAccordionButton from '@/components/ui/RowAccordionButton.vue'
import {Icon} from '@iconify/vue'
import {JsonViewer} from "vue3-json-viewer"
import "vue3-json-viewer/dist/index.css";
import {bodyIsJson, copyToClipboard, isEmptyObject, isNestedStructure, toPhpArray} from '@/lib/utils'
import {nextTick, onMounted, onUnmounted, reactive, ref, watch} from 'vue'
import {useLocalStorage} from '@/lib/composables/useLocalStorage'
import {
    ArrowsRightLeftIcon,
    CircleStackIcon,
    CodeBracketIcon,
    DocumentTextIcon
} from "@heroicons/vue/16/solid";
import AccordionTableHeader from "@/components/ui/table/AccordionTableHeader.vue";
import BodyViewButton from "@/components/ui/BodyViewButton.vue";
import {useColorMode} from "@vueuse/core";
import IconCopyButton from "@/components/ui/IconCopyButton.vue";


const props = defineProps<{
    request: RequestData
}>()


const requestHeadersVisible = useLocalStorage<boolean>('requestHeadersVisible', true)
const postParametersVisible = useLocalStorage<boolean>('postParametersVisible', true)
const pluginVisible = useLocalStorage<boolean>('pluginVisible', true)

const accordionState = ref('requestHeaderOpen' as string);
const postParametersAccordionState = ref('postParametersOpen' as string);
const bodyAccordionState = ref('bodyOpen' as string);
const pluginAccordionState = ref('pluginOpen' as string);

const bodyView = ref('json' as 'json' | 'raw')

const rowAccordion = reactive({} as Record<string, boolean>)

const mode = useColorMode();

onMounted(async () => {
    await nextTick();

    if (requestHeadersVisible.value === false) {
        accordionState.value = ''
    }
    if (postParametersVisible.value === false) {
        postParametersAccordionState.value = ''
    }
    if (pluginVisible.value === false) {
        pluginAccordionState.value = ''
    }

    await checkTruncatedRows();
    window.addEventListener('resize', checkTruncatedRows);
})

watch(() => props.request, async () => {

    if (bodyIsJson(props.request)) {
        bodyView.value = 'json';
    } else {
        bodyView.value = 'raw';
    }


    await checkTruncatedRows();
});

watch(accordionState, (value) => {
    if (value === 'requestHeaderOpen') {
        requestHeadersVisible.value = true;
    } else {
        requestHeadersVisible.value = false;
    }
});

watch(postParametersAccordionState, (value) => {
    if (value === 'postParametersOpen') {
        postParametersVisible.value = true;
    } else {
        postParametersVisible.value = false;
    }
});

watch(pluginAccordionState, (value) => {
    if (value === 'pluginOpen') {
        pluginVisible.value = true;
    } else {
        pluginVisible.value = false;
    }
});

const checkTruncatedRows = async () => {
    await nextTick();

    Object.entries(props.request.post).forEach(([_, value]: [string, PostValue]) => {
        const el = document.querySelector(`[data-truncate="post_${value.name}"]`);
        if (el) {
            const rowName = el.getAttribute('data-truncate') ?? 'post_' + value.name;

            if (el.scrollWidth > el.clientWidth) {
                rowAccordion[rowName] = false;
            } else {
                delete rowAccordion[rowName];
            }
        }
    });

    Object.entries(props.request.headers).forEach(([key, _]) => {
        const el = document.querySelector(`[data-truncate="headers_${key}"]`);

        if (el) {
            const rowName = el.getAttribute('data-truncate') ?? 'headers_' + key;
            if (el.scrollWidth > el.clientWidth) {
                rowAccordion[rowName] = false;
            } else {
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
    <div class="max-w-full px-6 pt-3">
        <Accordion type="single" collapsible v-model="pluginAccordionState"
                   v-if="request.plugin && !isEmptyObject(request.plugin)">

            <AccordionItem value="pluginOpen">
                <AccordionTrigger>
                    <div>{{ request.plugin.plugin }}: <span class="font-mono">{{ request.plugin.uiLabel }}</span></div>
                    <template v-slot:action>
                        <IconCopyButton @click="copyToClipboard(toPhpArray(request.plugin.details, 'pluginDetails'))">
                            Copy as PHP array
                        </IconCopyButton>
                    </template>
                    <template v-slot:icon>
                        <CircleStackIcon class="size-4"/>
                    </template>
                </AccordionTrigger>
                <AccordionContent class="px-2">
                    <JsonViewer v-if="isNestedStructure(request.plugin.details)" :expand-depth="2" :value="request.plugin.details"
                                :class="{ 'jv-light': mode === 'light', 'jv-dark': mode === 'dark' }"/>

                    <AccordionTable v-else class="table-fixed max-w-full ">
                        <TableBody class="font-mono">
                            <AccordionTableRow v-for="[key, value] of Object.entries(request.plugin.details)"
                                               :key="key">
                                <TableCell class="w-2/5 align-top text-gray-500 dark:text-gray-300">
                                    {{ key }}
                                </TableCell>

                                <TableCell class="pr-0 text-gray-800 dark:text-white" v-html="value">

                                </TableCell>

                            </AccordionTableRow>
                        </TableBody>
                    </AccordionTable>

                    <div class="p-4 rounded-md bg-gray-100 dark:bg-gray-800 mt-4 flex items-center">
                        <Icon icon="radix-icons:info-circled" class="h-4 w-4 mr-2"/>
                        <p>Learn how to use and write your own <a class="text-pink-600 dark:text-pink-400 underline">Request
                            Plugins</a> in the documentation.</p>
                    </div>
                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <div v-if="Object.keys(request.query).length > 0"
             class="bg-gray-50 dark:bg-white/10 border border-gray-200 dark:border-white/10 rounded-xl  py-2.5  mb-6">

            <div class="flex flex-1 items-center justify-between mb-3 pl-4 pr-2 font-medium">
                <div class="flex items-center space-x-2 text-sm text-gray-800 dark:text-white">
                    <CodeBracketIcon class="size-4 "/>
                    <div>Query parameters</div>
                </div>
                <div>
                    <IconCopyButton @click="copyToClipboard(toPhpArray(request.query, 'queryParameters'))">
                        Copy as PHP array
                    </IconCopyButton>
                </div>
            </div>

            <div class="pl-2 pr-2">
                <AccordionTable>
                    <TableBody class="font-mono">
                        <AccordionTableRow v-for="[key, value] of Object.entries(request.query)" :key="key">
                            <TableCell class="w-1/5 text-gray-500 dark:text-gray-300">
                                {{ key }}
                            </TableCell>

                            <TableCell class="text-gray-800 break-all dark:text-white">
                                {{ value }}
                            </TableCell>
                        </AccordionTableRow>
                    </TableBody>
                </AccordionTable>
            </div>
        </div>

        <Accordion type="single" collapsible v-model="postParametersAccordionState"
                   v-if="request.post && !isEmptyObject(request.post)">
            <AccordionItem value="postParametersOpen">
                <AccordionTrigger>

                    <div>Post Parameters</div>
                    <template v-slot:action>
                        <IconCopyButton @click="copyToClipboard(toPhpArray(request.post, 'postData'))">
                            Copy as PHP array
                        </IconCopyButton>
                    </template>
                    <template v-slot:icon>
                        <ArrowsRightLeftIcon class="size-4"/>
                    </template>
                </AccordionTrigger>
                <AccordionContent class="px-2">
                    <AccordionTable>
                        <AccordionTableHeader/>
                        <TableBody class="font-mono">
                            <AccordionTableRow v-for="[key, value] of Object.entries(request.post)" :key="key">
                                <TableCell class="w-2/5 align-top text-gray-500 dark:text-gray-300">
                                    {{ value.name }}
                                </TableCell>

                                <TableCell class="pr-0 text-gray-800 dark:text-white">
                                    <div class="group w-[99%] relative flex items-center">
                                        <div class="pr-6 break-all" :data-truncate="'post_' + value.name"
                                             :class="{ 'truncate': !rowAccordion.hasOwnProperty('post_' + value.name) || rowAccordion['post_' + value.name] === false }">
                                            {{ value.value }}
                                        </div>
                                        <div>
                                            <RowAccordionButton v-if="rowAccordion.hasOwnProperty('post_' + value.name)"
                                                                @click="rowAccordion['post_' + value.name] = !rowAccordion['post_' + value.name]"
                                                                :rotate="rowAccordion['post_' + value.name]"/>
                                        </div>
                                    </div>
                                </TableCell>

                            </AccordionTableRow>
                        </TableBody>
                    </AccordionTable>

                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <Accordion type="single" collapsible v-model="accordionState">
            <AccordionItem value="requestHeaderOpen">
                <AccordionTrigger>
                    <div>Headers</div>
                    <template v-slot:action>
                        <IconCopyButton @click="copyToClipboard(toPhpArray(request.headers, 'headers'))">
                            Copy as PHP array
                        </IconCopyButton>
                    </template>
                    <template v-slot:icon>
                        <ArrowsRightLeftIcon class="size-4"/>
                    </template>
                </AccordionTrigger>
                <AccordionContent class="px-2">
                    <AccordionTable>
                        <AccordionTableHeader/>
                        <TableBody class="font-mono">
                            <AccordionTableRow v-for="[key, value] of Object.entries(request.headers)" :key="key"
                                      class="hover:bg-white">
                                <TableCell class="text-gray-500 dark:text-gray-300">
                                    {{ key }}
                                </TableCell>

                                <TableCell class="pr-0 text-gray-800 dark:text-white">
                                    <div class="group w-[99%] relative flex items-center">
                                        <div class="pr-6 break-all" :data-truncate="'headers_' + key"
                                             :class="{ 'truncate': !rowAccordion.hasOwnProperty('headers_' + key) || rowAccordion['headers_' + key] === false }">
                                            {{ value }}
                                        </div>
                                        <div>
                                            <RowAccordionButton v-if="rowAccordion.hasOwnProperty('headers_' + key)"
                                                                @click="rowAccordion['headers_' + key] = !rowAccordion['headers_' + key]"
                                                                :rotate="rowAccordion['headers_' + key]"/>
                                        </div>
                                    </div>
                                </TableCell>
                            </AccordionTableRow>
                        </TableBody>
                    </AccordionTable>

                </AccordionContent>
            </AccordionItem>
        </Accordion>

        <Accordion type="single" collapsible v-model="bodyAccordionState"
                   v-if="request.body">
            <AccordionItem value="bodyOpen">
                <AccordionTrigger>

                    <div>Body</div>
                    <template v-slot:action>
                        <IconCopyButton v-if="request.body" @click="copyToClipboard(request.body)">
                            Copy
                        </IconCopyButton>
                    </template>
                    <template v-slot:icon>
                        <DocumentTextIcon class="size-4"/>
                    </template>
                </AccordionTrigger>
                <AccordionContent class="px-2">
                    <div v-if="request.body === ''">
                        <span class="text-sm opacity-75 font-mono pt-2 inline-block px-2">Request body is empty.</span>
                    </div>

                    <div v-else class="border border-gray-200 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-white/10 dark:border-white/10">

                        <div class="flex items-center space-x-2 px-4 pt-4 mb-4">
                            <BodyViewButton @click="bodyView = $event" :active="bodyView === 'raw'" label="Raw"
                                            value="raw"/>
                            <BodyViewButton @click="bodyView = $event" :active="bodyView === 'json'" label="JSON"
                                            value="json" v-if="bodyIsJson(request)"/>
                        </div>

                        <JsonViewer v-if="bodyView === 'json'" :expand-depth="2" :value="JSON.parse(request.body ?? '')"
                                    :class="{ 'jv-light': mode === 'light', 'jv-dark': mode === 'dark' }"/>
                        <pre v-if="bodyView === 'raw'"
                             class="p-6 prettyprint break-all whitespace-pre-wrap">{{ request.body ?? '' }}
            </pre>
                    </div>

                </AccordionContent>
            </AccordionItem>
        </Accordion>

    </div>
</template>
