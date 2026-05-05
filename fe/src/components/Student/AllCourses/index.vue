<template>
  <div class="courses-page">
    <!-- Header -->
    <div class="page-header">
      <h1>Tất cả khóa học</h1>
      <p>Tìm kiếm khóa học phù hợp với bạn</p>
    </div>

    <!-- Filters -->
    <div class="filters">
      <div class="search-box">
        <i class="fa-solid fa-search"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm khóa học..."
        />
      </div>
      <div class="level-filters">
        <button
          v-for="level in levelOptions"
          :key="level.value"
          class="filter-btn"
          :class="{ active: selectedLevel === level.value }"
          @click="selectedLevel = level.value"
        >
          {{ level.label }}
        </button>
      </div>
    </div>

    <!-- Course Grid -->
    <div class="course-container">
      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
      </div>

      <div v-else-if="filteredCourses.length === 0" class="empty">
        <i class="fa-solid fa-book"></i>
        <h3>Không có khóa học</h3>
        <button @click="resetFilters">Xóa bộ lọc</button>
      </div>

      <div v-else class="course-grid">
        <div
          v-for="course in filteredCourses"
          :key="course.id"
          class="course-card"
          @click="goToCourse(course)"
        >
          <div class="card-image">
            <img :src="getThumbnailUrl(course.thumbnail)" :alt="course.title" />
            <div class="card-overlay">
              <i class="fa-solid fa-play"></i>
            </div>
            <span class="level-tag" :class="'level-' + course.level">
              {{ getLevelLabel(course.level) }}
            </span>
          </div>
          <div class="card-body">
            <h3>{{ course.title }}</h3>
            <p>{{ course.description }}</p>
            <div class="card-info">
              <span>
                <i class="fa-solid fa-book"></i>
                {{ course.chapters_count }} chương
              </span>
              <span v-if="course.review_count > 0" class="card-rating">
                <i class="fa-solid fa-star"></i>
                {{ course.avg_rating }}
                <span class="rating-count">({{ course.review_count }})</span>
              </span>
            </div>
            <div v-if="course.is_enrolled && course.progress_percent !== undefined" class="card-progress">
              <div class="progress-info">
                <span>Tiến độ</span>
                <span class="progress-percent">{{ course.progress_percent }}%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: course.progress_percent + '%' }"></div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="price">{{ formatPrice(course.price) }}</div>
            <div
              class="enroll-btn"
              :class="{ enrolled: course.is_enrolled }"
              @click.stop="handleEnroll(course)"
            >
              <span>{{ course.is_enrolled ? 'Học ngay' : 'Đăng ký' }}</span>
              <i :class="course.is_enrolled ? 'fa-solid fa-play' : 'fa-solid fa-arrow-right'"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { CourseService, ProgressService } from '../../../services/api.js'
import { useAuthStore } from '../../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const isLoading = ref(false)
const searchQuery = ref('')
const selectedLevel = ref('')
const allCourses = ref([])
const enrolledCourseIds = ref([])

const fetchAllCourses = async () => {
  isLoading.value = true
  try {
    const res = await CourseService.getAll({ published_only: true })
    allCourses.value = Array.isArray(res) ? res : (res.data || [])
  } catch (error) {
    console.error('Lỗi khi tải khóa học:', error)
    allCourses.value = []
  } finally {
    isLoading.value = false
  }
}

const fetchEnrolledCourseIds = async () => {
  if (!authStore.isAuthenticated) return
  try {
    const res = await ProgressService.getMyCourses()
    enrolledCourseIds.value = (res.data || []).map(c => c.id)
  } catch (error) {
    console.error('Lỗi khi tải khóa đã đăng ký:', error)
  }
}

const handleEnroll = async (course) => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
    return
  }

  if (course.is_enrolled) {
    router.push(`/student/khoa-hoc/${course.id}`)
    return
  }

  // Neu khoa hoc co phi -> chuyen sang trang thanh toan
  if (course.price && Number(course.price) > 0) {
    router.push(`/student/thanh-toan/${course.id}`)
    return
  }

  // Khoa hoc mien phi -> dang ky truc tiep
  try {
    await ProgressService.enroll(course.id)
    enrolledCourseIds.value.push(course.id)
  } catch (error) {
    if (error.response?.status === 401) {
      router.push('/auth/login')
    } else {
      console.error('Lỗi đăng ký khóa học:', error)
    }
  }
}

const filteredCourses = computed(() => {
  let result = allCourses.value.map(course => ({
    ...course,
    is_enrolled: authStore.isAuthenticated && enrolledCourseIds.value.includes(course.id)
  }))

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(c =>
      c.title.toLowerCase().includes(query) ||
      (c.description && c.description.toLowerCase().includes(query))
    )
  }

  if (selectedLevel.value) {
    result = result.filter(c => c.level === selectedLevel.value)
  }

  return result
})

const levelOptions = [
  { value: '', label: 'Tất cả' },
  { value: 'beginner', label: 'Sơ cấp' },
  { value: 'intermediate', label: 'Trung cấp' },
  { value: 'advanced', label: 'Nâng cao' }
]

const getLevelLabel = (level) => {
  const found = levelOptions.find(l => l.value === level)
  return found ? found.label : level
}

const formatPrice = (price) => {
  const numPrice = Number(price)
  if (numPrice === 0 || isNaN(numPrice)) return 'Miễn phí'
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    minimumFractionDigits: 0
  }).format(numPrice)
}

const getThumbnailUrl = (thumbnail) => {
  if (!thumbnail) return 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800'
  if (thumbnail.startsWith('http')) return thumbnail
  return 'http://localhost:8000/uploads/' + thumbnail
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedLevel.value = ''
}

const goToCourse = (course) => {
  if (course.is_enrolled) {
    router.push(`/student/khoa-hoc/${course.id}`)
  }
}

onMounted(async () => {
  await authStore.initAuth()
  await fetchAllCourses()
  await fetchEnrolledCourseIds()
})
</script>

<style scoped>
.courses-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f1f5f9 0%, #ffffff 100%);
  padding-bottom: 80px;
}

.page-header {
  background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #3b82f6 100%);
  padding: 60px 24px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  opacity: 0.3;
}

.page-header h1 {
  font-size: 42px;
  font-weight: 900;
  color: white;
  margin: 0 0 12px;
  position: relative;
  letter-spacing: -0.5px;
}

.page-header p {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.85);
  margin: 0;
  position: relative;
}

.filters {
  max-width: 1200px;
  margin: -30px auto 40px;
  padding: 0 24px;
  position: relative;
  z-index: 10;
}

.search-box {
  background: white;
  border-radius: 16px;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  margin-bottom: 20px;
  border: 1px solid #e2e8f0;
}

.search-box i {
  color: #94a3b8;
  font-size: 16px;
}

.search-box input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #1e293b;
  background: transparent;
  font-family: inherit;
}

.search-box input::placeholder {
  color: #94a3b8;
}

.level-filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 8px 20px;
  border-radius: 999px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.filter-btn:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

.filter-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.course-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.course-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 28px;
}

.course-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  cursor: pointer;
  transition: all 0.3s;
  border: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
}

.course-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
  border-color: #dbeafe;
}

.card-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s;
}

.course-card:hover .card-image img {
  transform: scale(1.05);
}

.card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(30, 58, 138, 0.7) 0%, transparent 60%);
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
  padding: 16px;
  opacity: 0;
  transition: opacity 0.3s;
}

.course-card:hover .card-overlay {
  opacity: 1;
}

.card-overlay i {
  width: 44px;
  height: 44px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3b82f6;
  font-size: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.level-tag {
  position: absolute;
  top: 14px;
  left: 14px;
  padding: 5px 14px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: white;
}

.level-tag.level-beginner { background: #22c55e; }
.level-tag.level-intermediate { background: #f59e0b; }
.level-tag.level-advanced { background: #ef4444; }

.card-body {
  padding: 20px;
  flex: 1;
}

.card-body h3 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-body p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 12px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-info {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #94a3b8;
}

.card-info span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.card-rating {
  color: #f59e0b !important;
  font-weight: 700;
}

.rating-count {
  color: #94a3b8 !important;
  font-weight: 400;
}

.card-progress {
  margin-top: 14px;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 6px;
}

.progress-percent {
  font-weight: 700;
  color: #3b82f6;
}

.progress-bar {
  height: 6px;
  background: #f1f5f9;
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
  border-radius: 999px;
  transition: width 0.5s ease;
}

.card-footer {
  padding: 16px 20px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fafafa;
}

.price {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
}

.enroll-btn {
  padding: 10px 20px;
  border-radius: 999px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.25s;
  border: none;
  font-family: inherit;
  box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
}

.enroll-btn:hover {
  transform: translateY(-2px) scale(1.03);
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.45);
}

.enroll-btn:active {
  transform: scale(0.97);
}

.enroll-btn.enrolled {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
}

.enroll-btn.enrolled:hover {
  box-shadow: 0 8px 24px rgba(34, 197, 94, 0.45);
}

.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.spinner {
  width: 44px;
  height: 44px;
  border: 4px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty {
  text-align: center;
  padding: 80px 24px;
  grid-column: 1 / -1;
}

.empty i {
  font-size: 56px;
  color: #cbd5e1;
  margin-bottom: 20px;
  display: block;
}

.empty h3 {
  font-size: 20px;
  font-weight: 700;
  color: #64748b;
  margin: 0 0 12px;
}

.empty button {
  padding: 10px 24px;
  border-radius: 999px;
  border: 2px solid #3b82f6;
  background: transparent;
  color: #3b82f6;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.empty button:hover {
  background: #3b82f6;
  color: white;
}

@media (max-width: 768px) {
  .page-header {
    padding: 40px 20px;
  }

  .page-header h1 {
    font-size: 28px;
  }

  .course-grid {
    grid-template-columns: 1fr;
  }

  .filters {
    margin-top: -20px;
  }
}
</style>
