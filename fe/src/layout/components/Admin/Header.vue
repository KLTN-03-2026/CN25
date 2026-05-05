<template>
  <header class="admin-header">
    <div class="header-left">
      <button class="toggle-btn" @click="$emit('toggle-sidebar')">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </button>
      <div class="logo">
        <div class="logo-icon">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="logo-text">
          <span class="logo-title">DTU</span>
          <span class="logo-bottom">LingoAI</span>
        </div>
      </div>
    </div>

    <div class="header-center">
      <div class="search-wrapper">
        <span class="search-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35"/>
          </svg>
        </span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm nhanh..."
          class="search-input"
          @keyup.enter="handleSearch"
        />
        <span v-if="searchQuery" class="clear-btn" @click="searchQuery = ''">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </span>
      </div>
    </div>

    <div class="header-right">
      <div class="header-actions">
        <button class="action-btn" title="Tin nhắn" @click="toggleMessages">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <span v-if="unreadMessages > 0" class="badge">{{ unreadMessages }}</span>
        </button>

        <button class="action-btn" title="Thông báo" @click="toggleNotifications">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <span v-if="unreadNotifications > 0" class="badge danger">{{ unreadNotifications }}</span>
        </button>

        <button class="action-btn" title="Cài đặt" @click="goToSettings">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
          </svg>
        </button>
      </div>

      <div class="divider"></div>

      <div class="user-menu" @click="toggleUserMenu">
        <img :src="currentUser.avatar" :alt="currentUser.hoTen" class="user-avatar" />
        <div class="user-info">
          <span class="user-name">{{ currentUser.hoTen }}</span>
          <span class="user-role">{{ currentUser.vaiTro }}</span>
        </div>
        <span class="dropdown-arrow" :class="{ open: showUserMenu }">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </span>

        <div v-if="showUserMenu" class="dropdown-menu">
          <router-link to="/admin/profile" class="dropdown-item" @click="showUserMenu = false">
            <i class="fa-solid fa-user"></i>
            Hồ sơ cá nhân
          </router-link>
          <router-link to="/admin/setting" class="dropdown-item" @click="showUserMenu = false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Cài đặt
          </router-link>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item danger" @click="handleLogout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Đăng xuất
          </button>
        </div>
      </div>
    </div>

    <div v-if="showNotifications" class="notifications-panel" @click.stop>
      <div class="panel-header">
        <h3>Thông báo</h3>
        <button class="mark-read-btn" @click="markAllRead">Đánh dấu đã đọc</button>
      </div>
      <div class="notifications-list">
        <div
          v-for="notif in notifications"
          :key="notif.id"
          :class="['notification-item', { unread: !notif.daDoc }]"
          @click="handleNotificationClick(notif)"
        >
          <div :class="['notif-icon', notif.loai]">
            <span v-if="notif.loai === 'payment'">💳</span>
            <span v-else-if="notif.loai === 'user'">👤</span>
            <span v-else-if="notif.loai === 'course'">📚</span>
            <span v-else>🔔</span>
          </div>
          <div class="notif-content">
            <p class="notif-text">{{ notif.noiDung }}</p>
            <span class="notif-time">{{ notif.thoiGian }}</span>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import '@fortawesome/fontawesome-free/css/all.min.css'

const router = useRouter()
const authStore = useAuthStore()

defineEmits(['toggle-sidebar'])

const searchQuery = ref('')
const showUserMenu = ref(false)
const showNotifications = ref(false)
const showMessages = ref(false)

const unreadNotifications = ref(3)
const unreadMessages = ref(2)

const currentUser = computed(() => {
  const user = authStore.user
  return {
    hoTen: user?.name || 'Admin',
    vaiTro: 'Quản trị viên',
    avatar: user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'Admin')}&background=4f46e5&color=fff`
  }
})

const notifications = ref([
  { id: 1, loai: 'payment', noiDung: 'Có giao dịch thanh toán mới từ học viên', thoiGian: '5 phút trước', daDoc: false },
  { id: 2, loai: 'user', noiDung: 'Người dùng mới đăng ký tài khoản', thoiGian: '30 phút trước', daDoc: false },
  { id: 3, loai: 'course', noiDung: 'Khoá học mới được thêm vào hệ thống', thoiGian: '2 giờ trước', daDoc: false },
  { id: 4, loai: 'system', noiDung: 'Hệ thống đã sao lưu dữ liệu thành công', thoiGian: '1 ngày trước', daDoc: true }
])

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
  showNotifications.value = false
  showMessages.value = false
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  showUserMenu.value = false
  showMessages.value = false
}

const toggleMessages = () => {
  showMessages.value = !showMessages.value
  showUserMenu.value = false
  showNotifications.value = false
}

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    console.log('Tìm kiếm:', searchQuery.value)
  }
}

const handleLogout = async () => {
  if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
    await authStore.logout()
    router.push('/auth/login')
  }
}

const handleNotificationClick = (notif) => {
  notif.daDoc = true
  unreadNotifications.value = Math.max(0, unreadNotifications.value - 1)
}

const markAllRead = () => {
  notifications.value.forEach(n => n.daDoc = true)
  unreadNotifications.value = 0
}

const goToSettings = () => {
  router.push('/admin/setting')
}

const closeDropdowns = (e) => {
  if (!e.target.closest('.user-menu')) {
    showUserMenu.value = false
  }
  if (!e.target.closest('.action-btn') && !e.target.closest('.notifications-panel')) {
    showNotifications.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdowns)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns)
})
</script>

<style scoped>
.admin-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 64px;
  background: white;
  display: flex;
  align-items: center;
  padding: 0 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  z-index: 100;
  gap: 24px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.toggle-btn {
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding: 8px;
  background: none;
  border: none;
  cursor: pointer;
  border-radius: 6px;
  transition: background 0.2s;
}

.toggle-btn:hover { background: #f3f4f6; }

.toggle-btn .bar {
  display: block;
  width: 20px;
  height: 2px;
  background: #4b5563;
  border-radius: 2px;
}

.logo { display: flex; align-items: center; gap: 12px; }

.logo-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
}

.logo-icon i {
  font-size: 20px;
}

.logo-text {
  display: flex;
  flex-direction: column;
  line-height: 1.05;
}

.logo-title {
  display: block;
  font-size: 22px;
  font-weight: 900;
  letter-spacing: 1.5px;
  background: linear-gradient(90deg, #667eea, #764ba2, #db2777);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.logo-bottom {
  display: block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: #6b7280;
  text-transform: uppercase;
}

.header-center {
  flex: 1;
  max-width: 480px;
  margin: 0 auto;
}

.search-wrapper { position: relative; width: 100%; }

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  display: flex;
  align-items: center;
}

.search-input {
  width: 100%;
  padding: 10px 40px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  font-size: 14px;
  background: #f9fafb;
  transition: all 0.2s;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  border-color: #4f46e5;
  background: white;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.clear-btn {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.clear-btn:hover { color: #6b7280; }

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-shrink: 0;
}

.header-actions { display: flex; align-items: center; gap: 4px; }

.action-btn {
  position: relative;
  padding: 8px;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f3f4f6;
  color: #4f46e5;
}

.badge {
  position: absolute;
  top: 2px;
  right: 2px;
  min-width: 18px;
  height: 18px;
  background: #4f46e5;
  color: white;
  font-size: 11px;
  font-weight: 600;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
}

.badge.danger { background: #ef4444; }

.divider {
  width: 1px;
  height: 32px;
  background: #e5e7eb;
}

.user-menu {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 10px;
  transition: background 0.2s;
}

.user-menu:hover { background: #f9fafb; }

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
}

.user-info { display: flex; flex-direction: column; }

.user-name {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.2;
}

.user-role {
  font-size: 11px;
  color: #9ca3af;
  line-height: 1;
}

.dropdown-arrow {
  color: #9ca3af;
  display: flex;
  align-items: center;
  transition: transform 0.2s;
}

.dropdown-arrow.open { transform: rotate(180deg); }

.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 200px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid #f3f4f6;
  overflow: hidden;
  z-index: 200;
  animation: fadeInDown 0.2s ease;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  font-size: 14px;
  color: #4b5563;
  text-decoration: none;
  transition: background 0.15s;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}

.dropdown-item:hover {
  background: #f9fafb;
  color: #1f2937;
}

.dropdown-item.danger { color: #ef4444; }
.dropdown-item.danger:hover { background: #fef2f2; }

.dropdown-divider {
  height: 1px;
  background: #e5e7eb;
  margin: 4px 0;
}

.notifications-panel {
  position: fixed;
  top: 64px;
  right: 24px;
  width: 360px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid #f3f4f6;
  z-index: 200;
  overflow: hidden;
  animation: fadeInDown 0.2s ease;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #f3f4f6;
}

.panel-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.mark-read-btn {
  background: none;
  border: none;
  font-size: 13px;
  color: #4f46e5;
  cursor: pointer;
  font-weight: 500;
}

.mark-read-btn:hover { text-decoration: underline; }

.notifications-list { max-height: 400px; overflow-y: auto; }

.notification-item {
  display: flex;
  gap: 12px;
  padding: 14px 20px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f9fafb;
}

.notification-item:last-child { border-bottom: none; }
.notification-item:hover { background: #f9fafb; }
.notification-item.unread { background: #eff6ff; }
.notification-item.unread:hover { background: #dbeafe; }

.notif-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 16px;
}

.notif-icon.payment { background: #dbeafe; }
.notif-icon.user { background: #d1fae5; }
.notif-icon.course { background: #fef3c7; }
.notif-icon.system { background: #e0e7ff; }

.notif-content { flex: 1; min-width: 0; }

.notif-text {
  margin: 0;
  font-size: 13px;
  color: #374151;
  line-height: 1.5;
}

.notif-time {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 4px;
  display: block;
}
</style>
