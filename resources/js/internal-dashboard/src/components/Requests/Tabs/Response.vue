<script setup lang="ts">
import {
    AccordionTable, AccordionTableRow,
    TableBody,
    TableCell,
} from '@/components/ui/table'
import {Accordion, AccordionContent, AccordionItem, AccordionTrigger} from '@/components/ui/accordion'
import RowAccordionButton from '@/components/ui/RowAccordionButton.vue'
import {JsonViewer} from "vue3-json-viewer"
import "vue3-json-viewer/dist/index.css";
import {bodyIsJson, copyToClipboard, bodyIsHtml, toPhpArray} from '@/lib/utils'
import {useLocalStorage} from '@/lib/composables/useLocalStorage'
import {computed, nextTick, onMounted, onUnmounted, reactive, ref, watch} from 'vue'
import {ArrowsRightLeftIcon, DocumentTextIcon} from "@heroicons/vue/16/solid";
import IconCopyButton from "@/components/ui/IconCopyButton.vue";
import AccordionTableHeader from "@/components/ui/table/AccordionTableHeader.vue";
import BodyViewButton from "@/components/ui/BodyViewButton.vue";
import {useColorMode} from "@vueuse/core";


const props = defineProps<{
    response: ResponseData
}>()


const responseHeadersVisible = useLocalStorage<boolean>('responseHeadersVisible', true)
const accordionState = ref('responseHeaderOpen' as string);
const bodyAccordionState = ref('bodyOpen' as string);

const bodyView = ref('raw' as 'json' | 'raw' | 'preview')

const rowAccordion = reactive({} as Record<string, boolean>)

const mode = useColorMode();


onMounted(async () => {
    await nextTick();

    if (responseHeadersVisible.value === false) {
        accordionState.value = ''
    }

    await checkTruncatedRows();
    window.addEventListener('resize', checkTruncatedRows);
})

watch(accordionState, (value) => {
    if (value === 'responseHeaderOpen') {
        responseHeadersVisible.value = true;
    } else {
        responseHeadersVisible.value = false;
    }
});

watch(() => props.response, async () => {

    if (bodyIsJson(props.response)) {
        bodyView.value = 'json';
    } else if (bodyIsHtml(props.response)) {
        bodyView.value = 'preview';
    } else {
        bodyView.value = 'raw';
    }

    await checkTruncatedRows();
});


const checkTruncatedRows = async () => {
    await nextTick();

    Object.entries(props.response.headers).forEach(([key, _]) => {
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

const responseEmpty = computed(() => {
    return props.response.body === '';
});

onUnmounted(() => {
    window.removeEventListener('resize', checkTruncatedRows);
})
</script>

<template>
    <div class="max-w-full px-6 pt-3">
        <Accordion type="single" collapsible v-model="accordionState">
            <AccordionItem value="responseHeaderOpen">
                <AccordionTrigger>
                    <div>Headers</div>
                    <template v-slot:action>
                        <IconCopyButton @click="copyToClipboard(toPhpArray(response.headers, 'headers'))">
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
                            <AccordionTableRow v-for="[key, value] of Object.entries(response.headers)" :key="key">
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


        <Accordion type="single" collapsible v-model="bodyAccordionState">
            <AccordionItem value="bodyOpen">
                <AccordionTrigger>

                    <div>Body</div>
                    <template v-slot:action>
                        <IconCopyButton v-if="!responseEmpty" @click="copyToClipboard(response.body)">
                            Copy
                        </IconCopyButton>
                    </template>
                    <template v-slot:icon>
                        <DocumentTextIcon class="size-4"/>
                    </template>
                </AccordionTrigger>
                <AccordionContent class="px-2">
                    <div v-if="responseEmpty">
                        <span class="text-sm opacity-75 font-mono pt-2 inline-block px-2">Response body is empty.</span>
                    </div>
                    <div v-else
                         class="border border-gray-200 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-white/10  dark:border-[#606062]">

                        <div class="flex items-center space-x-2 px-4 pt-4 mb-4">
                            <BodyViewButton @click="bodyView = $event" :active="bodyView === 'raw'" label="Raw"
                                            value="raw"/>
                            <BodyViewButton @click="bodyView = $event" :active="bodyView === 'json'" label="JSON"
                                            value="json" v-if="bodyIsJson(response)"/>
                            <BodyViewButton @click="bodyView = $event" :active="bodyView === 'preview'" label="Preview"
                                            value="preview"/>
                        </div>

                        <JsonViewer v-if="bodyView === 'json'" :expand-depth="2"
                                    :value="JSON.parse(response.body ?? '')"
                                    :class="{ 'jv-light': mode === 'light', 'jv-dark': mode === 'dark' }"/>
                        <pre v-if="bodyView === 'raw'"
                             class="p-6 text-pretty break-all whitespace-pre-wrap">{{ response.body ?? '' }}
</pre>

                        <div v-if="bodyView === 'preview'"
                             class="border border-gray-200 dark:border-gray-700 rounded-md m-4 overflow-hidden">
                            <iframe :srcdoc="response.body" style="height: 500px;"
                                    class="w-full h-full"></iframe>
                        </div>
                    </div>

                </AccordionContent>
            </AccordionItem>
        </Accordion>

    </div>
</template>
