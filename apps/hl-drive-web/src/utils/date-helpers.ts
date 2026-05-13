import { TIMEZONES } from '@/configs/date';
import type { TimezoneConfig } from '@/types';

export type DateInput = string | number | Date;

export const TZ_BRT = 'America/Sao_Paulo';
export const TZ_UTC = 'UTC';
export const TZ_DEFAULT = 'America/Sao_Paulo';

export type DatePattern = 'iso-date' | 'br-date' | 'datetime' | 'datetime-seconds';

/**
 * Parse and validate date input
 */
export function parseDate(input: DateInput): Date | null {
    try {
        if (!input) {
            return null;
        }

        if (input instanceof Date) {
            if (isNaN(input.getTime())) {
                return null;
            }

            return input;
        }

        if (typeof input === 'number') {
            const date = new Date(input);

            if (isNaN(date.getTime())) {
                return null;
            }

            return date;
        }

        if (typeof input === 'string') {
            const hasTimezone = /Z|[+-]\d{2}:\d{2}$/.test(input);

            const normalized = hasTimezone ? input : `${input}Z`;

            const date = new Date(normalized);

            if (isNaN(date.getTime())) {
                return null;
            }

            return date;
        }

        return null;
    } catch {
        return null;
    }
}

/**
 * Validate timezone
 */
export function resolveTimezone(input?: string | null): string | undefined {
    try {
        if (!input) {
            return undefined;
        }

        new Intl.DateTimeFormat('en-US', { timeZone: input });

        return input;
    } catch {
        return undefined;
    }
}

/**
 * Convert date to target timezone
 */
export function convertToTimezone(date: Date, timezone?: string): Date {
    try {
        if (!timezone) {
            return date;
        }

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
            if (part.type === 'literal') {
                continue;
            }

            map[part.type] = part.value;
        }

        return new Date(`${map.year}-${map.month}-${map.day}T${map.hour}:${map.minute}:${map.second}`);
    } catch {
        return date;
    }
}

/**
 * Format using fixed patterns
 */
export function formatByPattern(date: Date, pattern: DatePattern): string {
    try {
        const pad = (value: number): string => {
            return String(value).padStart(2, '0');
        };

        const year = date.getFullYear();
        const month = pad(date.getMonth() + 1);
        const day = pad(date.getDate());

        const hour = pad(date.getHours());
        const minute = pad(date.getMinutes());
        const second = pad(date.getSeconds());

        if (pattern === 'iso-date') {
            return `${year}-${month}-${day}`;
        }

        if (pattern === 'br-date') {
            return `${day}/${month}/${year}`;
        }

        if (pattern === 'datetime') {
            return `${year}-${month}-${day} ${hour}:${minute}`;
        }

        if (pattern === 'datetime-seconds') {
            return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
        }

        return '';
    } catch {
        return '';
    }
}

/**
 * Format using Intl
 */
export function formatWithIntl(
    date: Date,
    options: {
        locale?: string;
        timezone?: string;
        format?: Intl.DateTimeFormatOptions;
        dateStyle?: Intl.DateTimeFormatOptions['dateStyle'];
        timeStyle?: Intl.DateTimeFormatOptions['timeStyle'];
    }
): string | null {
    try {
        const { locale = 'en-US', timezone, format, dateStyle = 'short', timeStyle = 'medium' } = options;

        if (format) {
            return new Intl.DateTimeFormat(locale, {
                timeZone: timezone,
                ...format,
            }).format(date);
        }

        return new Intl.DateTimeFormat(locale, {
            timeZone: timezone,
            dateStyle,
            timeStyle,
        }).format(date);
    } catch {
        return null;
    }
}

export const getTimezoneList = (prepare: Function | null = null): Record<string, TimezoneConfig> => {
    if (!prepare || typeof prepare !== 'function') {
        return TIMEZONES;
    }

    return prepare(TIMEZONES);
};
