/** @ts-ignore */
import type { URL as URLType } from 'node:url';
import { type Ref } from 'vue';

export type { URLType };

export function isNull(value: any): boolean {
    return !value && value === null;
}

export function isNumber(value: any, strictType: boolean = true): boolean {
    if (typeof value === 'number') {
        return true;
    }

    if (strictType && typeof value !== 'number') {
        return false;
    }

    if (isNull(value)) {
        return false;
    }

    if (isNull(value)) {
        return false;
    }

    if (isString(value)) {
        return value?.trim() && !isNaN(Number(value));
    }

    return false;
}

export function isNumeric(value: any): boolean {
    if (isNumber(value, true) || isNumber(value, false)) {
        return true;
    }

    return isNumber(Number(value), true);
}

export function isString(value: any): boolean {
    return typeof value === 'string';
}

export function isUndefined(value: any): boolean {
    return !value && typeof value === 'undefined';
}

export function isFunction(value: any): boolean {
    return typeof value === 'function';
}

export function isArray(value: any): boolean {
    return Array.isArray(value);
}

export function isObject(value: any): boolean {
    return value && typeof value === 'object' && !isArray(value);
}

export function isBool(value: any): boolean {
    return typeof value === 'boolean';
}

export function toBool(value: any): boolean {
    let valueType = typeof value;

    if ([null, undefined, false, '', 0].includes(value)) {
        return false;
    }

    if (isBool(value)) {
        return Boolean(value);
    }

    if (valueType === 'boolean') {
        return value;
    }

    if (valueType === 'string') {
        switch (value.toLowerCase().trim()) {
            case 'true':
            case 't':
            case 'on':
            case 'yes':
            case 'si':
            case 'sim':
            case 's':
            case 'y':
            case '1':
                return true;

            case 'false':
            case 'f':
            case 'off':
            case 'no':
            case 'nao':
            case 'não':
            case 'n':
            case '0':
                return false;
        }

        return String(value).trim().length > 0;
    }

    if (valueType === 'object') {
        return Object.keys(value || []).length > 0;
    }

    if (valueType === 'number') {
        return Boolean(value);
    }

    return Boolean(value);
}

export function isNullable(value: any, strict: boolean = false): boolean {
    /* strict: only 'null' and 'undefined' as nullable (ignore empty) */
    if (isNull(value) || isUndefined(value)) {
        return true;
    }

    if (isString(value)) {
        return strict ? false : Boolean(value);
    }

    return Boolean(value);
}

export function ifString(value: any): string | null {
    return typeof value === 'string' ? value : null;
}

export function ifStringOrNull(value: any): string | null {
    return isString(value) ? value : null;
}

export function ifStringOrEmpty(value: any): string {
    return ifStringOrNull(value) ?? '';
}

export function ifStringOr(value: any, defaultValue: any = null): any {
    return ifStringOrNull(value) ?? defaultValue;
}

export function ifArray(value: any): object | null {
    return value && typeof value === 'object' ? value : null;
}

export function ifArrayOrNull(value: any): object | null {
    return isArray(value) ? value : null;
}

export function ifArrayOrEmpty(value: any): object {
    return ifArrayOrNull(value) ?? [];
}

export function ifArrayOr(value: any, defaultValue: any = null): any {
    return ifArrayOrNull(value) ?? defaultValue;
}

export function ifObject(value: any): object | null {
    return value && typeof value === 'object' ? value : null;
}

export function ifObjectOrNull(value: any): object | null {
    return isObject(value) ? value : null;
}

export function ifObjectOrEmpty(value: any): object {
    return ifObjectOrNull(value) ?? {};
}

export function ifObjectOr(value: any, defaultValue: any = null): any {
    return ifObjectOrNull(value) ?? defaultValue;
}

export function ifBool(value: any): boolean | null {
    return typeof value === 'boolean' ? value : null;
}

export function ifBoolOrNull(value: any): boolean | null {
    return isBool(value) ? value : null;
}

export function ifBoolOrTrue(value: any): boolean {
    return ifBoolOrNull(value) ?? true;
}

export function ifBoolOrFalse(value: any): boolean {
    return ifBoolOrNull(value) ?? false;
}

export function ifBoolOr(value: any, defaultValue: any = null): any {
    return ifBoolOrNull(value) ?? defaultValue;
}

// WIP
export function objectFilter(value: any, filterFn: any = null): any {
    value = ifObjectOrEmpty(value);
    let _filterFn: Function = isFunction(filterFn)
        ? filterFn
        : function (key: any, val: any) {
              if (key === undefined || key === null) {
                  return false;
              }

              if (val === undefined || val === null || isNaN(val)) {
                  return false;
              }

              return Boolean(val);
          };

    value = Object.entries(value).filter((item: any[]) => {
        if (!_filterFn) {
            return true;
        }

        try {
            let [key, val] = item;

            return _filterFn(key, val);
        } catch (error) {
            return false;
        }
    });

    return Object.fromEntries(value);
}

// WIP
export function objectMap(value: any, mapFn: any = null): any {
    value = ifObjectOrEmpty(value);
    let _mapFn: Function | null = isFunction(mapFn) ? mapFn : null;

    value = Object.entries(value).filter((item: any[]) => {
        if (!_mapFn) {
            return item;
        }

        try {
            let [key, val] = item;

            let _result: any = _mapFn(key, val);

            _result = isArray(_result) ? _result : [_result];

            [key, val] = _result;

            key = isString(key) || isNumeric(key) ? key : undefined;

            return [key, val];
        } catch (error) {
            return item;
        }
    });

    return Object.fromEntries(value);
}

/** @ts-ignore */
export function getWindowObject(parentObject: any = null): object | null {
    parentObject = ifObjectOrEmpty(parentObject) || globalThis || {};

    /** @ts-ignore */
    if (!parentObject?.window || typeof parentObject?.window !== 'object') {
        return null;
    }

    /** @ts-ignore */
    return (parentObject?.window || null)?.constructor?.name === 'Window' ? parentObject?.window : null;
}

/** @ts-ignore */
export function getWindowLocation(...args: any): Location | null {
    let _location: any = null;

    /** @ts-ignore */
    if (typeof location !== 'undefined') {
        /** @ts-ignore */
        _location = location || null;
    }

    if (!_location) {
        return null;
    }

    /** @ts-ignore */
    return _location instanceof Location ? _location : null;
}

export function getWindowLocalStorage(parentObject: any = null): any {
    /** @ts-ignore */
    let _localStorage: any = getWindowObject(parentObject)?.localStorage || undefined;

    if (!_localStorage) {
        return null;
    }

    if (typeof _localStorage !== 'object') {
        return null;
    }

    /** @ts-ignore */
    return _localStorage instanceof Storage ? _localStorage : null;
}

export function getWindowSessionStorage(parentObject: any = null): any {
    /** @ts-ignore */
    let _sessionStorage: any = getWindowObject(parentObject)?.sessionStorage || undefined;

    if (!_sessionStorage) {
        return null;
    }

    if (typeof _sessionStorage !== 'object') {
        return null;
    }

    /** @ts-ignore */
    return _sessionStorage instanceof Storage ? _sessionStorage : null;
}

export function getWindowDocument(parentObject: any = null): any {
    /** @ts-ignore */
    let _document: any = getWindowObject(parentObject)?.document || undefined;

    if (!_document) {
        return null;
    }

    if (typeof _document !== 'object') {
        return null;
    }

    /** @ts-ignore */
    return _document?.constructor?.name === 'HTMLDocument' ? _document : null;
}

/**
 * @example 'https:', 'http:'
 */
export function getLocationProtocol(defaultValue: string | null = 'https'): string {
    const HTTPS_PROTOCOL = 'https';
    defaultValue =
        defaultValue && ['http', 'https'].includes(ifStringOrEmpty(defaultValue)) ? defaultValue : HTTPS_PROTOCOL;

    let _protocol: string | null = getWindowLocation()?.protocol || defaultValue || HTTPS_PROTOCOL;

    if (!_protocol) {
        _protocol = defaultValue || HTTPS_PROTOCOL;
    }

    _protocol =
        ((_protocol || HTTPS_PROTOCOL)?.match(/^(http|https){1}(:){0,1}$/)?.at(0) || HTTPS_PROTOCOL)
            ?.trim()
            ?.replaceAll(/(:)$/g, '')
            ?.trim() || HTTPS_PROTOCOL;

    return [_protocol || HTTPS_PROTOCOL, ':'].join('');
}

/**
 * @example 'https', 'http'
 */
export function getLocationSchema(defaultValue: string | null = 'https'): string {
    const HTTPS_PROTOCOL = 'https';

    let _schema = getLocationProtocol(defaultValue) || null;

    _schema =
        ((_schema || HTTPS_PROTOCOL || '').match(/^(http|https){1}(:){0,1}$/)?.at(0) || HTTPS_PROTOCOL)
            ?.trim()
            ?.replaceAll(/(:)$/g, '') || HTTPS_PROTOCOL;

    return _schema;
}

export function isValidHost(value: any = null, toCheckDomainPattern = false): boolean {
    if (!value || typeof value !== 'string' || !value.trim()) {
        return false;
    }

    try {
        /** @ts-ignore */
        const url = new URL('', `http://${value}`);

        // Ensure the hostname matches the input (rejects paths/queries/invalid chars)
        if (url.hostname !== value.toLowerCase()) {
            return false;
        }

        if (toCheckDomainPattern) {
            return true;
        }

        // Basic domain regex: requires at least one dot and a TLD
        const domainRegex = /^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i;

        return domainRegex.test(value);
    } catch (err) {
        return false;
    }
}

export function ifValidHost(value: any = null, toCheckDomainPattern = false): string | null {
    return isValidHost(value, toCheckDomainPattern) ? value : null;
}

export function makeUrlPath(path: any, defaultValue: string = '', toTrim: boolean = true): string {
    path = ifStringOrEmpty(path) || defaultValue;

    if (!path) {
        return '';
    }

    path = String(path).replace(/\/+/g, '/').replace(/^\/+/g, '').replace(/\/+$/g, '');

    if (!toTrim) {
        return path;
    }

    return (
        path
            ?.trim()
            ?.replaceAll(/^[\/]{0,}/g, '')
            ?.trim() || defaultValue
    );
}

export function isValidUrl(value: any): boolean {
    try {
        if (isObject(value) && value?.constructor?.name === 'URL') {
            return true;
        }

        if (!value || !isString(value)) {
            return false;
        }

        /** @ts-ignore */
        new URL('', value);

        return true;
    } catch (e) {
        return false;
    }
}

export function ifValidUrl(value: any, asURL: boolean = true): URLType | string | null {
    try {
        value = isValidUrl(value) ? value : null;

        value = isValidUrl(value) ? value : null;

        if (!value) {
            return null;
        }

        if (!asURL) {
            return String(value || '');
        }

        if (isObject(value)) {
            return value as URLType;
        }

        /** @ts-ignore */
        return new URL('', value);
    } catch (error) {
        return null;
    }
}

export function makeUrl(
    value: any = null,
    path: any = null,
    params: any = {},
    asURL: boolean = true
): URLType | string | null {
    let _url: URLType | null = ifValidUrl(value, true) as URLType | null;

    if (!_url && isString(value)) {
        value = ifValidHost(value);
        let _protocol = getLocationProtocol('https');
        _url = value ? (makeUrl(`${_protocol}${value}`) as URLType | null) : null;
    }

    if (!_url) {
        return null;
    }

    _url.pathname = makeUrlPath(path, '/', true);

    params = ifObjectOrEmpty(params);

    let searchParams = _url.searchParams;

    Object.entries(params).map((item) => {
        let [key, val] = item;

        if (val === undefined || key === null) {
            return;
        }

        val = val ?? '';

        if (typeof val === 'object') {
            val = val ? JSON.stringify(val) : '';
        }

        searchParams.set(key, `${val}`);
    });

    return asURL ? _url : _url?.toString();
}

export function isValidEmail(value: any) {
    value = typeof value === 'string' ? value.trim() : '';

    if (!value.length) {
        return false;
    }

    let matches = value.match(/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/) || [];

    return Array.isArray(matches) ? matches?.length > 0 : false;
}

export const makeRequiredInputs = (rules: any) => {
    /*
    const requiredInputs = makeRequiredInputs(['abc', 'def']);
    const requiredInputs = makeRequiredInputs({
        title: true,
        wallet_id: true,
        description: false,
        algo: true,
    });
    /* */

    if (isArray(rules)) {
        let _r: any = {};

        for (let key of rules.filter((i: any) => isString(i))) {
            _r[key] = true;
        }

        rules = _r;
    }

    rules = ifObjectOr(rules, {});

    // ... talvez fazer alguma validação?

    return rules;
};

export const fieldFillInfo = (
    value: any,
    key: string | null = null
): { key: string | null; value: any; filled: boolean } => {
    let info = {
        key,
        value,
        filled: true,
    };

    if (['', undefined, null].includes(value)) {
        info.filled = false;
    }

    if (Array.isArray(value) && !value.length) {
        info.filled = false;
    }

    if (value && typeof value === 'object' && !Object.keys(value || []).length) {
        info.filled = false;
    }

    if (typeof value === 'string' && !value?.trim()?.length) {
        info.filled = false;
    }

    return {
        ...info,
        key,
        value,
    };
};

export const fieldIsFilled = (value: any, key: string | null = null) => {
    return Boolean(fieldFillInfo(value, key)?.filled);
};

export const getRequiredEmptyInputs = (form: any, requiredInputs: any) => {
    if (!form || !isObject(form)) {
        return [];
    }

    requiredInputs = makeRequiredInputs(requiredInputs);

    let emptyItems: any[] = [];
    let onlyRequired: any[] = Object.entries(requiredInputs || []).filter((i: any) => i && i[1]);

    if (!onlyRequired.length) {
        return [];
    }

    for (let item of onlyRequired) {
        let [key, _] = item;

        if (!(key in form)) {
            continue;
        }

        let value = form[key];

        if (!fieldIsFilled(value)) {
            emptyItems.push(key);
            continue;
        }
    }

    return emptyItems;
};

export const allFilled = (form: any, requiredInputs: any) => {
    return getRequiredEmptyInputs(form, requiredInputs).length <= 0;
};

export const allRequiredFieldsIsFilled = (form: any, requiredInputs: any) => {
    return getRequiredEmptyInputs(form, requiredInputs).length <= 0;
};
