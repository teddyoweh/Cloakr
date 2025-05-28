import {type ClassValue, clsx} from 'clsx'
import {twMerge} from 'tailwind-merge'
import {useClipboard} from '@vueuse/core'

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export function isEmptyObject(obj: any): boolean {
    if (!obj) {
        return true
    }
    return Object.keys(obj).length === 0
}

export function copyToClipboard(source: string): void {
    const {copy} = useClipboard({source})
    copy()
}

export function toPhpArray(rows: Record<string, any>, variableName: string | null): string {
    let output = "";

    if (variableName) {
        output = `$${variableName} = [\n`;
    } else {
        output = "[\n";
    }

    for (let key in rows) {
        let value = rows[key];
        let arrayKey: string | number = key;

        if (value === null) {
            arrayKey = key;
            value = 'null';
        } else {
            if (typeof value.name !== 'undefined' && variableName !== null) {
                arrayKey = value.name;
            }
            if (typeof value.value !== 'undefined') {
                value = value.value;
            }
        }


        if (isNaN(<number>(arrayKey))) {
            arrayKey = `'${arrayKey}'`;
        }

        if (typeof value === 'object') {
            value = toPhpArray(value, null);
            output += `    ${arrayKey} => ${value},\n`;
        } else {
            if (isNaN((value))) {
                value = `'${value}'`;
            }

            output += `    ${arrayKey} => ${value},\n`;
        }

    }


    if (variableName) {
        output += `];`;
    } else {
        output += "    ]";
    }

    return output;
}

export function bodyIsJson(payload: ResponseData | RequestData): boolean {
    if (!payload || !payload.headers || payload.headers['Content-Type'] === null) {
        return false;
    }

    const contentType = payload.headers['Content-Type'];
    let hasContentType = contentType ? /application\/json/g.test(contentType) : false;
    try {
        if (payload.body) {
            JSON.parse(payload.body);
        }
        return hasContentType;
    } catch (e) {
        return false;
    }
}

export function bodyIsHtml(payload: ResponseData | RequestData): boolean {
    if (!payload || !payload.headers || payload.headers['Content-Type'] === null) {
        return false;
    }

    const contentType = payload.headers['Content-Type'];

    return contentType ? /text\/html/g.test(contentType) : false;
}

export function openInNewTab(url: string): void {
    window.open(url, '_blank');
}

export function isDarwin(): boolean {
    return navigator.userAgent.indexOf('Mac OS X') !== -1;
}

export function isNestedStructure(obj: Record<string, any>): boolean {
    return Object.keys(obj).some(key => typeof obj[key] === 'object');
}
