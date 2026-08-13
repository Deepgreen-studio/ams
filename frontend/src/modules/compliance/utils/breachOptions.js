export const breachStatusOptions = [
  { value: 'reported', label: 'Reported' },
  { value: 'assessing', label: 'Assessing' },
  { value: 'contained', label: 'Contained' },
  { value: 'recovering', label: 'Recovering' },
  { value: 'notifying', label: 'Notifying' },
  { value: 'closed', label: 'Closed' },
  { value: 'cancelled', label: 'Cancelled' },
];

export const breachSeverityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
];

export const breachTypeOptions = [
  { value: 'unauthorized_access', label: 'Unauthorized access' },
  { value: 'data_loss', label: 'Data loss' },
  { value: 'ransomware', label: 'Ransomware' },
  { value: 'phishing', label: 'Phishing' },
  { value: 'insider_threat', label: 'Insider threat' },
  { value: 'misconfiguration', label: 'Misconfiguration' },
  { value: 'third_party', label: 'Third party' },
  { value: 'other', label: 'Other' },
];

export const breachNotificationStatusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'queued', label: 'Queued' },
  { value: 'sent', label: 'Sent' },
  { value: 'failed', label: 'Failed' },
  { value: 'acknowledged', label: 'Acknowledged' },
];

export const breachNotificationTypeOptions = [
  { value: 'regulator', label: 'Regulator' },
  { value: 'customer', label: 'Customer' },
  { value: 'internal', label: 'Internal' },
  { value: 'affected_user', label: 'Affected user' },
];

export const breachNotificationChannelOptions = [
  { value: 'email', label: 'Email' },
  { value: 'letter', label: 'Letter' },
  { value: 'phone', label: 'Phone' },
  { value: 'portal', label: 'Portal' },
  { value: 'sms', label: 'SMS' },
  { value: 'other', label: 'Other' },
];
