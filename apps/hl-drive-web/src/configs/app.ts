import { toBool } from '../utils/data-helpers';

export const ENABLED_SPEED_INSIGHTS = Boolean(import.meta.env.VITE_ENABLED_SPEED_INSIGHTS ?? false);
export const APP_ENV =
    import.meta.env.APP_ENV || import.meta.env.VITE_APP_ENV || import.meta.env.NODE_ENV || 'production';
export const IS_DEVELOPMENT = APP_ENV === 'development';
export const IS_PRODUCTION = APP_ENV === 'production';
export const SHOW_DEV_HELPERS = IS_PRODUCTION ? false : toBool(import.meta.env.VITE_SHOW_DEV_HELPERS);
// export const SHOW_DEV_HELPERS = import.meta.env.VITE_SHOW_DEV_HELPERS;
