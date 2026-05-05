<template>
  <div class="admin-layout">
    <Sidebar :is-collapsed="sidebarCollapsed" :is-mobile-open="mobileMenuOpen" @toggle-collapse="sidebarCollapsed = !sidebarCollapsed" @close-mobile="mobileMenuOpen = false" />
    <div class="content">
      <Header @toggle-sidebar="handleToggle" />
      <div :class="['page-content', { 'sidebar-collapsed': sidebarCollapsed }]">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Header from '../components/Admin/Header.vue'
import Sidebar from '../components/Admin/Sidebar.vue'

const sidebarCollapsed = ref(false)
const mobileMenuOpen = ref(false)

const handleToggle = () => {
  if (window.innerWidth <= 768) {
    mobileMenuOpen.value = !mobileMenuOpen.value
  } else {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg-secondary);
  transition: background 0.3s ease;
}

.content {
  flex: 1;
  display: flex;
  flex-direction: column;
  margin-left: 260px;
  transition: margin-left 0.3s ease;
  min-height: 100vh;
}

.page-content {
  margin-top: 64px;
  padding: 24px;
  flex: 1;
  transition: margin-left 0.3s ease;
}

.page-content.sidebar-collapsed {
  margin-left: 72px;
}

@media (max-width: 768px) {
  .content {
    margin-left: 0;
  }
  .page-content {
    margin-left: 0;
    padding: 16px;
  }
  .page-content.sidebar-collapsed {
    margin-left: 0;
  }
}
</style>
