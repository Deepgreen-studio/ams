import { formatAppDate, formatAppDateTime, getAppTimezone } from '@/utils/appTimezone';

const DATE_ONLY = /^(\d{4})-(\d{2})-(\d{2})$/;

function parseDate(value) {
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : { date: value, dateOnly: false };
    }

    const text = String(value).trim();
    const match = text.match(DATE_ONLY);

    if (match) {
        const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
        return Number.isNaN(date.getTime()) ? null : { date, dateOnly: true };
    }

    const date = new Date(text);
    return Number.isNaN(date.getTime()) ? null : { date, dateOnly: false };
}

export function formatDate(value) {
    if (!value) {
        return '';
    }

    const parsed = parseDate(value);
    if (!parsed) {
        return '';
    }

    return formatAppDate(parsed.date, parsed.dateOnly ? 'UTC' : getAppTimezone());
}

export function formatDateTime(value) {
    if (!value) {
        return '';
    }

    const parsed = parseDate(value);
    if (!parsed) {
        return '';
    }

    return formatAppDateTime(parsed.date, parsed.dateOnly ? 'UTC' : getAppTimezone());
}
