<template>
  <aside :class="['admin-sidebar', { collapsed: isCollapsed, 'mobile-open': isMobileOpen }]">
    <div class="sidebar-overlay" @click="$emit('close-mobile')"></div>

    <div class="sidebar-content">
      <nav class="sidebar-nav">
        <div v-for="group in menuGroups" :key="group.title" class="menu-group">
          <div v-if="!isCollapsed" class="group-title">{{ group.title }}</div>
          <div v-if="isCollapsed" class="group-divider"></div>

          <router-link
            v-for="item in group.items"
            :key="item.path"
            :to="item.path"
            :class="['menu-item', { active: isActive(item.path) }]"
            :title="isCollapsed ? item.label : ''"
            @click="$emit('close-mobile')"
          >
            <span class="menu-icon">{{ item.icon }}</span>
            <span v-if="!isCollapsed" class="menu-label">{{ item.label }}</span>
            <span v-if="!isCollapsed && item.badge" class="menu-badge">{{ item.badge }}</span>
          </router-link>
        </div>
      </nav>

      <div class="sidebar-footer">
        <div v-if="!isCollapsed" class="sidebar-info">
          <span class="info-text">Phiên bản</span>
          <span class="info-version">1.0.0</span>
        </div>
        <button
          class="collapse-btn"
          :title="isCollapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'"
          @click="toggleCollapse"
        >
          <svg
            :class="['collapse-icon', { rotated: isCollapsed }]"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  isCollapsed: {
    type: Boolean,
    default: false
  },
  isMobileOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['toggle-collapse', 'close-mobile'])

const route = useRoute()

const menuGroups = [
  {
    title: 'Tổng Quan',
    items: [
      {
        label: 'Dashboard',
        path: '/admin/dashboard',
        icon: '📊'
      }
    ]
  },
  {
    title: 'Quản lý khóa học',
    items: [
      {
        label: 'Khóa học',
        path: '/admin/khoa-hoc',
        icon: '🎓'
      },
      {
        label: 'Bài học',
        path: '/admin/bai-hoc',
        icon: '📖'
      }
    ]
  },
  {
    title: 'Tài Nguyên',
    items: [
      {
        label: 'Tài liệu',
        path: '/admin/tai-lieu',
        icon: '📁'
      },
      {
        label: 'Bài viết',
        path: '/admin/bai-viet',
        icon: '📝'
      },
      {
        label: 'Từ Vựng',
        path: '/admin/tu-vung',
        icon: '🔤',
        desc: 'Chương 1'
      },
      {
        label: 'Ngữ Pháp',
        path: '/admin/ngu-phap',
        icon: '📋',
        desc: 'Chương 2'
      },
      {
        label: 'Luyện Nghe',
        path: '/admin/luyen-nghe',
        icon: '🎧',
        desc: 'Chương 3'
      },
      {
        label: 'Luyện nói',
        path: '/admin/luyen-noi',
        icon: '🎤',
        desc: 'Chương 4'
      },
      {
        label: 'Luyện tập',
        path: '/admin/luyen-tap',
        icon: '✏️',
        desc: 'Quiz'
      }
    ]
  },
  {
    title: 'Kiểm tra & Đánh giá',
    items: [
      {
        label: 'Bài thi cuối khóa',
        path: '/admin/quiz-cuoi-khoa',
        icon: '🎯'
      },
      {
        label: 'Kết Quả',
        path: '/admin/ket-qua',
        icon: '🏆'
      }
    ]
  },
  {
    title: 'Quản lý người dùng',
    items: [
      {
        label: 'Người Dùng',
        path: '/admin/nguoi-dung',
        icon: '👥'
      },
      {
        label: 'Tiến Độ Học',
        path: '/admin/tien-do-hoc',
        icon: '📈'
      }
    ]
  },
  {
    title: 'Hệ Thống',
    items: [
      {
        label: 'Thanh Toán',
        path: '/admin/thanh-toan',
        icon: '💳',
        badge: '3'
      },
      {
        label: 'Đánh giá & Bình luận',
        path: '/admin/danh-gia-binh-luan',
        icon: '💬'
      },
      {
        label: 'Cài đặt',
        path: '/admin/setting',
        icon: '⚙️'
      }
    ]
  }
]

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const toggleCollapse = () => {
  emit('toggle-collapse')
}
</script>

<style scoped>
.admin-sidebar {
  position: fixed;
  top: 64px;
  left: 0;
  bottom: 0;
  width: 260px;
  background: white;
  border-right: 1px solid #e5e7eb;
  z-index: 90;
  transition: width 0.3s ease;
  display: flex;
  flex-direction: column;
}

.admin-sidebar.collapsed {
  width: 72px;
}

.sidebar-overlay {
  display: none;
}

.sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 16px 0;
}

.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 2px;
}

.menu-group {
  margin-bottom: 8px;
}

.group-title {
  padding: 8px 20px 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #9ca3af;
}

.group-divider {
  height: 1px;
  background: #f3f4f6;
  margin: 8px 12px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  margin: 2px 12px;
  border-radius: 8px;
  text-decoration: none;
  color: #6b7280;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.15s ease;
  position: relative;
  white-space: nowrap;
}

.menu-item:hover {
  background: #f3f4f6;
  color: #374151;
}

.menu-item.active {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: white;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.menu-item.active::before {
  content: '';
  position: absolute;
  left: -12px;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 24px;
  background: #4f46e5;
  border-radius: 0 4px 4px 0;
}

.collapsed .menu-item {
  justify-content: center;
  padding: 10px;
  margin: 2px 8px;
}

.collapsed .menu-item.active::before {
  display: none;
}

.menu-icon {
  font-size: 18px;
  flex-shrink: 0;
  width: 24px;
  text-align: center;
}

.menu-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
}

.menu-badge {
  background: #ef4444;
  color: white;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 7px;
  border-radius: 10px;
  min-width: 20px;
  text-align: center;
}

.sidebar-footer {
  padding: 12px;
  border-top: 1px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.collapsed .sidebar-footer {
  justify-content: center;
}

.sidebar-info {
  display: flex;
  flex-direction: column;
}

.collapsed .sidebar-info {
  display: none;
}

.info-text {
  font-size: 11px;
  color: #9ca3af;
}

.info-version {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
}

.collapse-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
  flex-shrink: 0;
}

.collapse-btn:hover {
  background: #e5e7eb;
  color: #4f46e5;
}

.collapse-icon {
  transition: transform 0.3s ease;
}

.collapse-icon.rotated {
  transform: rotate(180deg);
}

@media (max-width: 768px) {
  .admin-sidebar {
    transform: translateX(-100%);
    width: 260px;
    top: 0;
    padding-top: 64px;
  }

  .admin-sidebar.mobile-open {
    transform: translateX(0);
  }

  .admin-sidebar.collapsed {
    width: 260px;
  }

  .collapsed .menu-item {
    justify-content: flex-start;
    padding: 10px 20px;
    margin: 2px 12px;
  }

  .collapsed .sidebar-footer {
    justify-content: space-between;
  }

  .collapsed .sidebar-info {
    display: flex;
  }

  .sidebar-overlay {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: -1;
  }
}
</style>
