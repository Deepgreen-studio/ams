/**
 * Build initials from a user-like object.
 * Prefers first/last name letters, then full name words.
 */
export function getUserInitials(user) {
  if (!user) {
    return 'U';
  }

  const first = String(user.first_name || '').trim();
  const last = String(user.last_name || '').trim();
  if (first || last) {
    return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase() || 'U';
  }

  const full = String(user.full_name || user.name || '').trim();
  if (!full) {
    const email = String(user.email || '').trim();
    return email ? email.charAt(0).toUpperCase() : 'U';
  }

  const parts = full.split(/\s+/).filter(Boolean);
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }

  return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
}

export function getUserAvatarUrl(user) {
  if (!user) {
    return '';
  }

  const url = user.avatar_url || user.avatar || '';
  return typeof url === 'string' ? url.trim() : '';
}
