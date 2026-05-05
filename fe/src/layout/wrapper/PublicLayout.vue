<template>
  <div class="public-layout">
    <!-- Header -->
    <header class="nav-wrap">
      <div class="container nav">
        <!-- LEFT: Logo -->
        <div class="nav-left">
          <router-link to="/" class="brand">
            <div class="brand-icon">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="brand-text">
              <span class="brand-top">DTU</span>
              <span class="brand-bottom">LingoAI</span>
            </div>
          </router-link>
        </div>

        <!-- CENTER: Menu -->
        <div class="nav-center">
          <nav class="nav-menu">
            <router-link to="/" class="menu-item">Trang chủ</router-link>
            <router-link to="/student/khoa-hoc" class="menu-item">Khóa học</router-link>
            <router-link to="/features" class="menu-item">Tính năng</router-link>
            <router-link to="/contact" class="menu-item">Liên hệ</router-link>
          </nav>
        </div>

        <!-- RIGHT: Actions -->
        <div class="nav-right">
          <template v-if="!isLoggedIn">
            <router-link to="/auth/login" class="btn btn-outline">Đăng nhập</router-link>
            <router-link to="/auth/register" class="btn btn-cta">
              <i class="fa-solid fa-bolt"></i>
              Đăng ký
            </router-link>
          </template>
          <template v-else>
            <div class="user-menu">
              <div class="user-avatar" @click="showUserMenu = !showUserMenu">
                <span>{{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}</span>
              </div>
              <div v-if="showUserMenu" class="user-dropdown">
                <div class="user-info">
                  <p class="user-name">{{ user?.name }}</p>
                  <p class="user-email">{{ user?.email }}</p>
                </div>
                <div class="dropdown-divider"></div>
                <router-link to="/student" class="dropdown-item">
                  <i class="fa-solid fa-book-open"></i>
                  Khóa học của tôi
                </router-link>
                <button class="dropdown-item logout" @click="handleLogout">
                  <i class="fa-solid fa-right-from-bracket"></i>
                  Đăng xuất
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-brand">
            <div class="brand-icon">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="brand-text">
              <span class="brand-top">DTU</span>
              <span class="brand-bottom">LingoAI</span>
            </div>
            <p class="footer-desc">Nền tảng học tiếng Anh thông minh cùng AI, giúp bạn giao tiếp tự tin trong 6 tháng.</p>
          </div>

          <div class="footer-links">
            <div class="footer-col">
              <h4>Khóa học</h4>
              <a href="#">Tiếng Anh cơ bản</a>
              <a href="#">Giao tiếp hàng ngày</a>
              <a href="#">Business English</a>
              <a href="#">IELTS Preparation</a>
            </div>

            <div class="footer-col">
              <h4>Hỗ trợ</h4>
              <router-link to="/features">Tính năng</router-link>
              <router-link to="/contact">Liên hệ</router-link>
              <a href="#">Điều khoản sử dụng</a>
              <a href="#">Chính sách bảo mật</a>
            </div>

            <div class="footer-col">
              <h4>Liên hệ</h4>
              <a href="mailto:support@dtu-lingoai.edu.vn"><i class="fa-solid fa-envelope"></i> support@dtu-lingoai.edu.vn</a>
              <a href="tel:19001234"><i class="fa-solid fa-phone"></i> 1900 1234</a>
              <a href="#"><i class="fa-solid fa-location-dot"></i> Đại học Duy Tân, Đà Nẵng</a>
            </div>
          </div>
        </div>

        <div class="footer-bottom">
          <p>&copy; 2026 DTU LingoAI. Tất cả quyền được bảo lưu.</p>
          <div class="social-links">
            <a href="#" class="social-link"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" class="social-link"><i class="fa-brands fa-youtube"></i></a>
            <a href="#" class="social-link"><i class="fa-brands fa-instagram"></i></a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const showUserMenu = ref(false)

const isLoggedIn = computed(() => !!authStore.token)
const user = computed(() => authStore.user)

onMounted(() => {
  authStore.initAuth()
})

const handleLogout = () => {
  authStore.logout()
  router.push('/auth/login')
}
</script>

<style scoped>
/* ===== LAYOUT ===== */
.public-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content {
  flex: 1;
}

/* ===== HEADER ===== */
.nav-wrap {
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  background: rgba(255, 255, 255, 0.95);
  border-bottom: 2px solid var(--nav-border);
  box-shadow: var(--shadow-sm);
}

.container {
  max-width: 1280px;
  margin: auto;
  padding: 0 24px;
}

.nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
  gap: 16px;
}

.nav-left {
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.nav-center {
  flex-shrink: 0;
  display: flex;
  justify-content: center;
}

.nav-right {
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
}

/* ===== NAV MENU ===== */
.nav-menu {
  display: flex;
  gap: 8px;
}

.menu-item {
  padding: 10px 18px;
  color: var(--text);
  text-decoration: none;
  font-size: 15px;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.3s;
}

.menu-item:hover,
.menu-item.router-link-active {
  background: var(--accent-bg);
  color: var(--accent);
}

/* ===== BRAND ===== */
.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  transition: all 0.3s ease;
}

.brand:hover {
  transform: translateY(-2px);
  opacity: 0.9;
}

.brand-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--accent), #7c3aed);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.05;
}

.brand-top {
  font-size: 28px;
  font-weight: 900;
  letter-spacing: 1.5px;
  background: linear-gradient(90deg, var(--accent), #7c3aed, #db2777);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-bottom {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: var(--text);
  text-transform: uppercase;
}

/* ===== BUTTON ===== */
.btn {
  padding: 10px 20px;
  border-radius: 999px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-outline {
  border: 2px solid var(--accent);
  background: transparent;
  color: var(--accent);
}

.btn-outline:hover {
  background: var(--accent-bg);
  box-shadow: 0 0 0 4px var(--accent-bg);
  transform: translateY(-2px);
}

.btn-cta {
  background: linear-gradient(135deg, var(--btn-cta), var(--btn-cta-hover));
  color: white;
  border: none;
  font-weight: 800;
  padding: 10px 24px;
  box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
  animation: ctaPulse 2s ease-in-out infinite;
}

.btn-cta:hover {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 8px 28px rgba(220, 38, 38, 0.5);
  animation: none;
}

.btn-cta i {
  font-size: 13px;
}

@keyframes ctaPulse {
  0%, 100% {
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
  }
  50% {
    box-shadow: 0 4px 24px rgba(220, 38, 38, 0.55);
  }
}

/* ===== FOOTER ===== */
.footer {
  background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
  border-top: 1px solid #e5e7eb;
  padding: 60px 0 30px;
  margin-top: auto;
}

.footer-content {
  display: grid;
  grid-template-columns: 1.5fr 2fr;
  gap: 60px;
  margin-bottom: 40px;
}

.footer-brand {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-brand .brand {
  flex-direction: row;
}

.footer-desc {
  color: #6b7280;
  font-size: 14px;
  line-height: 1.6;
  max-width: 300px;
  margin: 8px 0;
}

.footer-links {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 40px;
}

.footer-col {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-col h4 {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.footer-col a {
  color: #6b7280;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.footer-col a:hover {
  color: var(--accent);
}

.footer-col a i {
  width: 16px;
}

.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 30px;
  border-top: 1px solid #e5e7eb;
}

.footer-bottom p {
  color: #9ca3af;
  font-size: 14px;
  margin: 0;
}

.social-links {
  display: flex;
  gap: 12px;
}

.social-link {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  transition: all 0.3s;
  border: 1px solid #e5e7eb;
}

.social-link:hover {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
  transform: translateY(-3px);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .nav-center {
    display: none;
  }

  .nav {
    height: 65px;
  }

  .brand-top {
    font-size: 20px;
  }

  .brand-bottom {
    font-size: 11px;
  }

  .footer-content {
    grid-template-columns: 1fr;
    gap: 40px;
  }
}

@media (max-width: 768px) {
  .nav {
    height: 60px;
  }

  .btn {
    padding: 8px 14px;
    font-size: 14px;
  }

  .footer-links {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }
}

@media (max-width: 500px) {
  .brand-bottom {
    display: none;
  }

  .brand-top {
    font-size: 18px;
  }

  .nav-right {
    gap: 8px;
  }
}

/* ===== USER MENU ===== */
.user-menu {
  position: relative;
}

.user-avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.3s;
}

.user-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  background: white;
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
  min-width: 240px;
  overflow: hidden;
  z-index: 1000;
  animation: dropdownIn 0.2s ease;
}

@keyframes dropdownIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.user-info {
  padding: 16px 20px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.user-name {
  font-weight: 700;
  font-size: 16px;
  margin-bottom: 4px;
}

.user-email {
  font-size: 13px;
  opacity: 0.85;
}

.dropdown-divider {
  height: 1px;
  background: #e5e7eb;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  color: var(--text);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  border: none;
  background: none;
  width: 100%;
  cursor: pointer;
  text-align: left;
}

.dropdown-item:hover {
  background: var(--bg);
  color: var(--accent);
}

.dropdown-item i {
  width: 18px;
  text-align: center;
  color: var(--text-secondary);
}

.dropdown-item.logout {
  color: #ef4444;
}

.dropdown-item.logout i {
  color: #ef4444;
}

.dropdown-item.logout:hover {
  background: #fef2f2;
}
</style>
