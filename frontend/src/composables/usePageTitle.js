export function usePageTitle(title) {
    if (typeof document === 'undefined') {
        return;
    }

    const baseTitle = import.meta.env.VITE_APP_NAME || 'AMS';
    document.title = title ? `${title} · ${baseTitle}` : baseTitle;
}
