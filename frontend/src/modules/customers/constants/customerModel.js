export const CUSTOMER_TIMEZONES = [
  'UTC',
  'Africa/Cairo',
  'Africa/Johannesburg',
  'Africa/Lagos',
  'America/Chicago',
  'America/Denver',
  'America/Los_Angeles',
  'America/New_York',
  'America/Sao_Paulo',
  'America/Toronto',
  'Asia/Dhaka',
  'Asia/Dubai',
  'Asia/Hong_Kong',
  'Asia/Karachi',
  'Asia/Kolkata',
  'Asia/Singapore',
  'Asia/Tokyo',
  'Australia/Sydney',
  'Europe/Berlin',
  'Europe/London',
  'Europe/Paris',
  'Pacific/Auckland',
];

export const LEGAL_BASIS_OPTIONS = [
  { value: '', label: 'Not specified' },
  { value: 'consent', label: 'Consent' },
  { value: 'contract', label: 'Contract' },
  { value: 'legal_obligation', label: 'Legal obligation' },
  { value: 'legitimate_interests', label: 'Legitimate interests' },
  { value: 'vital_interests', label: 'Vital interests' },
  { value: 'public_task', label: 'Public task' },
];

export const CONTACT_TYPE_OPTIONS = [
  { value: 'primary', label: 'Primary' },
  { value: 'technical', label: 'Technical' },
  { value: 'support', label: 'Support' },
  { value: 'billing', label: 'Billing' },
  { value: 'security', label: 'Security' },
  { value: 'compliance', label: 'Compliance / Privacy' },
  { value: 'business', label: 'Business' },
  { value: 'emergency', label: 'Emergency' },
];

export const OWNERSHIP_OPTIONS = [
  {
    value: 'customer_owned',
    label: 'Customer owned',
    description: 'The customer operates this entitlement. The company still owns the application.',
  },
  {
    value: 'platform_managed',
    label: 'Platform managed',
    description: 'The company operates the application for the customer.',
  },
  {
    value: 'shared',
    label: 'Shared',
    description: 'The company and the customer share operational responsibility.',
  },
];
