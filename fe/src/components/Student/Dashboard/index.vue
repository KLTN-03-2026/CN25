<template>
  <div class="dashboard-page">

    <!-- ========== 1. HERO SECTION ========== -->
    <div class="hero-section">
      <div class="hero-bg">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
      </div>

      <div class="container">
        <!-- Greeting Row -->
        <div class="hero-top">
          <div class="hero-greeting">
            <div class="greeting-time">
              <i class="fa-regular fa-sun"></i>
              {{ greetingText }}
            </div>
            <h1 class="greeting-name">
              Chào <span class="name-highlight">{{ user?.name?.split(' ').pop() || 'Học viên' }}</span>
              <span class="greeting-wave">👋</span>
            </h1>
          </div>
          <div class="hero-right">
            <div class="date-chip">
              <i class="fa-solid fa-calendar-days"></i>
              {{ todayShort }}
            </div>
            <div class="streak-pill" v-if="stats.streak_days > 0">
              <i class="fa-solid fa-fire-flame-curved"></i>
              <span>{{ stats.streak_days }} ngày</span>
            </div>
          </div>
        </div>

        <!-- Continue Learning Card -->
        <div class="hero-continue" v-if="nextLesson" @click="continueToLesson">
          <div class="continue-eyebrow">
            <i class="fa-solid fa-forward-fast"></i>
            Học tiếp
          </div>
          <div class="continue-main">
            <div class="continue-info">
              <h2 class="continue-title">{{ nextLesson.title }}</h2>
              <div class="continue-meta-row">
                <span class="meta-chip course-chip">
                  <i class="fa-solid fa-graduation-cap"></i>
                  {{ nextLesson.course_title }}
                </span>
                <span class="meta-chip type-chip" :class="nextLesson.type">
                  <i :class="getTypeIcon(nextLesson.type)"></i>
                  {{ nextLesson.type_label }}
                </span>
              </div>
            </div>
            <div class="continue-actions">
              <div class="progress-ring-wrap">
                <svg class="progress-ring" viewBox="0 0 80 80">
                  <circle class="ring-bg" cx="40" cy="40" r="34"/>
                  <circle 
                    class="ring-fill"
                    cx="40" cy="40" r="34"
                    :stroke-dasharray="`${(nextLesson.progress_percent || 0) * 2.136} 213.6`"
                  />
                </svg>
                <div class="ring-center">
                  <span class="ring-pct">{{ nextLesson.progress_percent || 0 }}%</span>
                </div>
              </div>
              <button class="btn-continue">
                <i class="fa-solid fa-play"></i>
                Tiếp tục
              </button>
            </div>
          </div>
        </div>

        <!-- CTA Fallback -->
        <div class="hero-cta" v-else>
          <div class="cta-icon">
            <i class="fa-solid fa-rocket"></i>
          </div>
          <div class="cta-content">
            <h2>Bắt đầu hành trình học tiếng Anh</h2>
            <p>Đăng ký khóa học đầu tiên để bắt đầu!</p>
          </div>
          <router-link to="/student/khoa-hoc" class="btn btn-primary btn-hero">
            <i class="fa-solid fa-compass"></i>
            Khám phá khóa học
          </router-link>
        </div>

        <!-- Streak Warning -->
        <div class="streak-warning" v-if="showStreakWarning">
          <div class="warn-icon">
            <i class="fa-solid fa-fire-flame-curved"></i>
          </div>
          <div class="warn-content">
            <strong>Bạn sắp mất streak!</strong>
            <span>Học ít nhất 1 bài hôm nay để giữ chuỗi {{ stats.streak_days }} ngày</span>
          </div>
          <button @click="continueToLesson" class="btn btn-warn">
            Học ngay
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </div><!-- end hero-section -->

    <!-- ========== MAIN CONTENT ========== -->
    <div class="container main-content">

      <!-- ========== TOP ROW: Stats + Daily Goal ========== -->
      <div class="top-row">
        <!-- Stats Block -->
        <section class="stats-block">
          <div class="stats-grid">
            <div class="stat-item" v-for="s in statsItems" :key="s.key">
              <div class="stat-icon" :class="s.color">
                <i :class="s.icon"></i>
              </div>
              <div class="stat-body">
                <span class="stat-value">{{ s.value }}</span>
                <span class="stat-label">{{ s.label }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Daily Goal -->
        <section class="goal-block" v-if="enrolledCourses.length > 0">
          <div class="goal-card">
            <div class="goal-top">
              <div class="goal-title">
                <i class="fa-solid fa-bullseye"></i>
                <span>Mục tiêu hôm nay</span>
              </div>
              <div class="goal-badge" :class="{ done: remainingToday === 0 }">
                <span v-if="remainingToday === 0">🎉</span>
                <span v-else>Còn {{ remainingToday }}</span>
              </div>
            </div>
            <div class="goal-progress">
              <div class="progress-track">
                <div class="progress-fill" :style="{ width: goalProgressWidth + '%' }"></div>
              </div>
              <div class="progress-label">
                <span>{{ completedToday }}/{{ dailyGoalTarget }} bài</span>
                <strong>{{ goalProgressWidth }}%</strong>
              </div>
            </div>
          </div>
        </section>
      </div><!-- end top-row -->

      <!-- ========== MAIN GRID ========== -->
      <div class="dash-grid" :class="{ loading: loading }">

        <!-- Row 1: Khóa học + Hoạt động gần đây (2 cards cùng hàng, cao bằng nhau) -->
        <div class="grid-row row-2col">

          <!-- ========== 5. KHÓA HỌC ========== -->
          <section class="dash-card equal-height">
            <div class="card-header">
              <h2 class="card-title">
                <div class="title-icon">
                  <i class="fa-solid fa-graduation-cap"></i>
                </div>
                Khóa học của tôi
              </h2>
              <router-link to="/student/khoa-hoc" class="card-link">
                Xem tất cả
                <i class="fa-solid fa-chevron-right"></i>
              </router-link>
            </div>

            <div v-if="enrolledCourses.length === 0" class="empty-state">
              <div class="empty-icon">
                <i class="fa-solid fa-book-open"></i>
              </div>
              <h3>Chưa có khóa học nào</h3>
              <p>Đăng ký khóa học để bắt đầu!</p>
            </div>

            <div v-else class="cards-body">
              <div
                v-for="course in enrolledCourses.slice(0, 4)"
                :key="course.id"
                class="course-item"
                @click="goToCourse(course)"
              >
                <div class="course-thumb">
                  <img :src="course.thumbnail || defaultThumb" :alt="course.title" />
                  <div class="course-play">
                    <i class="fa-solid fa-play"></i>
                  </div>
                  <div class="course-check" v-if="course.is_completed">
                    <i class="fa-solid fa-circle-check"></i>
                  </div>
                </div>
                <div class="course-body">
                  <h4 class="course-title">{{ course.title }}</h4>
                  <div class="course-meta">
                    <span>
                      <i class="fa-solid fa-check-circle"></i>
                      {{ course.completed_lessons || 0 }}/{{ course.total_lessons || 0 }} bài
                    </span>
                    <span v-if="course.is_completed" class="done-badge">
                      Hoàn thành
                    </span>
                  </div>
                  <div class="course-progress">
                    <div class="progress-bar">
                      <div
                        class="progress-fill"
                        :class="{ green: course.is_completed }"
                        :style="{ width: (course.progress_percent || 0) + '%' }"
                      ></div>
                    </div>
                    <span class="progress-pct">{{ course.progress_percent || 0 }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- ========== 6. HOẠT ĐỘNG GẦN ĐÂY ========== -->
          <section class="dash-card equal-height">
            <div class="card-header">
              <h2 class="card-title">
                <div class="title-icon">
                  <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                Hoạt động gần đây
              </h2>
              <span class="count-badge" v-if="recentActivity.length > 0">
                {{ recentActivity.length }}
              </span>
            </div>

            <div v-if="recentActivity.length === 0" class="empty-state small">
              <div class="empty-icon small">
                <i class="fa-solid fa-clock"></i>
              </div>
              <p>Chưa có hoạt động nào</p>
            </div>

            <div v-else class="cards-body">
              <div
                v-for="act in recentActivity"
                :key="act.id || act.lesson_id"
                class="activity-item"
                @click="reopenActivity(act)"
              >
                <div class="activity-icon" :class="act.type">
                  <i :class="getActivityIcon(act.type)"></i>
                </div>
                <div class="activity-body">
                  <span class="activity-title">{{ act.title }}</span>
                  <span class="activity-time">
                    <i class="fa-solid fa-clock"></i>
                    {{ formatTime(act.completed_at) }}
                  </span>
                </div>
                <div class="activity-score" v-if="act.score" :class="getScoreClass(act.score)">
                  {{ act.score }}đ
                </div>
                <i class="fa-solid fa-chevron-right act-arrow"></i>
              </div>
            </div>
          </section>

        </div><!-- end row-full -->

        <!-- Row 4: Tài liệu học tập (full width) -->
        <div class="grid-row row-full" v-if="quickDocs.length > 0">
          <section class="docs-section equal-height">
            <div class="docs-header">
              <div class="docs-title">
                <div class="docs-icon">
                  <i class="fa-solid fa-folder-open"></i>
                </div>
                <div class="docs-title-text">
                  <h3>Tài liệu học tập</h3>
                  <p>Tổng hợp tài liệu, cheat sheet & bài tập</p>
                </div>
              </div>
              <router-link to="/student/tai-lieu" class="docs-link">
                Xem tất cả
                <i class="fa-solid fa-chevron-right"></i>
              </router-link>
            </div>

            <div class="docs-grid">
              <div
                v-for="doc in quickDocs"
                :key="doc.id"
                class="doc-card"
                @click="goToDoc(doc)"
              >
                <div class="doc-preview" :class="doc.ext">
                  <i :class="getDocIcon(doc.ext)"></i>
                  <span class="doc-ext-label">{{ doc.ext.toUpperCase() }}</span>
                </div>
                <div class="doc-content">
                  <h4 class="doc-title">{{ doc.title }}</h4>
                  <div class="doc-meta">
                    <span class="doc-type">
                      <i class="fa-solid fa-tag"></i>
                      {{ doc.type_label }}
                    </span>
                  </div>
                </div>
                <div class="doc-action">
                  <i class="fa-solid fa-download"></i>
                </div>
              </div>
            </div>
          </section>
        </div><!-- end row-full -->

        <!-- Row 5: Bài viết mới -->
        <div class="grid-row row-full" v-if="latestArticles.length > 0">
          <section class="dash-card equal-height">
            <div class="card-header">
              <h2 class="card-title">
                <div class="title-icon">
                  <i class="fa-solid fa-newspaper"></i>
                </div>
                Bài viết mới
              </h2>
              <router-link to="/student/bai-viet" class="card-link">
                Xem tất cả
                <i class="fa-solid fa-chevron-right"></i>
              </router-link>
            </div>
            <div class="articles-grid">
              <div
                v-for="article in latestArticles"
                :key="article.id"
                class="article-card"
                @click="goToArticle(article)"
              >
                <div class="article-image" v-if="article.thumbnail">
                  <img :src="article.thumbnail" :alt="article.title" />
                </div>
                <div class="article-image placeholder" v-else>
                  <i class="fa-solid fa-newspaper"></i>
                </div>
                <div class="article-body">
                  <span class="article-category" v-if="article.category">{{ article.category }}</span>
                  <h4 class="article-title">{{ article.title }}</h4>
                  <p class="article-excerpt" v-if="article.summary">{{ article.summary }}</p>
                  <div class="article-meta">
                    <span class="article-date">
                      <i class="fa-regular fa-calendar"></i>
                      {{ formatDate(article.created_at) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div><!-- end row-articles -->

      </div><!-- end dash-grid -->

      <!-- Loading -->
      <div v-if="loading" class="loading-overlay">
        <div class="loader"></div>
        <p>Đang tải dữ liệu...</p>
      </div>

    </div><!-- end main-content -->
  </div><!-- end dashboard-page -->
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import { ProgressService, DocumentService, ArticleService } from '../../../services/api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const user = computed(() => authStore.user)
const loading = ref(true)
const enrolledCourses = ref([])
const recentActivity = ref([])
const scoresByType = ref({})
const stats = ref({ completed_lessons: 0, total_time_minutes: 0, streak_days: 0, lessons_today: 0 })
const nextLesson = ref(null)
const quickDocs = ref([])
const latestArticles = ref([])

const defaultThumb = 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&h=250&fit=crop'
const dailyGoalTarget = 3

// 1. Greeting
const greetingText = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Buổi sáng tốt lành'
  if (h < 18) return 'Buổi chiều vui vẻ'
  return 'Buổi tối an nhiên'
})

const todayShort = computed(() => {
  return new Date().toLocaleDateString('vi-VN', { weekday: 'short', day: 'numeric', month: 'numeric' })
})

// 10. Daily Goal
const completedToday = computed(() => stats.value.lessons_today || 0)
const remainingToday = computed(() => Math.max(0, dailyGoalTarget - completedToday.value))
const goalProgressWidth = computed(() => Math.min(100, Math.round((completedToday.value / dailyGoalTarget) * 100)))

// 10. Streak Warning
const showStreakWarning = computed(() => {
  return stats.value.streak_days > 0 && completedToday.value === 0
})

// 4. Stats items
const statsItems = computed(() => [
  {
    key: 'streak',
    icon: 'fa-solid fa-fire-flame-curved',
    value: stats.value.streak_days || 0,
    label: 'Ngày streak',
    color: 'orange'
  },
  {
    key: 'completed',
    icon: 'fa-solid fa-check-double',
    value: enrolledCourses.value.filter(c => c.is_completed).length + '/' + enrolledCourses.value.length,
    label: 'Khóa hoàn thành',
    color: 'green'
  },
  {
    key: 'progress',
    icon: 'fa-solid fa-chart-line',
    value: avgProgress.value + '%',
    label: 'Tổng tiến độ',
    color: 'blue'
  },
  {
    key: 'time',
    icon: 'fa-solid fa-hourglass-half',
    value: totalTime.value,
    label: 'Tổng thời gian',
    color: 'purple'
  }
])

const avgProgress = computed(() => {
  if (enrolledCourses.value.length === 0) return 0
  const total = enrolledCourses.value.reduce((s, c) => s + (c.progress_percent || 0), 0)
  return Math.round(total / enrolledCourses.value.length)
})

const totalTime = computed(() => {
  const minutes = stats.value.total_time_minutes || 0
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  if (hours > 0) return `${hours}h ${mins}p`
  return `${mins}p`
})

// 2. Continue Lesson type
const getTypeIcon = (type) => {
  const map = { vocabulary: 'fa-solid fa-spell-check', grammar: 'fa-solid fa-book', listening: 'fa-solid fa-headphones', speaking: 'fa-solid fa-microphone-lines', quiz: 'fa-solid fa-file-signature', lesson: 'fa-solid fa-book-open' }
  return map[type] || 'fa-solid fa-book-open'
}

const getTypeLabel = (type) => {
  const map = { vocabulary: 'Từ vựng', grammar: 'Ngữ pháp', listening: 'Luyện nghe', speaking: 'Luyện nói', quiz: 'Quiz', lesson: 'Bài học' }
  return map[type] || 'Bài học'
}

// 6. Activity icons
const getActivityIcon = (type) => {
  const map = { vocabulary: 'fa-solid fa-spell-check', grammar: 'fa-solid fa-book', listening: 'fa-solid fa-headphones', speaking: 'fa-solid fa-microphone-lines', quiz: 'fa-solid fa-file-signature' }
  return map[type] || 'fa-solid fa-check'
}

const getScoreClass = (score) => {
  if (score >= 80) return 'high'
  if (score >= 60) return 'mid'
  return 'low'
}

const formatTime = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const now = new Date()
  const diff = now - d
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'Vừa xong'
  if (mins < 60) return `${mins} phút`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} giờ`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days} ngày`
  return d.toLocaleDateString('vi-VN')
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('vi-VN', { day: 'numeric', month: 'short', year: 'numeric' })
}

const reopenActivity = (act) => {
  if (act.course_id && act.lesson_id) router.push(`/student/khoa-hoc/${act.course_id}/bai/${act.lesson_id}`)
  else if (act.course_id) router.push(`/student/khoa-hoc/${act.course_id}`)
}

const getDocIcon = (ext) => {
  if (ext === 'pdf') return 'fa-solid fa-file-pdf'
  if (['doc', 'docx'].includes(ext)) return 'fa-solid fa-file-word'
  if (['xls', 'xlsx'].includes(ext)) return 'fa-solid fa-file-excel'
  return 'fa-solid fa-file-lines'
}

const buildAchievements = () => {
  const achs = []
  if (stats.value.completed_lessons >= 1) achs.push({ id: 1, icon: 'fa-solid fa-star', title: 'Bài đầu tiên', desc: 'Hoàn thành bài đầu tiên', color: 'gold' })
  if (stats.value.streak_days >= 3) achs.push({ id: 2, icon: 'fa-solid fa-fire-flame-curved', title: `Streak ${stats.value.streak_days} ngày`, desc: 'Kiên trì mỗi ngày', color: 'orange' })
  if (stats.value.completed_lessons >= 10) achs.push({ id: 3, icon: 'fa-solid fa-graduation-cap', title: 'Học giả', desc: `${stats.value.completed_lessons} bài học`, color: 'purple' })
  return achs
}

const achievements = computed(() => buildAchievements())

const fetchDashboard = async () => {
  try {
    loading.value = true
    const res = await ProgressService.getDashboard()
    if (res.success) {
      enrolledCourses.value = res.data.enrolled_courses || []
      enrolledCourses.value.sort((a, b) => {
        if (a.is_completed && !b.is_completed) return 1
        if (!a.is_completed && b.is_completed) return -1
        return (b.progress_percent || 0) - (a.progress_percent || 0)
      })

      stats.value = {
        completed_lessons: res.data.total_completed_lessons || 0,
        total_time_minutes: res.data.total_time_minutes || 0,
        streak_days: res.data.streak_days || 0,
        lessons_today: res.data.lessons_today || 0,
      }

      recentActivity.value = res.data.recent_activity || []
      scoresByType.value = res.data.scores_by_type || {}

      if (enrolledCourses.value.length > 0) {
        const current = enrolledCourses.value.find(c => !c.is_completed)
        if (current) {
          nextLesson.value = {
            id: current.next_lesson_id,
            title: current.next_lesson_title || 'Bài tiếp theo',
            course_id: current.id,
            course_title: current.title,
            type: current.next_lesson_type || 'lesson',
            type_label: getTypeLabel(current.next_lesson_type),
            progress_percent: current.progress_percent || 0,
          }
        }
      }
    }

    const docRes = await DocumentService.getAll({ active_only: true })
    if (docRes.success && docRes.data) {
      quickDocs.value = docRes.data.slice(0, 4).map(d => ({
        id: d.id,
        title: d.title,
        ext: d.file_type || 'pdf',
        type_label: d.description || 'Tài liệu',
        file_url: d.file_url
      }))
    }

    const articleRes = await ArticleService.getLatest(3)
    if (articleRes.success && articleRes.data) {
      latestArticles.value = articleRes.data
    }
  } catch (error) {
    console.error('Error fetching dashboard:', error)
  } finally {
    loading.value = false
  }
}

const continueToLesson = () => {
  if (nextLesson.value) {
    const { course_id, id } = nextLesson.value
    if (id) router.push(`/student/khoa-hoc/${course_id}/bai/${id}`)
    else router.push(`/student/khoa-hoc/${course_id}`)
  } else {
    router.push('/student/khoa-hoc')
  }
}

const goToCourse = (course) => router.push(`/student/khoa-hoc/${course.id || course.course_id}`)
const goToDoc = (doc) => {
  if (doc.file_url) {
    window.open(doc.file_url, '_blank')
  } else {
    router.push('/student/tai-lieu/' + doc.id)
  }
}

const goToArticle = (article) => {
  router.push('/student/bai-viet/' + article.id)
}

onMounted(() => { fetchDashboard() })

// Reload khi quay lai tu trang thanh toan
watch(() => route.path, (newPath, oldPath) => {
  if (oldPath && oldPath.startsWith('/student/thanh-toan') && newPath === '/student') {
    fetchDashboard()
  }
})
</script>

<style scoped>
.dashboard-page {
  background: var(--bg-secondary);
  min-height: 100vh;
  padding-bottom: 60px;
}

/* ===== HERO ===== */
.hero-section {
  position: relative;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 40%, #f093fb 100%);
  padding: 40px 0 48px;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(ellipse at 20% 80%, rgba(102, 126, 234, 0.4) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(118, 75, 162, 0.3) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 50%, rgba(240, 147, 251, 0.15) 0%, transparent 60%);
}

.floating-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.15;
}

.shape-1 {
  width: 600px; height: 600px;
  background: #818cf8;
  top: -250px; right: -150px;
  animation: float1 22s ease-in-out infinite;
}

.shape-2 {
  width: 400px; height: 400px;
  background: #c084fc;
  bottom: -150px; left: -50px;
  animation: float2 28s ease-in-out infinite;
}

.shape-3 {
  width: 250px; height: 250px;
  background: #f472b6;
  top: 20%; left: 30%;
  animation: float3 20s ease-in-out infinite;
}

@keyframes float1 {
  0%, 100% { transform: translate(0, 0) rotate(0deg); }
  33% { transform: translate(-40px, 30px) rotate(5deg); }
  66% { transform: translate(20px, -20px) rotate(-3deg); }
}
@keyframes float2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(30px, -40px) scale(1.1); }
}
@keyframes float3 {
  0%, 100% { transform: translate(0, 0); opacity: 0.15; }
  50% { transform: translate(-20px, 25px); opacity: 0.2; }
}

.hero-section .container {
  position: relative;
  z-index: 1;
}

/* Greeting */
.hero-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 28px;
  gap: 20px;
}

.hero-greeting { display: flex; flex-direction: column; gap: 4px; }

.greeting-time {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.45);
}

.greeting-name {
  font-size: 30px;
  font-weight: 900;
  color: white;
  line-height: 1.1;
}

.name-highlight {
  background: linear-gradient(90deg, #60a5fa, #a78bfa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.greeting-wave {
  font-size: 26px;
  display: inline-block;
  animation: wave 2s ease-in-out infinite;
}

@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(20deg); }
  50% { transform: rotate(0deg); }
  75% { transform: rotate(-20deg); }
}

.hero-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.date-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.15);
  padding: 7px 14px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.date-chip i { font-size: 11px; }

.streak-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(249, 115, 22, 0.18);
  border: 1px solid rgba(249, 115, 22, 0.3);
  color: #fb923c;
  border-radius: 999px;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 700;
  animation: glow 2.5s ease-in-out infinite;
}

@keyframes glow {
  0%, 100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.3); }
  50% { box-shadow: 0 0 16px 2px rgba(249, 115, 22, 0.2); }
}

.streak-pill i { font-size: 14px; color: #f97316; }

/* Continue Card */
.hero-continue {
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 24px;
  padding: 28px 32px;
  cursor: pointer;
  transition: all 0.3s;
}

.hero-continue:hover {
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
}

.continue-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  border-radius: 999px;
  padding: 5px 14px;
  font-size: 12px;
  font-weight: 700;
  color: white;
  margin-bottom: 16px;
  width: fit-content;
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
}

.continue-eyebrow i { font-size: 10px; }

.continue-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
}

.continue-info { flex: 1; }

.continue-title {
  font-size: 22px;
  font-weight: 900;
  color: white;
  margin-bottom: 14px;
  line-height: 1.2;
}

.continue-meta-row { display: flex; gap: 10px; flex-wrap: wrap; }

.meta-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  padding: 5px 12px;
  font-size: 12px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.7);
}

.meta-chip i { font-size: 11px; }

.course-chip {
  background: rgba(34, 211, 238, 0.1);
  border-color: rgba(34, 211, 238, 0.2);
  color: #67e8f9;
}

.type-chip.vocabulary { background: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.3); color: #86efac; }
.type-chip.grammar { background: rgba(234, 179, 8, 0.15); border-color: rgba(234, 179, 8, 0.3); color: #fde047; }
.type-chip.listening { background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #93c5fd; }
.type-chip.speaking { background: rgba(219, 39, 119, 0.15); border-color: rgba(219, 39, 119, 0.3); color: #f9a8d4; }
.type-chip.quiz { background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.3); color: #d8b4fe; }

.continue-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  flex-shrink: 0;
}

.progress-ring-wrap {
  position: relative;
  width: 80px;
  height: 80px;
}

.progress-ring { width: 80px; height: 80px; transform: rotate(-90deg); }

.ring-bg {
  fill: none;
  stroke: rgba(255, 255, 255, 0.1);
  stroke-width: 7;
}

.ring-fill {
  fill: none;
  stroke: #6366f1;
  stroke-width: 7;
  stroke-linecap: round;
  transition: stroke-dasharray 0.8s ease;
  filter: drop-shadow(0 0 6px rgba(99, 102, 241, 0.5));
}

.ring-center {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ring-pct {
  font-size: 18px;
  font-weight: 900;
  color: white;
}

.btn-continue {
  display: flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
  border-radius: 14px;
  padding: 11px 22px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s;
  box-shadow: 0 4px 18px rgba(99, 102, 241, 0.4);
  white-space: nowrap;
}

.btn-continue:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(99, 102, 241, 0.5);
}

/* Hero CTA */
.hero-cta {
  display: flex;
  align-items: center;
  gap: 24px;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 24px;
  padding: 28px 32px;
}

.cta-icon {
  width: 72px;
  height: 72px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.cta-content { flex: 1; }
.cta-content h2 { font-size: 20px; font-weight: 800; color: white; margin-bottom: 6px; }
.cta-content p { font-size: 14px; color: rgba(255, 255, 255, 0.6); }

.btn-hero {
  background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
  color: white !important;
  border: none !important;
  box-shadow: 0 4px 18px rgba(99, 102, 241, 0.4) !important;
  padding: 12px 24px !important;
  font-size: 14px !important;
  font-weight: 700 !important;
}

.btn-hero:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 24px rgba(99, 102, 241, 0.5) !important;
}

/* Streak Warning */
.streak-warning {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 18px;
  padding: 16px 22px;
  color: white;
  margin-top: 16px;
  animation: pulse-warn 2s infinite;
}

@keyframes pulse-warn {
  0%, 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.3); }
  50% { box-shadow: 0 0 0 8px rgba(255, 255, 255, 0); }
}

.warn-icon { font-size: 24px; flex-shrink: 0; }
.warn-content { flex: 1; }
.warn-content strong { display: block; font-size: 14px; font-weight: 800; margin-bottom: 2px; }
.warn-content span { font-size: 12px; opacity: 0.85; }

.btn-warn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.95);
  color: #7c3aed;
  border: none;
  border-radius: 10px;
  padding: 9px 16px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.btn-warn:hover { transform: scale(1.03); background: white; }
.btn-warn i { font-size: 11px; }

/* ===== MAIN CONTENT ===== */
.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
}

.main-content {
  padding-top: 28px;
}

/* TOP ROW */
.top-row {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 20px;
  margin-bottom: 24px;
  align-items: stretch;
}

/* Stats */
.stats-block {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 20px 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 16px;
  border-right: 1px solid var(--border);
}

.stat-item:last-child { border-right: none; }

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.stat-icon.orange { background: rgba(249, 115, 22, 0.1); color: #f97316; }
.stat-icon.green { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.stat-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.stat-icon.purple { background: rgba(168, 85, 247, 0.1); color: #a855f7; }

.stat-body { display: flex; flex-direction: column; }

.stat-value {
  font-size: 20px;
  font-weight: 900;
  color: var(--text-h);
  line-height: 1;
}

.stat-label {
  font-size: 11px;
  color: var(--muted);
  margin-top: 2px;
}

/* Goal */
.goal-block {
  display: flex;
  flex-direction: column;
}

.goal-card {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border: 1px solid rgba(34, 197, 94, 0.2);
  border-radius: 20px;
  padding: 20px 24px;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

[data-theme="dark"] .goal-card {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.08) 0%, rgba(34, 197, 94, 0.15) 100%);
  border-color: rgba(34, 197, 94, 0.2);
}

.goal-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.goal-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 800;
  color: #15803d;
}

[data-theme="dark"] .goal-title { color: #86efac; }

.goal-title i { font-size: 15px; }

.goal-badge {
  font-size: 12px;
  font-weight: 700;
  color: #16a34a;
  background: rgba(34, 197, 94, 0.15);
  padding: 4px 10px;
  border-radius: 999px;
}

[data-theme="dark"] .goal-badge { color: #86efac; }

.goal-badge.done {
  background: rgba(34, 197, 94, 0.2);
}

.goal-progress {
  margin-top: 8px;
}

.progress-track {
  height: 8px;
  background: rgba(34, 197, 94, 0.2);
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #22c55e, #16a34a);
  border-radius: 999px;
  transition: width 0.6s ease;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #16a34a;
}

[data-theme="dark"] .progress-label { color: #86efac; }

.progress-label strong { font-weight: 800; }

/* MAIN GRID */
.dash-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: relative;
}

.grid-row {
  display: grid;
  gap: 20px;
  align-items: stretch;
}

.row-2col {
  grid-template-columns: 1fr 1fr;
}

.row-2col-wide {
  grid-template-columns: 1fr 1.4fr;
}

.row-full {
  grid-template-columns: 1fr;
}


/* Dash Card */
.dash-card {
  display: flex;
  flex-direction: column;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 22px;
  transition: box-shadow 0.25s;
}

.dash-card > .card-header {
  flex-shrink: 0;
}

.dash-card > .cards-body,
.dash-card > .ach-list,
.dash-card > .activity-list,
.dash-card > .empty-state {
  flex: 1;
  min-height: 0;
}

.dash-card:hover { box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07); }

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.card-title {
  font-size: 15px;
  font-weight: 800;
  color: var(--text-h);
  display: flex;
  align-items: center;
  gap: 10px;
}

.title-icon {
  width: 32px;
  height: 32px;
  background: var(--accent-bg);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent);
  font-size: 14px;
}

.card-link {
  font-size: 12px;
  font-weight: 600;
  color: var(--accent);
  display: flex;
  align-items: center;
  gap: 4px;
  transition: all 0.2s;
}

.card-link:hover { color: var(--accent-hover); gap: 6px; }
.card-link i { font-size: 11px; }

/* Courses */
.courses-list { display: flex; flex-direction: column; gap: 12px; }

.cards-body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Equal height for grid items */
.equal-height {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

.equal-height > * {
  flex-shrink: 0;
}

.equal-height > *:last-child {
  flex: 1;
  min-height: 0;
}

.course-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  border-radius: 16px;
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: all 0.25s;
}

.course-item:hover {
  border-color: var(--accent-border);
  transform: translateX(5px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.course-thumb {
  position: relative;
  width: 64px;
  height: 48px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
}

.course-thumb img { width: 100%; height: 100%; object-fit: cover; }

.course-play {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
}

.course-play i { color: white; font-size: 12px; }
.course-item:hover .course-play { opacity: 1; }

.course-check {
  position: absolute;
  top: -3px;
  right: -3px;
  width: 18px;
  height: 18px;
  background: #22c55e;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 9px;
  color: white;
  border: 2px solid var(--card-bg);
}

.course-body { flex: 1; min-width: 0; }

.course-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-h);
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.course-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--muted);
  margin-bottom: 6px;
}

.course-meta span { display: flex; align-items: center; gap: 3px; }
.course-meta i { color: #22c55e; }

.done-badge {
  color: #22c55e !important;
  font-weight: 600;
}

.course-progress {
  display: flex;
  align-items: center;
  gap: 8px;
}

.progress-bar {
  flex: 1;
  height: 5px;
  background: var(--border);
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  border-radius: 999px;
  transition: width 0.5s;
}

.progress-fill.green { background: #22c55e; }

.progress-pct {
  font-size: 11px;
  font-weight: 700;
  color: var(--accent);
  min-width: 32px;
  text-align: right;
}

/* Activity */
.count-badge {
  font-size: 11px;
  font-weight: 600;
  color: var(--muted);
  background: var(--bg-secondary);
  padding: 3px 8px;
  border-radius: 999px;
}

.activity-list { display: flex; flex-direction: column; gap: 8px; }

.activity-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: all 0.25s;
}

.activity-item:hover {
  border-color: var(--accent-border);
  background: var(--accent-bg);
}

.activity-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.activity-icon.vocabulary { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.activity-icon.grammar { background: rgba(234, 179, 8, 0.1); color: #ca8a04; }
.activity-icon.listening { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
.activity-icon.speaking { background: rgba(219, 39, 119, 0.1); color: #db2777; }
.activity-icon.quiz { background: rgba(168, 85, 247, 0.1); color: #8b5cf6; }

.activity-body { flex: 1; min-width: 0; }

.activity-title {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-h);
  margin-bottom: 1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-time {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--muted);
}

.activity-time i { font-size: 10px; }

.activity-score {
  font-size: 12px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 8px;
  flex-shrink: 0;
}

.activity-score.high { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.activity-score.mid { background: rgba(234, 179, 8, 0.1); color: #ca8a04; }
.activity-score.low { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

.act-arrow { font-size: 11px; color: var(--muted); flex-shrink: 0; transition: all 0.2s; }
.activity-item:hover .act-arrow { color: var(--accent); transform: translateX(3px); }

/* Documents Section */
.docs-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 22px;
  transition: box-shadow 0.25s;
}

.docs-section > .docs-header {
  flex-shrink: 0;
}

.docs-section > .docs-grid,
.docs-section > .docs-empty {
  flex: 1;
  min-height: 0;
}

.docs-section:hover { box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07); }

.docs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.docs-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.docs-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, rgba(251, 146, 60, 0.15), rgba(249, 115, 22, 0.15));
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #f97316;
}

.docs-title-text h3 {
  font-size: 16px;
  font-weight: 800;
  color: var(--text-h);
  margin-bottom: 2px;
}

.docs-title-text p {
  font-size: 12px;
  color: var(--muted);
}

.docs-link {
  font-size: 12px;
  font-weight: 600;
  color: var(--accent);
  display: flex;
  align-items: center;
  gap: 4px;
  transition: all 0.2s;
}

.docs-link:hover { color: var(--accent-hover); gap: 6px; }
.docs-link i { font-size: 11px; }

.docs-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.doc-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border-radius: 14px;
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: all 0.25s;
}

.doc-card:hover {
  border-color: rgba(251, 146, 60, 0.4);
  background: rgba(251, 146, 60, 0.04);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}

.doc-preview {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.doc-preview i { margin-bottom: 2px; }

.doc-preview.pdf { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
.doc-preview.doc, .doc-preview.docx { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
.doc-preview.xls, .doc-preview.xlsx { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.doc-preview.default { background: var(--bg); color: var(--muted); }

.doc-ext-label {
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.doc-content { flex: 1; min-width: 0; }

.doc-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-h);
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.doc-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.doc-type {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--muted);
}

.doc-type i { font-size: 10px; color: #f97316; }

.doc-action {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(251, 146, 60, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: #f97316;
  flex-shrink: 0;
  transition: all 0.2s;
  opacity: 0;
}

.doc-card:hover .doc-action { opacity: 1; }

.docs-empty {
  text-align: center;
  padding: 28px;
}

.docs-empty-icon {
  width: 56px;
  height: 56px;
  background: var(--bg-secondary);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  font-size: 24px;
  color: #f97316;
}

.docs-empty p {
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 14px;
}

.btn-docs-browse {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: rgba(251, 146, 60, 0.1);
  border: 1px solid rgba(251, 146, 60, 0.2);
  border-radius: 10px;
  color: #f97316;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-docs-browse:hover {
  background: rgba(251, 146, 60, 0.2);
  transform: translateY(-1px);
}

/* Achievements */
.ach-list { display: flex; flex-direction: column; gap: 10px; }

.ach-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: var(--bg-secondary);
  border: 1px solid var(--border);
}

.ach-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.ach-icon.gold { background: rgba(251, 191, 36, 0.1); color: #f59e0b; }
.ach-icon.orange { background: rgba(249, 115, 22, 0.1); color: #f97316; }
.ach-icon.purple { background: rgba(168, 85, 247, 0.1); color: #a855f7; }
.ach-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

.ach-info { flex: 1; }
.ach-info strong { display: block; font-size: 13px; font-weight: 700; color: var(--text-h); margin-bottom: 1px; }
.ach-info span { font-size: 11px; color: var(--muted); }

/* Articles Grid */
.articles-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.article-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.25s;
}

.article-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  border-color: var(--accent);
}

.article-image {
  width: 100%;
  height: 140px;
  object-fit: cover;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.article-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-image.placeholder {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  color: var(--muted);
  font-size: 32px;
}

.article-body {
  padding: 14px;
}

.article-category {
  display: inline-block;
  padding: 3px 8px;
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.article-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-h);
  margin-bottom: 6px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-clamp: 2;
  line-clamp: 2;
}

.article-excerpt {
  font-size: 12px;
  color: var(--muted);
  line-height: 1.5;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-clamp: 2;
  line-clamp: 2;
}

.article-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.article-date {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--muted);
}

.article-date i { font-size: 10px; }

/* Empty State */
.empty-state {
  text-align: center;
  padding: 28px 16px;
}

.empty-icon {
  width: 52px;
  height: 52px;
  background: var(--bg-secondary);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  font-size: 22px;
  color: var(--accent);
}

.empty-icon.small {
  width: 40px;
  height: 40px;
  font-size: 18px;
  margin-bottom: 8px;
}

.empty-state h3 { font-size: 14px; font-weight: 700; color: var(--text-h); margin-bottom: 4px; }
.empty-state p { font-size: 13px; color: var(--muted); }

/* Loading */
.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(4px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 20px;
  z-index: 10;
}

.loader {
  width: 36px;
  height: 36px;
  border: 3px solid var(--border);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 12px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.loading-overlay p { font-size: 13px; color: var(--muted); font-weight: 500; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1100px) {
  .top-row { grid-template-columns: 1fr; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .stat-item { border-right: none; border-bottom: 1px solid var(--border); }
  .stat-item:nth-last-child(-n+2) { border-bottom: none; }
  .row-2col, .row-2col-wide { grid-template-columns: 1fr; }
  .articles-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .hero-section { padding: 28px 0 36px; }
  .hero-top { flex-direction: column; gap: 14px; }
  .greeting-name { font-size: 26px; }
  .hero-right { align-self: flex-start; flex-wrap: wrap; }
  .hero-continue { padding: 22px; }
  .continue-main { flex-direction: column; align-items: flex-start; }
  .continue-actions { flex-direction: row; width: 100%; justify-content: space-between; }
  .continue-title { font-size: 18px; }
  .hero-cta { flex-wrap: wrap; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .streak-warning { flex-wrap: wrap; }
  .row-2col, .row-2col-wide { grid-template-columns: 1fr; }
  .articles-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .stat-item { padding: 10px 12px; }
  .stat-value { font-size: 18px; }
  .hero-right { gap: 8px; }
}
</style>
