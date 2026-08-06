import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAppStore = defineStore('app', () => {
    const sidebarOpen = ref(true);
    const isLoading = ref(false);
    const appName = ref(import.meta.env.VITE_APP_NAME || 'AMS');

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value;
    }

    function setLoading(value) {
        isLoading.value = value;
    }

    return {
        sidebarOpen,
        isLoading,
        appName,
        toggleSidebar,
        setLoading,
    };
});
