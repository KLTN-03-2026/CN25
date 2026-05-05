<template>
  <div class="profile-page">
    <div class="container">
      <!-- Page Header -->
      <div class="page-header">
        <router-link to="/student" class="back-link">
          <i class="fa-solid fa-arrow-left"></i>
          Quay lại trang chủ
        </router-link>
        <h1 class="page-title">Hồ Sơ Cá Nhân</h1>
        <p class="page-subtitle">Quản lý thông tin và cài đặt tài khoản của bạn</p>
      </div>

      <div class="profile-layout">
        <!-- Sidebar -->
        <aside class="profile-sidebar">
          <div class="avatar-section">
            <div class="avatar-wrapper" @click="triggerAvatarInput">
              <img :src="avatarUrl" :key="avatarKey" alt="Avatar" class="avatar-img" />
              <div class="avatar-overlay">
                <i class="fa-solid fa-camera"></i>
                <span>Đổi ảnh</span>
              </div>
              <div v-if="uploadingAvatar" class="avatar-uploading">
                <i class="fa-solid fa-spinner fa-spin"></i>
              </div>
            </div>
            <input
              type="file"
              ref="avatarInput"
              accept="image/*"
              @change="handleAvatarChange"
              style="display: none"
            />
            <h3 class="user-name">{{ user?.name }}</h3>
            <span class="user-role">
              <i class="fa-solid fa-graduation-cap"></i>
              Học viên
            </span>
          </div>

          <nav class="profile-nav">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              :class="['nav-item', { active: activeTab === tab.id }]"
              @click="activeTab = tab.id"
            >
              <i :class="tab.icon"></i>
              <span>{{ tab.label }}</span>
            </button>
          </nav>
        </aside>

        <!-- Content -->
        <main class="profile-content">
          <!-- Tab: Thông tin cá nhân -->
          <div v-if="activeTab === 'info'" class="tab-panel">
            <div class="panel-header">
              <h2 class="panel-title">
                <i class="fa-solid fa-user"></i>
                Thông tin cá nhân
              </h2>
            </div>

            <form @submit.prevent="saveProfile" class="profile-form">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-user"></i>
                    Họ và tên
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-input"
                    placeholder="Nhập họ và tên"
                    :disabled="saving"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-envelope"></i>
                    Email
                  </label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="form-input"
                    placeholder="Nhập email"
                    disabled
                  />
                  <span class="form-hint">Email không thể thay đổi</span>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-phone"></i>
                    Số điện thoại
                  </label>
                  <input
                    v-model="form.phone"
                    type="tel"
                    class="form-input"
                    placeholder="Nhập số điện thoại"
                    :disabled="saving"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-venus-mars"></i>
                    Giới tính
                  </label>
                  <select v-model="form.gender" class="form-input" :disabled="saving">
                    <option value="">-- Chọn giới tính --</option>
                    <option value="male">Nam</option>
                    <option value="female">Nữ</option>
                    <option value="other">Khác</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-cake-candles"></i>
                    Ngày sinh
                  </label>
                  <input
                    v-model="form.birthday"
                    type="date"
                    class="form-input"
                    :disabled="saving"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-globe"></i>
                    Quốc gia
                  </label>
                  <input
                    v-model="form.country"
                    type="text"
                    class="form-input"
                    placeholder="Ví dụ: Việt Nam"
                    :disabled="saving"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-city"></i>
                    Thành phố
                  </label>
                  <input
                    v-model="form.city"
                    type="text"
                    class="form-input"
                    placeholder="Ví dụ: Đà Nẵng"
                    :disabled="saving"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fa-solid fa-clock"></i>
                    Múi giờ
                  </label>
                  <select v-model="form.timezone" class="form-input" :disabled="saving">
                    <option value="Asia/Ho_Chi_Minh">GMT+7 - Asia/Ho_Chi_Minh (HCM)</option>
                    <option value="Asia/Bangkok">GMT+7 - Asia/Bangkok</option>
                    <option value="Asia/Singapore">GMT+8 - Asia/Singapore</option>
                    <option value="Asia/Tokyo">GMT+9 - Asia/Tokyo</option>
                    <option value="UTC">UTC</option>
                  </select>
                </div>
              </div>

              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="resetForm" :disabled="saving">
                  <i class="fa-solid fa-rotate-left"></i>
                  Đặt lại
                </button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                  <span v-if="saving">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Đang lưu...
                  </span>
                  <span v-else>
                    <i class="fa-solid fa-check"></i>
                    Lưu thay đổi
                  </span>
                </button>
              </div>
            </form>
          </div>

          <!-- Tab: Đổi mật khẩu -->
          <div v-if="activeTab === 'password'" class="tab-panel">
            <div class="panel-header">
              <h2 class="panel-title">
                <i class="fa-solid fa-lock"></i>
                Đổi mật khẩu
              </h2>
              <p class="panel-desc">Cập nhật mật khẩu để bảo vệ tài khoản của bạn</p>
            </div>

            <form @submit.prevent="changePassword" class="password-form">
              <div class="form-group">
                <label class="form-label">
                  <i class="fa-solid fa-key"></i>
                  Mật khẩu hiện tại
                </label>
                <div class="input-wrapper">
                  <input
                    v-model="passwordForm.current_password"
                    :type="showCurrent ? 'text' : 'password'"
                    class="form-input"
                    placeholder="Nhập mật khẩu hiện tại"
                    :disabled="changingPassword"
                  />
                  <button type="button" class="toggle-password" @click="showCurrent = !showCurrent">
                    <i :class="showCurrent ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="fa-solid fa-key"></i>
                  Mật khẩu mới
                </label>
                <div class="input-wrapper">
                  <input
                    v-model="passwordForm.password"
                    :type="showNew ? 'text' : 'password'"
                    class="form-input"
                    placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                    :disabled="changingPassword"
                  />
                  <button type="button" class="toggle-password" @click="showNew = !showNew">
                    <i :class="showNew ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="fa-solid fa-key"></i>
                  Xác nhận mật khẩu mới
                </label>
                <div class="input-wrapper">
                  <input
                    v-model="passwordForm.password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    class="form-input"
                    placeholder="Nhập lại mật khẩu mới"
                    :disabled="changingPassword"
                  />
                  <button type="button" class="toggle-password" @click="showConfirm = !showConfirm">
                    <i :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                  </button>
                </div>
              </div>

              <div v-if="passwordError" class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ passwordError }}
              </div>

              <div v-if="passwordSuccess" class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                {{ passwordSuccess }}
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="changingPassword">
                  <span v-if="changingPassword">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Đang đổi mật khẩu...
                  </span>
                  <span v-else>
                    <i class="fa-solid fa-shield-halved"></i>
                    Đổi mật khẩu
                  </span>
                </button>
              </div>
            </form>
          </div>

          <!-- Tab: Thống kê -->
          <div v-if="activeTab === 'stats'" class="tab-panel">
            <div class="panel-header">
              <h2 class="panel-title">
                <i class="fa-solid fa-chart-line"></i>
                Thống kê học tập
              </h2>
              <p class="panel-desc">Xem tổng quan tiến độ học tập của bạn</p>
            </div>

            <div v-if="statsLoading" class="stats-loading">
              <i class="fa-solid fa-spinner fa-spin"></i>
              Đang tải dữ liệu...
            </div>

            <div v-else class="stats-grid">
              <div class="stat-card">
                <div class="stat-icon" style="background: var(--accent-bg); color: var(--accent)">
                  <i class="fa-solid fa-book-open"></i>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ stats.total_courses || 0 }}</span>
                  <span class="stat-label">Khóa học đã đăng ký</span>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon" style="background: rgba(5, 150, 105, 0.1); color: var(--success)">
                  <i class="fa-solid fa-check-circle"></i>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ stats.completed_lessons || 0 }}</span>
                  <span class="stat-label">Bài học đã hoàn thành</span>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon" style="background: rgba(217, 119, 6, 0.1); color: var(--warning)">
                  <i class="fa-solid fa-fire"></i>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ stats.streak_days || 0 }}</span>
                  <span class="stat-label">Ngày học liên tiếp</span>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--info)">
                  <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ stats.total_time || 0 }}h</span>
                  <span class="stat-label">Tổng thời gian học</span>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7">
                  <i class="fa-solid fa-medal"></i>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ stats.badges || 0 }}</span>
                  <span class="stat-label">Huy hiệu đạt được</span>
                </div>
              </div>

              <div class="stat-card">
                <div class="stat-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899">
                  <i class="fa-solid fa-star"></i>
                </div>
                <div class="stat-info">
                  <span class="stat-value">{{ stats.avg_score || 0 }}</span>
                  <span class="stat-label">Điểm trung bình</span>
                </div>
              </div>
            </div>

            <div v-if="!statsLoading && user?.created_at" class="member-since">
              <i class="fa-solid fa-calendar-alt"></i>
              Thành viên từ: {{ formatDate(user.created_at) }}
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../../../stores/auth'
import { ProgressService, AuthService } from '../../../services/api'

const authStore = useAuthStore()

const user = computed(() => authStore.user)
const activeTab = ref('info')
const saving = ref(false)
const changingPassword = ref(false)
const statsLoading = ref(false)

const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

const avatarInput = ref(null)
const uploadingAvatar = ref(false)
const localAvatar = ref('')
const avatarKey = ref(Date.now())

const form = ref({
  name: '',
  email: '',
  phone: '',
  birthday: '',
  gender: '',
  country: '',
  city: '',
  timezone: 'Asia/Ho_Chi_Minh'
})

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const stats = ref({})

const tabs = [
  { id: 'info', label: 'Thông tin cá nhân', icon: 'fa-solid fa-user-pen' },
  { id: 'password', label: 'Đổi mật khẩu', icon: 'fa-solid fa-lock' },
  { id: 'stats', label: 'Thống kê học tập', icon: 'fa-solid fa-chart-bar' }
]

watch(activeTab, (val) => {
  if (val === 'stats') loadStats()
})

const avatarUrl = computed(() => {
  if (localAvatar.value && localAvatar.value !== '') return localAvatar.value
  if (user.value?.avatar) {
    return user.value.avatar.startsWith('http')
      ? user.value.avatar
      : `http://localhost:8000${user.value.avatar}`
  }
  const name = user.value?.name || 'U'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=4f46e5&color=fff&size=200`
})

const loadUserData = () => {
  form.value = {
    name: user.value?.name || '',
    email: user.value?.email || '',
    phone: user.value?.phone || '',
    birthday: user.value?.birthday || '',
    gender: user.value?.gender || '',
    country: user.value?.country || '',
    city: user.value?.city || '',
    timezone: user.value?.timezone || 'Asia/Ho_Chi_Minh'
  }
}

const resetForm = () => {
  loadUserData()
}

const saveProfile = async () => {
  saving.value = true
  try {
    const result = await authStore.updateProfile(form.value)
    if (result?.user) {
      authStore.$patch({ user: result.user })
      localStorage.setItem('auth_user', JSON.stringify(result.user))
    }
    showNotification('Cập nhật hồ sơ thành công!', 'success')
  } catch (error) {
    showNotification(error.response?.data?.message || 'Cập nhật thất bại', 'danger')
  } finally {
    saving.value = false
  }
}

const changePassword = async () => {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (!passwordForm.value.current_password) {
    passwordError.value = 'Vui lòng nhập mật khẩu hiện tại'
    return
  }
  if (!passwordForm.value.password || passwordForm.value.password.length < 6) {
    passwordError.value = 'Mật khẩu mới phải có ít nhất 6 ký tự'
    return
  }
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = 'Mật khẩu xác nhận không khớp'
    return
  }

  changingPassword.value = true
  try {
    await authStore.changePassword(passwordForm.value)
    passwordSuccess.value = 'Đổi mật khẩu thành công!'
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    setTimeout(() => { passwordSuccess.value = '' }, 5000)
  } catch (error) {
    passwordError.value = error.response?.data?.message || 'Đổi mật khẩu thất bại'
  } finally {
    changingPassword.value = false
  }
}

const loadStats = async () => {
  statsLoading.value = true
  try {
    const res = await ProgressService.getStats()
    if (res.success && res.data) {
      stats.value = {
        total_courses: res.data.total_courses || 0,
        completed_lessons: res.data.completed_lessons || 0,
        streak_days: res.data.streak_days || 0,
        total_time: res.data.total_time || 0,
        badges: res.data.badges || 0,
        avg_score: res.data.avg_score || 0,
      }
    } else {
      stats.value = {
        total_courses: 0,
        completed_lessons: 0,
        streak_days: 0,
        total_time: 0,
        badges: 0,
        avg_score: 0,
      }
    }
  } catch (error) {
    console.error('Failed to load stats:', error)
    stats.value = {
      total_courses: 0,
      completed_lessons: 0,
      streak_days: 0,
      total_time: 0,
      badges: 0,
      avg_score: 0,
    }
  } finally {
    statsLoading.value = false
  }
}

const triggerAvatarInput = () => {
  avatarInput.value?.click()
}

const handleAvatarChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    showNotification('Vui lòng chọn file hình ảnh', 'warning')
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    showNotification('Kích thước ảnh không được vượt quá 2MB', 'warning')
    return
  }

  // Preview ảnh ngay lập tức bằng FileReader
  const reader = new FileReader()
  reader.onload = (event) => {
    localAvatar.value = event.target.result
    avatarKey.value++
  }
  reader.readAsDataURL(file)

  uploadingAvatar.value = true
  try {
    const result = await AuthService.uploadAvatar(file)
    console.log('Upload avatar result:', result)

    // Cập nhật avatar URL từ server
    let newAvatarUrl = result.avatar
    if (newAvatarUrl && !newAvatarUrl.startsWith('http')) {
      newAvatarUrl = `http://localhost:8000${newAvatarUrl}`
    }

    authStore.$patch({ user: { ...authStore.user, avatar: newAvatarUrl } })
    localStorage.setItem('auth_user', JSON.stringify(authStore.user))
    localAvatar.value = newAvatarUrl
    avatarKey.value++
    showNotification('Cập nhật ảnh đại diện thành công!', 'success')
  } catch (error) {
    console.error('Upload avatar error:', error)
    showNotification(error.response?.data?.message || 'Upload ảnh thất bại', 'danger')
  } finally {
    uploadingAvatar.value = false
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('vi-VN', { day: '2-digit', month: 'long', year: 'numeric' })
}

const showNotification = (message, type) => {
  const container = document.querySelector('.profile-page .container')
  if (!container) return

  const notification = document.createElement('div')
  notification.className = `toast-notification toast-${type}`
  notification.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'circle-exclamation'}"></i> ${message}`
  notification.style.cssText = `
    position: fixed;
    top: 90px;
    right: 24px;
    padding: 14px 20px;
    border-radius: 12px;
    background: ${type === 'success' ? 'var(--success)' : type === 'warning' ? 'var(--warning)' : 'var(--danger)'};
    color: white;
    font-weight: 600;
    font-size: 14px;
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    animation: slideInRight 0.3s ease;
  `
  document.body.appendChild(notification)

  setTimeout(() => {
    notification.style.animation = 'slideOutRight 0.3s ease'
    setTimeout(() => notification.remove(), 300)
  }, 3000)
}

onMounted(async () => {
  authStore.initAuth()

  if (authStore.user?.avatar) {
    localAvatar.value = authStore.user.avatar
  }

  if (!user.value) {
    await authStore.fetchUser()
    if (authStore.user?.avatar) {
      localAvatar.value = authStore.user.avatar
    }
  }

  loadUserData()
})
</script>

<style scoped>
.profile-page {
  background: var(--bg-secondary);
  min-height: 100vh;
  padding: 32px 0 60px;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 24px;
}

/* Page Header */
.page-header {
  margin-bottom: 32px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 16px;
  transition: color 0.2s;
}

.back-link:hover {
  color: var(--accent);
}

.page-title {
  font-size: 32px;
  font-weight: 800;
  color: var(--text-h);
  margin-bottom: 8px;
  letter-spacing: -0.5px;
}

.page-subtitle {
  color: var(--muted);
  font-size: 16px;
}

/* Layout */
.profile-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 28px;
  align-items: start;
}

/* Sidebar */
.profile-sidebar {
  background: var(--card-bg);
  border-radius: 20px;
  box-shadow: var(--card-shadow);
  overflow: hidden;
  border: 1px solid var(--border);
  position: sticky;
  top: 100px;
}

.avatar-section {
  padding: 32px 24px;
  text-align: center;
  background: linear-gradient(135deg, var(--accent) 0%, #7c3aed 100%);
  position: relative;
  overflow: hidden;
}

.avatar-section::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  pointer-events: none;
}

.avatar-wrapper {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  margin: 0 auto 16px;
  position: relative;
  cursor: pointer;
  border: 4px solid rgba(255, 255, 255, 0.3);
  transition: transform 0.3s;
  overflow: hidden;
}

.avatar-wrapper:hover .avatar-overlay {
  opacity: 1;
}

.avatar-wrapper:hover {
  transform: scale(1.05);
}

.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 12px;
  font-weight: 600;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.3s;
}

.avatar-uploading {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  z-index: 2;
}

.user-name {
  font-size: 18px;
  font-weight: 700;
  color: white;
  margin-bottom: 6px;
}

.user-role {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 14px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  font-size: 13px;
  font-weight: 600;
}

/* Profile Nav */
.profile-nav {
  padding: 12px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 14px 16px;
  border: none;
  border-radius: 12px;
  background: transparent;
  color: var(--text);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.nav-item i {
  width: 20px;
  text-align: center;
  color: var(--muted);
  transition: color 0.2s;
}

.nav-item:hover {
  background: var(--bg-secondary);
}

.nav-item.active {
  background: var(--accent-bg);
  color: var(--accent);
}

.nav-item.active i {
  color: var(--accent);
}

/* Content */
.profile-content {
  background: var(--card-bg);
  border-radius: 20px;
  box-shadow: var(--card-shadow);
  border: 1px solid var(--border);
  overflow: hidden;
}

.tab-panel {
  padding: 32px;
}

.panel-header {
  margin-bottom: 28px;
  padding-bottom: 20px;
  border-bottom: 2px solid var(--border);
}

.panel-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 22px;
  font-weight: 700;
  color: var(--text-h);
  margin-bottom: 6px;
}

.panel-title i {
  color: var(--accent);
  font-size: 20px;
}

.panel-desc {
  color: var(--muted);
  font-size: 14px;
  margin-left: 30px;
}

/* Form */
.profile-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
}

.form-label i {
  color: var(--muted);
  width: 16px;
  text-align: center;
}

.form-input {
  padding: 12px 16px;
  border: 2px solid var(--input-border);
  border-radius: 12px;
  font-size: 15px;
  font-weight: 500;
  background: var(--input-bg);
  color: var(--text);
  transition: all 0.25s;
  font-family: inherit;
  width: 100%;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-bg);
}

.form-input:disabled {
  background: var(--bg-secondary);
  color: var(--muted);
  cursor: not-allowed;
}

.form-hint {
  font-size: 12px;
  color: var(--muted);
  font-style: italic;
}

.input-wrapper {
  position: relative;
}

.toggle-password {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--muted);
  cursor: pointer;
  padding: 4px;
  font-size: 14px;
  transition: color 0.2s;
}

.toggle-password:hover {
  color: var(--accent);
}

/* Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 12px;
  border-top: 2px solid var(--border);
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s;
  border: none;
  font-family: inherit;
}

.btn-primary {
  background: linear-gradient(135deg, var(--accent), #7c3aed);
  color: white;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.45);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text);
  border: 2px solid var(--border);
}

.btn-secondary:hover:not(:disabled) {
  border-color: var(--accent);
  color: var(--accent);
}

/* Password Form */
.password-form {
  max-width: 480px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Alerts */
.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  animation: fadeIn 0.3s ease;
}

.alert-danger {
  background: var(--danger-bg);
  color: var(--danger);
  border: 1px solid rgba(220, 38, 38, 0.2);
}

.alert-success {
  background: var(--success-bg);
  color: var(--success);
  border: 1px solid rgba(5, 150, 105, 0.2);
}

/* Stats */
.stats-loading {
  text-align: center;
  padding: 48px;
  color: var(--muted);
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.stats-loading i {
  font-size: 20px;
  color: var(--accent);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 28px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  border: 1px solid var(--border);
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--card-shadow-hover);
  border-color: var(--accent-border);
}

.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--text-h);
  line-height: 1;
}

.stat-label {
  font-size: 13px;
  color: var(--muted);
  font-weight: 500;
}

.member-since {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: var(--bg-secondary);
  border-radius: 12px;
  border: 1px solid var(--border);
  color: var(--muted);
  font-size: 14px;
  font-weight: 500;
}

.member-since i {
  color: var(--accent);
}

/* Animations */
@keyframes slideInRight {
  from { transform: translateX(100px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOutRight {
  from { transform: translateX(0); opacity: 1; }
  to { transform: translateX(100px); opacity: 0; }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 900px) {
  .profile-layout {
    grid-template-columns: 1fr;
  }

  .profile-sidebar {
    position: static;
  }

  .profile-nav {
    display: flex;
    overflow-x: auto;
    padding: 12px 16px;
    gap: 8px;
  }

  .nav-item {
    white-space: nowrap;
    flex-shrink: 0;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .profile-page {
    padding: 20px 0 40px;
  }

  .container {
    padding: 0 16px;
  }

  .tab-panel {
    padding: 20px;
  }

  .page-title {
    font-size: 24px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }

  .stat-value {
    font-size: 22px;
  }
}
</style>
