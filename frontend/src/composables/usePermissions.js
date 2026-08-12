import { useAuthStore } from '@/modules/authentication/stores/auth';

/**
 * Permission helpers for UI gating (sidebar, buttons, routes).
 */
export function usePermissions() {
  const authStore = useAuthStore();

  function can(permission) {
    if (Array.isArray(permission)) {
      return authStore.hasAnyPermission(...permission);
    }

    return authStore.hasPermission(permission);
  }

  function canAny(...permissions) {
    return authStore.hasAnyPermission(...permissions);
  }

  function canAll(...permissions) {
    return authStore.hasAllPermissions(...permissions);
  }

  return {
    permissions: authStore.permissions,
    roles: authStore.roles,
    isSuperAdmin: authStore.isSuperAdmin,
    can,
    canAny,
    canAll,
    hasPermission: authStore.hasPermission,
    hasAnyPermission: authStore.hasAnyPermission,
    hasAllPermissions: authStore.hasAllPermissions,
  };
}
