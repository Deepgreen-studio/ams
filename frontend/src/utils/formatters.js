import { formatAppDate, formatAppDateTime, getAppTimezone } from '@/utils/appTimezone';

const DATE_ONLY = /^(\d{4})-(\d{2})-(\d{2})$/;
const NAIVE_DATE_TIME = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?$/;
const HAS_ZONE = /(?:z|Z|[+-]\d{2}:?\d{2})$/;

function parseDate(value) {
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : { date: value, dateOnly: false };
    }

    const text = String(value).trim();
    const dateOnly = text.match(DATE_ONLY);

    if (dateOnly) {
        const date = new Date(Date.UTC(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3])));
        return Number.isNaN(date.getTime()) ? null : { date, dateOnly: true };
    }

    const naive = text.match(NAIVE_DATE_TIME);
    if (naive && !HAS_ZONE.test(text)) {
        const date = new Date(Date.UTC(
            Number(naive[1]),
            Number(naive[2]) - 1,
            Number(naive[3]),
            Number(naive[4]),
            Number(naive[5]),
            Number(naive[6] || 0),
        ));
        return Number.isNaN(date.getTime()) ? null : { date, dateOnly: false };
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
