<template>
  <div class="payment-page">
    <div class="payment-container">
      <!-- Back Button -->
      <button class="back-btn" @click="goBack">
        <i class="fa-solid fa-arrow-left"></i>
        Quay lại
      </button>

      <!-- Loading -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải thông tin thanh toán...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="error-state">
        <i class="fa-solid fa-circle-exclamation"></i>
        <p>{{ error }}</p>
        <button @click="goBack">Quay lại</button>
      </div>

      <!-- Already Enrolled -->
      <div v-else-if="alreadyEnrolled" class="enrolled-state">
        <i class="fa-solid fa-check-circle"></i>
        <h2>Bạn đã đăng ký khóa học này</h2>
        <p>Khóa học "{{ course?.title }}" đã có trong danh sách khóa học của bạn.</p>
        <button class="btn-primary" @click="goToCourse">Học ngay</button>
      </div>

      <!-- Da gui yeu cau roi -->
        <div v-else-if="paymentPending" class="pending-state">
        <div class="pending-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <h2>Yêu cầu đã được gửi!</h2>
        <p>Chúng tôi đã nhận được yêu cầu thanh toán của bạn. Vui lòng chờ admin xác nhận trong thời gian sớm nhất.</p>
        <div class="pending-info">
          <div class="info-item">
            <span class="info-label">Mã giao dịch:</span>
            <span class="info-value">{{ pendingTransactionId }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Số tiền:</span>
            <span class="info-value">{{ formatPrice(currentPrice) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Trạng thái:</span>
            <span class="info-badge">Chờ duyệt</span>
          </div>
        </div>
        <button class="btn-primary" @click="goToCourse">
          <i class="fa-solid fa-arrow-left"></i>
          Quay về khóa học
        </button>
      </div>

      <!-- Payment Form -->
      <div v-else class="payment-content">
        <div class="payment-header">
          <h1>Thanh toán khóa học</h1>
          <p>Chuyển khoản ngân hàng để hoàn tất thanh toán</p>
        </div>

        <div class="payment-grid">
          <!-- Course Info -->
          <div class="course-summary">
            <div class="course-card">
              <img :src="getThumbnailUrl(course?.thumbnail)" :alt="course?.title" />
              <div class="course-info">
                <span class="level-tag" :class="'level-' + course?.level">
                  {{ getLevelLabel(course?.level) }}
                </span>
                <h2>{{ course?.title }}</h2>
                <p>{{ course?.description }}</p>
                <div class="course-meta">
                  <span>
                    <i class="fa-solid fa-book"></i>
                    {{ course?.chapters_count }} chương
                  </span>
                </div>
              </div>
            </div>

            <div class="price-summary">
              <div class="price-row total">
                <span>Số tiền cần thanh toán</span>
                <span class="total-amount">{{ formatPrice(currentPrice) }}</span>
              </div>
              <div class="price-row">
                <span>Mã giao dịch</span>
                <span class="transaction-id">{{ transactionId }}</span>
              </div>
            </div>
          </div>

          <!-- Bank Transfer Info -->
          <div class="payment-methods">
            <h3>
              <i class="fa-solid fa-building-columns"></i>
              Thông tin chuyển khoản
            </h3>

            <!-- Bank Card -->
            <div class="bank-card">
              <div class="bank-header">
                <div class="bank-icon">
                  <i class="fa-solid fa-university"></i>
                </div>
                <div class="bank-name">
                  <span class="bank-title">{{ bankInfo.bankName }}</span>
                  <span class="bank-branch">{{ bankInfo.branch }}</span>
                </div>
              </div>

              <div class="bank-details">
                <div class="detail-row">
                  <span class="detail-label">Số tài khoản</span>
                  <div class="detail-value-group">
                    <span class="detail-value">{{ bankInfo.accountNumber }}</span>
                    <button class="copy-btn" @click="copyToClipboard(bankInfo.accountNumber)" title="Sao chép">
                      <i class="fa-regular fa-copy"></i>
                    </button>
                  </div>
                </div>

                <div class="detail-row">
                  <span class="detail-label">Tên tài khoản</span>
                  <span class="detail-value">{{ bankInfo.accountName }}</span>
                </div>

                <div class="detail-row">
                  <span class="detail-label">Số tiền</span>
                  <div class="detail-value-group">
                    <span class="detail-value amount-highlight">{{ currentPrice.toLocaleString('vi-VN') }} VND</span>
                    <button class="copy-btn" @click="copyToClipboard(currentPrice.toString())" title="Sao chép">
                      <i class="fa-regular fa-copy"></i>
                    </button>
                  </div>
                </div>

                <div class="detail-row">
                  <span class="detail-label">Nội dung CK</span>
                  <div class="detail-value-group">
                    <span class="detail-value content-highlight">{{ transactionId }}</span>
                    <button class="copy-btn" @click="copyToClipboard(transactionId)" title="Sao chép">
                      <i class="fa-regular fa-copy"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- QR Code -->
            <div class="qr-section">
              <h4>Quét mã QR</h4>
              <div class="qr-code">
                <img :src="qrCodeUrl" alt="QR Code" />
              </div>
              <p class="qr-note">Quét mã QR bằng app ngân hàng để chuyển khoản nhanh</p>
            </div>

            <!-- Instructions -->
            <div class="instructions">
              <h4>Hướng dẫn thanh toán</h4>
              <ol>
                <li>Sao chép <strong>Số tài khoản</strong> hoặc <strong>Quét mã QR</strong></li>
                <li>Mở app ngân hàng (Vietcombank, MB Bank, TPBank...)</li>
                <li>Chuyển khoản với đúng <strong>Số tiền</strong> và <strong>Nội dung CK</strong></li>
                <li>Bấm <strong>"Đã chuyển khoản"</strong> bên dưới</li>
              </ol>
            </div>

            <!-- Confirm Button -->
            <div class="payment-actions">
              <button
                class="btn-primary btn-pay"
                :disabled="isSubmitting"
                @click="submitPayment"
              >
                <span v-if="isSubmitting" class="btn-loading">
                  <div class="spinner small white"></div>
                  Đang gửi yêu cầu...
                </span>
                <span v-else>
                  <i class="fa-solid fa-check"></i>
                  Đã chuyển khoản
                </span>
              </button>
              <button class="btn-secondary" @click="goBack">
                Quay lại
              </button>
            </div>

            <!-- Warning -->
            <div class="payment-warning">
              <i class="fa-solid fa-info-circle"></i>
              <p>Thanh toán sẽ được xác nhận trong vòng <strong>1-24 giờ</strong> bởi quản trị viên.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <transition name="toast">
      <div v-if="toast.show" class="toast" :class="toast.type">
        <i :class="toast.type === 'success' ? 'fa-solid fa-check-circle' : 'fa-solid fa-circle-exclamation'"></i>
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { CourseService } from '../../../services/api.js'

const router = useRouter()
const route = useRoute()

const courseId = route.params.id

// Thong tin ngan hang - cau hinh o day
const bankInfo = ref({
  bankName: 'MB Bank',
  branch: 'Chi nhánh Quảng Trị',
  accountNumber: '1234561582004',
  accountName: 'LÊ VĂN ĐỨC',
})

const isLoading = ref(true)
const isSubmitting = ref(false)
const error = ref('')
const course = ref(null)
const alreadyEnrolled = ref(false)
const paymentPending = ref(false)
const pendingTransactionId = ref('')
const currentPrice = ref(0)
const toast = ref({ show: false, message: '', type: 'success' })

// Generate transaction ID
const transactionId = ref('')
const generateTransactionId = () => {
  const timestamp = Date.now().toString(36).toUpperCase()
  const random = Math.random().toString(36).substring(2, 6).toUpperCase()
  transactionId.value = `DTU-${timestamp}-${random}`
}

const qrCodeUrl = computed(() => {
  // Ma QR MB Bank theo dinh dang NAPAS/VietQR
  const amount = currentPrice.value
  const account = bankInfo.value.accountNumber
  const content = transactionId.value
  const bankId = '970422' // MB Bank
  const url = `https://img.vietqr.io/image/${bankId}-${account}-compact.png?amount=${amount}&addInfo=${encodeURIComponent(content)}&accountName=${encodeURIComponent(bankInfo.value.accountName)}`
  return url
})

const getThumbnailUrl = (thumbnail) => {
  if (!thumbnail) return 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800'
  if (thumbnail.startsWith('http')) return thumbnail
  return 'http://localhost:8000/uploads/' + thumbnail
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

const getLevelLabel = (level) => {
  const levels = {
    beginner: 'Sơ cấp',
    intermediate: 'Trung cấp',
    advanced: 'Nâng cao'
  }
  return levels[level] || level
}

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    showToast('Đã sao chép!', 'success')
  } catch (err) {
    showToast('Không thể sao chép', 'error')
  }
}

const fetchCourse = async () => {
  try {
    const res = await CourseService.getById(courseId)
    course.value = res
    currentPrice.value = Number(res.price || 0)
  } catch (err) {
    error.value = 'Không tìm thấy khóa học'
    console.error(err)
  }
}

const checkEnrollmentAndPayment = async () => {
  try {
    const res = await CourseService.getById(courseId)
    course.value = res
    currentPrice.value = Number(res.price || 0)

    // Kiem tra da dang ky chua (backend tra ve is_enrolled tu UserCourse)
    if (res.is_enrolled) {
      alreadyEnrolled.value = true
      return
    }

    // Neu chua dang ky, kiem tra xem co yeu cau dang cho khong
    const pendingRes = await fetch('http://localhost:8000/api/payments/pending', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      },
    })

    if (pendingRes.ok) {
      const pendingData = await pendingRes.json()
      const pending = pendingData.data || pendingData
      const coursePending = Array.isArray(pending)
        ? pending.find(p => p.course_id == courseId && p.status === 'cho')
        : (pending.course_id == courseId && pending.status === 'cho' ? pending : null)

      if (coursePending) {
        pendingTransactionId.value = coursePending.transaction_id
        paymentPending.value = true
        return
      }
    }

    // Neu khong co yeu cau cho nao -> cho phep thanh toan
    // (neu user da bi xoa khoi UserCourse nhung payment chua duoc tao, cho phep tao moi)
    paymentPending.value = false
  } catch (err) {
    console.error(err)
  }
}

const checkEnrollment = async () => {
  try {
    const res = await CourseService.getById(courseId)
    if (res.is_enrolled) {
      alreadyEnrolled.value = true
    }
  } catch (err) {
    console.error(err)
  }
}

const submitPayment = async () => {
  if (isSubmitting.value) return

  isSubmitting.value = true

  try {
    const response = await fetch('http://localhost:8000/api/payments/manual', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        course_id: courseId,
        amount: currentPrice.value,
        transaction_id: transactionId.value,
        payment_method: 'banking',
      }),
    })

    const data = await response.json()

    if (response.ok) {
      pendingTransactionId.value = transactionId.value
      paymentPending.value = true
      showToast('Yêu cầu thanh toán đã được gửi!', 'success')
    } else {
      showToast(data.message || 'Có lỗi xảy ra', 'error')
    }
  } catch (err) {
    showToast('Không thể gửi yêu cầu. Vui lòng thử lại.', 'error')
    console.error(err)
  } finally {
    isSubmitting.value = false
  }
}

const goBack = () => {
  router.back()
}

const goToCourse = () => {
  router.push(`/student/khoa-hoc/${courseId}`)
}

onMounted(async () => {
  generateTransactionId()
  await checkEnrollmentAndPayment()
  isLoading.value = false
})
</script>

<style scoped>
.payment-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f1f5f9 0%, #ffffff 100%);
  padding: 40px 24px 80px;
}

.payment-container {
  max-width: 960px;
  margin: 0 auto;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 32px;
  font-family: inherit;
}

.back-btn:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

/* Loading & Error States */
.loading-state {
  text-align: center;
  padding: 80px 24px;
}

.loading-state .spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 20px;
}

.loading-state p {
  color: #64748b;
  font-size: 16px;
}

.error-state,
.enrolled-state {
  text-align: center;
  padding: 80px 24px;
}

.error-state i,
.enrolled-state i {
  font-size: 64px;
  color: #ef4444;
  margin-bottom: 20px;
}

.enrolled-state i {
  color: #22c55e;
}

.error-state p,
.enrolled-state p {
  font-size: 16px;
  color: #64748b;
  margin-bottom: 24px;
}

.enrolled-state h2 {
  font-size: 24px;
  color: #1e293b;
  margin: 0 0 12px;
}

/* Pending State */
.pending-state {
  text-align: center;
  padding: 60px 40px;
  background: white;
  border-radius: 24px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
  max-width: 600px;
  margin: 40px auto;
}

.pending-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.pending-icon i {
  font-size: 36px;
  color: white;
}

.pending-state h2 {
  font-size: 26px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 12px;
}

.pending-state > p {
  font-size: 15px;
  color: #64748b;
  line-height: 1.6;
  margin: 0 0 28px;
}

.pending-info {
  background: #f8fafc;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 28px;
  text-align: left;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #e2e8f0;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 14px;
  color: #64748b;
}

.info-value {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

.info-badge {
  padding: 4px 12px;
  background: #fef3c7;
  color: #d97706;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

/* Payment Content */
.payment-header {
  text-align: center;
  margin-bottom: 40px;
}

.payment-header h1 {
  font-size: 32px;
  font-weight: 900;
  color: #1e293b;
  margin: 0 0 8px;
}

.payment-header p {
  font-size: 16px;
  color: #64748b;
  margin: 0;
}

.payment-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
}

/* Course Summary */
.course-summary {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.course-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border: 1px solid #f1f5f9;
}

.course-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}

.course-info {
  padding: 20px;
}

.level-tag {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: white;
  margin-bottom: 12px;
}

.level-tag.level-beginner { background: #22c55e; }
.level-tag.level-intermediate { background: #f59e0b; }
.level-tag.level-advanced { background: #ef4444; }

.course-info h2 {
  font-size: 20px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 8px;
  line-height: 1.3;
}

.course-info p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 12px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.course-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #94a3b8;
}

.course-meta span {
  display: flex;
  align-items: center;
  gap: 5px;
}

/* Price Summary */
.price-summary {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border: 1px solid #f1f5f9;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  font-size: 15px;
  color: #64748b;
}

.price-row.total {
  padding-top: 16px;
  border-top: 2px solid #e2e8f0;
  margin-top: 8px;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
}

.total-amount {
  font-size: 28px;
  font-weight: 900;
  color: #ef4444;
}

.transaction-id {
  font-family: monospace;
  font-size: 13px;
  font-weight: 700;
  color: #3b82f6;
  background: #eff6ff;
  padding: 4px 10px;
  border-radius: 6px;
}

/* Payment Methods */
.payment-methods {
  background: white;
  border-radius: 20px;
  padding: 28px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border: 1px solid #f1f5f9;
  height: fit-content;
}

.payment-methods h3 {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.payment-methods h3 i {
  color: #3b82f6;
}

/* Bank Card */
.bank-card {
  background: linear-gradient(135deg, #1e40af, #1e3a8a);
  border-radius: 20px;
  padding: 24px;
  color: white;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}

.bank-card::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -30%;
  width: 200px;
  height: 200px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 50%;
}

.bank-card::after {
  content: '';
  position: absolute;
  bottom: -30%;
  left: -20%;
  width: 150px;
  height: 150px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 50%;
}

.bank-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  position: relative;
  z-index: 1;
}

.bank-icon {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bank-icon i {
  font-size: 24px;
  color: white;
}

.bank-name {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.bank-title {
  font-size: 18px;
  font-weight: 800;
}

.bank-branch {
  font-size: 12px;
  opacity: 0.7;
}

.bank-details {
  position: relative;
  z-index: 1;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-size: 13px;
  opacity: 0.7;
}

.detail-value-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-value {
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.amount-highlight {
  color: #fbbf24;
  font-size: 16px;
}

.content-highlight {
  color: #60a5fa;
  font-family: monospace;
  font-size: 13px;
}

.copy-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: none;
  background: rgba(255, 255, 255, 0.15);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  font-size: 12px;
}

.copy-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.1);
}

/* QR Section */
.qr-section {
  text-align: center;
  margin-bottom: 24px;
  padding: 20px;
  background: #f8fafc;
  border-radius: 16px;
  border: 2px dashed #e2e8f0;
}

.qr-section h4 {
  font-size: 14px;
  font-weight: 700;
  color: #64748b;
  margin: 0 0 16px;
}

.qr-code {
  width: 160px;
  height: 160px;
  margin: 0 auto 12px;
  background: white;
  border-radius: 12px;
  padding: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
}

.qr-code img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.qr-note {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
}

/* Instructions */
.instructions {
  background: #fffbeb;
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid #fef3c7;
}

.instructions h4 {
  font-size: 14px;
  font-weight: 700;
  color: #92400e;
  margin: 0 0 12px;
}

.instructions ol {
  margin: 0;
  padding-left: 20px;
}

.instructions li {
  font-size: 13px;
  color: #92400e;
  line-height: 1.8;
  padding: 2px 0;
}

.instructions strong {
  color: #ea580c;
}

/* Payment Actions */
.payment-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 16px 24px;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s;
  font-family: inherit;
  box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(34, 197, 94, 0.45);
}

.btn-primary:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 14px 24px;
  background: white;
  color: #64748b;
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 10px;
}

.spinner.small {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Warning */
.payment-warning {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px;
  background: #eff6ff;
  border-radius: 12px;
  border: 1px solid #bfdbfe;
}

.payment-warning i {
  color: #3b82f6;
  font-size: 16px;
  margin-top: 2px;
  flex-shrink: 0;
}

.payment-warning p {
  font-size: 13px;
  color: #1e40af;
  margin: 0;
  line-height: 1.5;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  padding: 14px 28px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 600;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
  z-index: 9999;
}

.toast.success {
  background: #22c55e;
  color: white;
}

.toast.error {
  background: #ef4444;
  color: white;
}

.toast i {
  font-size: 18px;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(20px);
}

/* Responsive */
@media (max-width: 768px) {
  .payment-grid {
    grid-template-columns: 1fr;
  }

  .payment-header h1 {
    font-size: 24px;
  }

  .pending-state {
    padding: 40px 24px;
    margin: 20px;
  }
}
</style>
