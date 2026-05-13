import type { TimezoneConfig } from '@/types';

export const TIMEZONES: Record<string, TimezoneConfig> = {
    'America/Sao_Paulo': {
        offset: -3,
        label: 'America/Sao_Paulo',
        timezone_id: 'America/Sao_Paulo',
        country: 'BR',
    },

    'America/New_York': {
        offset: -5,
        label: 'America/New_York',
        timezone_id: 'America/New_York',
        country: 'US',
    },

    'America/Chicago': {
        offset: -6,
        label: 'America/Chicago',
        timezone_id: 'America/Chicago',
        country: 'US',
    },

    'America/Denver': {
        offset: -7,
        label: 'America/Denver',
        timezone_id: 'America/Denver',
        country: 'US',
    },

    'America/Los_Angeles': {
        offset: -8,
        label: 'America/Los_Angeles',
        timezone_id: 'America/Los_Angeles',
        country: 'US',
    },

    'America/Toronto': {
        offset: -5,
        label: 'America/Toronto',
        timezone_id: 'America/Toronto',
        country: 'CA',
    },

    'America/Vancouver': {
        offset: -8,
        label: 'America/Vancouver',
        timezone_id: 'America/Vancouver',
        country: 'CA',
    },

    'Europe/London': {
        offset: 0,
        label: 'Europe/London',
        timezone_id: 'Europe/London',
        country: 'GB',
    },
};
