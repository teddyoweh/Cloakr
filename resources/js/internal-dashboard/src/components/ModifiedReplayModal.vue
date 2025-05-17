<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { reactive, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'

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

const availableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']


watch(show, (newVal) => {
    if (newVal && props.currentLog) {
        reset()
    }
})

const replay = () => {
    const headers = filterHeaders(replayRequest.headers, headersToSend.value);
    const additionalHeaders = filterHeaders(addedHeaders.value, headersToSend.value);

    const replay = {
        uri: replayRequest.uri,
        method: replayRequest.method,
        headers: { ...headers, ...additionalHeaders },
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
        replayRequest.headers = { ...props.currentLog.request.headers };
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
}

defineCloakr({ show })
</script>

<template>
    <Dialog v-model:open="show">
        <DialogContent class="max-w-7xl max-h-[90%]">
            <DialogTitle>
                Replay request
            </DialogTitle>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-4">

                <div class="col-span-2 flex space-x-2">
                    <div class="w-[130px]">
                        <Select v-model="replayRequest.method">
                            <SelectTrigger>
                                <SelectValue :placeholder="replayRequest.method" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectLabel>Method</SelectLabel>
                                    <SelectItem v-for="method in availableMethods" :value="method" :key="method">
                                        {{ method }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                    <Input v-model="replayRequest.uri" />
                </div>

                <div>
                    <div class="flex items-center justify-between pr-1">
                        <div class="text-lg font-medium">
                            Headers
                        </div>

                        <div @click="addHeader" class="text-pink-600 text-sm underline cursor-pointer">
                            <span>Add header</span>
                        </div>
                    </div>

                    <div class="h-[550px] overflow-y-auto text-sm pt-2 pr-1">
                        <div v-for="(_, key) in addedHeaders" :key="'added_header_' + key"
                            class="grid grid-cols-3 gap-x-2 items-center mb-1">
                            <div class="flex items-center space-x-1 h-auto">
                                <checkbox :checked="headersToSend.includes(key)"
                                    @update:checked="toggleHeaderToSend(key)" variant="secondary" />
                                <Input :modelValue="key" @change="handleAddedHeaderKeyChange(key, $event)"
                                    class="p-1 h-auto" />
                            </div>
                            <Input :id="'value_' + key" v-model="addedHeaders[key]" class="p-1 h-auto col-span-2" />
                        </div>
                        <div v-for="(_, key) in replayRequest.headers" :key="'header_' + key"
                            class="grid grid-cols-3 gap-x-2 items-center mb-1">
                            <div class="flex items-center space-x-1">
                                <checkbox :checked="headersToSend.includes(key)"
                                    @update:checked="toggleHeaderToSend(key)" variant="secondary" />
                                <Input :modelValue="key" @change="handleHeaderKeyChange(key, $event)"
                                    class="p-1 h-auto" />
                            </div>
                            <Input :id="'value_' + key" v-model="replayRequest.headers[key]"
                                class="p-1 h-auto col-span-2" />
                        </div>
                    </div>
                </div>
                <div>

                    <div class="text-lg font-medium">
                        Body
                    </div>

                    <Textarea v-model="replayRequest.body" class="mt-2 h-[92%] max-h-full" />
                </div>

                <div class="col-span-2 flex justify-end space-x-2">
                    <Button @click="reset" variant="outline">Reset</Button>
                    <Button @click="replay">Replay</Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>