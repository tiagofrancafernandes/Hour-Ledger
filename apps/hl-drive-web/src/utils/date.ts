export type DateInput = string | number | Date;

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
 * Format date with specific pattern (pt-BR friendly)
 */
export function formatDate(input: DateInput, pattern: DatePattern = 'br-date'): string {
    const date = parseDate(input);

    if (!date) {
        return '';
    }

    return formatByPattern(date, pattern);
}
