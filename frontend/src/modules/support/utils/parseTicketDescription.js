const INGEST_HINTS = [
  'Auto-ingested from connected app webhook.',
  'Auto-ingested from EasyCare incoming webhook.',
];

const FIELD_LABELS = {
  from: 'From',
  to: 'To',
  customer_name: 'Customer name',
  customer_email: 'Customer email',
  customer_phone: 'Customer phone',
  message_id: 'Message ID',
  'App / webhook': 'App / webhook',
  Event: 'Event',
  'Webhook log': 'Webhook log',
  'Received at': 'Received at',
  name: 'Name',
  email: 'Email',
  phone: 'Phone',
};

/**
 * Split webhook-ingested ticket descriptions into message body + structured fields.
 * Falls back to the raw description when the ticket was created manually.
 *
 * @param {string|null|undefined} description
 * @returns {{
 *   isIngested: boolean,
 *   body: string,
 *   fields: Record<string, string>,
 *   contact: { name: string|null, email: string|null, phone: string|null, from: string|null, to: string|null },
 *   ingestMeta: Array<{ key: string, label: string, value: string }>,
 *   idempotencyTag: string|null,
 * }}
 */
export function parseTicketDescription(description) {
  const raw = typeof description === 'string' ? description.trim() : '';

  if (!raw) {
    return emptyResult('');
  }

  const isIngested = INGEST_HINTS.some((hint) => raw.includes(hint))
    || /\[ams-support-ingest:/.test(raw)
    || /\[easycare-ingest:/.test(raw);

  if (!isIngested) {
    return emptyResult(raw);
  }

  const lines = raw.split(/\r?\n/);
  const fields = {};
  const bodyLines = [];
  let pastSeparator = false;
  let idempotencyTag = null;

  for (const line of lines) {
    const trimmed = line.trim();

    if (/^\[(ams-support-ingest|easycare-ingest):.+]$/.test(trimmed)) {
      idempotencyTag = trimmed;
      continue;
    }

    if (trimmed === '---') {
      pastSeparator = true;
      continue;
    }

    if (INGEST_HINTS.includes(trimmed)) {
      pastSeparator = true;
      continue;
    }

    if (trimmed === 'Payload summary:') {
      pastSeparator = true;
      continue;
    }

    const bulletMatch = trimmed.match(/^- ([^:]+):\s*(.*)$/);
    if (bulletMatch) {
      const key = bulletMatch[1].trim();
      const value = bulletMatch[2].trim();
      if (value) {
        fields[key] = value;
      }
      pastSeparator = true;
      continue;
    }

    const kvMatch = trimmed.match(/^([A-Za-z][A-Za-z0-9_ /]+):\s*(.+)$/);
    if (kvMatch && (pastSeparator || looksLikeMetaKey(kvMatch[1]))) {
      const key = kvMatch[1].trim();
      const value = kvMatch[2].trim();
      if (value) {
        fields[key] = value;
      }
      pastSeparator = true;
      continue;
    }

    if (!pastSeparator) {
      bodyLines.push(line);
    }
  }

  const body = bodyLines.join('\n').trim()
    || fields.message
    || fields.body
    || '';

  const contact = {
    name: fields.customer_name || fields.name || null,
    email: fields.customer_email || fields.email || null,
    phone: fields.customer_phone || fields.phone || fields.from || null,
    from: fields.from || null,
    to: fields.to || null,
  };

  const ingestKeys = [
    'App / webhook',
    'Event',
    'Webhook log',
    'Received at',
    'message_id',
  ];

  const ingestMeta = ingestKeys
    .filter((key) => fields[key])
    .map((key) => ({
      key,
      label: FIELD_LABELS[key] || key,
      value: fields[key],
    }));

  return {
    isIngested: true,
    body,
    fields,
    contact,
    ingestMeta,
    idempotencyTag,
  };
}

function looksLikeMetaKey(key) {
  return [
    'from',
    'to',
    'customer_name',
    'customer_email',
    'customer_phone',
    'message_id',
    'App / webhook',
    'Event',
    'Webhook log',
    'Received at',
  ].includes(key);
}

function emptyResult(body) {
  return {
    isIngested: false,
    body,
    fields: {},
    contact: {
      name: null,
      email: null,
      phone: null,
      from: null,
      to: null,
    },
    ingestMeta: [],
    idempotencyTag: null,
  };
}

export function formatFieldLabel(key) {
  return FIELD_LABELS[key] || key.replace(/_/g, ' ');
}
