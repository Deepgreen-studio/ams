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
  'compliance.dashboard': ['compliance.view', 'compliance.cases'],
  'content.review': ['content.review', 'content.approve', 'content.publish'],
  'webhooks.index': 'webhooks.view',
  'webhooks.create': 'webhooks.create',
  'webhooks.show': 'webhooks.view',
  'webhooks.edit': 'webhooks.update',
  'webhooks.logs': 'webhooks.logs',
  'webhooks.events': 'webhooks.events',
  'webhooks.tester': 'webhooks.test',
  'webhooks.trash': 'webhooks.view-trash',
  'integrations.docs': ['integrations.view', 'webhooks.docs'],
  'sync.dashboard': 'sync.view',
  'mappings.index': 'sync.configs',
};

const SECTION_PREFIXES = [
  ['notifications.templates.approvals', 'notifications.approve'],
  ['notifications.templates', 'notifications.templates'],
  ['notifications.center', 'notifications.center'],
  ['notifications.unread', 'notifications.unread'],
  ['notifications.history', 'notifications.history'],
  ['notifications.preferences', 'notifications.preferences'],
  ['notifications.logs', 'notifications.logs'],
  ['automation.rules', 'automation.rules'],
  ['automation.history', 'automation.history'],
  ['workflows.designer', 'workflows.designer'],
  ['workflows.monitor', 'workflows.monitor'],
  ['workflows.instances', 'workflows.monitor'],
  ['workflows.queue', 'workflows.approve'],
  ['workflows.history', 'workflows.history'],
  ['scheduler.jobs', 'scheduler.jobs'],
  ['scheduler.history', 'scheduler.history'],
  ['scheduler.running', 'scheduler.running'],
  ['scheduler.failed', 'scheduler.failed'],
  ['scheduler.logs', 'scheduler.logs'],
  ['scheduler.statistics', 'scheduler.statistics'],
  ['ai.settings', 'ai.settings'],
  ['ai.prompts', 'ai.prompts'],
  ['ai.conversations', 'ai.conversations'],
  ['ai.analytics', 'ai.analytics'],
  ['ai.logs', 'ai.logs'],
  ['queue.running', 'queue.running'],
  ['queue.failed', 'queue.failed'],
  ['queue.statistics', 'queue.statistics'],
  ['sync.configs', 'sync.configs'],
  ['sync.history', 'sync.history'],
  ['sync.logs', 'sync.logs'],
  ['mappings.', 'sync.configs'],
  ['support.tickets.board', 'support.board'],
  ['support.tickets.queue', 'support.queue'],
  ['support.tickets.assignment', 'support.assignment'],
  ['support.tickets', 'support.tickets'],
  ['support.sla', 'support.sla'],
  ['support.knowledge', 'support.knowledge'],
  ['support.canned', 'support.canned'],
  ['compliance.cases', 'compliance.cases'],
  ['compliance.privacy', 'compliance.privacy'],
  ['compliance.consents', 'compliance.consents'],
  ['compliance.breaches', 'compliance.breaches'],
  ['compliance.dpia', 'compliance.dpia'],
  ['compliance.policies', 'compliance.policies'],
  ['compliance.analytics', 'compliance.reports'],
  ['analytics.dashboards', 'analytics.dashboards'],
  ['analytics.templates', 'analytics.templates'],
  ['analytics.saved-reports', 'analytics.saved-reports'],
  ['analytics.saved-views', 'analytics.saved-views'],
  ['analytics.reports', 'analytics.reports'],
  ['analytics.events', 'analytics.events'],
  ['analytics.business', 'analytics.business'],
  ['analytics.executive', 'analytics.executive'],
  ['analytics.security', 'analytics.security'],
  ['analytics.operational', 'analytics.operational'],
  ['analytics.delivery', 'analytics.operational'],
  ['analytics.automation', 'analytics.operational'],
  ['analytics.workflows', 'analytics.operational'],
  ['analytics.ai', 'analytics.operational'],
  ['settings.email', 'settings.email'],
  ['settings.storage', 'settings.storage'],
  ['settings.security', 'settings.security'],
  ['settings.api', 'settings.api'],
  ['settings.queue', 'settings.queue'],
  ['settings.media', 'settings.media'],
  ['settings.files', 'settings.files'],
  ['audit.trail', 'audit.trail'],
  ['audit.login', 'audit.login'],
  ['audit.events', 'audit.events'],
  ['audit.api', 'audit.api'],
  ['audit.errors', 'audit.errors'],
  ['monitoring.realtime', 'monitoring.realtime'],
  ['monitoring.api', 'monitoring.api'],
  ['monitoring.webhooks', 'monitoring.webhooks'],
  ['monitoring.queue', 'monitoring.queue'],
  ['monitoring.integrations', 'monitoring.integrations'],
  ['monitoring.timeline', 'monitoring.timeline'],
  ['monitoring.history', 'monitoring.history'],
  ['monitoring.alerts', 'monitoring.alerts'],
];

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
  ['companies.customers', 'customers'],
  ['users.', 'users'],
  ['roles.', 'roles'],
  ['companies.', 'companies'],
  ['customers.', 'customers'],
  ['applications.', 'applications'],
  ['webhooks.', 'webhooks'],
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
  ['sync.', 'sync'],
  ['mappings.', 'sync'],
];

export function resolveRoutePermission(routeName) {
  if (!routeName || typeof routeName !== 'string') {
    return null;
  }

  if (Object.prototype.hasOwnProperty.call(EXACT, routeName)) {
    return EXACT[routeName];
  }

  const isCreate = /\.create$/.test(routeName) || routeName.includes('.create.');
  const isEdit = /\.edit$/.test(routeName) || routeName.includes('.edit.');

  for (const [prefix, module] of PREFIX_MODULES) {
    if (!routeName.startsWith(prefix)) {
      continue;
    }

    if (isCreate || isEdit) {
      if (module === 'sync') {
        return 'sync.configs';
      }

      return isCreate ? `${module}.create` : `${module}.update`;
    }

    break;
  }

  for (const [prefix, permission] of SECTION_PREFIXES) {
    if (routeName === prefix || routeName.startsWith(`${prefix}.`) || (prefix.endsWith('.') && routeName.startsWith(prefix))) {
      return permission;
    }
  }

  for (const [prefix, module] of PREFIX_MODULES) {
    if (routeName.startsWith(prefix)) {
      return `${module}.view`;
    }
  }

  return null;
}
