<script setup lang="ts">
import {
    Dialog,
    DialogContent,
} from '@/components/ui/dialog'
import QrCode from './QrCode.vue';
import { ref } from 'vue';

defineProps<{
    subdomains: string[]
}>()

const show = ref(false as boolean)

defineCloakr({ show })

</script>

<template>
    <Dialog v-model:open="show">
        <DialogContent>
            <div class="text-lg font-medium">
                {{ subdomains[0] }}
            </div>
            <QrCode :subdomains="subdomains" />
        </DialogContent>
    </Dialog>
</template>