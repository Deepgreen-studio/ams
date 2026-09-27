const rules = [
  {
    id: 'lowercase',
    label: 'At least one lowercase letter',
    test: (value) => /\p{Ll}/u.test(value),
  },
  {
    id: 'uppercase',
    label: 'At least one uppercase letter',
    test: (value) => /\p{Lu}/u.test(value),
  },
  {
    id: 'number',
    label: 'At least one number',
    test: (value) => /\p{N}/u.test(value),
  },
  {
    id: 'symbol',
    label: 'At least one symbol',
    test: (value) => /\p{Z}|\p{S}|\p{P}/u.test(value),
  },
  {
    id: 'length',
    label: 'Minimum 8 characters',
    test: (value) => value.length >= 8,
  },
];

export function evaluatePassword(value) {
  const password = value || '';

  return rules.map((rule) => ({
    id: rule.id,
    label: rule.label,
    met: rule.test(password),
  }));
}

export function passwordIsValid(value) {
  return evaluatePassword(value).every((rule) => rule.met);
}
