<template>
    <header class="nav-wrap">
      <div class="container nav">
  
        <!-- LEFT: Logo -->
        <div class="nav-left">
          <router-link to="/student" class="brand">
            <div class="brand-icon">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="brand-text">
              <span class="brand-top">DTU</span>
              <span class="brand-bottom">LingoAI</span>
            </div>
          </router-link>
        </div>

        <!-- CENTER: Search -->
        <div class="nav-search" ref="searchWrapper">
          <div class="search-box">
            <i class="fa-solid fa-search search-icon"></i>
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Tìm khóa học, bài học..."
              @input="onSearch"
              @keyup.enter="goToResults"
              @focus="showDropdown = true"
            />
            <button v-if="searchQuery" class="search-clear" @click="clearSearch">
              <i class="fa-solid fa-times"></i>
            </button>
          </div>

          <!-- Search Dropdown -->
          <Transition name="dropdown-fade">
            <div v-if="showDropdown && searchQuery.length > 0" class="search-dropdown">
              <div v-if="isSearching" class="search-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Đang tìm kiếm...</span>
              </div>
              <template v-else-if="hasResults">
                <!-- ========== MY COURSES (progress) ========== -->
                <div v-if="results.my_courses?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-rocket"></i> Khóa của tôi
                  </div>
                  <a
                    v-for="item in results.my_courses"
                    :key="'mycourse-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div v-if="item.image_url" class="search-item-thumb">
                      <img :src="item.image_url" :alt="item.title" />
                    </div>
                    <div v-else class="search-item-icon course-icon">
                      <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.title }}</span>
                      <span class="search-item-meta">{{ item.level }}</span>
                      <div class="progress-mini">
                        <div class="progress-mini-bar" :style="{ width: item.progress_percent + '%' }"></div>
                      </div>
                      <span class="progress-label">{{ item.progress_percent }}% hoàn thành</span>
                    </div>
                  </a>
                </div>

                <!-- ========== COURSES (giá cả) ========== -->
                <div v-if="results.courses?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-graduation-cap"></i> Khóa học
                  </div>
                  <a
                    v-for="item in results.courses"
                    :key="'course-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div v-if="item.image_url" class="search-item-thumb">
                      <img :src="item.image_url" :alt="item.title" />
                    </div>
                    <div v-else class="search-item-icon course-icon">
                      <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.title }}</span>
                      <span class="search-item-meta">{{ item.level }}</span>
                      <div class="search-item-stats">
                        <span class="stat">
                          <i class="fa-solid fa-users"></i> {{ item.total_students ?? 0 }}
                        </span>
                        <span v-if="item.rating" class="stat star">
                          <i class="fa-solid fa-star"></i> {{ Number(item.rating).toFixed(1) }}
                        </span>
                      </div>
                    </div>
                    <div class="search-price">
                      <span v-if="item.is_free" class="price-free">Miễn phí</span>
                      <template v-else>
                        <span class="price-current">{{ item.price_formatted }}</span>
                        <span v-if="item.discount_percent > 0" class="price-badge">-{{ item.discount_percent }}%</span>
                      </template>
                    </div>
                  </a>
                </div>

                <!-- ========== LESSONS ========== -->
                <div v-if="results.lessons?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-book-open"></i> Bài học
                  </div>
                  <a
                    v-for="item in results.lessons"
                    :key="'lesson-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div class="search-item-icon lesson-icon">
                      <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.title }}</span>
                      <span class="search-item-meta">{{ item.course_title }} &rsaquo; {{ item.chapter_title }}</span>
                      <span class="lesson-type-badge">{{ item.type }}</span>
                    </div>
                  </a>
                </div>

                <!-- ========== DOCUMENTS ========== -->
                <div v-if="results.documents?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-folder-open"></i> Tài liệu
                  </div>
                  <a
                    v-for="item in results.documents"
                    :key="'doc-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div class="search-item-icon doc-icon">
                      <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.title }}</span>
                      <span class="search-item-meta">{{ item.category }} &bull; {{ item.file_type }} &bull; <i class="fa-solid fa-download"></i> {{ item.download_count ?? 0 }}</span>
                    </div>
                  </a>
                </div>

                <!-- ========== ARTICLES ========== -->
                <div v-if="results.articles?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-newspaper"></i> Bài viết
                  </div>
                  <a
                    v-for="item in results.articles"
                    :key="'article-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div v-if="item.image_url" class="search-item-thumb">
                      <img :src="item.image_url" :alt="item.title" />
                    </div>
                    <div v-else class="search-item-icon article-icon">
                      <i class="fa-solid fa-newspaper"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.title }}</span>
                      <span class="search-item-meta">{{ item.category }} &bull; <i class="fa-solid fa-eye"></i> {{ item.view_count ?? 0 }}</span>
                    </div>
                  </a>
                </div>

                <!-- ========== VOCABULARIES ========== -->
                <div v-if="results.vocabularies?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-spell-check"></i> Từ vựng
                  </div>
                  <a
                    v-for="item in results.vocabularies"
                    :key="'vocab-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div class="search-item-icon vocab-icon">
                      <span class="vocab-letter">A</span>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.word }}</span>
                      <span v-if="item.phonetic" class="search-item-phonetic">{{ item.phonetic }}</span>
                      <span class="search-item-meta">{{ item.meaning }}</span>
                    </div>
                  </a>
                </div>

                <!-- ========== GRAMMARS ========== -->
                <div v-if="results.grammars?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-book"></i> Ngữ pháp
                  </div>
                  <a
                    v-for="item in results.grammars"
                    :key="'grammar-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div class="search-item-icon grammar-icon">
                      <i class="fa-solid fa-pen-nib"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.title }}</span>
                      <span class="search-item-phonetic grammar-structure">{{ item.structure }}</span>
                      <span class="search-item-meta">{{ item.course_title }}</span>
                    </div>
                  </a>
                </div>

                <!-- ========== PAYMENTS ========== -->
                <div v-if="results.payments?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-credit-card"></i> Thanh toán
                  </div>
                  <a
                    v-for="item in results.payments"
                    :key="'payment-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div class="search-item-icon payment-icon">
                      <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.course_title }}</span>
                      <span class="search-item-meta">
                        <span class="payment-status" :class="'status-' + item.status">{{ item.status }}</span>
                        &bull; {{ item.payment_method }}
                      </span>
                    </div>
                    <div class="search-price">
                      <span class="price-current">{{ item.amount_formatted }}</span>
                      <span class="payment-date">{{ item.date_formatted }}</span>
                    </div>
                  </a>
                </div>

                <!-- ========== PROGRESS ========== -->
                <div v-if="results.progress?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-chart-line"></i> Tiến độ học tập
                  </div>
                  <a
                    v-for="item in results.progress"
                    :key="'prog-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div class="search-item-icon progress-icon">
                      <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div class="search-item-content">
                      <span class="search-item-title">{{ item.lesson_title }}</span>
                      <span class="search-item-meta">{{ item.course_title }} &rsaquo; {{ item.chapter_title }}</span>
                      <div class="progress-mini">
                        <div class="progress-mini-bar" :style="{ width: item.progress_percent + '%' }"></div>
                      </div>
                      <span class="progress-label">
                        {{ item.progress_percent }}% &bull; <i class="fa-solid fa-clock"></i> {{ item.time_formatted }}
                        <span v-if="item.last_accessed_formatted">&bull; {{ item.last_accessed_formatted }}</span>
                      </span>
                    </div>
                  </a>
                </div>

                <!-- ========== REVIEWS ========== -->
                <div v-if="results.reviews?.length" class="search-group">
                  <div class="search-group-label">
                    <i class="fa-solid fa-star"></i> Đánh giá
                  </div>
                  <a
                    v-for="item in results.reviews"
                    :key="'review-' + item.id"
                    :href="item.url"
                    class="search-item"
                    @click="hideDropdown"
                  >
                    <div class="search-item-icon review-icon">
                      <img v-if="item.user_avatar_url" :src="item.user_avatar_url" :alt="item.user_name" class="reviewer-avatar" />
                      <i v-else class="fa-solid fa-user"></i>
                    </div>
                    <div class="search-item-content">
                      <div class="review-header">
                        <span class="reviewer-name">{{ item.user_name }}</span>
                        <div class="review-stars">
                          <i v-for="n in 5" :key="n" class="fa-solid fa-star" :class="{ filled: n <= item.rating }"></i>
                        </div>
                      </div>
                      <span class="search-item-meta">{{ item.course_title }}</span>
                      <span v-if="item.comment" class="review-comment">{{ truncate(item.comment, 80) }}</span>
                    </div>
                  </a>
                </div>

                <!-- View all results -->
                <a :href="'/student/khoa-hoc?q=' + encodeURIComponent(searchQuery)" class="search-view-all" @click="hideDropdown">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  Xem tất cả kết quả cho "{{ searchQuery }}"
                </a>
              </template>
              <div v-else class="search-no-results">
                <i class="fa-solid fa-search"></i>
                <span>Không tìm thấy kết quả nào</span>
              </div>
            </div>
          </Transition>
        </div>
  
        <!-- CENTER: Menu -->
        <div class="nav-center">
          <AppMenu />
        </div>

        <!-- RIGHT: Actions -->
        <div class="nav-right">
          <!-- Dark mode toggle -->
          <button class="icon-btn" @click="toggleDarkMode" :title="isDark ? 'Chế độ sáng' : 'Chế độ tối'">
            <i :class="isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
          </button>

          <!-- User Menu (khi đã đăng nhập) -->
          <div v-if="isLoggedIn" class="user-menu-wrapper">
            <div class="user-menu" @click="toggleUserMenu">
              <img :src="userAvatar" :alt="userName" class="user-avatar" />
              <span class="user-name">{{ userName }}</span>
              <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div v-if="showUserMenu" class="dropdown-menu">
              <router-link to="/student/ho-so" class="dropdown-item" @click="showUserMenu = false">
                <i class="fa-solid fa-user"></i>
                Hồ sơ cá nhân
              </router-link>
              <router-link to="/student/lich-su-hoc" class="dropdown-item" @click="showUserMenu = false">
                <i class="fa-solid fa-history"></i>
                Lịch sử học tập
              </router-link>
              <div class="dropdown-divider"></div>
              <button class="dropdown-item danger" @click="handleLogout">
                <i class="fa-solid fa-sign-out-alt"></i>
                Đăng xuất
              </button>
            </div>
          </div>

          <!-- Auth buttons (khi chưa đăng nhập) -->
          <template v-else>
            <router-link to="/auth/login" class="btn btn-outline">Đăng nhập</router-link>
            <router-link to="/auth/register" class="btn btn-cta">
              <i class="fa-solid fa-bolt"></i>
              Đăng ký
            </router-link>
          </template>
        </div>
  
      </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import { SearchService } from '../../../services/api'
import AppMenu from './MenuStudent.vue'

const router = useRouter()
const authStore = useAuthStore()
const searchQuery = ref('')
const isDark = ref(false)
const showUserMenu = ref(false)
const showDropdown = ref(false)
const isSearching = ref(false)
const results = ref({})
const searchWrapper = ref(null)

const isLoggedIn = computed(() => authStore.isAuthenticated)
const userName = computed(() => authStore.user?.name || 'User')
const userAvatar = computed(() => {
  if (authStore.user?.avatar) {
    return authStore.user.avatar.startsWith('http')
      ? authStore.user.avatar
      : `http://localhost:8000${authStore.user.avatar}`
  }
  const name = authStore.user?.name || 'U'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=667eea&color=fff`
})

const hasResults = computed(() => {
  const r = results.value
  return (r.courses?.length || r.lessons?.length || r.documents?.length ||
          r.articles?.length || r.vocabularies?.length || r.grammars?.length ||
          r.my_courses?.length || r.payments?.length || r.progress?.length || r.reviews?.length)
})

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

let debounceTimer = null
function onSearch() {
  clearTimeout(debounceTimer)
  if (searchQuery.value.length < 2) {
    results.value = {}
    isSearching.value = false
    return
  }
  isSearching.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const data = await SearchService.search({ q: searchQuery.value, type: 'all' })
      results.value = data
    } catch (e) {
      console.error('Search error:', e)
      results.value = {}
    } finally {
      isSearching.value = false
    }
  }, 250)
}

function clearSearch() {
  searchQuery.value = ''
  results.value = {}
  showDropdown.value = false
}

function goToResults() {
  if (searchQuery.value.trim()) {
    hideDropdown()
    router.push({ path: '/student/khoa-hoc', query: { q: searchQuery.value.trim() } })
  }
}

function hideDropdown() {
  showDropdown.value = false
}

function handleClickOutside(e) {
  if (searchWrapper.value && !searchWrapper.value.contains(e.target)) {
    showDropdown.value = false
  }
  if (!e.target.closest('.user-menu-wrapper')) {
    showUserMenu.value = false
  }
}

const toggleDarkMode = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.setAttribute('data-theme', 'dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.removeAttribute('data-theme')
    localStorage.setItem('theme', 'light')
  }
}

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const handleLogout = async () => {
  if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
    await authStore.logout()
    showUserMenu.value = false
    router.push('/')
  }
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark') {
    isDark.value = true
  }
  authStore.initAuth()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(debounceTimer)
})
</script>
  
<style scoped>
/* ===== HEADER ===== */
.nav-wrap {
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  background: var(--nav-bg);
  border-bottom: 2px solid var(--nav-border);
  box-shadow: var(--shadow-sm);
  transition: background 0.3s ease, border-color 0.3s ease;
}

/* ===== CONTAINER ===== */
.container {
  max-width: 1280px;
  margin: auto;
  padding: 0 24px;
}

/* ===== NAV ===== */
.nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
  gap: 16px;
}

/* ===== LAYOUT ===== */
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

/* ===== NAV SEARCH ===== */
.nav-search {
  flex: 1;
  max-width: 320px;
  display: flex;
  justify-content: center;
}

.search-box {
  position: relative;
  width: 100%;
}

.search-box input {
  width: 100%;
  padding: 10px 40px 10px 40px;
  border: 2px solid var(--input-border);
  border-radius: 999px;
  font-size: 15px;
  font-weight: 500;
  background: var(--input-bg);
  color: var(--text);
  transition: all 0.25s ease;
  font-family: inherit;
}

.search-box input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-bg);
}

.search-box input::placeholder {
  color: var(--muted);
  font-weight: 400;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 14px;
  pointer-events: none;
}

.search-clear {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--muted);
  font-size: 13px;
  padding: 4px;
  display: flex;
  align-items: center;
  transition: color 0.2s, transform 0.2s;
}

.search-clear:hover {
  color: var(--danger);
  transform: translateY(-50%) scale(1.2);
}

/* ===== ICON BUTTON ===== */
.icon-btn {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  border: 2px solid var(--border);
  background: var(--card-bg);
  color: var(--text);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  font-size: 16px;
}

.icon-btn:hover {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
}

.icon-btn:active {
  transform: translateY(0);
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

.brand:active {
  transform: translateY(0);
}

/* ===== BRAND ICON ===== */
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
  transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.brand:hover .brand-icon {
  box-shadow: 0 6px 24px rgba(79, 70, 229, 0.5);
  transform: scale(1.05);
}

/* ===== TEXT BLOCK ===== */
.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.05;
}

/* ===== DTU ===== */
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

.btn-outline:active {
  transform: translateY(0);
}

/* ===== CTA BUTTON - STAND OUT ===== */
.btn-cta {
  background: linear-gradient(135deg, var(--btn-cta), var(--btn-cta-hover));
  color: white;
  border: none;
  font-weight: 800;
  letter-spacing: 0.3px;
  padding: 10px 24px;
  box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
  position: relative;
  overflow: hidden;
  animation: ctaPulse 2s ease-in-out infinite;
}

.btn-cta::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}

.btn-cta:hover {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 8px 28px rgba(220, 38, 38, 0.5);
  animation: none;
  background: linear-gradient(135deg, #ef4444, var(--btn-cta));
}

.btn-cta:hover::before {
  left: 100%;
}

.btn-cta:active {
  transform: translateY(0) scale(0.98);
}

.btn-cta i {
  font-size: 13px;
}

@keyframes ctaPulse {
  0%, 100% {
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
  }
  50% {
    box-shadow: 0 4px 24px rgba(220, 38, 38, 0.55), 0 0 0 0 rgba(220, 38, 38, 0.1);
  }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .nav-search {
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
}

@media (max-width: 768px) {
  .nav-center {
    display: none;
  }

  .nav {
    height: 60px;
  }

  .btn {
    padding: 8px 14px;
    font-size: 14px;
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
.user-menu-wrapper {
  position: relative;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 12px;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.3s;
  background: var(--card-bg);
  border: 2px solid var(--border);
}

.user-menu:hover {
  background: var(--accent-bg);
  border-color: var(--accent);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
}

.user-menu i {
  font-size: 12px;
  color: var(--muted);
}

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
  z-index: 1000;
  animation: fadeInDown 0.2s ease;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

.dropdown-item.danger {
  color: #ef4444;
}

.dropdown-item.danger:hover {
  background: #fef2f2;
}

.dropdown-item i {
  width: 16px;
  text-align: center;
}

.dropdown-divider {
  height: 1px;
  background: #e5e7eb;
  margin: 4px 0;
}

/* ===== SEARCH DROPDOWN ===== */
.nav-search {
  position: relative;
}

.search-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 50%;
  transform: translateX(-50%);
  width: 480px;
  max-width: 90vw;
  max-height: 520px;
  overflow-y: auto;
  background: var(--card-bg);
  border: 1.5px solid var(--border-color);
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08);
  z-index: 9999;
}

.search-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 28px 16px;
  color: var(--muted);
  font-size: 14px;
}

.search-loading i {
  color: var(--accent);
}

.search-no-results {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 28px 16px;
  color: var(--muted);
  font-size: 14px;
}

.search-no-results i {
  font-size: 18px;
  opacity: 0.4;
}

.search-group {
  padding: 6px 0;
}

.search-group:not(:last-child) {
  border-bottom: 1px solid var(--border-color);
}

.search-group-label {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 14px 6px;
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.search-group-label i {
  font-size: 10px;
  color: var(--accent);
}

.search-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 14px;
  text-decoration: none;
  color: var(--text);
  font-size: 13.5px;
  transition: all 0.15s;
  cursor: pointer;
}

.search-item:hover {
  background: var(--accent-bg);
  color: var(--accent);
  padding-left: 18px;
}

.search-item-thumb {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--input-bg);
}

.search-item-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.search-item-icon {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: var(--accent-bg);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.search-item-icon.vocab-icon {
  font-size: 16px;
  font-weight: 800;
  font-style: italic;
  color: var(--accent);
}

.search-item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.search-item-title {
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-item-meta {
  font-size: 11px;
  color: var(--muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-view-all {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 14px;
  border-top: 1px solid var(--border-color);
  text-decoration: none;
  color: var(--accent);
  font-size: 13px;
  font-weight: 600;
  transition: all 0.15s;
  background: var(--accent-bg);
  border-radius: 0 0 16px 16px;
}

.search-view-all:hover {
  background: var(--accent);
  color: white;
}

/* Dropdown animation */
.dropdown-fade-enter-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.dropdown-fade-leave-active {
  transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
}
.dropdown-fade-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(-8px) scale(0.96);
}
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-4px) scale(0.98);
}

@media (max-width: 768px) {
  .search-dropdown {
    position: fixed;
    top: 70px;
    left: 12px;
    right: 12px;
    width: auto;
    transform: none;
    max-height: calc(100vh - 90px);
  }
}

/* ===== COURSE ICON ===== */
.search-item-icon.course-icon {
  background: linear-gradient(135deg, #667eea22, #764ba222);
  color: #667eea;
}

/* ===== LESSON TYPE BADGE ===== */
.lesson-type-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 20px;
  background: var(--accent-bg);
  color: var(--accent);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-top: 2px;
  width: fit-content;
}

/* ===== LESSON ICON ===== */
.search-item-icon.lesson-icon {
  background: #f0fdf422;
  color: #22c55e;
}

/* ===== DOC ICON ===== */
.search-item-icon.doc-icon {
  background: #fef3c722;
  color: #f59e0b;
}

/* ===== ARTICLE ICON ===== */
.search-item-icon.article-icon {
  background: #fce7f322;
  color: #ec4899;
}

/* ===== GRAMMAR ICON ===== */
.search-item-icon.grammar-icon {
  background: #ede9fe22;
  color: #8b5cf6;
}

/* ===== PAYMENT ICON ===== */
.search-item-icon.payment-icon {
  background: #dbeafe22;
  color: #3b82f6;
}

/* ===== PROGRESS ICON ===== */
.search-item-icon.progress-icon {
  background: #dcfce722;
  color: #22c55e;
}

/* ===== REVIEW ICON ===== */
.search-item-icon.review-icon {
  background: #fef9c322;
  display: flex;
  align-items: center;
  justify-content: center;
}

.reviewer-avatar {
  width: 100%;
  height: 100%;
  border-radius: 8px;
  object-fit: cover;
}

/* ===== VOCABULARY LETTER ===== */
.vocab-letter {
  font-size: 16px;
  font-weight: 900;
  font-style: italic;
  color: #8b5cf6;
}

/* ===== PHONETIC ===== */
.search-item-phonetic {
  font-size: 11px;
  color: #8b5cf6;
  font-style: italic;
  font-family: monospace;
}

/* ===== GRAMMAR STRUCTURE ===== */
.grammar-structure {
  font-family: 'Courier New', monospace;
  font-size: 11px;
  color: #8b5cf6;
  background: #ede9fe22;
  padding: 1px 5px;
  border-radius: 4px;
}

/* ===== SEARCH ITEM STATS ===== */
.search-item-stats {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 2px;
}

.stat {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 11px;
  color: var(--muted);
}

.stat i {
  font-size: 10px;
}

.stat.star {
  color: #f59e0b;
}

/* ===== SEARCH PRICE ===== */
.search-price {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  flex-shrink: 0;
  margin-left: 8px;
}

.price-free {
  font-size: 12px;
  font-weight: 700;
  color: #22c55e;
  background: #dcfce722;
  padding: 2px 8px;
  border-radius: 6px;
}

.price-current {
  font-size: 13px;
  font-weight: 800;
  color: var(--accent);
}

.price-badge {
  font-size: 10px;
  font-weight: 700;
  color: white;
  background: #ef4444;
  padding: 1px 5px;
  border-radius: 4px;
}

.payment-date {
  font-size: 10px;
  color: var(--muted);
}

/* ===== PAYMENT STATUS ===== */
.payment-status {
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 4px;
  text-transform: capitalize;
}

.payment-status.status-pending {
  color: #f59e0b;
  background: #fef3c722;
}

.payment-status.status-completed {
  color: #22c55e;
  background: #dcfce722;
}

.payment-status.status-failed {
  color: #ef4444;
  background: #fef2f222;
}

/* ===== MINI PROGRESS BAR ===== */
.progress-mini {
  width: 100%;
  height: 4px;
  background: var(--input-border);
  border-radius: 2px;
  overflow: hidden;
  margin-top: 4px;
}

.progress-mini-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--accent), #22c55e);
  border-radius: 2px;
  transition: width 0.3s;
}

.progress-label {
  font-size: 10px;
  color: #22c55e;
  font-weight: 600;
}

/* ===== REVIEW ===== */
.review-header {
  display: flex;
  align-items: center;
  gap: 6px;
}

.reviewer-name {
  font-size: 12px;
  font-weight: 600;
  color: var(--text);
}

.review-stars {
  display: flex;
  gap: 1px;
}

.review-stars i {
  font-size: 9px;
  color: #e5e7eb;
}

.review-stars i.filled {
  color: #f59e0b;
}

.review-comment {
  font-size: 11px;
  color: var(--muted);
  font-style: italic;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ===== SEARCH ITEM - extra right padding for price ===== */
.search-dropdown .search-item:has(.search-price) {
  padding-right: 4px;
}
</style>
