/**
 * Resolve Spatie permission(s) required for a named Vue route.
 * Returns a string, string[], or null (no permission check).
 */
const EXACT = {
  dashboard: 'dashboard.view',
  'customers.documents.upload': 'customer-documents.create',
  'departments.index': 'departments.view',
  'departments.show': 'departments.view',
  'users.trash': ['users.view', 'users.restore', 'users.force-delete'],
  'roles.trash': 'roles.view-trash',
  'roles.matrix': 'roles.matrix',
  'roles.assign': ['roles.assign-roles', 'users.assign-roles'],
  'roles.permissions': 'roles.assign',
  'companies.trash': 'companies.view-trash',
  'companies.console': 'companies.console',
  'companies.profile': 'companies.profile',
  'customers.trash': 'customers.view-trash',
  'applications.trash': 'applications.view-trash',
  'integrations.trash': 'integrations.view-trash',
  'content.workflow': ['content.review', 'content.approve'],
  'content.review': ['content.review', 'content.approve', 'content.publish'],
  'webhooks.index': 'integrations.view',
  'webhooks.create': 'integrations.create',
  'webhooks.show': 'integrations.view',
  'webhooks.edit': 'integrations.update',
  'sync.dashboard': 'integrations.view',
  'mappings.index': 'integrations.view',
};

const PREFIX_MODULES = [
  ['customers.contacts', 'customer-contacts'],
  ['customers.applications', 'customer-applications'],
  ['customers.subscriptions', 'customer-subscriptions'],
  ['customers.licenses', 'customer-licenses'],
  ['customers.documents', 'customer-documents'],
  ['customers.communications', 'customer-communications'],
  ['customers.analytics', 'customer-analytics'],
  ['companies.departments', 'departments'],
  ['companies.teams', 'teams'],
  ['companies.locations', 'locations'],
  ['users.', 'users'],
  ['roles.', 'roles'],
  ['companies.', 'companies'],
  ['customers.', 'customers'],
  ['applications.', 'applications'],
  ['integrations.', 'integrations'],
  ['content.', 'content'],
  ['support.', 'support'],
  ['notifications.', 'notifications'],
  ['automation.', 'automation'],
  ['workflows.', 'workflows'],
  ['scheduler.', 'scheduler'],
  ['ai.', 'ai'],
  ['analytics.', 'analytics'],
  ['compliance.', 'compliance'],
  ['reports.', 'reports'],
  ['settings.', 'settings'],
  ['audit.', 'audit'],
  ['queue.', 'queue'],
  ['monitoring.', 'monitoring'],
];

export function resolveRoutePermission(routeName) {
  if (!routeName || typeof routeName !== 'string') {
    return null;
  }

  if (Object.prototype.hasOwnProperty.call(EXACT, routeName)) {
    return EXACT[routeName];
  }

  for (const [prefix, module] of PREFIX_MODULES) {
    if (!routeName.startsWith(prefix)) {
      continue;
    }

    if (/\.create$/.test(routeName) || routeName.includes('.create.')) {
      return `${module}.create`;
    }

    if (/\.edit$/.test(routeName) || routeName.includes('.edit.')) {
      return `${module}.update`;
    }

    return `${module}.view`;
  }

  return null;
}
