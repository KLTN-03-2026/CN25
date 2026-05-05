<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <div class="header-text">
        <h1 class="page-title">Tổng Quan Dashboard</h1>
        <p class="page-subtitle">Chào mừng quay trở lại, Admin Nguyễn! 👋</p>
      </div>
      <div class="header-actions">
        <select v-model="selectedPeriod" class="period-select">
          <option value="today">Hôm nay</option>
          <option value="week">7 ngày qua</option>
          <option value="month" selected>30 ngày qua</option>
          <option value="quarter">Quý này</option>
          <option value="year">Năm nay</option>
        </select>
        <button class="btn-refresh" @click="refreshData">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"/>
            <polyline points="1 20 1 14 7 14"/>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
          </svg>
          Làm mới
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div v-for="stat in statsCards" :key="stat.label" :class="['stat-card', stat.color]">
        <div class="stat-icon">{{ stat.icon }}</div>
        <div class="stat-info">
          <span class="stat-value">{{ stat.value }}</span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
        <div :class="['stat-change', stat.trend === 'up' ? 'up' : 'down']">
          <svg v-if="stat.trend === 'up'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="18 15 12 9 6 15"/>
          </svg>
          <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
          {{ stat.change }}
        </div>
      </div>
    </div>

    <div class="charts-row">
      <div class="chart-card revenue-chart">
        <div class="chart-header">
          <div>
            <h3 class="chart-title">Doanh Thu</h3>
            <p class="chart-subtitle">Doanh thu theo tháng</p>
          </div>
          <div class="chart-legend">
            <span class="legend-item"><span class="dot primary"></span>Doanh thu</span>
            <span class="legend-item"><span class="dot secondary"></span>Chi phí</span>
          </div>
        </div>
        <div class="chart-area">
          <div class="chart-bars">
            <div v-for="month in revenueData" :key="month.name" class="bar-group">
              <div class="bar-stack">
                <div class="bar primary" :style="{ height: month.revenue + '%' }" :title="month.revenueValue"></div>
                <div class="bar secondary" :style="{ height: month.cost + '%' }" :title="month.costValue"></div>
              </div>
              <span class="bar-label">{{ month.name }}</span>
            </div>
          </div>
          <div class="chart-y-axis">
            <span>100M</span>
            <span>75M</span>
            <span>50M</span>
            <span>25M</span>
            <span>0</span>
          </div>
        </div>
      </div>

      <div class="chart-card enrollment-chart">
        <div class="chart-header">
          <div>
            <h3 class="chart-title">Lượt Đăng Ký</h3>
            <p class="chart-subtitle">Số học viên mới theo tháng</p>
          </div>
        </div>
        <div class="chart-area line-area">
          <svg viewBox="0 0 400 180" class="line-chart" preserveAspectRatio="none">
            <defs>
              <linearGradient id="lineGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#4f46e5" stop-opacity="0.3"/>
                <stop offset="100%" stop-color="#4f46e5" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <path d="M0,140 L57,100 L114,120 L171,60 L228,80 L285,40 L342,55 L400,20 L400,180 L0,180 Z" fill="url(#lineGrad)"/>
            <path d="M0,140 L57,100 L114,120 L171,60 L228,80 L285,40 L342,55 L400,20" fill="none" stroke="#4f46e5" stroke-width="2.5"/>
            <circle cx="0" cy="140" r="4" fill="#4f46e5"/>
            <circle cx="57" cy="100" r="4" fill="#4f46e5"/>
            <circle cx="114" cy="120" r="4" fill="#4f46e5"/>
            <circle cx="171" cy="60" r="4" fill="#4f46e5"/>
            <circle cx="228" cy="80" r="4" fill="#4f46e5"/>
            <circle cx="285" cy="40" r="4" fill="#4f46e5"/>
            <circle cx="342" cy="55" r="4" fill="#4f46e5"/>
            <circle cx="400" cy="20" r="4" fill="#4f46e5"/>
          </svg>
          <div class="line-labels">
            <span>T1</span><span>T2</span><span>T3</span><span>T4</span><span>T5</span><span>T6</span><span>T7</span><span>T8</span>
          </div>
        </div>
      </div>
    </div>

    <div class="bottom-row">
      <div class="activity-card">
        <div class="card-header">
          <h3 class="card-title">Hoạt Động Gần Đây</h3>
          <router-link to="/admin/nguoi-dung" class="view-all">Xem tất cả</router-link>
        </div>
        <div class="activity-list">
          <div v-for="act in recentActivities" :key="act.id" class="activity-item">
            <div :class="['activity-icon', act.type]">{{ act.icon }}</div>
            <div class="activity-content">
              <p class="activity-text"><strong>{{ act.user }}</strong> {{ act.action }}</p>
              <span class="activity-time">{{ act.time }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="top-courses-card">
        <div class="card-header">
          <h3 class="card-title">Khoá Học Phổ Biến</h3>
          <router-link to="/admin/khoa-hoc" class="view-all">Xem tất cả</router-link>
        </div>
        <div class="courses-list">
          <div v-for="course in topCourses" :key="course.id" class="course-item">
            <div class="course-rank">{{ course.rank }}</div>
            <div class="course-info">
              <span class="course-name">{{ course.name }}</span>
              <div class="course-meta">
                <span class="course-students">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                  {{ course.students }} học viên
                </span>
                <div class="course-rating">
                  <span class="stars">★★★★★</span>
                  <span class="rating">{{ course.rating }}</span>
                </div>
              </div>
            </div>
            <div class="course-progress-wrap">
              <span class="course-percent">{{ course.progress }}%</span>
              <div class="course-progress-bar">
                <div class="course-progress-fill" :style="{ width: course.progress + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="quick-actions-card">
        <div class="card-header">
          <h3 class="card-title">Thao Tác Nhanh</h3>
        </div>
        <div class="quick-actions">
          <router-link v-for="action in quickActions" :key="action.label" :to="action.path" class="quick-action-btn">
            <span class="qa-icon">{{ action.icon }}</span>
            <span class="qa-label">{{ action.label }}</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PublicService } from '../../../services/api.js'

const selectedPeriod = ref('month')

const statsCards = ref([
  { label: 'Tổng Doanh Thu', value: '...', icon: 'Rp', color: 'green', trend: 'up', change: '–' },
  { label: 'Người Dùng', value: '...', icon: 'Us', color: 'blue', trend: 'up', change: '–' },
  { label: 'Khóa Học', value: '...', icon: 'Kh', color: 'purple', trend: 'up', change: '–' },
  { label: 'Yêu Cầu Chờ', value: '...', icon: 'YC', color: 'orange', trend: 'up', change: '–' },
])
const revenueData = ref([])
const recentActivities = ref([])
const topCourses = ref([])
const quickActions = ref([])

const formatCurrency = (value) => {
  if (!value) return '0đ'
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0,
  }).format(value)
}

const loadStats = async () => {
  try {
    const res = await PublicService.getStats()
    const stats = res.data || res

    statsCards.value = [
      { label: 'Tổng Doanh Thu', value: formatCurrency(stats.total_revenue || 0), icon: 'Rp', color: 'green', trend: 'up', change: `${stats.success_count || 0} giao dịch` },
      { label: 'Người Dùng', value: (stats.total_students || 0).toLocaleString('vi-VN'), icon: 'Us', color: 'blue', trend: 'up', change: 'học viên' },
      { label: 'Khóa Học', value: (stats.total_courses || 0).toLocaleString('vi-VN'), icon: 'Kh', color: 'purple', trend: 'up', change: 'khóa học' },
      { label: 'Yêu Cầu Chờ', value: stats.pending_count || 0, icon: 'YC', color: 'orange', trend: 'up', change: `${stats.failed_count || 0} từ chối` },
    ]
  } catch (err) {
    console.error('Lỗi load stats:', err)
  }
}

const refreshData = () => {
  loadStats()
}

onMounted(() => {
  loadStats()
})
</script>

<style scoped>
.dashboard {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
}

.page-subtitle {
  margin: 4px 0 0;
  font-size: 14px;
  color: #9ca3af;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.period-select {
  padding: 8px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  color: #374151;
  background: white;
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
}

.period-select:focus {
  border-color: #4f46e5;
}

.btn-refresh {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-refresh:hover { background: #4338ca; }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 14px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  overflow: hidden;
  border: 1px solid #f3f4f6;
  transition: all 0.2s;
}

.stat-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transform: translateY(-2px);
}

.stat-card.blue { border-left: 4px solid #3b82f6; }
.stat-card.purple { border-left: 4px solid #8b5cf6; }
.stat-card.green { border-left: 4px solid #10b981; }
.stat-card.orange { border-left: 4px solid #f59e0b; }

.stat-icon {
  font-size: 14px;
  font-weight: 700;
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  letter-spacing: -0.5px;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-value {
  display: block;
  font-size: 26px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.stat-label {
  display: block;
  font-size: 13px;
  color: #9ca3af;
  margin-top: 4px;
}

.stat-change {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
  flex-shrink: 0;
}

.stat-change.up {
  color: #10b981;
  background: #d1fae5;
}

.stat-change.down {
  color: #ef4444;
  background: #fee2e2;
}

.charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.chart-card {
  background: white;
  border-radius: 14px;
  padding: 24px;
  border: 1px solid #f3f4f6;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.chart-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.chart-subtitle {
  margin: 4px 0 0;
  font-size: 13px;
  color: #9ca3af;
}

.chart-legend {
  display: flex;
  gap: 16px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot.primary { background: #4f46e5; }
.dot.secondary { background: #c7d2fe; }

.chart-area {
  position: relative;
  height: 200px;
  padding-left: 48px;
}

.chart-bars {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  height: 100%;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 8px;
}

.bar-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 1;
}

.bar-stack {
  display: flex;
  flex-direction: column-reverse;
  gap: 2px;
  height: 160px;
  justify-content: flex-start;
}

.bar {
  width: 28px;
  border-radius: 4px 4px 0 0;
  transition: opacity 0.2s;
  cursor: pointer;
}

.bar:hover { opacity: 0.8; }

.bar.primary { background: linear-gradient(180deg, #4f46e5, #7c3aed); }
.bar.secondary { background: #c7d2fe; }

.bar-label {
  font-size: 11px;
  color: #9ca3af;
  font-weight: 500;
}

.chart-y-axis {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 28px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-end;
  padding-right: 8px;
}

.chart-y-axis span {
  font-size: 10px;
  color: #d1d5db;
}

.line-area {
  padding: 0;
  height: 220px;
}

.line-chart {
  width: 100%;
  height: 180px;
}

.line-labels {
  display: flex;
  justify-content: space-between;
  padding: 0 4px;
  margin-top: 4px;
}

.line-labels span {
  font-size: 11px;
  color: #9ca3af;
  flex: 1;
  text-align: center;
}

.bottom-row {
  display: grid;
  grid-template-columns: 1fr 1fr 280px;
  gap: 20px;
}

.activity-card,
.top-courses-card,
.quick-actions-card {
  background: white;
  border-radius: 14px;
  padding: 20px;
  border: 1px solid #f3f4f6;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.card-title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.view-all {
  font-size: 13px;
  color: #4f46e5;
  text-decoration: none;
  font-weight: 500;
}

.view-all:hover { text-decoration: underline; }

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.activity-item {
  display: flex;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #f9fafb;
}

.activity-item:last-child { border-bottom: none; }

.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.activity-icon.payment { background: #dbeafe; }
.activity-icon.user { background: #d1fae5; }
.activity-icon.course { background: #fef3c7; }
.activity-icon.review { background: #fce7f3; }
.activity-icon.exam { background: #e0e7ff; }

.activity-content { flex: 1; min-width: 0; }

.activity-text {
  margin: 0;
  font-size: 13px;
  color: #4b5563;
  line-height: 1.5;
}

.activity-time {
  font-size: 11px;
  color: #9ca3af;
}

.courses-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.course-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.course-rank {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  color: #6b7280;
  flex-shrink: 0;
}

.course-item:nth-child(1) .course-rank { background: #fef3c7; color: #d97706; }
.course-item:nth-child(2) .course-rank { background: #f3f4f6; color: #6b7280; }
.course-item:nth-child(3) .course-rank { background: #fef3c7; color: #d97706; }

.course-info { flex: 1; min-width: 0; }

.course-name {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.course-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 2px;
}

.course-students {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 11px;
  color: #9ca3af;
}

.course-rating {
  display: flex;
  align-items: center;
  gap: 2px;
}

.stars {
  font-size: 10px;
  color: #fbbf24;
  letter-spacing: -1px;
}

.rating {
  font-size: 11px;
  color: #6b7280;
  font-weight: 600;
}

.course-progress-wrap {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  flex-shrink: 0;
}

.course-percent {
  font-size: 11px;
  font-weight: 600;
  color: #4f46e5;
}

.course-progress-bar {
  width: 60px;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.course-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #7c3aed);
  border-radius: 2px;
  transition: width 0.5s ease;
}

.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.quick-action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 14px 8px;
  background: #f9fafb;
  border-radius: 10px;
  text-decoration: none;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.quick-action-btn:hover {
  background: #eff6ff;
  border-color: #c7d2fe;
  transform: translateY(-1px);
}

.qa-icon {
  font-size: 22px;
}

.qa-label {
  font-size: 11px;
  color: #4b5563;
  text-align: center;
  line-height: 1.3;
  font-weight: 500;
}

@media (max-width: 1280px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .charts-row { grid-template-columns: 1fr; }
  .bottom-row { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr; }
  .bottom-row { grid-template-columns: 1fr; }
  .dashboard-header { flex-direction: column; align-items: flex-start; gap: 12px; }
}
</style>
