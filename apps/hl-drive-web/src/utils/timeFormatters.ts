/**
 * Time Formatting Utilities
 * Converts between decimal hours and HH:MM human-readable format
 * Supports both display and input parsing
 */

/**
 * Convert decimal hours (1.5) to HH:MM format (01:30)
 * @param decimalHours - Hours as decimal (e.g., 1.5, 2.25, 0.5)
 * @returns Time in HH:MM format (e.g., "01:30", "02:15", "00:30")
 */
export function decimalToTimeFormat(decimalHours: number): string {
    const absoluteHours = Math.abs(decimalHours);
    const hours = Math.floor(absoluteHours);
    const minutes = Math.round((absoluteHours - hours) * 60);

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

/**
 * Convert HH:MM format (01:30) to decimal hours (1.5)
 * @param timeFormat - Time in HH:MM or H:MM format (e.g., "01:30", "1:30")
 * @returns Hours as decimal (e.g., 1.5)
 */
export function timeFormatToDecimal(timeFormat: string): number {
    const parts = timeFormat.trim().split(':');

    if (parts.length !== 2) {
        throw new Error('Invalid time format. Use HH:MM or H:MM');
    }

    const hoursStr = parts[0]!;
    const minutesStr = parts[1]!;
    const hours = parseInt(hoursStr, 10);
    const minutes = parseInt(minutesStr, 10);

    if (Number.isNaN(hours) || Number.isNaN(minutes)) {
        throw new Error('Hours and minutes must be valid numbers');
    }

    if (minutes < 0 || minutes >= 60) {
        throw new Error('Minutes must be between 0 and 59');
    }

    return hours + minutes / 60;
}

/**
 * Format decimal hours as displayable time with sign prefix
 * Used for ledger entries, balance displays, etc.
 * @param decimalHours - Hours as decimal (can be negative)
 * @param includeSign - Whether to include + or - prefix
 * @returns Formatted string (e.g., "+01:30", "-00:45")
 */
export function formatHoursDisplay(decimalHours: number, includeSign: boolean = true): string {
    const isNegative = decimalHours < 0;
    const timeStr = decimalToTimeFormat(decimalHours);

    if (!includeSign) {
        return timeStr;
    }

    const sign = isNegative ? '-' : '+';

    return sign + timeStr;
}

/**
 * Parse user input that could be decimal or time format
 * Examples: "1.5", "1:30", "1h30", "01:30"
 * @param input - User input string
 * @returns Decimal hours (e.g., 1.5)
 */
export function parseTimeInput(input: string): number {
    const trimmed = input.trim();

    // Check for HH:MM format
    if (trimmed.includes(':')) {
        return timeFormatToDecimal(trimmed);
    }

    // Check for "1h30" format
    if (trimmed.includes('h')) {
        const parts = trimmed.toLowerCase().split('h');

        if (parts.length !== 2) {
            throw new Error('Invalid time format. Use HH:MM, H:MM, or Xh');
        }

        const hoursStr = parts[0]!;
        const minutesStr = parts[1] || '0';
        const hours = parseInt(hoursStr, 10);
        const minutes = parseInt(minutesStr, 10);

        if (Number.isNaN(hours) || Number.isNaN(minutes)) {
            throw new Error('Hours and minutes must be valid numbers');
        }

        if (minutes >= 60) {
            throw new Error('Minutes must be less than 60');
        }

        return hours + minutes / 60;
    }

    // Otherwise treat as decimal
    const decimal = parseFloat(trimmed);

    if (Number.isNaN(decimal)) {
        throw new Error('Invalid time value');
    }

    return decimal;
}

/**
 * Separate decimal hours into hours and minutes components
 * Useful for dual input (hours input + minutes input)
 * @param decimalHours - Total hours as decimal (e.g., 1.5, 2.25)
 * @returns Object with hours and minutes as integers
 */
export function splitDecimalHours(decimalHours: number): { hours: number; minutes: number } {
    const absoluteHours = Math.abs(decimalHours);
    const hours = Math.floor(absoluteHours);
    const minutes = Math.round((absoluteHours - hours) * 60);

    return { hours, minutes };
}

/**
 * Combine separate hours and minutes into decimal
 * Used when submitting dual input (hours + minutes fields)
 * @param hours - Hours as integer
 * @param minutes - Minutes as integer
 * @returns Combined time as decimal hours
 */
export function combineDualTimeInput(hours: number, minutes: number): number {
    return hours + minutes / 60;
}

/**
 * Format seconds to HH:MM:SS (for timer displays)
 * @param seconds - Total seconds
 * @returns Time in HH:MM:SS format (e.g., "01:30:45")
 */
export function secondsToTimeFormat(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

/**
 * Validate if a time string is in valid HH:MM format
 * @param timeFormat - String to validate
 * @returns True if valid HH:MM format
 */
export function isValidTimeFormat(timeFormat: string): boolean {
    const timeRegex = /^(\d{1,2}):(\d{2})$/;
    const match = timeFormat.trim().match(timeRegex);

    if (!match || !match[2]) {
        return false;
    }

    const minutes = parseInt(match[2], 10);

    return minutes >= 0 && minutes < 60;
}
