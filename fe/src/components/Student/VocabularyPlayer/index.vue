<template>
  <div class="vocab-page">
    <!-- Header -->
    <div class="header">
      <button class="btn-back" @click="goBack">
        <i class="fa-solid fa-arrow-left"></i>
      </button>
      <div class="header-info">
        <span class="badge">
          <i class="fa-solid fa-spell-check"></i>
          Từ vựng
        </span>
        <h1>{{ lesson?.title || 'Bài học' }}</h1>
      </div>
      <div class="progress">{{ currentIndex + 1 }}/{{ vocabularies.length }}</div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="loading">
      <div class="spinner"></div>
      <p>Đang tải...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <p>{{ error }}</p>
      <button @click="goBack">Quay lại</button>
    </div>

    <!-- Main Content -->
    <div v-else-if="vocabularies.length > 0" class="content">
      <!-- Card -->
      <div class="card-wrapper" @click="flipCard" :class="{ flipped }">
        <div class="card">
          <!-- Audio Button -->
          <button class="btn-audio-card" @click.stop="playAudio">
            <i class="fa-solid fa-volume-high"></i>
          </button>

          <!-- Front -->
          <div class="card-face front">
            <div class="card-number">{{ currentIndex + 1 }}</div>
            <div class="word">{{ currentVocab.word }}</div>
            <div class="pronunciation">{{ currentVocab.pronunciation }}</div>
            <div class="hint">
              <i class="fa-solid fa-hand-pointer"></i>
              Chạm để xem nghĩa
            </div>
          </div>
          <!-- Back -->
          <div class="card-face back">
            <div class="card-number">{{ currentIndex + 1 }}</div>
            <div class="meaning">{{ currentVocab.meaning }}</div>
            <div class="pronunciation-small">{{ currentVocab.pronunciation }}</div>
          </div>
        </div>
      </div>

      <!-- Example -->
      <div class="example" v-if="currentVocab.example">
        <div class="example-icon">
          <i class="fa-solid fa-quote-left"></i>
        </div>
        <p>{{ currentVocab.example }}</p>
      </div>

      <!-- Dots -->
      <div class="dots">
        <span
          v-for="(v, i) in vocabularies"
          :key="v.id"
          class="dot"
          :class="{ active: i === currentIndex, done: i < currentIndex }"
          @click="goTo(i)"
        ></span>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty">
      <i class="fa-solid fa-book-open"></i>
      <p>Chưa có từ vựng</p>
      <button @click="goBack">Quay lại</button>
    </div>

    <!-- Bottom Nav -->
    <div class="bottom-nav" v-if="vocabularies.length > 0">
      <button class="btn-nav" :disabled="currentIndex === 0" @click="prev">
        <i class="fa-solid fa-chevron-left"></i>
      </button>

      <button v-if="currentIndex < vocabularies.length - 1" class="btn-next" @click="next">
        Tiếp theo
        <i class="fa-solid fa-chevron-right"></i>
      </button>
      <button v-else class="btn-done" @click="finish">
        <i class="fa-solid fa-check"></i>
        Hoàn thành
      </button>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccess" class="modal" @click.self="showSuccess = false">
      <div class="modal-content">
        <div class="modal-icon">
          <i class="fa-solid fa-trophy"></i>
        </div>
        <h2>Hoàn thành!</h2>
        <p>Chúc mừng bạn đã học xong {{ vocabularies.length }} từ vựng</p>
        <div class="modal-actions">
          <button class="btn-outline" @click="review">
            <i class="fa-solid fa-rotate-left"></i>
            Học lại
          </button>
          <button class="btn-solid" @click="continueNext">
            Tiếp tục
            <i class="fa-solid fa-arrow-right"></i>
          </button>
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
const vocabularies = ref([])
const currentIndex = ref(0)
const flipped = ref(false)
const showSuccess = ref(false)
const startTime = ref(Date.now())

let timerInterval = null
const elapsedSeconds = ref(0)

const updateTimer = () => {
  if (startTime.value) {
    elapsedSeconds.value = Math.round((Date.now() - startTime.value) / 1000)
  }
}

const currentVocab = computed(() => vocabularies.value[currentIndex.value] || {})

const fetchData = async () => {
  isLoading.value = true
  error.value = null

  try {
    const res = await apiClient.get(`/lessons/${route.params.id}`)
    lesson.value = res.data
    vocabularies.value = res.data.vocabularies || []
  } catch (err) {
    error.value = 'Không thể tải dữ liệu'
  } finally {
    isLoading.value = false
  }
}

const flipCard = () => {
  flipped.value = !flipped.value
}

const goTo = (index) => {
  currentIndex.value = index
  flipped.value = false
}

const prev = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    flipped.value = false
  }
}

const next = () => {
  if (currentIndex.value < vocabularies.value.length - 1) {
    currentIndex.value++
    flipped.value = false
  }
}

const finish = async () => {
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

const review = () => {
  currentIndex.value = 0
  flipped.value = false
  showSuccess.value = false
  startTime.value = Date.now()
  elapsedSeconds.value = 0
  timerInterval = setInterval(updateTimer, 1000)
}

const continueNext = async () => {
  showSuccess.value = false
  window.location.href = `/student/khoa-hoc/${lesson.value?.chapter?.course_id}`
}

const playAudio = () => {
  const utterance = new SpeechSynthesisUtterance(currentVocab.value.word)
  utterance.lang = 'en-US'
  utterance.rate = 0.9
  speechSynthesis.speak(utterance)
}

const goBack = () => {
  router.back()
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
.vocab-page {
  min-height: 100vh;
  background: linear-gradient(160deg, #f0fdf4 0%, #dcfce7 100%);
  display: flex;
  flex-direction: column;
  padding-bottom: 90px;
}

/* Header */
.header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 22px;
  background: white;
  border-radius: 0 0 24px 24px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.btn-back {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: none;
  background: #dcfce7;
  color: #22c55e;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-back:hover {
  background: #bbf7d0;
  transform: scale(1.05);
}

.btn-back i {
  font-size: 18px;
}

.header-info {
  flex: 1;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  background: #dcfce7;
  color: #16a34a;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.header-info h1 {
  font-size: 17px;
  font-weight: 700;
  color: #1e293b;
  margin: 4px 0 0;
}

.progress {
  padding: 8px 16px;
  background: #f0fdf4;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 700;
  color: #22c55e;
}

/* Loading */
.loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
}

.spinner {
  width: 44px;
  height: 44px;
  border: 4px solid #bbf7d0;
  border-top-color: #22c55e;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading p {
  color: #64748b;
  font-size: 14px;
}

/* Error */
.error {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  text-align: center;
  padding: 20px;
}

.error i {
  font-size: 50px;
  color: #fca5a5;
}

.error p {
  color: #64748b;
  margin: 0;
}

.error button {
  padding: 12px 24px;
  background: #f1f5f9;
  border: none;
  border-radius: 10px;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
}

/* Content */
.content {
  flex: 1;
  padding: 30px 20px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

/* Card */
.card-wrapper {
  perspective: 1000px;
  width: 100%;
  max-width: 380px;
  cursor: pointer;
}

.card {
  position: relative;
  width: 100%;
  height: 300px;
  transition: transform 0.5s;
  transform-style: preserve-3d;
}

.card-wrapper.flipped .card {
  transform: rotateY(180deg);
}

.card-face {
  position: absolute;
  width: 100%;
  height: 100%;
  backface-visibility: hidden;
  border-radius: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px;
}

.card-number {
  position: absolute;
  top: 18px;
  left: 18px;
  width: 38px;
  height: 38px;
  background: #22c55e;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 700;
}

.btn-audio-card {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 10;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: #22c55e;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
}

.btn-audio-card:hover {
  transform: scale(1.1);
  background: #16a34a;
}

.btn-audio-card i {
  font-size: 18px;
}

.front {
  background: linear-gradient(145deg, #ffffff, #f8fafc);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
}

.back {
  background: linear-gradient(145deg, #22c55e, #16a34a);
  transform: rotateY(180deg);
  box-shadow: 0 20px 60px rgba(34, 197, 94, 0.3);
}

.back .card-number {
  background: rgba(255, 255, 255, 0.25);
}

.word {
  font-size: 44px;
  font-weight: 800;
  color: #1e293b;
  text-align: center;
  margin-bottom: 10px;
}

.pronunciation {
  font-size: 18px;
  color: #94a3b8;
  font-style: italic;
  margin-bottom: 20px;
}

.hint {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #f1f5f9;
  border-radius: 20px;
  font-size: 13px;
  color: #94a3b8;
}

.meaning {
  font-size: 38px;
  font-weight: 800;
  color: white;
  text-align: center;
  margin-bottom: 10px;
}

.pronunciation-small {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.8);
  font-style: italic;
}

/* Example */
.example {
  width: 100%;
  max-width: 380px;
  background: white;
  border-radius: 18px;
  padding: 20px 22px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border-left: 4px solid #22c55e;
  display: flex;
  gap: 14px;
}

.example-icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  background: #dcfce7;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.example-icon i {
  font-size: 14px;
  color: #22c55e;
}

.example p {
  font-size: 14px;
  color: #334155;
  line-height: 1.7;
  margin: 0;
  font-style: italic;
}

/* Dots */
.dots {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: center;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #d1d5db;
  cursor: pointer;
  transition: all 0.2s;
}

.dot.active {
  background: #22c55e;
  width: 32px;
  border-radius: 5px;
}

.dot.done {
  background: #bbf7d0;
}

/* Empty */
.empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  text-align: center;
}

.empty i {
  font-size: 60px;
  color: #bbf7d0;
}

.empty p {
  color: #64748b;
  margin: 0;
}

.empty button {
  padding: 12px 24px;
  background: #22c55e;
  border: none;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  cursor: pointer;
}

/* Bottom Nav */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 16px 22px;
  background: white;
  border-radius: 24px 24px 0 0;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

.btn-nav {
  width: 52px;
  height: 52px;
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

.btn-nav i {
  font-size: 20px;
}

.btn-next, .btn-done {
  flex: 1;
  max-width: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 16px 24px;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-next {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
  box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
}

.btn-next:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(34, 197, 94, 0.45);
}

.btn-done {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
  box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
}

.btn-done:hover {
  transform: translateY(-2px);
}

/* Modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 28px;
  padding: 40px 36px;
  text-align: center;
  max-width: 340px;
  width: 100%;
  animation: pop 0.3s ease;
}

@keyframes pop {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-icon {
  width: 90px;
  height: 90px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  box-shadow: 0 8px 30px rgba(202, 138, 4, 0.25);
}

.modal-icon i {
  font-size: 44px;
  color: #ca8a04;
}

.modal-content h2 {
  font-size: 26px;
  color: #1e293b;
  margin: 0 0 10px;
}

.modal-content p {
  color: #64748b;
  margin: 0 0 28px;
  font-size: 15px;
  line-height: 1.6;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.modal-actions button {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline {
  background: #f1f5f9;
  border: none;
  color: #64748b;
}

.btn-outline:hover {
  background: #e2e8f0;
}

.btn-solid {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  border: none;
  color: white;
  box-shadow: 0 4px 15px rgba(34, 197, 94, 0.35);
}

.btn-solid:hover {
  transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 480px) {
  .card {
    height: 260px;
  }

  .word {
    font-size: 36px;
  }

  .meaning {
    font-size: 32px;
  }

  .example {
    padding: 16px 18px;
  }

  .modal-content {
    padding: 32px 28px;
  }
}
</style>
