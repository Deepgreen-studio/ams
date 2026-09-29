import { ref } from 'vue';

export const appTimezone = ref('UTC');
export const appDateFormat = ref('Y-m-d');
export const appTimeFormat = ref('H:i');

const DATE_FORMATS = new Set(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd M Y']);
const TIME_FORMATS = new Set(['H:i', 'h:i A']);
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const nativeDateTimeFormat = Intl.DateTimeFormat;
const nativeToLocaleString = Date.prototype.toLocaleString;
const nativeToLocaleDateString = Date.prototype.toLocaleDateString;
const nativeToLocaleTimeString = Date.prototype.toLocaleTimeString;

export function setAppTimezone(value) {
    const candidate = typeof value === 'string' ? value.trim() : '';

    if (!candidate) {
        appTimezone.value = 'UTC';
        return;
    }

    try {
        new nativeDateTimeFormat('en-US', { timeZone: candidate }).format(new Date());
        appTimezone.value = candidate;
    } catch {
        appTimezone.value = 'UTC';
    }
}

export function setAppDateTimeFormats(dateFormat, timeFormat) {
    if (DATE_FORMATS.has(dateFormat)) {
        appDateFormat.value = dateFormat;
    }

    if (TIME_FORMATS.has(timeFormat)) {
        appTimeFormat.value = timeFormat;
    }
}

export function getAppTimezone() {
    return appTimezone.value || 'UTC';
}

function asDate(value) {
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

function clockParts(date, timeZone) {
    const parts = new nativeDateTimeFormat('en-US', {
        timeZone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date);

    const mapped = {};
    parts.forEach((part) => {
        if (part.type !== 'literal') {
            mapped[part.type] = part.value;
        }
    });

    let hour24 = Number(mapped.hour);
    if (hour24 === 24) {
        hour24 = 0;
    }

    const hour12 = hour24 % 12 || 12;

    return {
        Y: mapped.year,
        m: mapped.month,
        d: mapped.day,
        M: MONTHS[Number(mapped.month) - 1] || mapped.month,
        H: String(hour24).padStart(2, '0'),
        h: String(hour12).padStart(2, '0'),
        i: mapped.minute,
        s: mapped.second,
        A: hour24 < 12 ? 'AM' : 'PM',
    };
}

function applyPattern(date, pattern, timeZone) {
    const tokens = clockParts(date, timeZone);
    return pattern.replace(/Y|m|d|M|H|h|i|s|A/g, (token) => tokens[token] ?? token);
}

export function formatAppDate(value, timeZone = getAppTimezone()) {
    const date = asDate(value);
    if (!date) {
        return '';
    }

    return applyPattern(date, appDateFormat.value, timeZone);
}

export function formatAppTime(value, timeZone = getAppTimezone()) {
    const date = asDate(value);
    if (!date) {
        return '';
    }

    return applyPattern(date, appTimeFormat.value, timeZone);
}

export function formatAppDateTime(value, timeZone = getAppTimezone()) {
    const date = asDate(value);
    if (!date) {
        return '';
    }

    return `${applyPattern(date, appDateFormat.value, timeZone)} ${applyPattern(date, appTimeFormat.value, timeZone)}`;
}

function formatIntent(date, intent, options) {
    const timeZone = options?.timeZone || getAppTimezone();

    if (intent === 'time') {
        return formatAppTime(date, timeZone);
    }

    if (intent === 'date') {
        return formatAppDate(date, timeZone);
    }

    return formatAppDateTime(date, timeZone);
}

function intentFromOptions(options) {
    if (!options) {
        return 'datetime';
    }

    if (options.dateStyle && options.timeStyle) {
        return 'datetime';
    }

    if (options.dateStyle) {
        return 'date';
    }

    if (options.timeStyle) {
        return 'time';
    }

    const hasDate = ['year', 'month', 'day', 'weekday'].some((key) => options[key]);
    const hasTime = ['hour', 'minute', 'second'].some((key) => options[key]);

    if (hasDate && hasTime) {
        return 'datetime';
    }

    if (hasTime) {
        return 'time';
    }

    if (hasDate) {
        return 'date';
    }

    return 'datetime';
}

/**
 * Dates across the app are formatted with Intl or Date locale methods.
 * Default those to the General Settings timezone, date format, and time format.
 */
export function installAppTimezone() {
    if (!Intl.DateTimeFormat.__amsTimezone) {
        function AppDateTimeFormat(locales, options) {
            const resolved = options ? { ...options } : {};

            if (!resolved.timeZone) {
                resolved.timeZone = getAppTimezone();
            }

            return new nativeDateTimeFormat(locales, resolved);
        }

        AppDateTimeFormat.prototype = nativeDateTimeFormat.prototype;
        AppDateTimeFormat.supportedLocalesOf = nativeDateTimeFormat.supportedLocalesOf.bind(nativeDateTimeFormat);
        AppDateTimeFormat.__amsTimezone = true;

        Intl.DateTimeFormat = AppDateTimeFormat;
    }

    if (Date.prototype.toLocaleString.__amsFormats) {
        return;
    }

    function toLocaleString(locales, options) {
        if (Number.isNaN(this.getTime())) {
            return nativeToLocaleString.call(this, locales, options);
        }

        return formatIntent(this, intentFromOptions(options), options);
    }

    function toLocaleDateString(locales, options) {
        if (Number.isNaN(this.getTime())) {
            return nativeToLocaleDateString.call(this, locales, options);
        }

        return formatIntent(this, 'date', options);
    }

    function toLocaleTimeString(locales, options) {
        if (Number.isNaN(this.getTime())) {
            return nativeToLocaleTimeString.call(this, locales, options);
        }

        return formatIntent(this, 'time', options);
    }

    toLocaleString.__amsFormats = true;
    Date.prototype.toLocaleString = toLocaleString;
    Date.prototype.toLocaleDateString = toLocaleDateString;
    Date.prototype.toLocaleTimeString = toLocaleTimeString;
}

installAppTimezone();
