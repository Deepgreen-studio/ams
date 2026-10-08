const LABELS = {
  android: 'Android',
  ios: 'iOS',
  web: 'Web',
  desktop: 'Desktop',
};

export function applicationPlatforms(application) {
  if (Array.isArray(application?.platforms) && application.platforms.length) {
    return application.platforms;
  }

  return application?.platform ? [application.platform] : [];
}

export function platformLabel(value) {
  return LABELS[value] || String(value || '')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

export function platformLabels(application) {
  const values = applicationPlatforms(application);
  return values.length ? values.map(platformLabel).join(', ') : '—';
}
