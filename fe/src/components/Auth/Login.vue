<template>
  <div class="auth-page">
    <div class="auth-container">
      <!-- Left: Form -->
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
          <h1 class="auth-title">Chào mừng trở lại!</h1>
          <p class="auth-subtitle">Đăng nhập để tiếp tục học tập</p>

          <!-- Error Message -->
          <div v-if="error" class="error-message">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ error }}
          </div>

          <!-- Login Form -->
          <form @submit.prevent="handleLogin" class="auth-form">
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
                  placeholder="Nhập mật khẩu"
                  required
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

            <!-- Submit -->
            <button type="submit" class="btn-submit" :disabled="loading">
              <span v-if="loading" class="loader"></span>
              <span v-else>Đăng nhập</span>
            </button>
          </form>

          <!-- Divider -->
          <div class="auth-divider">
            <span>hoặc</span>
          </div>

          <!-- Register Link -->
          <p class="auth-footer-text">
            Chưa có tài khoản?
            <router-link to="/auth/register" class="auth-link">Đăng ký ngay</router-link>
          </p>

          <!-- Back to Home -->
          <router-link to="/" class="back-home">
            <i class="fa-solid fa-arrow-left"></i>
            Quay lại trang chủ
          </router-link>
        </div>
      </div>

      <!-- Right: Illustration -->
      <div class="auth-illustration-section">
        <div class="auth-illustration">
          <div class="illustration-content">
            <div class="illustration-icon">
              <i class="fa-solid fa-comments"></i>
            </div>
            <h2>Học tiếng Anh thông minh</h2>
            <p>Cùng DTU LingoAI, việc học tiếng Anh trở nên dễ dàng và thú vị hơn bao giờ hết!</p>
            <div class="feature-list">
              <div class="feature-item">
                <i class="fa-solid fa-microphone"></i>
                <span>Luyện nói với AI</span>
              </div>
              <div class="feature-item">
                <i class="fa-solid fa-headphones"></i>
                <span>Luyện nghe hiệu quả</span>
              </div>
              <div class="feature-item">
                <i class="fa-solid fa-book-open"></i>
                <span>Học từ vựng mỗi ngày</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    const response = await authStore.login(form.value)
    
    // Redirect dựa trên role
    if (response.user.role === 'admin') {
      router.push('/admin/dashboard')
    } else {
      router.push('/student')
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Đăng nhập thất bại. Vui lòng thử lại.'
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
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
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
