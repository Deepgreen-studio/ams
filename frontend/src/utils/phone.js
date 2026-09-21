import {
  getCountries,
  getCountryCallingCode,
  isPossiblePhoneNumber,
  parsePhoneNumberFromString,
} from 'libphonenumber-js';

export const PHONE_INVALID_MESSAGE =
  'Enter a valid phone number with a country code.';

export const E164_PATTERN = /^\+[1-9]\d{6,14}$/;

const PRIORITY_COUNTRIES = ['US', 'GB', 'CA', 'AU', 'IN', 'BD', 'AE', 'SA', 'DE', 'FR', 'SG', 'MY', 'PK', 'NG'];

const regionNames = typeof Intl !== 'undefined' && Intl.DisplayNames
  ? new Intl.DisplayNames(['en'], { type: 'region' })
  : null;

let countryCache = null;

export function flagEmoji(iso) {
  if (!iso || iso.length !== 2) {
    return '';
  }

  return iso
    .toUpperCase()
    .replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
}

export function getPhoneCountries() {
  if (countryCache) {
    return countryCache;
  }

  const countries = getCountries().map((iso) => ({
    iso,
    dialCode: `+${getCountryCallingCode(iso)}`,
    name: regionNames?.of(iso) || iso,
    flag: flagEmoji(iso),
  }));

  countries.sort((a, b) => {
    const aRank = PRIORITY_COUNTRIES.indexOf(a.iso);
    const bRank = PRIORITY_COUNTRIES.indexOf(b.iso);
    if (aRank !== -1 || bRank !== -1) {
      return (aRank === -1 ? 999 : aRank) - (bRank === -1 ? 999 : bRank);
    }
    return a.name.localeCompare(b.name);
  });

  countryCache = countries;
  return countryCache;
}

export function detectDefaultCountry(fallback = 'US') {
  if (typeof navigator === 'undefined') {
    return fallback;
  }

  const locale = navigator.language || '';
  const region = locale.split('-')[1]?.toUpperCase();
  const countries = getPhoneCountries();

  if (region && countries.some((country) => country.iso === region)) {
    return region;
  }

  return fallback;
}

export function digitsOnly(value) {
  return String(value || '').replace(/\D/g, '');
}

export function parseStoredPhone(value, fallbackCountry = detectDefaultCountry()) {
  const raw = String(value || '').trim();
  if (!raw) {
    return { country: fallbackCountry, nationalNumber: '', e164: '' };
  }

  const parsed = parsePhoneNumberFromString(raw);
  if (parsed?.country) {
    return {
      country: parsed.country,
      nationalNumber: parsed.nationalNumber || '',
      e164: parsed.number || '',
    };
  }

  if (parsed?.nationalNumber) {
    return {
      country: fallbackCountry,
      nationalNumber: parsed.nationalNumber,
      e164: parsed.number || '',
    };
  }

  return {
    country: fallbackCountry,
    nationalNumber: digitsOnly(raw),
    e164: '',
  };
}

export function toE164(country, nationalNumber) {
  const digits = digitsOnly(nationalNumber);
  if (!digits) {
    return '';
  }

  const parsed = parsePhoneNumberFromString(digits, country);
  if (parsed?.number && E164_PATTERN.test(parsed.number)) {
    return parsed.number;
  }

  try {
    const dial = getCountryCallingCode(country);
    return `+${dial}${digits}`;
  } catch {
    return '';
  }
}

export function isValidE164(value) {
  if (value == null || value === '') {
    return true;
  }

  if (typeof value !== 'string' || !E164_PATTERN.test(value)) {
    return false;
  }

  try {
    return isPossiblePhoneNumber(value);
  } catch {
    return false;
  }
}
