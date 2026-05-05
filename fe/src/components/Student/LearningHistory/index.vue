<template>
  <div class="history-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="container">
        <div class="header-top">
          <div class="header-left">
            <h1 class="page-title">
              <i class="fa-solid fa-clock-rotate-left"></i>
              Lịch sử học tập
            </h1>
            <p class="page-subtitle">Theo dõi quá trình học tập và thành tích của bạn</p>
          </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
          <div class="stat-item">
            <div class="stat-icon blue">
              <i class="fa-solid fa-book-open"></i>
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ stats.total_lessons_completed }}</span>
              <span class="stat-label">Bài học hoàn thành</span>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon green">
              <i class="fa-solid fa-pen"></i>
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ stats.total_practice_done }}</span>
              <span class="stat-label">Lần luyện tập</span>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon purple">
              <i class="fa-solid fa-file-signature"></i>
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ stats.total_exams_done }}</span>
              <span class="stat-label">Lần thi cuối khóa</span>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon orange">
              <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ stats.total_time_formatted }}</span>
              <span class="stat-label">Thời gian học</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container main-content">

      <!-- Activity Chart -->
      <div class="section-card">
        <div class="card-header">
          <h2 class="card-title">
            <i class="fa-solid fa-chart-bar"></i>
            Hoạt động 7 ngày gần nhất
          </h2>
        </div>
        <div class="card-body">
          <div class="chart-container" v-if="dailyStats.length > 0">
            <div class="chart-bars">
              <div
                v-for="(day, idx) in dailyStats"
                :key="idx"
                class="bar-col"
              >
                <div class="bar-value-label" v-if="day.count > 0">{{ day.count }}</div>
                <div class="bar-wrapper">
                  <div
                    class="bar-fill"
                    :style="{ height: getBarHeight(day.count) + '%' }"
                  ></div>
                </div>
                <div class="bar-day-label">{{ formatDayLabel(day.date) }}</div>
              </div>
            </div>
          </div>
          <div class="chart-empty" v-else>
            <i class="fa-solid fa-chart-simple"></i>
            <p>Chưa có dữ liệu hoạt động trong 7 ngày gần đây</p>
          </div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="filter-bar">
        <button
          v-for="tab in filterTabs"
          :key="tab.value"
          class="filter-btn"
          :class="{ active: activeFilter === tab.value }"
          @click="changeFilter(tab.value)"
        >
          <i :class="tab.icon"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- History List -->
      <div class="history-list">

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <span>Đang tải lịch sử học tập...</span>
        </div>

        <!-- Empty -->
        <div v-else-if="historyItems.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fa-solid fa-books"></i>
          </div>
          <h3>Chưa có hoạt động nào</h3>
          <p>Bạn chưa hoàn thành bài học nào. Hãy bắt đầu học ngay!</p>
          <router-link to="/student/khoa-hoc" class="btn-primary">
            <i class="fa-solid fa-compass"></i>
            Khám phá khóa học
          </router-link>
        </div>

        <!-- Items -->
        <div v-else class="items-grid">
          <div
            v-for="(item, index) in historyItems"
            :key="index"
            class="history-card"
            @click="goToItem(item)"
          >
            <div class="card-icon" :class="item.type">
              <i :class="getTypeIcon(item)"></i>
            </div>
            <div class="card-content">
              <div class="card-top">
                <span class="type-tag" :class="item.type">{{ getTypeLabel(item) }}</span>
                <span class="card-date">
                  <i class="fa-regular fa-calendar"></i>
                  {{ formatDate(item.completed_at || item.created_at) }}
                </span>
              </div>
              <h3 class="card-title">{{ getTitle(item) }}</h3>
              <div class="card-meta">
                <span v-if="item.course_title">
                  <i class="fa-solid fa-graduation-cap"></i>
                  {{ item.course_title }}
                </span>
                <span v-if="item.chapter_title">
                  <i class="fa-solid fa-layer-group"></i>
                  {{ item.chapter_title }}
                </span>
              </div>
              <div class="card-footer">
                <!-- Score -->
                <div class="badge-score" v-if="item.score !== null && item.score !== undefined">
                  <span class="score-dot" :class="getScoreClass(item)"></span>
                  {{ Math.round(item.score) }}%
                </div>
                <!-- Completed -->
                <div class="badge-done" v-else-if="item.completed">
                  <i class="fa-solid fa-check-circle"></i>
                  Đã hoàn thành
                </div>
                <!-- Time spent -->
                <div class="badge-time" v-if="item.time_spent">
                  <i class="fa-regular fa-clock"></i>
                  {{ formatTime(item.time_spent) }}
                </div>
                <!-- Attempt -->
                <div class="badge-attempt" v-if="item.attempt">
                  <i class="fa-solid fa-repeat"></i>
                  Lần {{ item.attempt }}
                </div>
                <!-- Passed -->
                <div class="badge-pass" v-if="item.passed !== undefined && item.passed !== null">
                  <span :class="item.passed ? 'pass' : 'fail'">
                    <i :class="item.passed ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                    {{ item.passed ? 'Đạt' : 'Chưa đạt' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="card-arrow">
              <i class="fa-solid fa-chevron-right"></i>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="pagination.total_pages > 1">
          <button
            class="pg-btn"
            :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)"
          >
            <i class="fa-solid fa-chevron-left"></i>
          </button>

          <template v-for="p in visiblePages">
            <span class="pg-ellipsis" v-if="p === '...'">...</span>
            <button
              v-else
              :key="'page-' + p"
              class="pg-btn"
              :class="{ active: p === pagination.current_page }"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
          </template>

          <button
            class="pg-btn"
            :disabled="pagination.current_page >= pagination.total_pages"
            @click="goToPage(pagination.current_page + 1)"
          >
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ProgressService } from '../../../services/api.js'

const router = useRouter()

const loading = ref(false)
const historyItems = ref([])
const activeFilter = ref('all')
const currentPage = ref(1)
const perPage = 20

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
})

const stats = ref({
  total_lessons_completed: 0,
  total_practice_done: 0,
  total_exams_done: 0,
  total_time_spent: 0,
  total_time_formatted: '0s',
})

const dailyStats = ref([])

const filterTabs = computed(() => [
  { label: 'Tất cả', value: 'all', icon: 'fa-solid fa-border-all' },
  { label: 'Bài học', value: 'lesson', icon: 'fa-solid fa-book-open' },
  { label: 'Luyện tập', value: 'practice', icon: 'fa-solid fa-pen' },
  { label: 'Thi cuối khóa', value: 'final', icon: 'fa-solid fa-file-signature' },
])

const maxDailyCount = computed(() => {
  if (dailyStats.value.length === 0) return 1
  return Math.max(...dailyStats.value.map(d => d.count), 1)
})

const visiblePages = computed(() => {
  const total = pagination.value.total_pages
  const current = pagination.value.current_page
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  const pages = []
  if (current <= 4) {
    for (let i = 1; i <= 5; i++) pages.push(i)
    pages.push('...')
    pages.push(total)
  } else if (current >= total - 3) {
    pages.push(1)
    pages.push('...')
    for (let i = total - 4; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    pages.push('...')
    for (let i = current - 1; i <= current + 1; i++) pages.push(i)
    pages.push('...')
    pages.push(total)
  }
  return pages
})

function getBarHeight(count) {
  if (!count || count === 0) return 4
  return Math.max(Math.round((count / maxDailyCount.value) * 100), 4)
}

function formatDayLabel(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr + 'T00:00:00')
  const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
  return days[date.getDay()]
}

function getTypeIcon(item) {
  if (item.type === 'practice') return 'fa-solid fa-pen'
  if (item.type === 'final') return 'fa-solid fa-file-signature'
  if (item.lesson_type === 'vocabulary') return 'fa-solid fa-spell-check'
  if (item.lesson_type === 'grammar') return 'fa-solid fa-language'
  if (item.lesson_type === 'listening') return 'fa-solid fa-headphones'
  if (item.lesson_type === 'speaking') return 'fa-solid fa-microphone'
  return 'fa-solid fa-book-open'
}

function getTypeLabel(item) {
  if (item.type === 'practice') return 'Luyện tập'
  if (item.type === 'final') return 'Thi cuối khóa'
  if (item.lesson_type === 'vocabulary') return 'Từ vựng'
  if (item.lesson_type === 'grammar') return 'Ngữ pháp'
  if (item.lesson_type === 'listening') return 'Luyện nghe'
  if (item.lesson_type === 'speaking') return 'Luyện nói'
  return 'Bài học'
}

function getTitle(item) {
  if (item.type === 'lesson') return item.title || 'Bài học'
  if (item.type === 'practice') return `Luyện tập - ${item.course_title || 'Khóa học'}`
  if (item.type === 'final') return `Thi cuối khóa - ${item.course_title || 'Khóa học'}`
  return item.title || 'Hoạt động'
}

function getScoreClass(item) {
  if (!item.score && item.score !== 0) return 'neutral'
  if (item.score >= 80) return 'excellent'
  if (item.score >= 60) return 'good'
  if (item.score >= 40) return 'ok'
  return 'low'
}

function formatTime(seconds) {
  if (!seconds) return ''
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  if (h > 0) return `${h}h ${m}m`
  if (m > 0) return `${m}m ${s}s`
  return `${s}s`
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now - date
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) {
    const h = date.getHours().toString().padStart(2, '0')
    const m = date.getMinutes().toString().padStart(2, '0')
    return `Hôm nay lúc ${h}:${m}`
  }
  if (days === 1) return 'Hôm qua'
  if (days < 7) return `${days} ngày trước`

  const d = date.getDate().toString().padStart(2, '0')
  const mo = (date.getMonth() + 1).toString().padStart(2, '0')
  const y = date.getFullYear()
  return `${d}/${mo}/${y}`
}

function changeFilter(filter) {
  activeFilter.value = filter
  currentPage.value = 1
  fetchHistory()
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.total_pages) return
  currentPage.value = page
  fetchHistory()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function goToItem(item) {
  if (item.course_id) {
    router.push(`/student/khoa-hoc/${item.course_id}`)
  }
}

async function fetchHistory() {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage,
    }
    if (activeFilter.value !== 'all') {
      params.type = activeFilter.value
    }
    const res = await ProgressService.getLearningHistory(params)
    if (res.success) {
      historyItems.value = res.data?.items || []
      pagination.value = res.data?.pagination || {}
      stats.value = res.data?.stats || stats.value
      dailyStats.value = res.data?.daily_stats || []
    }
  } catch (err) {
    console.error('Lỗi khi tải lịch sử học tập:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHistory()
})
</script>

<style scoped>
.history-page {
  min-height: 100vh;
  background: var(--bg);
  padding-bottom: 60px;
}

/* ===== PAGE HEADER ===== */
.page-header {
  background: var(--card-bg);
  border-bottom: 1px solid var(--border);
  padding: 28px 0 24px;
}

.header-top {
  margin-bottom: 20px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--text-h);
  margin-bottom: 4px;
}

.page-title i {
  color: var(--accent);
  font-size: 1.4rem;
}

.page-subtitle {
  font-size: 14px;
  color: var(--muted);
  margin: 0;
}

/* Stats Row */
.stats-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 180px;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.stat-icon.blue { background: var(--info-bg); color: var(--info); }
.stat-icon.green { background: var(--success-bg); color: var(--success); }
.stat-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.stat-icon.orange { background: var(--warning-bg); color: var(--warning); }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--text-h);
  line-height: 1.2;
}

.stat-label {
  font-size: 11px;
  color: var(--muted);
  font-weight: 600;
}

/* ===== MAIN CONTENT ===== */
.main-content {
  padding-top: 28px;
}

/* ===== SECTION CARD ===== */
.section-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 20px;
}

.card-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
}

.card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-h);
  margin: 0;
}

.card-title i {
  color: var(--accent);
}

.card-body {
  padding: 20px;
}

/* ===== CHART ===== */
.chart-container {
  width: 100%;
}

.chart-bars {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 140px;
  gap: 12px;
}

.bar-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 1;
  max-width: 70px;
}

.bar-value-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--accent);
}

.bar-wrapper {
  width: 100%;
  height: 110px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.bar-fill {
  width: 55%;
  background: var(--accent);
  border-radius: 6px 6px 4px 4px;
  min-height: 4px;
  transition: height 0.5s ease;
}

.bar-day-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--muted);
}

.chart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 36px;
  text-align: center;
  color: var(--muted);
}

.chart-empty i {
  font-size: 32px;
  color: var(--muted);
}

.chart-empty p {
  font-size: 14px;
  margin: 0;
}

/* ===== FILTER BAR ===== */
.filter-bar {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 8px;
  border: 2px solid var(--border);
  background: var(--card-bg);
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: inherit;
}

.filter-btn i {
  font-size: 12px;
  color: var(--muted);
}

.filter-btn:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.filter-btn:hover i {
  color: var(--accent);
}

.filter-btn.active {
  border-color: var(--accent);
  background: var(--accent);
  color: white;
}

.filter-btn.active i {
  color: white;
}

/* ===== HISTORY LIST ===== */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Loading */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 60px 20px;
  color: var(--muted);
  font-size: 14px;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid var(--border);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Empty */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--bg-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: var(--muted);
}

.empty-state h3 {
  font-size: 18px;
  color: var(--text-h);
  margin: 0;
}

.empty-state p {
  font-size: 14px;
  color: var(--muted);
  margin: 0;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  background: var(--accent);
  color: white;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: var(--accent-hover);
}

/* Items Grid */
.items-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.history-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.history-card:hover {
  border-color: var(--accent-border);
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  transform: translateY(-1px);
}

.card-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: white;
  flex-shrink: 0;
}

.card-icon.lesson { background: var(--info); }
.card-icon.practice { background: var(--success); }
.card-icon.final { background: #8b5cf6; }

.card-content {
  flex: 1;
  min-width: 0;
}

.card-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
}

.type-tag {
  padding: 3px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}

.type-tag.lesson { background: var(--info-bg); color: var(--info); }
.type-tag.practice { background: var(--success-bg); color: var(--success); }
.type-tag.final { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.card-date {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--muted);
}

.card-date i {
  font-size: 11px;
}

.card-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-h);
  margin: 0 0 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-meta {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.card-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--muted);
}

.card-meta i {
  font-size: 10px;
  color: var(--accent);
}

.card-footer {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.badge-score {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-h);
}

.score-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.score-dot.excellent { background: var(--success); }
.score-dot.good { background: var(--info); }
.score-dot.ok { background: var(--warning); }
.score-dot.low { background: var(--danger); }
.score-dot.neutral { background: var(--accent); }

.badge-done {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  color: var(--success);
}

.badge-done i {
  font-size: 12px;
}

.badge-time, .badge-attempt {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--muted);
}

.badge-time i, .badge-attempt i {
  font-size: 11px;
}

.badge-pass span {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 700;
}

.badge-pass .pass { color: var(--success); }
.badge-pass .fail { color: var(--danger); }

.badge-pass i {
  font-size: 12px;
}

.card-arrow {
  color: var(--muted);
  font-size: 14px;
  flex-shrink: 0;
  transition: all 0.2s;
}

.history-card:hover .card-arrow {
  color: var(--accent);
  transform: translateX(3px);
}

/* ===== PAGINATION ===== */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 28px;
}

.pg-btn {
  min-width: 38px;
  height: 38px;
  padding: 0 10px;
  border-radius: 8px;
  border: 2px solid var(--border);
  background: var(--card-bg);
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  font-family: inherit;
}

.pg-btn:hover:not(:disabled) {
  border-color: var(--accent);
  color: var(--accent);
}

.pg-btn.active {
  background: var(--accent);
  border-color: var(--accent);
  color: white;
}

.pg-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.pg-ellipsis {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 38px;
  color: var(--muted);
  font-weight: 600;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .stats-row {
    gap: 12px;
  }
  .stat-item {
    min-width: 160px;
  }
}

@media (max-width: 768px) {
  .page-title {
    font-size: 1.3rem;
  }
  .stats-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .stat-item {
    min-width: unset;
  }
  .chart-bars {
    height: 120px;
    gap: 8px;
  }
  .bar-col {
    max-width: 45px;
  }
  .filter-btn {
    padding: 7px 14px;
    font-size: 12px;
  }
  .card-icon {
    width: 36px;
    height: 36px;
    font-size: 14px;
  }
  .card-title {
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .history-card {
    padding: 14px;
    gap: 12px;
  }
  .card-title {
    font-size: 13px;
    white-space: normal;
  }
  .card-meta {
    gap: 10px;
  }
  .card-footer {
    gap: 8px;
  }
}
</style>
