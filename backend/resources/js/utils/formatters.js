export function formatDate(value, locale = 'en-US') {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}
