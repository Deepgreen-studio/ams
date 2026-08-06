export const statusOptions = [
  { value: 'open', label: 'Open' },
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'waiting_for_customer', label: 'Waiting for Customer' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed', label: 'Closed' },
  { value: 'reopened', label: 'Reopened' },
  { value: 'cancelled', label: 'Cancelled' },
];

export const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
  { value: 'emergency', label: 'Emergency' },
];

export const categoryOptions = [
  { value: 'customer_support', label: 'Customer Support' },
  { value: 'technical_support', label: 'Technical Support' },
  { value: 'billing_support', label: 'Billing Support' },
  { value: 'general_inquiry', label: 'General Inquiry' },
  { value: 'bug_report', label: 'Bug Report' },
  { value: 'feature_request', label: 'Feature Request' },
  { value: 'emergency_support', label: 'Emergency Support' },
];

export const sourceOptions = [
  { value: 'portal', label: 'Portal' },
  { value: 'email', label: 'Email' },
  { value: 'phone', label: 'Phone' },
  { value: 'chat', label: 'Chat' },
  { value: 'api', label: 'API' },
  { value: 'internal', label: 'Internal' },
  { value: 'web', label: 'Web' },
];

export const assignmentTypeOptions = [
  { value: 'manual', label: 'Manual Assignment' },
  { value: 'auto', label: 'Auto Assignment' },
  { value: 'department', label: 'Department Assignment' },
  { value: 'team', label: 'Team Assignment' },
  { value: 'agent', label: 'Agent Assignment' },
];

export const queueOptions = [
  { value: 'open', label: 'Active queue' },
  { value: 'unassigned', label: 'Unassigned' },
  { value: 'assignment', label: 'Needs assignment' },
  { value: 'mine', label: 'My tickets' },
  { value: 'critical', label: 'Critical / Emergency' },
  { value: 'waiting', label: 'Waiting for customer' },
  { value: 'reopened', label: 'Reopened' },
];
