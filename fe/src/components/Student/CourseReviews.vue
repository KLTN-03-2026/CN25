<template>
  <div class="reviews-section">
    <!-- Section Header -->
    <div class="reviews-header">
      <div class="header-left">
        <h2 class="reviews-title">
          <i class="fa-solid fa-star"></i>
          Đánh giá khóa học
        </h2>
        <span class="reviews-count" v-if="stats">({{ stats.total }} đánh giá)</span>
      </div>
      <div class="header-actions">
        <!-- Chua dang nhap -->
        <router-link
          v-if="!authStore.isAuthenticated"
          to="/auth/login"
          class="btn-login-review"
        >
          <i class="fa-solid fa-right-to-bracket"></i>
          Đăng nhập để đánh giá
        </router-link>
        <!-- Da dang nhap, chua enroll -->
        <router-link
          v-else-if="authStore.isAuthenticated && !props.isEnrolled && !hasReviewed"
          to="#"
          class="btn-enroll-review"
          title="Bạn cần đăng ký khóa học trước"
        >
          <i class="fa-solid fa-lock"></i>
          Đăng ký để đánh giá
        </router-link>
        <!-- Da dang nhap, da enroll, chua danh gia -->
        <button
          v-else-if="canWriteReview"
          class="btn-write-review"
          @click="showForm = true"
        >
          <i class="fa-solid fa-pen"></i>
          Viết đánh giá
        </button>
        <!-- Da danh gia -->
        <span v-else-if="myReview" class="reviewed-badge">
          <i class="fa-solid fa-check-circle"></i>
          Bạn đã đánh giá
        </span>
        <!-- Fallback: dang nhap roi nhung chua ro trang thai enroll -->
        <span v-else class="reviewed-badge" style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;">
          <i class="fa-solid fa-spinner fa-spin" style="font-size:12px;"></i>
          Đang tải...
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="reviews-loading">
      <div class="loading-spinner"></div>
      <span>Đang tải đánh giá...</span>
    </div>

    <!-- Content -->
    <div v-else class="reviews-content">
      <!-- Rating Summary -->
      <div class="rating-summary" v-if="stats">
        <div class="rating-big">
          <span class="big-number">{{ formatRating(stats.avg_rating) }}</span>
          <div class="big-stars">
            <i
              v-for="n in 5"
              :key="n"
              class="fa-solid fa-star"
              :class="{ filled: n <= Math.round(stats.avg_rating) }"
            ></i>
          </div>
          <span class="big-label">trung bình</span>
        </div>

        <div class="rating-bars">
          <div
            v-for="star in [5, 4, 3, 2, 1]"
            :key="star"
            class="rating-bar-row"
          >
            <span class="bar-label">{{ star }} sao</span>
            <div class="bar-track">
              <div
                class="bar-fill"
                :style="{ width: getBarWidth(star) + '%' }"
              ></div>
            </div>
            <span class="bar-count">{{ stats.rating_distribution[star] || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Form viết / sửa đánh giá -->
      <transition name="slide-down">
        <div v-if="showForm" class="review-form-card">
          <div class="form-header">
            <h3>{{ isEditing ? 'Chỉnh sửa đánh giá' : 'Viết đánh giá của bạn' }}</h3>
            <button class="btn-close-form" @click="closeForm">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="form-body">
            <div class="form-rating">
              <label>Đánh giá của bạn</label>
              <div class="star-picker">
                <button
                  v-for="star in 5"
                  :key="star"
                  class="star-btn"
                  :class="{ active: form.rating >= star }"
                  @click="form.rating = star"
                >
                  <i class="fa-solid fa-star"></i>
                </button>
              </div>
              <span class="rating-label">{{ ratingLabel }}</span>
            </div>

            <div class="form-text">
              <label>Nội dung đánh giá</label>
              <textarea
                v-model="form.content"
                rows="4"
                placeholder="Chia sẻ trải nghiệm học tập của bạn với khóa học này..."
                maxlength="2000"
              ></textarea>
              <span class="char-count">{{ form.content.length }}/2000</span>
            </div>

            <div v-if="formError" class="form-error">
              <i class="fa-solid fa-circle-exclamation"></i>
              {{ formError }}
            </div>

            <div class="form-actions">
              <button class="btn-cancel" @click="closeForm">Hủy</button>
              <button
                class="btn-submit"
                :disabled="submitting"
                @click="submitReview"
              >
                <span v-if="submitting">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                  Đang gửi...
                </span>
                <span v-else>
                  <i class="fa-solid fa-paper-plane"></i>
                  {{ isEditing ? 'Cập nhật' : 'Gửi đánh giá' }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </transition>

      <!-- Alert: đã đánh giá -->
      <div v-if="myReview?.id && !showForm && !isEditing" class="my-review-alert">
        <div class="alert-icon">
          <i class="fa-solid fa-check-circle"></i>
        </div>
        <div class="alert-body">
          <strong>Bạn đã đánh giá khóa học này</strong>
          <div class="my-review-stars">
            <i
              v-for="n in 5"
              :key="n"
              class="fa-solid fa-star"
              :class="{ filled: n <= myReview.rating }"
            ></i>
          </div>
          <p class="my-review-content">{{ myReview.content }}</p>
          <span class="my-review-status" :class="myReview.status">
            <span v-if="myReview.status === 'cho'">
              <i class="fa-solid fa-clock"></i>
              Đang chờ duyệt
            </span>
            <span v-else-if="myReview.status === 'duyet'">
              <i class="fa-solid fa-check"></i>
              Đã duyệt
            </span>
            <span v-else>
              <i class="fa-solid fa-eye-slash"></i>
              Đã ẩn
            </span>
          </span>
        </div>
        <div class="alert-actions">
          <button class="btn-edit-review" @click="startEdit">
            <i class="fa-solid fa-pen"></i>
            Sửa
          </button>
          <button class="btn-delete-my-review" @click="deleteMyReview">
            <i class="fa-solid fa-trash"></i>
            Xóa
          </button>
        </div>
      </div>

      <!-- Danh sách reviews -->
      <div class="reviews-list" v-if="reviews.length > 0">
        <div
          v-for="review in reviews"
          :key="review.id"
          class="review-item"
        >
          <div class="review-avatar">
            <img
              v-if="review.user?.avatar"
              :src="getAvatarUrl(review.user.avatar)"
              :alt="review.user.name"
            />
            <div v-else class="avatar-placeholder">
              {{ getInitials(review.user?.name) }}
            </div>
          </div>

          <div class="review-body">
            <div class="review-meta">
              <span class="review-author">{{ review.user?.name || 'Học viên' }}</span>
              <div class="review-stars">
                <i
                  v-for="n in 5"
                  :key="n"
                  class="fa-solid fa-star"
                  :class="{ filled: n <= review.rating }"
                ></i>
              </div>
              <span class="review-date">{{ formatDate(review.created_at) }}</span>
            </div>

            <p class="review-content">{{ review.content }}</p>

            <!-- Reply from admin -->
            <div v-if="review.admin_reply" class="admin-reply">
              <div class="reply-header">
                <i class="fa-solid fa-robot"></i>
                <span>Phản hồi từ quản trị viên</span>
              </div>
              <p class="reply-content">{{ review.admin_reply }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="!loading" class="reviews-empty">
        <div class="empty-icon">
          <i class="fa-solid fa-star"></i>
        </div>
        <h4>Chưa có đánh giá nào</h4>
        <p>Hãy là người đầu tiên đánh giá khóa học này!</p>
        <button
          class="btn-first-review"
          @click="showForm = true"
        >
          <i class="fa-solid fa-pen"></i>
          Viết đánh giá đầu tiên
        </button>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.total_pages > 1" class="reviews-pagination">
        <button
          class="page-btn"
          :disabled="pagination.current_page <= 1"
          @click="changePage(pagination.current_page - 1)"
        >
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <span class="page-info">
          {{ pagination.current_page }} / {{ pagination.total_pages }}
        </span>
        <button
          class="page-btn"
          :disabled="pagination.current_page >= pagination.total_pages"
          @click="changePage(pagination.current_page + 1)"
        >
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ReviewService } from '../../services/api'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  courseId: {
    type: [Number, String],
    required: true
  },
  isEnrolled: {
    type: Boolean,
    default: false
  }
})

const authStore = useAuthStore()

const loading = ref(false)
const reviews = ref([])
const stats = ref(null)
const pagination = ref(null)
const myReview = ref(null)
const showForm = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const formError = ref('')

const form = ref({
  rating: 5,
  content: ''
})

const currentPage = ref(1)

const canWriteReview = computed(() => {
  if (!authStore.isAuthenticated) return false
  if (hasReviewed.value) return false
  if (!props.isEnrolled) return false
  return true
})
const hasReviewed = computed(() => myReview.value !== null)

const ratingLabel = computed(() => {
  const labels = ['', 'Rất không hài lòng', 'Không hài lòng', 'Bình thường', 'Hài lòng', 'Tuyệt vời']
  return labels[form.value.rating] || ''
})

const getBarWidth = (star) => {
  if (!stats.value || stats.value.total === 0) return 0
  const count = stats.value.rating_distribution[star] || 0
  return Math.round((count / stats.value.total) * 100)
}

const formatRating = (val) => {
  if (!val) return '0.0'
  return Number(val).toFixed(1)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('vi-VN', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const getAvatarUrl = (avatar) => {
  if (!avatar) return ''
  if (avatar.startsWith('http')) return avatar
  return 'http://localhost:8000/uploads/' + avatar
}

const getInitials = (name) => {
  if (!name) return 'HV'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[parts.length - 1].charAt(0) + parts[0].charAt(0)).toUpperCase()
}

const fetchReviews = async (page = 1) => {
  loading.value = true
  try {
    const res = await ReviewService.getByCourse(props.courseId, { page, per_page: 10 })
    reviews.value = res.reviews?.data || []
    stats.value = res.stats
    pagination.value = {
      current_page: res.reviews?.current_page || 1,
      total_pages: res.reviews?.last_page || 1,
      total: res.reviews?.total || 0
    }
  } catch (error) {
    console.error('Lỗi khi tải reviews:', error)
    reviews.value = []
  } finally {
    loading.value = false
  }
}

const fetchMyReview = async () => {
  if (!authStore.isAuthenticated) {
    myReview.value = null
    return
  }
  try {
    const res = await ReviewService.getMyReview(props.courseId)
    myReview.value = (res && res.id) ? res : null
  } catch (error) {
    myReview.value = null
  }
}

const closeForm = () => {
  showForm.value = false
  isEditing.value = false
  formError.value = ''
}

const startEdit = () => {
  if (!myReview.value?.id) return
  form.value.rating = myReview.value.rating
  form.value.content = myReview.value.content
  isEditing.value = true
  showForm.value = true
}

const submitReview = async () => {
  formError.value = ''

  if (!form.value.rating || form.value.rating < 1 || form.value.rating > 5) {
    formError.value = 'Vui lòng chọn số sao đánh giá.'
    return
  }

  if (!form.value.content || form.value.content.trim().length < 10) {
    formError.value = 'Nội dung đánh giá phải có ít nhất 10 ký tự.'
    return
  }

  submitting.value = true
  try {
    if (isEditing.value && myReview.value?.id) {
      await ReviewService.update(myReview.value.id, {
        rating: form.value.rating,
        content: form.value.content
      })
    } else {
      await ReviewService.create({
        course_id: props.courseId,
        rating: form.value.rating,
        content: form.value.content
      })
    }

    form.value.rating = 5
    form.value.content = ''
    showForm.value = false
    isEditing.value = false

    await fetchMyReview()
    await fetchReviews(currentPage.value)
  } catch (error) {
    const status = error.response?.status
    const msg = error.response?.data?.message || 'Đã xảy ra lỗi. Vui lòng thử lại.'

    if (status === 403) {
      formError.value = 'Bạn cần đăng ký khóa học trước khi đánh giá.'
    } else if (status === 422) {
      formError.value = msg
    } else {
      formError.value = msg
    }
  } finally {
    submitting.value = false
  }
}

const deleteMyReview = async () => {
  if (!myReview.value?.id) {
    myReview.value = null
    return
  }
  if (!confirm('Bạn có chắc muốn xóa đánh giá này?')) return
  try {
    await ReviewService.delete(myReview.value.id)
    myReview.value = null
    await fetchReviews(currentPage.value)
  } catch (error) {
    alert('Xóa đánh giá thất bại. Vui lòng thử lại.')
  }
}

const changePage = (page) => {
  currentPage.value = page
  fetchReviews(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const refresh = () => {
  fetchReviews(1)
  fetchMyReview()
}

watch(() => props.courseId, () => {
  currentPage.value = 1
  reviews.value = []
  stats.value = null
  pagination.value = null
  myReview.value = null
  showForm.value = false
  isEditing.value = false
  fetchReviews(1)
  fetchMyReview()
})

defineExpose({ refresh })

onMounted(() => {
  fetchReviews(1)
  fetchMyReview()
})
</script>

<style scoped>
.reviews-section {
  margin-top: 48px;
  padding-top: 48px;
  border-top: 2px solid #e2e8f0;
}

.reviews-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  gap: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.reviews-title {
  font-size: 22px;
  font-weight: 800;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
}

.reviews-title i {
  color: #f59e0b;
}

.reviews-count {
  font-size: 14px;
  color: #64748b;
  background: #f1f5f9;
  padding: 4px 12px;
  border-radius: 999px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-login-review {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
  font-family: inherit;
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.btn-login-review:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
}

.btn-enroll-review {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #94a3b8, #64748b);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: not-allowed;
  text-decoration: none;
  transition: all 0.2s;
  font-family: inherit;
  box-shadow: 0 4px 14px rgba(100, 116, 139, 0.35);
  opacity: 0.8;
}

.reviewed-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  color: #16a34a;
  border: 1px solid #86efac;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
}

.reviewed-badge i {
  color: #22c55e;
}

.btn-write-review {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
}

.btn-write-review:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
}

/* Loading */
.reviews-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 48px;
  color: #64748b;
  font-size: 14px;
}

.loading-spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e2e8f0;
  border-top-color: #f59e0b;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Content */
.reviews-content {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

/* Rating Summary */
.rating-summary {
  display: flex;
  align-items: center;
  gap: 40px;
  background: linear-gradient(135deg, #fffbeb, #fef3c7);
  border: 1px solid #fde68a;
  border-radius: 20px;
  padding: 28px 32px;
}

.rating-big {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  min-width: 100px;
}

.big-number {
  font-size: 52px;
  font-weight: 900;
  color: #1e293b;
  line-height: 1;
}

.big-stars {
  display: flex;
  gap: 3px;
}

.big-stars i {
  font-size: 16px;
  color: #e2e8f0;
}

.big-stars i.filled {
  color: #f59e0b;
}

.big-label {
  font-size: 12px;
  color: #92400e;
  font-weight: 600;
}

.rating-bars {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.rating-bar-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.bar-label {
  font-size: 13px;
  color: #64748b;
  width: 48px;
  text-align: right;
  font-weight: 500;
}

.bar-track {
  flex: 1;
  height: 8px;
  background: rgba(0, 0, 0, 0.08);
  border-radius: 999px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
  border-radius: 999px;
  transition: width 0.5s ease;
}

.bar-count {
  font-size: 13px;
  color: #64748b;
  width: 24px;
  text-align: left;
  font-weight: 600;
}

/* Review Form */
.review-form-card {
  background: white;
  border: 2px solid #fbbf24;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.form-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background: linear-gradient(135deg, #fffbeb, #fef3c7);
  border-bottom: 1px solid #fde68a;
}

.form-header h3 {
  font-size: 16px;
  font-weight: 800;
  color: #78350f;
  margin: 0;
}

.btn-close-form {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: rgba(0, 0, 0, 0.06);
  color: #92400e;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close-form:hover {
  background: rgba(0, 0, 0, 0.12);
}

.form-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-rating {
  display: flex;
  align-items: center;
  gap: 16px;
}

.form-rating label {
  font-size: 14px;
  font-weight: 700;
  color: #374151;
  min-width: 110px;
}

.star-picker {
  display: flex;
  gap: 4px;
}

.star-btn {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 2px solid #e5e7eb;
  background: white;
  color: #e5e7eb;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  transition: all 0.15s;
}

.star-btn.active {
  border-color: #f59e0b;
  color: #f59e0b;
  background: #fffbeb;
}

.star-btn:hover {
  border-color: #fbbf24;
  color: #fbbf24;
  transform: scale(1.1);
}

.rating-label {
  font-size: 13px;
  color: #92400e;
  font-weight: 600;
  min-width: 100px;
}

.form-text {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-text label {
  font-size: 14px;
  font-weight: 700;
  color: #374151;
}

.form-text textarea {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  color: #1e293b;
  resize: vertical;
  font-family: inherit;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.form-text textarea:focus {
  outline: none;
  border-color: #f59e0b;
}

.char-count {
  font-size: 12px;
  color: #9ca3af;
  text-align: right;
}

.form-error {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  color: #dc2626;
  font-size: 13px;
  font-weight: 600;
}

.form-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
}

.btn-cancel {
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

.btn-cancel:hover {
  border-color: #cbd5e1;
  color: #475569;
}

.btn-submit {
  padding: 10px 24px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
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
  box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* My Review Alert */
.my-review-alert {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 20px;
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  border: 1px solid #86efac;
  border-radius: 16px;
  flex-wrap: wrap;
}

.alert-icon {
  width: 40px;
  height: 40px;
  background: #22c55e;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: white;
  flex-shrink: 0;
}

.alert-body {
  flex: 1;
  min-width: 200px;
}

.alert-body strong {
  display: block;
  font-size: 14px;
  font-weight: 700;
  color: #166534;
  margin-bottom: 6px;
}

.my-review-stars {
  display: flex;
  gap: 3px;
  margin-bottom: 8px;
}

.my-review-stars i {
  font-size: 14px;
  color: #e2e8f0;
}

.my-review-stars i.filled {
  color: #f59e0b;
}

.my-review-content {
  font-size: 13px;
  color: #166534;
  margin: 0 0 8px;
  line-height: 1.5;
}

.my-review-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 999px;
}

.my-review-status.cho {
  background: rgba(251, 191, 36, 0.15);
  color: #d97706;
}

.my-review-status.duyet {
  background: rgba(34, 197, 94, 0.15);
  color: #16a34a;
}

.my-review-status.an {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
}

.btn-edit-review {
  padding: 8px 14px;
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
  color: #16a34a;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.btn-edit-review:hover {
  background: rgba(34, 197, 94, 0.2);
}

.alert-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
}

.btn-delete-my-review {
  padding: 8px 14px;
  background: rgba(220, 38, 38, 0.1);
  border: 1px solid rgba(220, 38, 38, 0.2);
  color: #dc2626;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.btn-delete-my-review:hover {
  background: rgba(220, 38, 38, 0.2);
}

/* Reviews List */
.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.review-item {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  transition: box-shadow 0.2s;
}

.review-item:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.review-avatar {
  flex-shrink: 0;
}

.review-avatar img {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  object-fit: cover;
}

.avatar-placeholder {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  font-size: 16px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.review-body {
  flex: 1;
  min-width: 0;
}

.review-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.review-author {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

.review-stars {
  display: flex;
  gap: 2px;
}

.review-stars i {
  font-size: 13px;
  color: #e2e8f0;
}

.review-stars i.filled {
  color: #f59e0b;
}

.review-date {
  font-size: 12px;
  color: #94a3b8;
}

.review-content {
  font-size: 14px;
  color: #475569;
  line-height: 1.6;
  margin: 0;
}

.admin-reply {
  margin-top: 14px;
  padding: 14px 16px;
  background: #f8fafc;
  border-left: 3px solid #6366f1;
  border-radius: 0 10px 10px 0;
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

.reply-header i {
  font-size: 13px;
}

.reply-content {
  font-size: 13px;
  color: #475569;
  line-height: 1.5;
  margin: 0;
}

/* Empty State */
.reviews-empty {
  text-align: center;
  padding: 48px 24px;
  background: white;
  border: 2px dashed #e2e8f0;
  border-radius: 20px;
}

.empty-icon {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  font-size: 28px;
  color: #f59e0b;
}

.reviews-empty h4 {
  font-size: 16px;
  font-weight: 700;
  color: #374151;
  margin: 0 0 8px;
}

.reviews-empty p {
  font-size: 14px;
  color: #9ca3af;
  margin: 0 0 20px;
}

.btn-first-review {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
}

.btn-first-review:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
}

/* Pagination */
.reviews-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px;
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
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  border-color: #f59e0b;
  color: #f59e0b;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
}

/* Transition */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 800px;
}

/* Responsive */
@media (max-width: 768px) {
  .reviews-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .rating-summary {
    flex-direction: column;
    gap: 20px;
    padding: 20px;
  }

  .rating-big {
    min-width: auto;
  }

  .review-item {
    flex-direction: column;
    gap: 12px;
  }

  .form-rating {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .my-review-alert {
    flex-wrap: wrap;
  }
}
</style>
