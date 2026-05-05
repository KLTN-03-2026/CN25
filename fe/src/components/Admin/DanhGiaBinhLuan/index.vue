<template>
  <div class="danh-gia-container">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon">
          <i class="fa-solid fa-star-and-crescent"></i>
        </div>
        <div class="header-text">
          <h1 class="page-title">Quản Lý Đánh Giá & Bình Luận</h1>
          <p class="page-subtitle">Kiểm duyệt và quản lý đánh giá từ học viên</p>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
      <div class="stat-card stat-total">
        <div class="stat-icon">
          <i class="fa-solid fa-comments"></i>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.total }}</span>
          <span class="stat-label">Tổng đánh giá</span>
        </div>
      </div>
      <div class="stat-card stat-pending">
        <div class="stat-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.pending }}</span>
          <span class="stat-label">Chờ duyệt</span>
        </div>
      </div>
      <div class="stat-card stat-approved">
        <div class="stat-icon">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.approved }}</span>
          <span class="stat-label">Đã duyệt</span>
        </div>
      </div>
      <div class="stat-card stat-hidden">
        <div class="stat-icon">
          <i class="fa-solid fa-eye-slash"></i>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ stats.hidden }}</span>
          <span class="stat-label">Đã ẩn</span>
        </div>
      </div>
      <div class="stat-card stat-rating">
        <div class="stat-icon">
          <i class="fa-solid fa-star"></i>
        </div>
        <div class="stat-info">
          <span class="stat-number">{{ formatAvgRating(stats.avg_rating) }}</span>
          <span class="stat-label">Sao TB</span>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm theo tên, email, nội dung..."
          @input="debounceSearch"
        />
        <button v-if="searchQuery" class="btn-clear-search" @click="searchQuery = ''; fetchReviews()">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <select v-model="filterStatus" class="filter-select" @change="fetchReviews()">
        <option value="">Tất cả trạng thái</option>
        <option value="cho">Chờ duyệt</option>
        <option value="duyet">Đã duyệt</option>
        <option value="an">Ẩn</option>
      </select>
      <select v-model="filterCourse" class="filter-select" @change="fetchReviews()">
        <option value="">Tất cả khóa học</option>
        <option v-for="course in courses" :key="course.id" :value="course.id">
          {{ course.title }}
        </option>
      </select>
      <button class="btn-refresh" @click="fetchReviews(); fetchStats()" title="Làm mới">
        <i class="fa-solid fa-rotate"></i>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <span>Đang tải dữ liệu...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredBinhLuan.length === 0" class="empty-state">
      <div class="empty-icon">
        <i class="fa-solid fa-inbox"></i>
      </div>
      <h3>Không tìm thấy đánh giá nào</h3>
      <p>{{ searchQuery || filterStatus || filterCourse ? 'Thử thay đổi bộ lọc tìm kiếm.' : 'Chưa có đánh giá nào được gửi.' }}</p>
    </div>

    <!-- Reviews List -->
    <div v-else class="reviews-list">
      <div
        v-for="item in paginatedReviews"
        :key="item.id"
        class="review-card"
        :class="'status-' + item.status"
      >
        <!-- Card Header -->
        <div class="card-header">
          <div class="user-section">
            <div class="avatar-wrapper">
              <img
                v-if="item.user?.avatar"
                :src="getAvatarUrl(item.user.avatar)"
                :alt="item.user.name"
                class="avatar-img"
              />
              <div v-else class="avatar-placeholder">
                {{ getInitials(item.user?.name) }}
              </div>
              <span v-if="item.status === 'cho'" class="pending-dot" title="Chờ duyệt"></span>
            </div>
            <div class="user-details">
              <span class="user-name">{{ item.user?.name || 'Học viên' }}</span>
              <span class="user-email">{{ item.user?.email || '-' }}</span>
            </div>
          </div>
          <div class="meta-section">
            <div class="course-badge">
              <i class="fa-solid fa-graduation-cap"></i>
              <span>{{ item.course?.title || 'Khóa học' }}</span>
            </div>
            <span class="status-badge" :class="item.status">
              <i :class="getStatusIcon(item.status)"></i>
              {{ getStatusLabel(item.status) }}
            </span>
            <span class="review-date">{{ formatDate(item.created_at) }}</span>
          </div>
        </div>

        <!-- Rating Stars -->
        <div class="rating-row">
          <div class="stars-display">
            <i
              v-for="n in 5"
              :key="n"
              class="fa-solid fa-star"
              :class="{ filled: n <= item.rating }"
            ></i>
          </div>
          <span class="rating-value">{{ item.rating }}/5</span>
        </div>

        <!-- Content -->
        <div class="card-body">
          <p class="review-content">{{ item.content }}</p>
        </div>

        <!-- Admin Reply -->
        <div v-if="item.admin_reply" class="admin-reply-block">
          <div class="reply-header">
            <i class="fa-solid fa-user-shield"></i>
            <span>Phản hồi từ quản trị viên</span>
          </div>
          <p class="reply-content">{{ item.admin_reply }}</p>
        </div>

        <!-- Card Footer -->
        <div class="card-footer">
          <div class="footer-left">
            <button
              v-if="item.status === 'cho'"
              class="action-btn btn-approve"
              @click="duyetBinhLuan(item.id)"
              title="Duyệt đánh giá"
            >
              <i class="fa-solid fa-check"></i>
              <span>Duyệt</span>
            </button>
            <button
              v-if="item.status === 'an'"
              class="action-btn btn-unhide"
              @click="duyetBinhLuan(item.id)"
              title="Khôi phục"
            >
              <i class="fa-solid fa-eye"></i>
              <span>Hiển thị</span>
            </button>
            <button
              v-if="item.status === 'duyet'"
              class="action-btn btn-hide"
              @click="anBinhLuan(item.id)"
              title="Ẩn đánh giá"
            >
              <i class="fa-solid fa-eye-slash"></i>
              <span>Ẩn</span>
            </button>
          </div>
          <div class="footer-right">
            <button
              class="action-btn btn-reply"
              @click="openReplyModal(item)"
              title="Trả lời"
            >
              <i class="fa-solid fa-reply"></i>
              <span>{{ item.admin_reply ? 'Sửa phản hồi' : 'Trả lời' }}</span>
            </button>
            <button
              class="action-btn btn-delete"
              @click="deleteBinhLuan(item.id)"
              title="Xóa đánh giá"
            >
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination-bar">
      <span class="pagination-info">
        Hiển thị {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, filteredBinhLuan.length) }}
        trong {{ filteredBinhLuan.length }} đánh giá
      </span>
      <div class="pagination-controls">
        <button
          class="page-btn"
          :disabled="currentPage <= 1"
          @click="currentPage--"
        >
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button
          v-for="page in visiblePages"
          :key="page"
          class="page-btn"
          :class="{ active: page === currentPage }"
          @click="currentPage = page"
        >
          {{ page }}
        </button>
        <button
          class="page-btn"
          :disabled="currentPage >= totalPages"
          @click="currentPage++"
        >
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Reply Modal -->
    <div v-if="showReplyModalFlag" class="modal-overlay" @click.self="closeReplyModal">
      <div class="reply-modal">
        <div class="modal-header">
          <div class="modal-title-group">
            <i class="fa-solid fa-reply modal-icon"></i>
            <h2>Phản hồi đánh giá</h2>
          </div>
          <button class="btn-close-modal" @click="closeReplyModal">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="replyingTo" class="original-review">
            <div class="original-user">
              <strong>{{ replyingTo.user?.name }}</strong>
              <span class="original-stars">
                <i v-for="n in 5" :key="n" class="fa-solid fa-star" :class="{ filled: n <= replyingTo.rating }"></i>
              </span>
            </div>
            <p class="original-content">{{ replyingTo.content }}</p>
          </div>
          <div class="form-group">
            <label>Nội dung phản hồi</label>
            <textarea
              v-model="replyContent"
              rows="5"
              placeholder="Nhập nội dung phản hồi của bạn..."
              maxlength="2000"
            ></textarea>
            <span class="char-counter">{{ replyContent.length }}/2000</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeReplyModal">Hủy</button>
          <button class="btn-primary" :disabled="!replyContent.trim()" @click="sendReply">
            <i class="fa-solid fa-paper-plane"></i>
            Gửi phản hồi
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <transition name="toast">
      <div v-if="toast.show" class="toast-notification" :class="toast.type">
        <i :class="toast.icon"></i>
        <span>{{ toast.message }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ReviewService, CourseService } from '../../../services/api.js'

const loading = ref(false)
const searchQuery = ref('')
const filterStatus = ref('')
const filterCourse = ref('')
const showReplyModalFlag = ref(false)
const replyingTo = ref(null)
const replyContent = ref('')
const debounceTimer = ref(null)

const binhLuanList = ref([])
const courses = ref([])
const stats = ref({ total: 0, pending: 0, approved: 0, hidden: 0, avg_rating: 0 })

const currentPage = ref(1)
const perPage = 10

// Toast
const toast = ref({ show: false, message: '', type: 'success', icon: '' })
const showToast = (message, type = 'success') => {
  const icons = {
    success: 'fa-solid fa-circle-check',
    error: 'fa-solid fa-circle-xmark',
    warning: 'fa-solid fa-triangle-exclamation'
  }
  toast.value = { show: true, message, type, icon: icons[type] || icons.success }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const openReplyModal = (item) => {
  replyingTo.value = item
  replyContent.value = item.admin_reply || ''
  showReplyModalFlag.value = true
}

const closeReplyModal = () => {
  showReplyModalFlag.value = false
  replyingTo.value = null
  replyContent.value = ''
}

// Fetch courses for dropdown
const fetchCourses = async () => {
  try {
    const res = await CourseService.getAll({ status: 'published', per_page: 100 })
    courses.value = res.data || res || []
  } catch (error) {
    console.error('Lỗi khi tải khóa học:', error)
  }
}

// Fetch reviews
const fetchReviews = async () => {
  loading.value = true
  try {
    const params = { per_page: 200 }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterCourse.value) params.course_id = filterCourse.value
    if (searchQuery.value) params.search = searchQuery.value
    const data = await ReviewService.getAll(params)
    binhLuanList.value = data.data || data || []
    currentPage.value = 1
  } catch (error) {
    console.error('Lỗi khi tải đánh giá:', error)
  } finally {
    loading.value = false
  }
}

// Fetch stats
const fetchStats = async () => {
  try {
    const data = await ReviewService.getStats()
    stats.value = data
  } catch (error) {
    console.error('Lỗi khi tải thống kê:', error)
  }
}

const debounceSearch = () => {
  clearTimeout(debounceTimer.value)
  debounceTimer.value = setTimeout(() => {
    fetchReviews()
  }, 400)
}

// Filtered reviews
const filteredBinhLuan = computed(() => binhLuanList.value)

// Pagination
const totalPages = computed(() => Math.ceil(filteredBinhLuan.value.length / perPage))
const paginatedReviews = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredBinhLuan.value.slice(start, start + perPage)
})
const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  let start = Math.max(1, current - 2)
  let end = Math.min(total, current + 2)
  if (end - start < 4) {
    if (start === 1) end = Math.min(total, start + 4)
    else if (end === total) start = Math.max(1, end - 4)
  }
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

// Status helpers
const getStatusLabel = (status) => {
  const labels = { duyet: 'Đã duyệt', cho: 'Chờ duyệt', an: 'Ẩn' }
  return labels[status] || status
}
const getStatusIcon = (status) => {
  const icons = { duyet: 'fa-solid fa-check', cho: 'fa-solid fa-clock', an: 'fa-solid fa-eye-slash' }
  return icons[status] || 'fa-solid fa-circle'
}

// Actions
const duyetBinhLuan = async (id) => {
  try {
    await ReviewService.approve(id)
    await fetchReviews()
    await fetchStats()
    showToast('Duyệt đánh giá thành công!', 'success')
  } catch (error) {
    showToast('Lỗi khi duyệt đánh giá.', 'error')
  }
}

const anBinhLuan = async (id) => {
  try {
    await ReviewService.hide(id)
    await fetchReviews()
    await fetchStats()
    showToast('Đã ẩn đánh giá.', 'success')
  } catch (error) {
    showToast('Lỗi khi ẩn đánh giá.', 'error')
  }
}

const sendReply = async () => {
  if (!replyContent.value.trim()) return
  try {
    await ReviewService.reply(replyingTo.value.id, { admin_reply: replyContent.value })
    closeReplyModal()
    await fetchReviews()
    showToast('Gửi phản hồi thành công!', 'success')
  } catch (error) {
    showToast('Lỗi khi gửi phản hồi.', 'error')
  }
}

const deleteBinhLuan = async (id) => {
  if (!confirm('Bạn có chắc chắn muốn xóa đánh giá này? Hành động này không thể hoàn tác.')) return
  try {
    await ReviewService.delete(id)
    await fetchReviews()
    await fetchStats()
    showToast('Xóa đánh giá thành công!', 'success')
  } catch (error) {
    showToast('Lỗi khi xóa đánh giá.', 'error')
  }
}

// Utils
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('vi-VN', { day: 'numeric', month: 'short', year: 'numeric' })
}
const formatAvgRating = (val) => {
  if (!val && val !== 0) return '0.0'
  return Number(val).toFixed(1)
}
const getAvatarUrl = (avatar) => {
  if (!avatar) return ''
  return avatar.startsWith('http') ? avatar : 'http://localhost:8000/uploads/' + avatar
}
const getInitials = (name) => {
  if (!name) return 'HV'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[parts.length - 1].charAt(0) + parts[0].charAt(0)).toUpperCase()
}

onMounted(() => {
  fetchReviews()
  fetchStats()
  fetchCourses()
})
</script>

<style scoped>
/* Container */
.danh-gia-container {
  padding: 28px 32px;
  max-width: 1200px;
  margin: 0 auto;
  font-family: inherit;
}

/* Page Header */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: white;
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.header-text .page-title {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 4px;
}

.header-text .page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* Stats Row */
.stats-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #f1f5f9;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
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

.stat-total .stat-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed; }
.stat-pending .stat-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
.stat-approved .stat-icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
.stat-hidden .stat-icon { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
.stat-rating .stat-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #f59e0b; }

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-number {
  font-size: 24px;
  font-weight: 900;
  color: #1e293b;
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

/* Filter Bar */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 280px;
  max-width: 400px;
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  font-size: 14px;
}

.search-box input {
  width: 100%;
  padding: 10px 40px 10px 38px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  color: #1e293b;
  background: white;
  transition: border-color 0.2s;
  font-family: inherit;
}

.search-box input:focus {
  outline: none;
  border-color: #6366f1;
}

.btn-clear-search {
  position: absolute;
  right: 10px;
  width: 24px;
  height: 24px;
  border: none;
  background: #f1f5f9;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-size: 11px;
  transition: all 0.2s;
}

.btn-clear-search:hover {
  background: #e2e8f0;
  color: #475569;
}

.filter-select {
  padding: 10px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  color: #475569;
  background: white;
  cursor: pointer;
  transition: border-color 0.2s;
  font-family: inherit;
  min-width: 150px;
}

.filter-select:focus {
  outline: none;
  border-color: #6366f1;
}

.btn-refresh {
  width: 42px;
  height: 42px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-size: 15px;
  transition: all 0.2s;
}

.btn-refresh:hover {
  border-color: #6366f1;
  color: #6366f1;
}

/* Loading */
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px;
  color: #64748b;
  font-size: 14px;
}

.loading-spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 24px;
  background: white;
  border: 2px dashed #e2e8f0;
  border-radius: 20px;
  margin-bottom: 24px;
}

.empty-icon {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  font-size: 32px;
  color: #94a3b8;
}

.empty-state h3 {
  font-size: 18px;
  font-weight: 700;
  color: #374151;
  margin: 0 0 8px;
}

.empty-state p {
  font-size: 14px;
  color: #9ca3af;
  margin: 0;
}

/* Reviews List */
.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

.review-card {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #f1f5f9;
  transition: box-shadow 0.2s, border-color 0.2s;
}

.review-card:hover {
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
}

.review-card.status-cho {
  border-left: 4px solid #f59e0b;
}

.review-card.status-duyet {
  border-left: 4px solid #22c55e;
}

.review-card.status-an {
  border-left: 4px solid #dc2626;
  opacity: 0.75;
}

/* Card Header */
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  flex-wrap: wrap;
  gap: 12px;
}

.user-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-wrapper {
  position: relative;
  flex-shrink: 0;
}

.avatar-img {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  object-fit: cover;
}

.avatar-placeholder {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  font-size: 15px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pending-dot {
  position: absolute;
  top: -3px;
  right: -3px;
  width: 12px;
  height: 12px;
  background: #f59e0b;
  border-radius: 50%;
  border: 2px solid white;
}

.user-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

.user-email {
  font-size: 12px;
  color: #94a3b8;
}

.meta-section {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.course-badge {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 12px;
  color: #475569;
  font-weight: 500;
}

.course-badge i {
  color: #6366f1;
  font-size: 11px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge i { font-size: 10px; }

.status-badge.duyet { background: #d1fae5; color: #059669; }
.status-badge.cho { background: #fef3c7; color: #d97706; }
.status-badge.an { background: #fee2e2; color: #dc2626; }

.review-date {
  font-size: 12px;
  color: #94a3b8;
}

/* Rating */
.rating-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.stars-display {
  display: flex;
  gap: 3px;
}

.stars-display i {
  font-size: 14px;
  color: #e2e8f0;
}

.stars-display i.filled {
  color: #f59e0b;
}

.rating-value {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

/* Card Body */
.card-body {
  margin-bottom: 14px;
}

.review-content {
  font-size: 14px;
  color: #475569;
  line-height: 1.6;
  margin: 0;
}

/* Admin Reply */
.admin-reply-block {
  margin-bottom: 14px;
  padding: 14px 16px;
  background: #f8fafc;
  border-left: 3px solid #6366f1;
  border-radius: 0 12px 12px 0;
}

.reply-header {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  color: #6366f1;
  margin-bottom: 6px;
}

.reply-header i { font-size: 13px; }

.reply-content {
  font-size: 13px;
  color: #475569;
  line-height: 1.5;
  margin: 0;
}

/* Card Footer */
.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
  gap: 8px;
  flex-wrap: wrap;
}

.footer-left, .footer-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-approve {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  color: #059669;
}

.btn-approve:hover { background: #a7f3d0; transform: translateY(-1px); }

.btn-unhide {
  background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
  color: #4f46e5;
}

.btn-unhide:hover { background: #c7d2fe; transform: translateY(-1px); }

.btn-hide {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  color: #d97706;
}

.btn-hide:hover { background: #fde68a; transform: translateY(-1px); }

.btn-reply {
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  color: #7c3aed;
}

.btn-reply:hover { background: #ddd6fe; transform: translateY(-1px); }

.btn-delete {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
  color: #dc2626;
  padding: 7px 12px;
}

.btn-delete:hover { background: #fecaca; transform: translateY(-1px); }

/* Pagination */
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  flex-wrap: wrap;
  gap: 12px;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 6px;
}

.page-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s;
  font-family: inherit;
}

.page-btn:hover:not(:disabled) {
  border-color: #6366f1;
  color: #6366f1;
}

.page-btn.active {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-color: transparent;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.reply-modal {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 560px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  border-bottom: 1px solid #e9d5ff;
}

.modal-title-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-icon {
  font-size: 20px;
  color: #7c3aed;
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #5b21b6;
}

.btn-close-modal {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: rgba(124, 58, 237, 0.1);
  color: #7c3aed;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}

.btn-close-modal:hover { background: rgba(124, 58, 237, 0.2); }

.modal-body {
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.original-review {
  background: #f8fafc;
  border-radius: 12px;
  padding: 14px 16px;
  border: 1px solid #e2e8f0;
}

.original-user {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}

.original-stars i {
  font-size: 11px;
  color: #e2e8f0;
}

.original-stars i.filled { color: #f59e0b; }

.original-content {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 700;
  color: #374151;
}

.form-group textarea {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  color: #1e293b;
  resize: vertical;
  font-family: inherit;
  transition: border-color 0.2s;
  box-sizing: border-box;
  min-height: 120px;
}

.form-group textarea:focus {
  outline: none;
  border-color: #6366f1;
}

.char-counter {
  font-size: 12px;
  color: #9ca3af;
  text-align: right;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid #f1f5f9;
}

.btn-secondary {
  padding: 10px 20px;
  border: 2px solid #e5e7eb;
  background: white;
  color: #64748b;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-secondary:hover { border-color: #cbd5e1; color: #475569; }

.btn-primary {
  padding: 10px 22px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Toast */
.toast-notification {
  position: fixed;
  bottom: 28px;
  right: 28px;
  padding: 14px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
  z-index: 2000;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.toast-notification.success {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  color: #059669;
  border: 1px solid #6ee7b7;
}

.toast-notification.error {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
  color: #dc2626;
  border: 1px solid #fca5a5;
}

.toast-notification.warning {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  color: #d97706;
  border: 1px solid #fcd34d;
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100px);
}

/* Responsive */
@media (max-width: 1024px) {
  .stats-row { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
  .danh-gia-container { padding: 16px; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .page-header { flex-direction: column; align-items: flex-start; }
  .card-header { flex-direction: column; align-items: flex-start; }
  .filter-bar { flex-direction: column; }
  .search-box { max-width: 100%; min-width: 0; }
  .filter-select { width: 100%; }
  .card-footer { flex-direction: column; align-items: stretch; }
  .footer-left, .footer-right { justify-content: center; }
}

@media (max-width: 480px) {
  .stats-row { grid-template-columns: 1fr; }
  .action-btn span { display: none; }
  .action-btn { padding: 8px 12px; }
}
</style>
