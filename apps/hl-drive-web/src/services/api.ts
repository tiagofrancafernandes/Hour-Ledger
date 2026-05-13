// import type { URL as URLType } from 'node:url';
import type { URLType } from '@/utils/data-helpers';
import {
    getWindowLocation,
    ifObjectOrEmpty,
    ifStringOrEmpty,
    ifValidUrl,
    isString,
    makeUrlPath,
} from '@/utils/data-helpers';
import type { Response } from 'undici-types';
// import { fetch } from 'undici';

const DEFAUL_API_DOMAIN = 'api.local.tiagoapps.com.br';
const API_DOMAIN = import.meta.env.VITE_API_DOMAIN || DEFAUL_API_DOMAIN;
const API_URL = import.meta.env.VITE_API_URL || 'http://api.local.tiagoapps.com.br/api';

interface RequestOptions {
    method?: string;
    body?: unknown;
    params?: unknown;
    headers?: Record<string, string>;
}

export function getApiBaseUrl(defaultValue: string | null | undefined = null): URLType {
    /** @ts-ignore */
    let _protocol: string = ifObjectOrEmpty(getWindowLocation())?.protocol || 'https:';

    let _apiUrl: string | null =
        ifStringOrEmpty(API_URL)
            ?.trim()
            ?.replaceAll(/[\/]{0,}$/g, '')
            ?.trim() || null;
    _apiUrl = ifStringOrEmpty(_apiUrl)?.trim() || defaultValue || `${_protocol}//${API_DOMAIN}`;

    /** @ts-ignore */
    let apiUrl: URLType = ifValidUrl(_apiUrl, true) as URLType;
    apiUrl.pathname = makeUrlPath(apiUrl.pathname || '');

    return apiUrl;
}

export function getApiUrl(path: string | null | undefined = ''): URLType {
    path = ifStringOrEmpty(path)?.trim() || '/';

    let [pathname, queryString] = path.split('?');

    pathname =
        ifStringOrEmpty(pathname)
            ?.trim()
            ?.replaceAll(/^[\/]{0,}/g, '')
            ?.trim() || '/';
    let apiBaseUrl: URLType = getApiBaseUrl(null);
    apiBaseUrl.pathname = makeUrlPath(
        [makeUrlPath(apiBaseUrl.pathname), makeUrlPath(pathname || '')].filter(Boolean).join('/').replaceAll('//', '/')
    );

    if (queryString) {
        let queryParams = new URLSearchParams(queryString);

        queryParams.forEach((value, key) => {
            apiBaseUrl.searchParams.append(key, value);
        });
    }

    return apiBaseUrl;
}

async function request<T>(
    endpoint: string,
    options: RequestOptions | any = {},
    returnResponse: boolean | null = null
): Promise<T> {
    options = {
        body: options?.params?.body || options?.params || {},
        params: options?.params || options?.params?.body || {},
        ...(options || {}),
    };

    let { method = 'GET', body, params, headers = {} } = options;

    let bodyIsFormData = (body || {})?.constructor?.name === 'FormData';

    let responseType: any =
        (options || {})['responseType'] || (body || {})['responseType'] || (params || {})['responseType'] || null;

    if (returnResponse === null) {
        returnResponse =
            (options || {})['returnResponse'] ||
            (body || {})['returnResponse'] ||
            (params || {})['returnResponse'] ||
            false;
    }

    if (typeof options?.returnResponse !== 'undefined') {
        delete options.returnResponse;
    }

    if (typeof params?.returnResponse !== 'undefined') {
        delete params.returnResponse;
    }

    if (typeof body?.returnResponse !== 'undefined') {
        delete body.returnResponse;
    }

    if (typeof options?.responseType !== 'undefined') {
        delete options.responseType;
    }

    const token = localStorage.getItem('auth_token');
    let isGet = Boolean(!method || ['GET', 'get'].includes(method));

    headers = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...headers,
    };

    if (bodyIsFormData) {
        // headers['Content-Type'] = 'multipart/form-data'; // (This breaks the request because the boundary is not sent.)

        let _headersToDelete = ['Content-Type', 'content-type'];

        for (const _header of _headersToDelete) {
            if (typeof headers[_header] !== 'undefined') {
                delete headers[_header];
            }
        }
        isGet = isGet ? false : isGet;
    }

    const config: RequestInit | any = {
        method: bodyIsFormData ? (method === 'GET' ? 'POST' : method) : method,
        headers,
        credentials: 'include',
    };

    if (token) {
        (config.headers as Record<string, string>)['Authorization'] = `Bearer ${token}`;
    }

    if (!isGet) {
        config.body = body || params;
        config.body = bodyIsFormData ? body : JSON.stringify(config.body);
    }

    let response: Response | any;

    if (isGet) {
        config.method = 'GET';

        let _data: any = { ...params, ...(body || {}) };
        let _url: URL = getApiUrl(endpoint);

        Object.keys(_data || {})
            .filter((key: any) => typeof _data[key] !== 'object')
            .forEach((key) => _url.searchParams.append(key, _data[key]));

        if (typeof config?.params !== 'undefined') {
            delete config.params;
        }

        if (typeof config?.body !== 'undefined') {
            delete config.body;
        }

        response = await fetch(_url, config);
    }

    if (!isGet) {
        let _url: URL = getApiUrl(endpoint);
        response = await fetch(_url, config);
    }

    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: 'Request failed' }));

        throw new Error(error.message || `HTTP error ${response.status}`);
    }

    if (response.status === 204) {
        return null as T;
    }

    if (returnResponse) {
        return response;
    }

    if (responseType === 'blob') {
        return response.blob();
    }

    if ((response.headers.get('Content-Type') || '').includes('application/json')) {
        return response.json();
    }

    return response.text();
}

async function downloadFile(endpoint: string, filename: string | null = null): Promise<void> {
    const token = localStorage.getItem('auth_token');

    const config: RequestInit = {
        method: 'GET',
        headers: {
            Accept: 'application/octet-stream',
        },
        credentials: 'include',
    };

    if (token) {
        (config.headers as Record<string, string>)['Authorization'] = `Bearer ${token}`;
    }

    const response: any = await request(endpoint, config, /* returnResponse */ true);

    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: 'Download failed' }));

        throw new Error(error.message || `HTTP error ${response.status}`);
    }

    const contentDisposition = response.headers.get('Content-Disposition');

    console.log('api.ts downloadFile', {
        filename,
        response,
        contentDisposition,
        responseHeaders: response.headers,
    });

    if (!filename && contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);

        if (filenameMatch && filenameMatch[1]) {
            filename = filenameMatch[1].replace(/['"]/g, '');
        }
    }

    filename = filename || response.headers.get('X-Filename');

    if (!filename) {
        filename = filename || `download_${Date.now()}`;
    }

    /** @ts-ignore */
    if (typeof document === 'undefined') {
        return;
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

export const api = {
    get: <T>(endpoint: string, options: object = {}, returnResponse: boolean | null = null) =>
        request<T>(endpoint, { method: 'GET', ...(options || {}), params: options }, returnResponse),

    post: <T>(endpoint: string, data: unknown = {}, options: object = {}, returnResponse: boolean | null = null) =>
        request<T>(endpoint, { method: 'POST', ...(options || {}), body: data }, returnResponse),

    put: <T>(endpoint: string, data: unknown = {}, options: object = {}, returnResponse: boolean | null = null) =>
        request<T>(endpoint, { method: 'PUT', ...(options || {}), body: data }, returnResponse),

    delete: <T>(endpoint: string, options: object = {}, returnResponse: boolean | null = null) =>
        request<T>(endpoint, { method: 'DELETE', ...(options || {}) }, returnResponse),

    download: (endpoint: string, filename: string | null = null) => downloadFile(endpoint, filename),
};

export default api;
