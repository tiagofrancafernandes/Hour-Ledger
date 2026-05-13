<script setup lang="ts">
import { computed } from 'vue';
import { useDate } from '@/composables/useDate';

import type { DateInput, DatePattern } from '@/utils/date-helpers';

interface Props {
    value: DateInput;
    from?: string;
    to?: string;
    locale?: string;
    dateStyle?: 'full' | 'long' | 'medium' | 'short';
    timeStyle?: 'full' | 'long' | 'medium' | 'short';
    format?: Intl.DateTimeFormatOptions;
    pattern?: DatePattern;
}

const props = withDefaults(defineProps<Props>(), {
    locale: 'en-US',
    dateStyle: 'short',
    timeStyle: 'medium',
});

const { formatted } = useDate({
    date: props.value,
    from: props.from,
    to: props.to,
    locale: props.locale,
    format: props.format,
    pattern: props.pattern,
});

const displayValue = computed(() => {
    return formatted.value;
});
</script>

<template>
    <span v-if="displayValue">
        {{ displayValue }}
    </span>
</template>
