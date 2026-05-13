<script setup lang="ts">
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { TZ_DEFAULT } from '@/utils/date-helpers';

type DateInput = string | number | Date | null | undefined;

type Pattern = 'iso-date' | 'br-date' | 'datetime' | 'datetime-seconds';

interface Props {
    value: DateInput;
    timezone?: string | null;
    locale?: string;
    dateStyle?: 'full' | 'long' | 'medium' | 'short';
    timeStyle?: 'full' | 'long' | 'medium' | 'short';
    format?: Intl.DateTimeFormatOptions;
    pattern?: Pattern;
}

const props = withDefaults(defineProps<Props>(), {
    locale: 'en-US',
    dateStyle: 'short',
    timeStyle: 'medium',
});

const { getTimezone } = useAuth();

/**
 * Validate and normalize date
 */
function parseDate(value: DateInput): Date | null {
    try {
        if (!value) return null;

        if (value instanceof Date) {
            return isNaN(value.getTime()) ? null : value;
        }

        if (typeof value === 'number') {
            const date = new Date(value);
            return isNaN(date.getTime()) ? null : date;
        }

        if (typeof value === 'string') {
            const date = new Date(value);
            return isNaN(date.getTime()) ? null : date;
        }

        return null;
    } catch {
        return null;
    }
}

/**
 * Safe timezone resolve
 */
function resolveTimezone(tz?: string | null): string | undefined {
    try {
        const finalTz = getTimezone(tz);

        if (!finalTz) {
            return undefined;
        }

        new Intl.DateTimeFormat('en-US', { timeZone: finalTz });

        return finalTz;
    } catch {
        return undefined;
    }
}

/**
 * Convert date to target timezone (critical step)
 */
function convertToTimezone(date: Date, timezone?: string): Date {
    try {
        if (!timezone) return date;

        const parts = new Intl.DateTimeFormat('en-US', {
            timeZone: timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).formatToParts(date);

        const map: Record<string, string> = {};

        for (const part of parts) {
            if (part.type !== 'literal') {
                map[part.type] = part.value;
            }
        }

        return new Date(`${map.year}-${map.month}-${map.day}T${map.hour}:${map.minute}:${map.second}`);
    } catch {
        return date;
    }
}

/**
 * Helpers
 */
function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function formatByPattern(date: Date, pattern: Pattern): string {
    try {
        const y = date.getFullYear();
        const m = pad(date.getMonth() + 1);
        const d = pad(date.getDate());

        const h = pad(date.getHours());
        const i = pad(date.getMinutes());
        const s = pad(date.getSeconds());

        switch (pattern) {
            case 'iso-date':
                return `${y}-${m}-${d}`;

            case 'br-date':
                return `${d}/${m}/${y}`;

            case 'datetime':
                return `${y}-${m}-${d} ${h}:${i}`;

            case 'datetime-seconds':
                return `${y}-${m}-${d} ${h}:${i}:${s}`;

            default:
                return '';
        }
    } catch {
        return '';
    }
}

const parsedDate = computed(() => parseDate(props.value));

const timezone = computed(() => resolveTimezone(props.timezone));

const formatted = computed(() => {
    try {
        if (!parsedDate.value) return null;

        const tz = timezone.value;

        // Pattern (fixed format)
        if (props.pattern) {
            const zoned = convertToTimezone(parsedDate.value, tz);
            return formatByPattern(zoned, props.pattern) || null;
        }

        // Custom format
        if (props.format) {
            return new Intl.DateTimeFormat(props.locale, {
                timeZone: tz,
                ...props.format,
            }).format(parsedDate.value);
        }

        // Default
        return new Intl.DateTimeFormat(props.locale, {
            timeZone: tz,
            dateStyle: props.dateStyle,
            timeStyle: props.timeStyle,
        }).format(parsedDate.value);
    } catch {
        return null;
    }
});
</script>

<template>
    <span v-if="formatted">
        {{ formatted }}
    </span>
</template>
