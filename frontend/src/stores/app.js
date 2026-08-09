import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAppStore = defineStore('app', () => {
    const sidebarOpen = ref(true);
    const sidebarCollapsed = ref(false);
    const isLoading = ref(false);
    const appName = ref(import.meta.env.VITE_APP_NAME || 'AMS');

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

    return {
        sidebarOpen,
        sidebarCollapsed,
        isLoading,
        appName,
        toggleSidebar,
        closeSidebar,
        openSidebar,
        toggleSidebarCollapse,
        setSidebarCollapsed,
        setLoading,
    };
});
