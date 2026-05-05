<template>
  <div class="grammar-player">
    <!-- Loading -->
    <div v-if="isLoading" class="loading-screen">
      <div class="loader"></div>
      <p>Đang tải ngữ pháp...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-screen">
      <div class="error-icon">
        <i class="fa-solid fa-circle-exclamation"></i>
      </div>
      <h2>Không tìm thấy bài học</h2>
      <p>{{ error }}</p>
      <button class="btn-primary" @click="goBack">
        <i class="fa-solid fa-arrow-left"></i>
        Quay lại
      </button>
    </div>

    <!-- Content -->
    <div v-else-if="grammars.length > 0" class="content-wrapper">
      <!-- Header -->
      <div class="player-header">
        <button class="btn-back" @click="goBack">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="header-info">
          <span class="badge">Ngữ pháp</span>
          <h1>{{ lesson?.title || 'Bài học' }}</h1>
        </div>
        <div class="progress-info">
          {{ currentIndex + 1 }} / {{ grammars.length }}
        </div>
      </div>

      <!-- Grammar Content -->
      <div class="grammar-content">
        <!-- Title -->
        <div class="grammar-title-section">
          <div class="title-number">{{ currentIndex + 1 }}</div>
          <h2>{{ currentGrammar.title }}</h2>
        </div>

        <!-- Structure -->
        <div class="content-card">
          <div class="card-header">
            <i class="fa-solid fa-puzzle-piece"></i>
            <span>Công thức</span>
          </div>
          <div class="card-body">
            <div class="structure-text">
              {{ currentGrammar.structure || 'Không có cấu trúc' }}
            </div>
          </div>
        </div>

        <!-- Explanation -->
        <div class="content-card">
          <div class="card-header">
            <i class="fa-solid fa-lightbulb"></i>
            <span>Cách dùng</span>
          </div>
          <div class="card-body">
            <p class="explanation-text">{{ currentGrammar.explanation }}</p>
          </div>
        </div>

        <!-- Signals -->
        <div class="content-card" v-if="currentGrammar.signals">
          <div class="card-header">
            <i class="fa-solid fa-search"></i>
            <span>Dấu hiệu nhận biết</span>
          </div>
          <div class="card-body">
            <p class="signals-text">{{ currentGrammar.signals }}</p>
          </div>
        </div>

        <!-- Example -->
        <div class="content-card" v-if="currentGrammar.example">
          <div class="card-header">
            <i class="fa-solid fa-comment-dots"></i>
            <span>Ví dụ</span>
          </div>
          <div class="card-body">
            <p class="example-text">{{ currentGrammar.example }}</p>
          </div>
        </div>

        <!-- Video -->
        <div class="content-card video-card" v-if="currentGrammar.youtube_url">
          <div class="card-header">
            <i class="fa-brands fa-youtube"></i>
            <span>Video hướng dẫn</span>
          </div>
          <div class="card-body">
            <button class="video-btn" @click="openVideo">
              <img :src="getYouTubeThumb(currentGrammar.youtube_url)" alt="Video thumbnail" />
              <div class="play-overlay">
                <div class="play-icon">
                  <i class="fa-solid fa-play"></i>
                </div>
              </div>
            </button>
          </div>
        </div>

    </div>

      <!-- Navigation -->
      <div class="bottom-nav">
        <button class="btn-nav" :disabled="currentIndex === 0" @click="prevGrammar">
          <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="dots">
          <span
            v-for="(_, index) in grammars"
            :key="index"
            class="dot"
            :class="{ active: index === currentIndex, done: index < currentIndex }"
            @click="selectGrammar(index)"
          ></span>
        </div>

        <button
          class="btn-nav btn-next"
          v-if="currentIndex < grammars.length - 1"
          @click="nextGrammar"
        >
          <i class="fa-solid fa-chevron-right"></i>
        </button>
        <button v-else class="btn-nav btn-done" @click="finishLesson">
          <i class="fa-solid fa-check"></i>
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <i class="fa-solid fa-book"></i>
      </div>
      <h3>Chưa có ngữ pháp</h3>
      <p>Bài học này chưa có nội dung ngữ pháp.</p>
      <button class="btn-primary" @click="goBack">Quay lại</button>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccess" class="modal-overlay" @click.self="showSuccess = false">
      <div class="success-modal">
        <div class="success-icon">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h2>Hoàn thành!</h2>
        <p>Bạn đã học xong {{ grammars.length }} cấu trúc ngữ pháp</p>
        <div class="success-actions">
          <button class="btn-secondary" @click="reviewAgain">
            <i class="fa-solid fa-rotate-left"></i>
            Học lại
          </button>
          <button class="btn-primary" @click="goNextLesson">
            Tiếp tục
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Video Modal -->
    <div v-if="showVideo" class="modal-overlay" @click.self="closeVideo">
      <div class="video-modal">
        <div class="video-modal-header">
          <h3>Video hướng dẫn</h3>
          <button class="btn-close-modal" @click="closeVideo">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="video-wrapper">
          <iframe
            v-if="videoUrl"
            :src="videoUrl"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiClient, { ProgressService } from '../../../services/api.js'

const route = useRoute()
const router = useRouter()

const isLoading = ref(true)
const error = ref(null)
const lesson = ref(null)
const grammars = ref([])
const currentIndex = ref(0)
const showSuccess = ref(false)
const showVideo = ref(false)
const videoUrl = ref('')
const startTime = ref(Date.now())

let timerInterval = null
const elapsedSeconds = ref(0)

const updateTimer = () => {
  if (startTime.value) {
    elapsedSeconds.value = Math.round((Date.now() - startTime.value) / 1000)
  }
}

const currentGrammar = computed(() => grammars.value[currentIndex.value] || {})

const fetchData = async () => {
  isLoading.value = true
  error.value = null

  const lessonId = route.params.id

  try {
    const res = await apiClient.get(`/lessons/${lessonId}`)
    lesson.value = res.data
    grammars.value = res.data.grammars || []
  } catch (err) {
    console.error('Lỗi khi tải dữ liệu:', err)
    error.value = 'Không thể tải bài học. Vui lòng thử lại.'
  } finally {
    isLoading.value = false
  }
}

const selectGrammar = (index) => {
  currentIndex.value = index
}

const prevGrammar = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

const nextGrammar = () => {
  if (currentIndex.value < grammars.value.length - 1) {
    currentIndex.value++
  }
}

const finishLesson = async () => {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
  const timeSpent = Math.round((Date.now() - startTime.value) / 1000)
  try {
    await ProgressService.completeLesson(route.params.id, { time_spent: timeSpent })
  } catch (e) {
    console.error('Lỗi cập nhật tiến độ:', e)
  }
  showSuccess.value = true
}

const reviewAgain = () => {
  currentIndex.value = 0
  showSuccess.value = false
  startTime.value = Date.now()
  elapsedSeconds.value = 0
  timerInterval = setInterval(updateTimer, 1000)
}

const goNextLesson = () => {
  showSuccess.value = false
  window.location.href = `/student/khoa-hoc/${lesson.value?.chapter?.course_id}`
}

const goBack = () => {
  router.back()
}

const getYouTubeThumb = (url) => {
  const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([^&?]+)/)
  if (match) {
    return `https://img.youtube.com/vi/${match[1]}/hqdefault.jpg`
  }
  return ''
}

const openVideo = () => {
  if (!currentGrammar.value.youtube_url) return
  const match = currentGrammar.value.youtube_url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([^&?]+)/)
  if (match) {
    videoUrl.value = `https://www.youtube.com/embed/${match[1]}?autoplay=1`
    showVideo.value = true
  }
}

const closeVideo = () => {
  showVideo.value = false
  videoUrl.value = ''
}

onMounted(() => {
  fetchData()
  timerInterval = setInterval(updateTimer, 1000)
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.grammar-player {
  min-height: 100vh;
  background: #f8fafc;
  padding-bottom: 90px;
}

/* Header */
.player-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.btn-back {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-back:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.header-info {
  flex: 1;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  background: #dbeafe;
  color: #2563eb;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 4px;
}

.header-info h1 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.progress-info {
  padding: 8px 16px;
  background: #f1f5f9;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
}

/* Content */
.grammar-content {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.grammar-title-section {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.title-number {
  width: 48px;
  height: 48px;
  background: #2563eb;
  color: white;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
  flex-shrink: 0;
}

.grammar-title-section h2 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  line-height: 1.3;
}

/* Cards */
.content-card {
  background: white;
  border-radius: 16px;
  margin-bottom: 16px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}

.card-header i {
  font-size: 16px;
  color: #64748b;
}

.card-body {
  padding: 20px;
}

/* Structure */
.structure-text {
  font-size: 20px;
  font-weight: 700;
  color: #2563eb;
  font-family: 'Segoe UI', sans-serif;
  text-align: center;
  padding: 8px 0;
}

/* Explanation */
.explanation-text {
  font-size: 15px;
  line-height: 1.8;
  color: #334155;
  margin: 0;
}

/* Signals */
.signals-text {
  font-size: 15px;
  line-height: 1.8;
  color: #334155;
  margin: 0;
}

/* Example */
.example-text {
  font-size: 15px;
  line-height: 1.8;
  color: #334155;
  margin: 0;
  font-style: italic;
  padding-left: 16px;
  border-left: 3px solid #2563eb;
}

/* Video */
.video-btn {
  width: 100%;
  border: none;
  background: none;
  padding: 0;
  cursor: pointer;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

.video-btn img {
  width: 100%;
  height: auto;
  display: block;
}

.play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.video-btn:hover .play-overlay {
  background: rgba(0, 0, 0, 0.4);
}

.play-icon {
  width: 64px;
  height: 64px;
  background: #ef4444;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
}

.play-icon i {
  font-size: 24px;
  color: white;
  margin-left: 4px;
}

/* Bottom Navigation */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 16px 20px;
  background: white;
  border-top: 1px solid #e2e8f0;
}

.btn-nav {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-nav:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-nav:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-nav.btn-next,
.btn-nav.btn-done {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
}

.btn-nav.btn-next:hover,
.btn-nav.btn-done:hover {
  transform: scale(1.05);
}

.dots {
  display: flex;
  gap: 6px;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #e2e8f0;
  cursor: pointer;
  transition: all 0.2s;
}

.dot.active {
  background: #2563eb;
  width: 24px;
  border-radius: 4px;
}

.dot.done {
  background: #bfdbfe;
}

/* Loading */
.loading-screen {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  min-height: 50vh;
}

.loader {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-screen p {
  color: #64748b;
  font-size: 14px;
}

/* Error & Empty */
.error-screen, .empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px 20px;
}

.error-icon, .empty-icon {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}

.error-icon i, .empty-icon i {
  font-size: 40px;
  color: #64748b;
}

.error-screen h2, .empty-state h3 {
  font-size: 24px;
  color: #1e293b;
  margin-bottom: 8px;
}

.error-screen p, .empty-state p {
  color: #64748b;
  margin-bottom: 24px;
}

/* Buttons */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e2e8f0;
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
  padding: 20px;
}

.success-modal {
  background: white;
  border-radius: 24px;
  padding: 40px;
  text-align: center;
  max-width: 360px;
  width: 100%;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.success-icon {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.success-icon i {
  font-size: 48px;
  color: #2563eb;
}

.success-modal h2 {
  font-size: 28px;
  color: #1e293b;
  margin-bottom: 8px;
}

.success-modal p {
  color: #64748b;
  margin-bottom: 28px;
  font-size: 16px;
}

.success-actions {
  display: flex;
  gap: 12px;
}

.success-actions button {
  flex: 1;
}

/* Video Modal */
.video-modal {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 800px;
  overflow: hidden;
  animation: slideUp 0.3s ease;
}

.video-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.video-modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1e293b;
}

.btn-close-modal {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: #f1f5f9;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close-modal:hover {
  background: #e2e8f0;
}

.btn-close-modal i {
  font-size: 16px;
  color: #64748b;
}

.video-wrapper {
  position: relative;
  padding-top: 56.25%;
}

.video-wrapper iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

/* Responsive */
@media (max-width: 480px) {
  .grammar-content {
    padding: 16px;
  }

  .grammar-title-section h2 {
    font-size: 20px;
  }

  .structure-text {
    font-size: 16px;
  }
}
</style>
