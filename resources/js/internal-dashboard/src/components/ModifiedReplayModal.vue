<script setup lang="ts">
import {
    Dialog,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {nextTick, reactive, ref, watch} from 'vue';
import {Input} from '@/components/ui/input';
import {Checkbox} from '@/components/ui/checkbox';
import {Textarea} from '@/components/ui/textarea'
import {Button} from '@/components/ui/button'
import SidebarDialog from "@/components/ui/dialog/SidebarDialog.vue";
import {Accordion, AccordionContent, AccordionItem, AccordionTrigger} from "@/components/ui/accordion";
import {ArrowsRightLeftIcon, DocumentTextIcon, PlusIcon, ArrowUturnLeftIcon, ArrowPathIcon} from "@heroicons/vue/16/solid";
import {AccordionTable, AccordionTableRow, TableBody, TableRow} from "@/components/ui/table";
import NarrowTableCell from "@/components/ui/table/NarrowTableCell.vue";
import MasterCheckbox from "@/components/ui/checkbox/MasterCheckbox.vue";
import TableInput from "@/components/ui/input/TableInput.vue";
import IconTextButton from "@/components/ui/IconTextButton.vue";

const emit = defineEmits(['replay-modified'])

const props = defineProps<{
    currentLog: CloakrLog | null
}>()

const replayRequest = reactive({
    uri: '',
    method: '',
    headers: {} as Record<string, string>,
    body: '',
} as ReplayRequest)

const show = ref(false as boolean)
const headersToSend = ref([] as string[])
const addedHeaders = ref({} as Record<string, string>)
const headerAccordionState = ref('headerOpen' as string);
const bodyAccordionState = ref('bodyOpen' as string);

const headersSelected = ref('checked' as 'checked' | 'unchecked' | 'indeterminate');

const availableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']


watch(show, (newVal) => {
    if (newVal && props.currentLog) {
        reset()
    }
})

watch(headersToSend, () => {
    updateMasterCheckbox()
})

const replay = () => {
    const headers = filterHeaders(replayRequest.headers, headersToSend.value);
    const additionalHeaders = filterHeaders(addedHeaders.value, headersToSend.value);

    const replay = {
        uri: replayRequest.uri,
        method: replayRequest.method,
        headers: {...headers, ...additionalHeaders},
        body: replayRequest.body,
    }

    fetch('/api/replay-modified', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(replay),
    });

    show.value = false
}

const reset = () => {
    if (props.currentLog) {
        replayRequest.uri = props.currentLog.request.uri;
        replayRequest.method = props.currentLog.request.method;
        replayRequest.headers = {...props.currentLog.request.headers};
        replayRequest.body = props.currentLog.request.body;

        headersToSend.value = Object.keys(replayRequest.headers);
        addedHeaders.value = {}
    }
}

const filterHeaders = (headers: Record<string, string>, keysToInclude: string[]) => {
    return Object.fromEntries(
        Object.entries(headers).filter(([key]) => keysToInclude.includes(key))
    );
};

const toggleHeaderToSend = (key: string) => {
    if (headersToSend.value.includes(key)) {
        headersToSend.value = headersToSend.value.filter((h) => h !== key);
    } else {
        headersToSend.value = [...headersToSend.value, key];
    }
}

const handleHeaderKeyChange = (oldKey: string, e: Event) => {
    const target = e.target as HTMLInputElement;
    const newKey = sanitizeHeaderKey(target.value);

    // Keep order of header entries
    const entries = Object.entries(replayRequest.headers);
    const index = entries.findIndex(([key]) => key === oldKey);

    if (index !== -1) {
        entries[index] = [newKey, entries[index][1]];
    }

    replayRequest.headers = Object.fromEntries(entries);

    if (headersToSend.value.includes(oldKey)) {
        headersToSend.value = headersToSend.value.map((h) => h === oldKey ? newKey : h);
    }

    setTimeout(() => {
        const valueInput = document.getElementById(`value_${newKey}`);
        if (valueInput) {
            valueInput.focus();
        }
    }, 0);
}

const handleAddedHeaderKeyChange = (oldKey: string, e: Event) => {
    const target = e.target as HTMLInputElement;
    const newKey = sanitizeHeaderKey(target.value);

    // Keep order of header entries
    const entries = Object.entries(addedHeaders.value);
    const index = entries.findIndex(([key]) => key === oldKey);

    if (index !== -1) {
        entries[index] = [newKey, entries[index][1]];
    }

    addedHeaders.value = Object.fromEntries(entries);

    if (headersToSend.value.includes(oldKey)) {
        headersToSend.value = headersToSend.value.map((h) => h === oldKey ? newKey : h);
    }

    setTimeout(() => {
        const valueInput = document.getElementById(`value_added_${newKey}`);
        if (valueInput) {
            valueInput.focus();
        }
    }, 0);
}

const sanitizeHeaderKey = (key: string) => {
    const invalidCharsRegex = /[^a-zA-Z0-9-]/g;
    return key.replace(invalidCharsRegex, '-');
};

const addHeader = () => {
    addedHeaders.value["Header"] = "Value"

    if (!headersToSend.value.includes("Header")) {
        headersToSend.value.push("Header")
    }

    nextTick(() => {
        const keyInput = document.getElementById('key_added_Header');
        if (keyInput) {
            keyInput.focus();
        }
    });
}

const applyHeaderSelection = (state: 'checked' | 'unchecked') => {

    headersSelected.value = state;
    if (state === 'checked') {
        headersToSend.value = [...Object.keys(replayRequest.headers), ...Object.keys(addedHeaders.value)];
    } else {
        headersToSend.value = [];
    }
}

const updateMasterCheckbox = () => {
    if(headersToSend.value.length !== Object.keys(replayRequest.headers).length) {
        headersSelected.value = 'indeterminate';
    }
    else if (headersToSend.value.length === Object.keys(replayRequest.headers).length) {
        headersSelected.value = 'checked';
    }
    else {
        headersSelected.value = 'unchecked';
    }
}
defineCloakr({show})
</script>

<template>
    <Dialog v-model:open="show">
        <SidebarDialog>
            <div class="flex flex-col h-[calc(100vh-4rem)]">
                <DialogTitle>
                    <h1 class="text-sm font-medium mb-4 px-4">
                        Replay request
                    </h1>
                </DialogTitle>

                <div class="flex space-x-2 border-b dark:border-gray-700 pb-4 px-4">
                    <div class="w-[130px]">
                        <Select v-model="replayRequest.method">
                            <SelectTrigger>
                                <SelectValue :placeholder="replayRequest.method"/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="method in availableMethods" :value="method" :key="method">
                                        {{ method }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                    <Input v-model="replayRequest.uri"/>
                </div>


                <div class="overflow-y-auto pt-4 border-b dark:border-gray-700 ">
                    <Accordion type="single" collapsible v-model="headerAccordionState" class="mx-4">
                        <AccordionItem value="headerOpen">
                            <AccordionTrigger>
                                <div>Headers</div>
                                <template v-slot:icon>
                                    <ArrowsRightLeftIcon class="size-4"/>
                                </template>
                            </AccordionTrigger>
                            <AccordionContent class="p-2">
                                <AccordionTable>
                                    <TableRow
                                        class="text-[13px] text-gray-500 dark:text-gray-300  dark:border-[#606062] dark:hover:bg-transparent">
                                        <NarrowTableCell class="w-[32px] py-2.5">
                                            <MasterCheckbox :state="headersSelected" @apply-state="applyHeaderSelection"/>
                                        </NarrowTableCell>
                                        <NarrowTableCell class="w-5/12">Key</NarrowTableCell>
                                        <NarrowTableCell>Value</NarrowTableCell>
                                    </TableRow>
                                    <TableBody class="font-mono">
                                        <AccordionTableRow v-for="(_, key) in replayRequest.headers"
                                                           :key="'header_' + key"
                                                           :class="{ 'bg-gray-50 dark:bg-[#303032] dark:hover:bg-[#303032]': !headersToSend.includes(key)}">
                                            <NarrowTableCell class="w-[42px]">
                                                <div class="flex">
                                                    <checkbox :checked="headersToSend.includes(key)"
                                                              @update:checked="toggleHeaderToSend(key)"/>
                                                </div>
                                            </NarrowTableCell>
                                            <NarrowTableCell
                                                class="text-gray-500 dark:text-gray-300 text-[13px] align-middle"
                                                :class="{ 'text-gray-400': !headersToSend.includes(key)}">
                                                <TableInput :model-value="key" @change="handleHeaderKeyChange(key, $event)"
                                                            :disabled="!headersToSend.includes(key)"/>
                                            </NarrowTableCell>

                                            <NarrowTableCell class="text-gray-800 dark:text-white py-0 pt-1 pb-1"
                                                             :class="{ 'text-gray-400': !headersToSend.includes(key)}">
                                                <TableInput :id="'value_' + key"
                                                       v-model="replayRequest.headers[key]"
                                                       :disabled="!headersToSend.includes(key)"/>
                                            </NarrowTableCell>
                                        </AccordionTableRow>
                                        <AccordionTableRow v-for="(_, key) in addedHeaders"
                                                           :key="'added_header_' + key"
                                                           :class="{ 'bg-gray-50': !headersToSend.includes(key)}">
                                            <NarrowTableCell class="w-[42px]">
                                                <div class="flex">
                                                    <checkbox :checked="headersToSend.includes(key)"
                                                              @update:checked="toggleHeaderToSend(key)"/>
                                                </div>
                                            </NarrowTableCell>
                                            <NarrowTableCell
                                                class="text-gray-500 dark:text-gray-300 text-[13px] align-middle"
                                                :class="{ 'text-gray-400': !headersToSend.includes(key)}">
                                                <TableInput :model-value="key" @change="handleAddedHeaderKeyChange(key, $event)"
                                                       :id="'key_added_' + key"
                                                       :disabled="!headersToSend.includes(key)"/>
                                            </NarrowTableCell>

                                            <NarrowTableCell class="text-gray-800 dark:text-white "
                                                             :class="{ 'text-gray-400': !headersToSend.includes(key)}">
                                                <TableInput :id="'value_added_' + key"
                                                       v-model="addedHeaders[key]"
                                                       :disabled="!headersToSend.includes(key)"/>
                                            </NarrowTableCell>
                                        </AccordionTableRow>
                                        <AccordionTableRow @click="addHeader"
                                                           class="bg-gray-50  dark:bg-[#303032] dark:hover:bg-[#303032] cursor-pointer text-gray-500 dark:text-gray-300 font-medium font-sans">
                                            <NarrowTableCell class="py-2.5 w-[42px]">
                                                <PlusIcon class="size-4 mb-0.5"/>
                                            </NarrowTableCell>
                                            <NarrowTableCell colspan="2" class="pl-1.5">
                                                Add new header
                                            </NarrowTableCell>
                                        </AccordionTableRow>
                                    </TableBody>
                                </AccordionTable>

                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>

                    <Accordion type="single" collapsible v-model="bodyAccordionState" class="mx-4">
                        <AccordionItem value="bodyOpen">
                            <AccordionTrigger>

                                <div>Body</div>
                                <template v-slot:icon>
                                    <DocumentTextIcon class="size-4"/>
                                </template>
                            </AccordionTrigger>
                            <AccordionContent class="p-2">
                                <div
                                    class="border border-gray-200 rounded-xl overflow-hidden shadow-sm bg-white dark:bg-[#444447] dark:border-[#57575A]">

                                    <Textarea v-model="replayRequest.body" class="min-h-[200px] font-mono border-0 dark:bg-[#444447]"/>
                                </div>

                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>

                </div>
                <div class="flex justify-end space-x-2 pt-4 px-4">
                    <IconTextButton :icon="ArrowUturnLeftIcon" @click="reset">Reset</IconTextButton>
                    <Button @click="replay" class="flex">
                        <ArrowPathIcon class="size-4 mr-1"/>
                        <div>Replay</div>

                    </Button>
                </div>
            </div>

        </SidebarDialog>
    </Dialog>
</template>
