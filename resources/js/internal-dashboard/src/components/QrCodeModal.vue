<script setup lang="ts">
import {
    Dialog,
    DialogContent,
} from '@/components/ui/dialog'
import QrCode from './QrCode.vue';
import {ref} from 'vue';

defineProps<{
    subdomains: string[]
}>()

const show = ref(false as boolean)

defineCloakr({show})
</script>

<template>
    <Dialog v-model:open="show">
        <DialogContent>
            <div class="flex flex-col items-center">
                <QrCode :subdomains="subdomains"/>
                <p class="text-gray-500 text-sm mb-8 dark:text-gray-400">
                    Scan this QR code to open the site on a mobile device.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
