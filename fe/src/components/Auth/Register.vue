<template>
  <div class="auth-page">
    <div class="auth-container">
      <!-- Left: Illustration -->
      <div class="auth-illustration-section">
        <div class="auth-illustration">
          <div class="illustration-content">
            <div class="illustration-icon">
              <i class="fa-solid fa-user-plus"></i>
            </div>
            <h2>Bắt đầu hành trình học tiếng Anh</h2>
            <p>Đăng ký ngay hôm nay để truy cập hàng ngàn bài học và luyện tập miễn phí!</p>
            <div class="feature-list">
              <div class="feature-item">
                <i class="fa-solid fa-check-circle"></i>
                <span>Đăng ký hoàn toàn miễn phí</span>
              </div>
              <div class="feature-item">
                <i class="fa-solid fa-check-circle"></i>
                <span>Học mọi lúc, mọi nơi</span>
              </div>
              <div class="feature-item">
                <i class="fa-solid fa-check-circle"></i>
                <span>Theo dõi tiến độ học tập</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Form -->
      <div class="auth-form-section">
        <div class="auth-card">
          <!-- Logo -->
          <router-link to="/" class="auth-logo">
            <div class="logo-icon">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="logo-text">
              <span class="logo-top">DTU</span>
              <span class="logo-bottom">LingoAI</span>
            </div>
          </router-link>

          <!-- Title -->
          <h1 class="auth-title">Tạo tài khoản mới</h1>
          <p class="auth-subtitle">Điền thông tin bên dưới để đăng ký</p>

          <!-- Error Message -->
          <div v-if="error" class="error-message">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ error }}
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="success-message">
            <i class="fa-solid fa-check-circle"></i>
            {{ successMessage }}
          </div>

          <!-- Register Form -->
          <form @submit.prevent="handleRegister" class="auth-form">
            <!-- Name -->
            <div class="form-group">
              <label for="name">Họ và tên</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-user input-icon"></i>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  placeholder="Nhập họ và tên của bạn"
                  required
                  :disabled="loading"
                />
              </div>
            </div>

            <!-- Email -->
            <div class="form-group">
              <label for="email">Email</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="Nhập email của bạn"
                  required
                  :disabled="loading"
                />
              </div>
            </div>

            <!-- Password -->
            <div class="form-group">
              <label for="password">Mật khẩu</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Nhập mật khẩu (ít nhất 6 ký tự)"
                  required
                  minlength="6"
                  :disabled="loading"
                />
                <button
                  type="button"
                  class="toggle-password"
                  @click="showPassword = !showPassword"
                >
                  <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                </button>
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
              <label for="password_confirmation">Xác nhận mật khẩu</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Nhập lại mật khẩu"
                  required
                  :disabled="loading"
                />
              </div>
            </div>

            <!-- Password mismatch error -->
            <div v-if="passwordMismatch" class="field-error">
              Mật khẩu xác nhận không khớp
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit" :disabled="loading || passwordMismatch">
              <span v-if="loading" class="loader"></span>
              <span v-else>Đăng ký</span>
            </button>
          </form>

          <!-- Divider -->
          <div class="auth-divider">
            <span>hoặc</span>
          </div>

          <!-- Login Link -->
          <p class="auth-footer-text">
            Đã có tài khoản?
            <router-link to="/auth/login" class="auth-link">Đăng nhập ngay</router-link>
          </p>

          <!-- Back to Home -->
          <router-link to="/" class="back-home">
            <i class="fa-solid fa-arrow-left"></i>
            Quay lại trang chủ
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const loading = ref(false)
const error = ref('')
const successMessage = ref('')
const showPassword = ref(false)

const passwordMismatch = computed(() => {
  return form.value.password_confirmation !== '' && 
         form.value.password !== form.value.password_confirmation
})

const handleRegister = async () => {
  if (passwordMismatch.value) return

  error.value = ''
  successMessage.value = ''
  loading.value = true

  try {
    const response = await authStore.register(form.value)
    successMessage.value = 'Đăng ký thành công! Đang chuyển hướng...'

    // Redirect dựa trên role (mặc định là student)
    setTimeout(() => {
      router.push('/student')
    }, 1500)
  } catch (err) {
    error.value = err.response?.data?.message || 
                  err.response?.data?.errors?.email?.[0] ||
                  'Đăng ký thất bại. Vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}

onUnmounted(() => {
  authStore.clearError()
})
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.auth-container {
  display: flex;
  background: white;
  border-radius: 24px;
  box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  max-width: 1000px;
  width: 100%;
}

/* Illustration Section */
.auth-illustration-section {
  flex: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px;
}

.auth-illustration {
  text-align: center;
  color: white;
}

.illustration-icon {
  width: 100px;
  height: 100px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  font-size: 48px;
}

.illustration-content h2 {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 16px;
}

.illustration-content p {
  font-size: 16px;
  opacity: 0.9;
  margin-bottom: 32px;
  line-height: 1.6;
}

.feature-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  text-align: left;
  max-width: 280px;
  margin: 0 auto;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 15px;
}

.feature-item i {
  color: #86efac;
}

/* Form Section */
.auth-form-section {
  flex: 1;
  padding: 48px 40px;
}

.auth-card {
  max-width: 400px;
  margin: 0 auto;
}

.auth-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  margin-bottom: 32px;
}

.logo-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-top {
  font-size: 24px;
  font-weight: 900;
  color: #1f2937;
  letter-spacing: 2px;
}

.logo-bottom {
  font-size: 12px;
  font-weight: 700;
  color: #667eea;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.auth-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 8px;
}

.auth-subtitle {
  color: #6b7280;
  margin-bottom: 24px;
}

/* Error Message */
.error-message {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 14px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Success Message */
.success-message {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #16a34a;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 14px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Form */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 16px;
  color: #9ca3af;
  font-size: 16px;
}

.input-wrapper input {
  width: 100%;
  padding: 14px 48px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 15px;
  transition: all 0.3s;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-wrapper input:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}

.toggle-password {
  position: absolute;
  right: 16px;
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  font-size: 16px;
  padding: 4px;
}

.toggle-password:hover {
  color: #6b7280;
}

/* Field Error */
.field-error {
  color: #dc2626;
  font-size: 13px;
  margin-top: -8px;
}

/* Submit Button */
.btn-submit {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loader {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Divider */
.auth-divider {
  display: flex;
  align-items: center;
  margin: 24px 0;
  color: #9ca3af;
  font-size: 14px;
}

.auth-divider::before,
.auth-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e5e7eb;
}

.auth-divider span {
  padding: 0 16px;
}

/* Footer */
.auth-footer-text {
  text-align: center;
  color: #6b7280;
  font-size: 14px;
}

.auth-link {
  color: #667eea;
  font-weight: 600;
  text-decoration: none;
}

.auth-link:hover {
  text-decoration: underline;
}

.back-home {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 20px;
  color: #6b7280;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.3s;
}

.back-home:hover {
  color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
  .auth-container {
    flex-direction: column;
  }

  .auth-illustration-section {
    display: none;
  }

  .auth-form-section {
    padding: 32px 24px;
  }
}
</style>
