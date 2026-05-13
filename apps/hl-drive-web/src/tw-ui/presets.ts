/**
 * Button base — shared across all button presets
 * rounded-lg, smooth shadow, spring tap effect
 */
const buttonBase =
    'cursor-pointer rounded-lg font-medium transition-all duration-150 active:scale-[.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';

/**
 * Size presets
 */
const sizes = {
    default: 'px-3 py-1.5 text-sm',
    sm: 'px-2.5 py-1 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-base',
    xl: 'px-6 py-3 text-base',
};

/**
 * Badge / Pill base
 */
const badgeBase = 'inline-flex items-center font-medium select-none';

/**
 * Badge / Pill sizes
 */
const badgeSizes = {
    default: 'px-2 py-0.5 text-xs',
    sm: 'px-1.5 py-0.5 text-[10px]',
    md: 'px-2.5 py-1 text-xs',
    lg: 'px-3 py-1.5 text-sm',
};

/**
 * Solid color presets
 * primary = red (#dc2626) — matches design system
 */
const solidColors = {
    // Primary brand (red)
    primary: 'bg-red-600 text-white shadow-sm shadow-red-500/20 hover:bg-red-700 focus-visible:ring-red-500',
    red: 'bg-red-600 text-white shadow-sm shadow-red-500/20 hover:bg-red-700 focus-visible:ring-red-500',

    // Neutrals
    black: 'bg-gray-900 text-white shadow-sm shadow-gray-900/20 hover:bg-gray-800 focus-visible:ring-gray-700',
    neutral: 'bg-gray-900 text-white shadow-sm shadow-gray-900/20 hover:bg-gray-800 focus-visible:ring-gray-700',
    gray: 'bg-gray-600 text-white shadow-sm shadow-gray-500/20 hover:bg-gray-700 focus-visible:ring-gray-500',
    lightgray: 'bg-gray-100 text-gray-700 shadow-sm hover:bg-gray-200 focus-visible:ring-gray-400',
    white: 'bg-white text-gray-800 shadow-sm border border-gray-200 hover:bg-gray-50 focus-visible:ring-gray-300',

    // Other colors
    green: 'bg-green-600 text-white shadow-sm shadow-green-500/20 hover:bg-green-700 focus-visible:ring-green-500',
    blue: 'bg-blue-600 text-white shadow-sm shadow-blue-500/20 hover:bg-blue-700 focus-visible:ring-blue-500',
    sky: 'bg-sky-600 text-white shadow-sm shadow-sky-500/20 hover:bg-sky-700 focus-visible:ring-sky-500',
    yellow: 'bg-yellow-400 text-gray-900 shadow-sm shadow-yellow-400/20 hover:bg-yellow-500 focus-visible:ring-yellow-400',
    orange: 'bg-orange-600 text-white shadow-sm shadow-orange-500/20 hover:bg-orange-700 focus-visible:ring-orange-500',
    none: '',

    // Semantic
    success: 'bg-green-600 text-white shadow-sm shadow-green-500/20 hover:bg-green-700 focus-visible:ring-green-500',
    danger: 'bg-red-600 text-white shadow-sm shadow-red-500/20 hover:bg-red-700 focus-visible:ring-red-500',
    warning: 'bg-amber-500 text-white shadow-sm shadow-amber-400/20 hover:bg-amber-600 focus-visible:ring-amber-400',
    info: 'bg-sky-600 text-white shadow-sm shadow-sky-500/20 hover:bg-sky-700 focus-visible:ring-sky-500',
    error: 'bg-red-700 text-white shadow-sm shadow-red-600/30 hover:bg-red-800 focus-visible:ring-red-600',
};

/**
 * Outlined color presets
 */
const outlinedColors = {
    primary:
        'border border-red-600 text-red-600 bg-transparent hover:bg-red-600 hover:text-white focus-visible:ring-red-500',
    red: 'border border-red-600 text-red-600 bg-transparent hover:bg-red-600 hover:text-white focus-visible:ring-red-500',

    black: 'border border-gray-900 text-gray-900 bg-transparent hover:bg-gray-900 hover:text-white focus-visible:ring-gray-700',
    neutral:
        'border border-gray-900 text-gray-900 bg-transparent hover:bg-gray-900 hover:text-white focus-visible:ring-gray-700',
    gray: 'border border-gray-500 text-gray-700 bg-transparent hover:bg-gray-500 hover:text-white focus-visible:ring-gray-400',
    lightgray:
        'border border-gray-200 text-gray-600 bg-transparent hover:bg-gray-100 hover:text-gray-800 focus-visible:ring-gray-300',
    white: 'border border-white text-white bg-transparent hover:bg-white hover:text-gray-900 focus-visible:ring-white',

    green: 'border border-green-600 text-green-600 bg-transparent hover:bg-green-600 hover:text-white focus-visible:ring-green-500',
    blue: 'border border-blue-600 text-blue-600 bg-transparent hover:bg-blue-600 hover:text-white focus-visible:ring-blue-500',
    sky: 'border border-sky-600 text-sky-600 bg-transparent hover:bg-sky-600 hover:text-white focus-visible:ring-sky-500',
    yellow: 'border border-yellow-500 text-yellow-700 bg-transparent hover:bg-yellow-500 hover:text-white focus-visible:ring-yellow-400',
    orange: 'border border-orange-600 text-orange-600 bg-transparent hover:bg-orange-600 hover:text-white focus-visible:ring-orange-500',
    none: '',

    success:
        'border border-green-600 text-green-600 bg-transparent hover:bg-green-600 hover:text-white focus-visible:ring-green-500',
    danger: 'border border-red-600 text-red-600 bg-transparent hover:bg-red-600 hover:text-white focus-visible:ring-red-500',
    warning:
        'border border-amber-500 text-amber-700 bg-transparent hover:bg-amber-500 hover:text-white focus-visible:ring-amber-400',
    info: 'border border-sky-600 text-sky-600 bg-transparent hover:bg-sky-600 hover:text-white focus-visible:ring-sky-500',
    error: 'border border-red-700 text-red-700 bg-transparent hover:bg-red-700 hover:text-white focus-visible:ring-red-600',
};

/**
 * Ghost (transparent, no border) color presets
 */
const ghostColors = {
    primary: 'text-red-600 bg-transparent hover:bg-red-50 focus-visible:ring-red-400',
    red: 'text-red-600 bg-transparent hover:bg-red-50 focus-visible:ring-red-400',
    black: 'text-gray-900 bg-transparent hover:bg-gray-100 focus-visible:ring-gray-400',
    gray: 'text-gray-600 bg-transparent hover:bg-gray-100 focus-visible:ring-gray-400',
    green: 'text-green-600 bg-transparent hover:bg-green-50 focus-visible:ring-green-400',
    blue: 'text-blue-600 bg-transparent hover:bg-blue-50 focus-visible:ring-blue-400',
    danger: 'text-red-600 bg-transparent hover:bg-red-50 focus-visible:ring-red-400',
    none: '',
};

/**
 * Soft (light tinted background) color presets
 */
const softColors = {
    primary: 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 focus-visible:ring-red-400',
    red: 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 focus-visible:ring-red-400',
    green: 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 focus-visible:ring-green-400',
    blue: 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 focus-visible:ring-blue-400',
    gray: 'bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200 focus-visible:ring-gray-400',
    amber: 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 focus-visible:ring-amber-400',
    danger: 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 focus-visible:ring-red-400',
    success: 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 focus-visible:ring-green-400',
    warning: 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 focus-visible:ring-amber-400',
    info: 'bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 focus-visible:ring-sky-400',
    none: '',
};

/**
 * Internal helper (buttons)
 */
function buildPresets(colors: Record<string, string>, prefix: string = '') {
    const result: Record<string, string> = {};

    Object.keys(colors).forEach((color) => {
        Object.keys(sizes).forEach((size) => {
            const key = size === 'default' ? `${prefix}${color}` : `${prefix}${color}-${size}`;

            result[key] = [buttonBase, sizes[size as keyof typeof sizes], colors[color]].join(' ');
        });
    });

    return result;
}

/**
 * Internal helper (badges / pills)
 */
function buildBadgePresets(colors: Record<string, string>, rounded: string, type: 'solid' | 'outlined' = 'solid') {
    const result: Record<string, string> = {};

    Object.keys(colors).forEach((color) => {
        Object.keys(badgeSizes).forEach((size) => {
            const prefix = type === 'outlined' ? 'outlined-' : '';
            const key = size === 'default' ? `${prefix}${color}` : `${prefix}${color}-${size}`;

            result[key] = [badgeBase, rounded, badgeSizes[size as keyof typeof badgeSizes], colors[color]].join(' ');
        });
    });

    return result;
}

/**
 * Button presets
 */
export function buttonPresets() {
    let _result: any = {
        ...buildPresets(solidColors),
        ...buildPresets(outlinedColors, 'outlined-'),
        ...buildPresets(ghostColors, 'ghost-'),
        ...buildPresets(softColors, 'soft-'),
    };

    const buttonCommonClasses = [
        'disabled:opacity-50 disabled:active:scale-none disabled:cursor-not-allowed',
        'c-button-presets',
    ];

    _result = Object.fromEntries(
        Object.entries(_result).map((item: any[]) => {
            let [key, value] = item;

            if (!buttonCommonClasses.length) {
                return [key, value];
            }

            if (value && typeof value === 'string' && value.trim()) {
                value = value.trim().split(' ');

                for (const _class of buttonCommonClasses) {
                    value.push(!value.includes(_class) ? _class : '');
                }

                value = value.filter(Boolean).join(' ');
            }

            return [key, value];
        })
    );

    return _result;
}

/**
 * Badge presets
 */
export function badgePresets() {
    return {
        ...buildBadgePresets(solidColors, 'rounded-md'),
        ...buildBadgePresets(outlinedColors, 'rounded-md', 'outlined'),
    };
}

/**
 * Pill presets
 */
export function pillPresets() {
    return {
        ...buildBadgePresets(solidColors, 'rounded-full'),
        ...buildBadgePresets(outlinedColors, 'rounded-full', 'outlined'),
    };
}

/**
 * Form field base — used by CInput, CSelect, CTextarea
 * Focus ring uses primary red to match design system
 */
const formFieldBase =
    'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400';

const formFieldSm = 'px-2.5 py-1.5 text-xs';
const formFieldLg = 'px-4 py-2.5 text-base';

/**
 * Select presets
 */
export function selectPresets() {
    return {
        default: formFieldBase,
        sm: [formFieldBase.replace('px-3 py-2', formFieldSm)].join(' '),
        md: formFieldBase,
        lg: [formFieldBase.replace('px-3 py-2', formFieldLg)].join(' '),
    };
}

export function selectLabelPresets() {
    return {
        default: 'block text-sm font-medium text-gray-700',
        mb1: 'block text-sm font-medium text-gray-700 mb-1',
        mb2: 'block text-sm font-medium text-gray-700 mb-2',
    };
}

/**
 * Date input presets
 */
export function dateInputPresets() {
    return {
        default: formFieldBase,
        sm: formFieldBase.replace('px-3 py-2', formFieldSm),
        md: formFieldBase,
        lg: formFieldBase.replace('px-3 py-2', formFieldLg),
    };
}

export function dateInputLabelPresets() {
    return {
        default: 'block text-sm font-medium text-gray-700',
        mb1: 'block text-sm font-medium text-gray-700 mb-1',
        mb2: 'block text-sm font-medium text-gray-700 mb-2',
    };
}

/**
 * Text input presets
 */
export function textInputPresets() {
    return {
        default: formFieldBase,
        sm: formFieldBase.replace('px-3 py-2', formFieldSm),
        md: formFieldBase,
        lg: formFieldBase.replace('px-3 py-2', formFieldLg),
    };
}

export function textInputLabelPresets() {
    return {
        default: 'block text-sm font-medium text-gray-700',
        mb1: 'block text-sm font-medium text-gray-700 mb-1',
        mb2: 'block text-sm font-medium text-gray-700 mb-2',
    };
}

/**
 * Textarea presets
 */
export function textareaPresets() {
    return {
        default: formFieldBase,
        sm: formFieldBase.replace('px-3 py-2', formFieldSm),
        md: formFieldBase,
        lg: formFieldBase.replace('px-3 py-2', formFieldLg),
    };
}

export function textareaLabelPresets() {
    return {
        default: 'block text-sm font-medium text-gray-700',
        mb1: 'block text-sm font-medium text-gray-700 mb-1',
        mb2: 'block text-sm font-medium text-gray-700 mb-2',
    };
}
