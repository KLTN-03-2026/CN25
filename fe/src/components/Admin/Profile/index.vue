<template>
  <div class="profile-page">
    <div class="container">
      <div class="page-header">
        <h1 class="page-title">Hồ sơ cá nhân</h1>
        <p class="page-subtitle">Quản lý thông tin tài khoản quản trị của bạn</p>
      </div>

      <div class="profile-layout">
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
              <i class="fa-solid fa-user-shield"></i>
              Quản trị viên
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

        <main class="profile-content">
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

          <div v-if="activeTab === 'activity'" class="tab-panel">
            <div class="panel-header">
              <h2 class="panel-title">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Hoạt động gần đây
              </h2>
              <p class="panel-desc">Xem lịch sử hoạt động của tài khoản</p>
            </div>

            <div class="activity-list">
              <div class="activity-item">
                <div class="activity-icon">
                  <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <div class="activity-info">
                  <span class="activity-text">Đăng nhập hệ thống</span>
                  <span class="activity-time">{{ user?.created_at ? formatDate(user.created_at) : 'N/A' }}</span>
                </div>
              </div>
              <div class="activity-item">
                <div class="activity-icon success">
                  <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="activity-info">
                  <span class="activity-text">Tài khoản quản trị viên</span>
                  <span class="activity-time">Vai trò: Quản trị viên</span>
                </div>
              </div>
              <div class="activity-item">
                <div class="activity-icon info">
                  <i class="fa-solid fa-server"></i>
                </div>
                <div class="activity-info">
                  <span class="activity-text">Quản lý hệ thống</span>
                  <span class="activity-time">Toàn quyền truy cập</span>
                </div>
              </div>
            </div>

            <div v-if="user?.created_at" class="member-since">
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
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../../stores/auth'
import { AuthService } from '../../../services/api'

const authStore = useAuthStore()

const user = computed(() => authStore.user)
const activeTab = ref('info')
const saving = ref(false)
const changingPassword = ref(false)

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

const tabs = [
  { id: 'info', label: 'Thông tin cá nhân', icon: 'fa-solid fa-user-pen' },
  { id: 'password', label: 'Đổi mật khẩu', icon: 'fa-solid fa-lock' },
  { id: 'activity', label: 'Hoạt động', icon: 'fa-solid fa-clock-rotate-left' }
]

const avatarUrl = computed(() => {
  if (localAvatar.value && localAvatar.value !== '') return localAvatar.value
  if (user.value?.avatar) {
    return user.value.avatar.startsWith('http')
      ? user.value.avatar
      : `http://localhost:8000${user.value.avatar}`
  }
  const name = user.value?.name || 'A'
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

  const reader = new FileReader()
  reader.onload = (event) => {
    localAvatar.value = event.target.result
    avatarKey.value++
  }
  reader.readAsDataURL(file)

  uploadingAvatar.value = true
  try {
    const result = await AuthService.uploadAvatar(file)

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
    background: ${type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#ef4444'};
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
  background: #f9fafb;
  min-height: calc(100vh - 64px);
  padding: 32px;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 28px;
}

.page-title {
  font-size: 26px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 6px;
}

.page-subtitle {
  color: #6b7280;
  font-size: 14px;
}

.profile-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 24px;
  align-items: start;
}

.profile-sidebar {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: hidden;
  position: sticky;
  top: 96px;
}

.avatar-section {
  padding: 28px 24px;
  text-align: center;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
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
  width: 100px;
  height: 100px;
  border-radius: 50%;
  margin: 0 auto 14px;
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
  font-size: 11px;
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
  font-size: 22px;
  color: white;
  z-index: 2;
}

.user-name {
  font-size: 17px;
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
  font-size: 12px;
  font-weight: 600;
}

.profile-nav {
  padding: 10px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 12px 14px;
  border: none;
  border-radius: 10px;
  background: transparent;
  color: #6b7280;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.nav-item i {
  width: 18px;
  text-align: center;
  color: #9ca3af;
  transition: color 0.2s;
}

.nav-item:hover {
  background: #f3f4f6;
}

.nav-item.active {
  background: #eef2ff;
  color: #4f46e5;
}

.nav-item.active i {
  color: #4f46e5;
}

.profile-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.tab-panel {
  padding: 28px;
}

.panel-header {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f3f4f6;
}

.panel-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.panel-title i {
  color: #4f46e5;
  font-size: 18px;
}

.panel-desc {
  color: #6b7280;
  font-size: 14px;
  margin-left: 28px;
}

.profile-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

.form-label i {
  color: #9ca3af;
  width: 16px;
  text-align: center;
}

.form-input {
  padding: 10px 14px;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  background: white;
  color: #111827;
  transition: all 0.2s;
  font-family: inherit;
  width: 100%;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-input:disabled {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
}

.form-hint {
  font-size: 11px;
  color: #9ca3af;
  font-style: italic;
}

.input-wrapper {
  position: relative;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 4px;
  font-size: 13px;
  transition: color 0.2s;
}

.toggle-password:hover {
  color: #4f46e5;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 12px;
  border-top: 2px solid #f3f4f6;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s;
  border: none;
  font-family: inherit;
}

.btn-primary {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: white;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1.5px solid #e5e7eb;
}

.btn-secondary:hover:not(:disabled) {
  border-color: #4f46e5;
  color: #4f46e5;
}

.password-form {
  max-width: 480px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  animation: fadeIn 0.3s ease;
}

.alert-danger {
  background: #fef2f2;
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-success {
  background: #ecfdf5;
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 24px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.activity-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef2ff;
  color: #4f46e5;
  font-size: 16px;
  flex-shrink: 0;
}

.activity-icon.success {
  background: #ecfdf5;
  color: #10b981;
}

.activity-icon.info {
  background: #fef3c7;
  color: #d97706;
}

.activity-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.activity-text {
  font-size: 14px;
  font-weight: 600;
  color: #111827;
}

.activity-time {
  font-size: 12px;
  color: #9ca3af;
}

.member-since {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #f9fafb;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  font-size: 13px;
  font-weight: 500;
}

.member-since i {
  color: #4f46e5;
}

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
    padding: 10px 14px;
    gap: 6px;
  }

  .nav-item {
    white-space: nowrap;
    flex-shrink: 0;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .profile-page {
    padding: 20px;
  }
}

@media (max-width: 600px) {
  .tab-panel {
    padding: 20px;
  }

  .page-title {
    font-size: 22px;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
