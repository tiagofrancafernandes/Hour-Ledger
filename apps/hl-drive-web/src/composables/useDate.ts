import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';

import {
    TZ_BRT,
    TZ_UTC,
    TZ_DEFAULT,
    parseDate,
    resolveTimezone,
    convertToTimezone,
    formatByPattern,
    formatWithIntl,
    type DateInput,
    type DatePattern,
} from '@/utils/date-helpers';

interface UseDateOptions {
    date?: DateInput;
    from?: string;
    to?: string;
    locale?: string;
    format?: Intl.DateTimeFormatOptions;
    pattern?: DatePattern;
}

export function useDate(options: UseDateOptions | null = null) {
    const { getTimezone } = useAuth();

    const parsedDate = computed(() => {
        return options?.date ? parseDate(options?.date) : null;
    });

    const targetTimezone = computed(() => {
        const preferred = options?.to ?? getTimezone();

        return resolveTimezone(preferred);
    });

    const formatted = computed(() => {
        if (!parsedDate.value) {
            return null;
        }

        const tz = targetTimezone.value || null;

        if (options?.pattern) {
            const zoned = convertToTimezone(parsedDate.value, tz || TZ_DEFAULT);

            const result = formatByPattern(zoned, options?.pattern);

            if (!result) {
                return null;
            }

            return result;
        }

        return formatWithIntl(parsedDate.value, {
            locale: options?.locale ?? 'en-US',
            timezone: tz,
            format: options?.format ?? undefined,
        });
    });

    return {
        parsedDate,
        formatted,
        targetTimezone,

        resolveTimezone,
        convertToTimezone,
        formatWithIntl,
    };
}
