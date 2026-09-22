import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAppStore = defineStore('app', () => {
    const sidebarOpen = ref(true);
    const sidebarCollapsed = ref(false);
    const isLoading = ref(false);
    const appName = ref(import.meta.env.VITE_APP_NAME || 'AMS');
    const logoUrl = ref('');

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    function closeSidebar() {
        sidebarOpen.value = false;
    }

    function openSidebar() {
        sidebarOpen.value = true;
    }

    function toggleSidebarCollapse() {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    }

    function setSidebarCollapsed(value) {
        sidebarCollapsed.value = Boolean(value);
    }

    function setLoading(value) {
        isLoading.value = value;
    }

    function setBranding({ appName: name, logoUrl: logo } = {}) {
        if (name) {
            appName.value = name;
        }
        logoUrl.value = logo || '';
    }

    return {
        sidebarOpen,
        sidebarCollapsed,
        isLoading,
        appName,
        logoUrl,
        toggleSidebar,
        closeSidebar,
        openSidebar,
        toggleSidebarCollapse,
        setSidebarCollapsed,
        setLoading,
        setBranding,
    };
});
