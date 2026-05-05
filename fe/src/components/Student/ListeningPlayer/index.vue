<template>
  <div class="listening-page">
    <!-- Loading -->
    <div v-if="isLoading" class="loading">
      <div class="spinner"></div>
      <p>Đang tải bài nghe...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error">
      <i class="fa-solid fa-circle-exclamation"></i>
      <h2>Không tìm thấy bài học</h2>
      <p>{{ error }}</p>
      <button @click="goBack">
        <i class="fa-solid fa-arrow-left"></i>
        Quay lại
      </button>
    </div>

    <!-- Content -->
    <div v-else-if="listenings.length > 0" class="content">
      <!-- Header -->
      <div class="header">
        <button class="back-btn" @click="goBack">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="header-info">
          <span class="badge">Luyện nghe</span>
          <h1>{{ lesson?.title || 'Bài học' }}</h1>
        </div>
        <div class="progress">{{ currentIndex + 1 }} / {{ listenings.length }}</div>
      </div>

      <!-- Step Indicator -->
      <div class="step-indicator">
        <div class="step-item" :class="{ active: currentStep === 'listen', done: ['exercise', 'summary'].includes(currentStep) }">
          <span class="step-num">1</span>
          <span class="step-label">Nghe</span>
        </div>
        <div class="step-line" :class="{ active: ['exercise', 'summary'].includes(currentStep) }"></div>
        <div class="step-item" :class="{ active: currentStep === 'exercise', done: currentStep === 'summary' }">
          <span class="step-num">2</span>
          <span class="step-label">Câu hỏi</span>
        </div>
        <div class="step-line" :class="{ active: currentStep === 'summary' }"></div>
        <div class="step-item" :class="{ active: currentStep === 'summary' }">
          <span class="step-num">3</span>
          <span class="step-label">Tổng kết</span>
        </div>
      </div>

      <!-- Step 1: Listen -->
      <div v-if="currentStep === 'listen'" class="step-content">
        <div class="player-section">
          <div class="player-card">
            <div class="current-info">
              <span class="number">{{ currentIndex + 1 }}</span>
              <div>
                <h2>{{ currentListening.title }}</h2>
                <p>Bài nghe số {{ currentIndex + 1 }}</p>
              </div>
            </div>

            <!-- Audio Player -->
            <div class="audio-player">
              <button class="play-btn" @click="togglePlayOrTTS" :class="{ playing: isPlaying || isTTSPlaying }">
                <i :class="isPlaying || isTTSPlaying ? 'fa-solid fa-stop' : 'fa-solid fa-play'"></i>
              </button>
              <div class="wave-container">
                <div class="wave" :class="{ active: isPlaying || isTTSPlaying }">
                  <span></span><span></span><span></span><span></span><span></span>
                  <span></span><span></span>
                </div>
              </div>
              <div class="audio-source">
                <i class="fa-solid" :class="hasAudio ? 'fa-file-audio' : 'fa-bullhorn'"></i>
                {{ hasAudio ? 'Audio file' : 'TTS Script' }}
              </div>
            </div>

            <!-- No Audio & No Script -->
            <div v-if="!hasAudio && !currentListening.script" class="no-content">
              <i class="fa-solid fa-folder-open"></i>
              Không có nội dung audio hoặc script
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Exercise -->
      <div v-else-if="currentStep === 'exercise'" class="step-content">
        <div class="exercise-section">
          <div class="exercise-header">
            <h2>Câu hỏi bài {{ currentIndex + 1 }}</h2>
            <p>{{ exerciseIndex + 1 }} / {{ exercises.length }}</p>
          </div>

          <template v-if="exercises.length > 0">
            <div class="exercise-card">
            <div class="question-box">
              <span class="q-num">Câu {{ exerciseIndex + 1 }}</span>
              <p class="question-text">{{ currentExercise.question }}</p>
            </div>

            <!-- Multiple Choice -->
            <div v-if="currentExercise.type === 'multiple_choice'" class="options">
              <button
                v-for="(option, optIdx) in currentExercise.options"
                :key="optIdx"
                class="option-btn"
                :class="{
                  selected: selectedAnswers[currentExercise.id] === option,
                  correct: showResult && option === currentExercise.correct_answer,
                  wrong: showResult && selectedAnswers[currentExercise.id] === option && option !== currentExercise.correct_answer
                }"
                :disabled="showResult"
                @click="selectAnswer(option)"
              >
                <span class="opt-letter">{{ String.fromCharCode(65 + optIdx) }}</span>
                <span class="opt-text">{{ option }}</span>
                <i v-if="showResult && option === currentExercise.correct_answer" class="fa-solid fa-check-circle correct-icon"></i>
                <i v-if="showResult && selectedAnswers[currentExercise.id] === option && option !== currentExercise.correct_answer" class="fa-solid fa-xmark-circle wrong-icon"></i>
              </button>
            </div>

            <!-- True/False -->
            <div v-else-if="currentExercise.type === 'true_false'" class="options true-false-options">
              <button
                v-for="(option, optIdx) in ['Đúng', 'Sai']"
                :key="optIdx"
                class="option-btn"
                :class="{
                  selected: selectedAnswers[currentExercise.id] === option,
                  correct: showResult && (currentExercise.correct_answer === option || currentExercise.correct_answer === (option === 'Đúng' ? 'true' : 'false')),
                  wrong: showResult && selectedAnswers[currentExercise.id] === option && currentExercise.correct_answer !== option && currentExercise.correct_answer !== (option === 'Đúng' ? 'true' : 'false')
                }"
                :disabled="showResult"
                @click="selectAnswer(option)"
              >
                <span class="opt-text">{{ option }}</span>
                <i v-if="showResult && (currentExercise.correct_answer === option || currentExercise.correct_answer === (option === 'Đúng' ? 'true' : 'false'))" class="fa-solid fa-check-circle correct-icon"></i>
                <i v-if="showResult && selectedAnswers[currentExercise.id] === option && currentExercise.correct_answer !== option && currentExercise.correct_answer !== (option === 'Đúng' ? 'true' : 'false')" class="fa-solid fa-xmark-circle wrong-icon"></i>
              </button>
            </div>

            <!-- Fill in Blank -->
            <div v-else-if="currentExercise.type === 'fill_blank'" class="fill-blank">
              <input
                v-model="fillAnswer"
                type="text"
                placeholder="Nhập đáp án..."
                class="fill-input"
                :disabled="showResult"
                @keyup.enter="submitFillBlank"
              />
              <button class="submit-btn" @click="submitFillBlank" :disabled="showResult">
                <i class="fa-solid fa-paper-plane"></i>
              </button>
            </div>

            <!-- Explanation -->
            <div v-if="showResult && currentExercise.explanation" class="explanation">
              <i class="fa-solid fa-lightbulb"></i>
              <div>
                <strong>Giải thích:</strong>
                <p>{{ currentExercise.explanation }}</p>
              </div>
            </div>

            <!-- Result feedback -->
            <div v-if="showResult" class="result-feedback" :class="{ correct: isCorrect, wrong: !isCorrect }">
              <i :class="isCorrect ? 'fa-solid fa-check-circle' : 'fa-solid fa-xmark-circle'"></i>
              {{ isCorrect ? 'Chính xác!' : 'Chưa đúng rồi!' }}
            </div>
          </div>

          <!-- Replay Audio Button -->
          <div class="replay-audio-wrapper">
            <button class="replay-audio-btn" @click="togglePlayOrTTS" title="Nghe lại audio">
              <i :class="isPlaying || isTTSPlaying ? 'fa-solid fa-stop' : 'fa-solid fa-volume-high'"></i>
            </button>
          </div>
          </template>

          <!-- No exercises -->
          <div v-if="exercises.length === 0" class="no-exercise">
            <i class="fa-solid fa-clipboard-list"></i>
            <p>Không có câu hỏi cho bài nghe này</p>
          </div>
        </div>
      </div>

      <!-- Step 3: Summary -->
      <div v-else-if="currentStep === 'summary'" class="step-content">
        <div class="summary-section">
          <div class="summary-header">
            <i class="fa-solid fa-award summary-icon"></i>
            <h2>Kết quả bài nghe</h2>
          </div>
          <div class="summary-stats">
            <div class="stat-item">
              <span class="stat-label">Số câu đúng</span>
              <span class="stat-value">{{ score }} / {{ totalScore }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Tỷ lệ chính xác</span>
              <span class="stat-value">{{ totalScore > 0 ? Math.round((score / totalScore) * 100) : 0 }}%</span>
            </div>
          </div>
          <div class="summary-actions">
            <button class="btn-action btn-replay" @click="togglePlayOrTTS">
              <i class="fa-solid fa-volume-high"></i>
              Nghe lại
            </button>
            <button class="btn-action btn-retry" @click="reviewAgain">
              <i class="fa-solid fa-rotate-left"></i>
              Làm lại
            </button>
          </div>
          <div class="summary-review">
            <h3>Chi tiết đáp án</h3>
            <div v-for="(ex, idx) in exercises" :key="ex.id" class="review-item" :class="{ correct: isAnswerCorrect(ex.id) }">
              <span class="review-num">Câu {{ idx + 1 }}</span>
              <span class="review-q">{{ ex.question }}</span>
              <i :class="isAnswerCorrect(ex.id) ? 'fa-solid fa-check-circle correct' : 'fa-solid fa-xmark-circle wrong'"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="nav-bar">
        <button class="nav-btn prev" @click="prevStep">
          <i class="fa-solid fa-chevron-left"></i>
          Quay lại
        </button>

        <button v-if="currentStep === 'listen'" class="nav-btn next" @click="goToExercise">
          Làm bài tập
          <i class="fa-solid fa-arrow-right"></i>
        </button>
        <button v-else-if="currentStep === 'exercise' && exerciseIndex < exercises.length - 1" class="nav-btn next" @click="nextExercise">
          Câu tiếp
          <i class="fa-solid fa-arrow-right"></i>
        </button>
        <button v-else-if="currentStep === 'exercise'" class="nav-btn finish" @click="goToSummary">
          <i class="fa-solid fa-check"></i>
          Xem kết quả
        </button>
        <button v-else-if="currentStep === 'summary'" class="nav-btn finish" @click="finishLesson">
          <i class="fa-solid fa-check"></i>
          Hoàn thành
        </button>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty">
      <i class="fa-solid fa-headphones"></i>
      <h3>Chưa có bài nghe</h3>
      <p>Bài học này chưa có nội dung luyện nghe.</p>
      <button @click="goBack">Quay lại</button>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccess" class="modal">
      <div class="modal-content">
        <div class="modal-icon">
          <i class="fa-solid fa-headphones"></i>
        </div>
        <h2>Hoàn thành!</h2>
        <p>Bạn đã hoàn thành {{ listenings.length }} bài luyện nghe</p>
        <div class="score-info">
          <span>Điểm: {{ score }} / {{ totalScore }}</span>
        </div>
        <div class="modal-actions">
          <button class="btn-review" @click="reviewAgain">
            <i class="fa-solid fa-rotate-left"></i>
            Làm lại
          </button>
          <button class="btn-next" @click="goNextLesson">
            Tiếp tục
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Audio -->
    <audio
      v-if="currentListening.audio"
      ref="audioRef"
      :src="getAudioUrl(currentListening.audio)"
      @ended="onAudioEnded"
    ></audio>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiClient, { ProgressService } from '../../../services/api.js'
import { ListeningService, ListeningExerciseService } from '../../../services/api.js'

const API_URL = 'http://localhost:8000'

const route = useRoute()
const router = useRouter()

const isLoading = ref(true)
const error = ref(null)
const lesson = ref(null)
const listenings = ref([])
const exercises = ref([])
const currentIndex = ref(0)
const currentStep = ref('listen')
const exerciseIndex = ref(0)
const showSuccess = ref(false)
const isPlaying = ref(false)
const isTTSPlaying = ref(false)
const audioRef = ref(null)
const selectedAnswers = ref({})
const showResult = ref(false)
const fillAnswer = ref('')
const hasListened = ref(false)
const score = ref(0)
const totalScore = ref(0)
const startTime = ref(Date.now())

let ttsUtterance = null
let timerInterval = null
const elapsedSeconds = ref(0)

const updateTimer = () => {
  if (startTime.value) {
    elapsedSeconds.value = Math.round((Date.now() - startTime.value) / 1000)
  }
}

const currentListening = computed(() => listenings.value[currentIndex.value] || {})
const currentExercise = computed(() => exercises.value[exerciseIndex.value] || {})
const hasAudio = computed(() => !!currentListening.value.audio)
const isCorrect = computed(() => {
  const key = currentExercise.value.id
  const userAnswer = selectedAnswers.value[key]
  const correct = currentExercise.value.correct_answer
  // So sánh đúng/sai (chấp nhận cả string và boolean)
  if (currentExercise.value.type === 'true_false') {
    const userVal = (userAnswer === 'Đúng' || userAnswer === 'true' || userAnswer === true)
    const correctVal = (correct === 'Đúng' || correct === 'true' || correct === true)
    return userVal === correctVal
  }
  // So sánh string thường
  const userStr = String(userAnswer || '').trim().toLowerCase()
  const correctStr = String(correct || '').trim().toLowerCase()
  return userStr === correctStr
})

const getAudioUrl = (audio) => {
  if (!audio) return ''
  if (audio.startsWith('http')) return audio
  // Nếu đã có /storage/ thì không thêm nữa
  if (audio.startsWith('/storage/')) return `${API_URL}${audio}`
  return `${API_URL}/storage/${audio}`
}

const fetchListenings = async () => {
  const lessonId = route.params.id
  try {
    const data = await ListeningService.getByLesson(lessonId)
    listenings.value = Array.isArray(data) ? data : (data.data || [])
    if (listenings.value.length > 0) {
      await fetchExercises()
    }
  } catch (err) {
    console.error('Lỗi khi tải listenings:', err)
    error.value = 'Không thể tải bài nghe. Vui lòng thử lại.'
  }
}

const fetchExercises = async () => {
  const listeningId = listenings.value[currentIndex.value]?.id
  if (!listeningId) return
  try {
    const data = await ListeningExerciseService.getByListening(listeningId)
    exercises.value = Array.isArray(data) ? data : (data.data || [])
    totalScore.value = exercises.value.length
  } catch (err) {
    exercises.value = []
  }
}

const fetchData = async () => {
  isLoading.value = true
  error.value = null
  const lessonId = route.params.id
  try {
    const data = await apiClient.get(`/lessons/${lessonId}`)
    lesson.value = data.data
    await fetchListenings()
  } catch (err) {
    console.error('Lỗi khi tải lesson:', err)
    error.value = 'Không thể tải bài học. Vui lòng thử lại.'
  } finally {
    isLoading.value = false
  }
}

const togglePlayOrTTS = () => {
  // Stop any currently playing audio/TTS first
  if (isPlaying.value || isTTSPlaying.value) {
    if (isPlaying.value && audioRef.value) {
      audioRef.value.pause()
      audioRef.value.currentTime = 0
    }
    if (isTTSPlaying.value) {
      speechSynthesis.cancel()
    }
    isPlaying.value = false
    isTTSPlaying.value = false
    return
  }

  // If has audio file, play it
  if (hasAudio.value) {
    if (!audioRef.value) return
    audioRef.value.play()
    isPlaying.value = true
  }
  // If has script (no audio), use TTS
  else if (currentListening.value.script) {
    ttsUtterance = new SpeechSynthesisUtterance(currentListening.value.script)
    ttsUtterance.lang = 'en-US'
    ttsUtterance.rate = 0.9
    ttsUtterance.pitch = 1

    const voices = speechSynthesis.getVoices()
    const englishVoice = voices.find(v => v.lang.startsWith('en'))
    if (englishVoice) ttsUtterance.voice = englishVoice

    ttsUtterance.onend = () => { isTTSPlaying.value = false }
    ttsUtterance.onerror = () => { isTTSPlaying.value = false }

    speechSynthesis.speak(ttsUtterance)
    isTTSPlaying.value = true
  }
}

const onAudioEnded = () => {
  isPlaying.value = false
  isTTSPlaying.value = false
  if (currentStep.value === 'listen') {
    hasListened.value = true
  }
}

const goToListening = async () => {
  exerciseIndex.value = 0
  currentStep.value = 'listen'
  showResult.value = false
  fillAnswer.value = ''
  isPlaying.value = false
  isTTSPlaying.value = false
  hasListened.value = false
  score.value = 0
  if (speechSynthesis.speaking) speechSynthesis.cancel()
  if (audioRef.value) { audioRef.value.pause(); audioRef.value.currentTime = 0 }
  await fetchExercises()
}

const goToExercise = () => {
  hasListened.value = true
  currentStep.value = 'exercise'
  showResult.value = false
  fillAnswer.value = ''
  // score được reset khi bắt đầu bài mới
  score.value = 0
}

const goToSummary = () => {
  // Tính lại điểm từ đầu để đảm bảo đúng
  recalcScore()
  currentStep.value = 'summary'
  showResult.value = true
}

const recalcScore = () => {
  score.value = 0
  exercises.value.forEach(ex => {
    if (isAnswerCorrect(ex.id)) {
      score.value += 1
    }
  })
}

const isAnswerCorrect = (exerciseId) => {
  const userAnswer = selectedAnswers.value[exerciseId]
  const exercise = exercises.value.find(e => e.id === exerciseId)
  const correct = exercise?.correct_answer
  // So sánh đúng/sai (chấp nhận cả string và boolean)
  if (exercise?.type === 'true_false') {
    const userVal = (userAnswer === 'Đúng' || userAnswer === 'true' || userAnswer === true)
    const correctVal = (correct === 'Đúng' || correct === 'true' || correct === true)
    return userVal === correctVal
  }
  // So sánh string thường
  const userStr = String(userAnswer || '').trim().toLowerCase()
  const correctStr = String(correct || '').trim().toLowerCase()
  return userStr === correctStr
}

const prevListening = () => {
  goBack()
}

const prevStep = () => {
  if (currentStep.value === 'exercise') {
    if (exerciseIndex.value > 0) {
      exerciseIndex.value--
      showResult.value = false
      fillAnswer.value = ''
    } else {
      currentStep.value = 'listen'
      score.value = 0
      selectedAnswers.value = {}
    }
  } else if (currentStep.value === 'summary') {
    exerciseIndex.value = 0
    currentStep.value = 'exercise'
    showResult.value = false
  } else {
    goBack()
  }
}

const nextExercise = () => {
  if (exerciseIndex.value < exercises.value.length - 1) {
    exerciseIndex.value++
    showResult.value = false
    fillAnswer.value = ''
  }
}

const selectAnswer = (option) => {
  if (showResult.value) return
  const key = currentExercise.value.id
  selectedAnswers.value[key] = String(option).trim()
  checkAnswer()
}

const submitFillBlank = () => {
  if (showResult.value || !fillAnswer.value.trim()) return
  const key = currentExercise.value.id
  selectedAnswers.value[key] = String(fillAnswer.value).trim()
  checkAnswer()
}

const checkAnswer = () => {
  showResult.value = true
  // Tính lại điểm từ đầu dựa trên tất cả đáp án hiện tại
  recalcScore()
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
  exerciseIndex.value = 0
  currentStep.value = 'exercise'
  showSuccess.value = false
  showResult.value = false
  selectedAnswers.value = {}
  fillAnswer.value = ''
  score.value = 0
  hasListened.value = true
  startTime.value = Date.now()
  elapsedSeconds.value = 0
  timerInterval = setInterval(updateTimer, 1000)
}

const goNextLesson = async () => {
  showSuccess.value = false
  window.location.href = `/student/khoa-hoc/${lesson.value?.chapter?.course_id}`
}

const goBack = () => {
  router.back()
}

onMounted(() => {
  fetchData()
  timerInterval = setInterval(updateTimer, 1000)
})

onUnmounted(() => {
  if (audioRef.value) audioRef.value.pause()
  if (speechSynthesis.speaking) speechSynthesis.cancel()
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.listening-page {
  min-height: 100vh;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
}

/* Loading & Error */
.loading, .error, .empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px;
}

.loading .spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.loading p, .error p, .empty p {
  color: #64748b;
  font-size: 15px;
}

.error i, .empty i {
  font-size: 56px;
  color: #cbd5e1;
  margin-bottom: 16px;
}

.error h2, .empty h3 {
  font-size: 20px;
  color: #1e293b;
  margin-bottom: 8px;
}

.error button, .empty button {
  margin-top: 16px;
  padding: 12px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.error button:hover, .empty button:hover {
  background: #2563eb;
}

/* Header */
.header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px 24px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
}

.back-btn {
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

.back-btn:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.header-info {
  flex: 1;
}

.header-info .badge {
  display: inline-block;
  padding: 4px 12px;
  background: #dbeafe;
  color: #2563eb;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.header-info h1 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.progress {
  padding: 8px 16px;
  background: #f1f5f9;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #3b82f6;
}

/* Step Indicator */
.step-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  gap: 0;
}

.step-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 20px;
  border-radius: 12px;
  background: #f1f5f9;
  color: #94a3b8;
  transition: all 0.3s;
}

.step-item.active {
  background: #dbeafe;
  color: #2563eb;
}

.step-item.done {
  background: #dcfce7;
  color: #16a34a;
}

.step-num {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: currentColor;
  color: white;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.step-item.done .step-num {
  background: #16a34a;
}

.step-label {
  font-size: 14px;
  font-weight: 600;
}

.step-line {
  width: 60px;
  height: 3px;
  background: #e2e8f0;
  margin: 0 8px;
}

/* Content */
.content {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding-bottom: 90px;
}

.step-content {
  flex: 1;
}

/* Player Section */
.player-section {
  max-width: 800px;
  margin: 0 auto;
  padding: 32px 24px;
  width: 100%;
}

.player-card {
  background: white;
  border-radius: 20px;
  padding: 28px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.04);
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.current-info {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 28px;
}

.number {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  font-size: 20px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.current-info h2 {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.current-info p {
  font-size: 14px;
  color: #94a3b8;
}

/* Audio Player */
.audio-player {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 28px;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: 16px;
  margin-bottom: 20px;
}

.play-btn {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border: none;
  color: white;
  font-size: 22px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s;
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
  flex-shrink: 0;
}

.play-btn:hover {
  transform: scale(1.06);
  box-shadow: 0 12px 32px rgba(59, 130, 246, 0.5);
}

.play-btn.playing {
  animation: glow 1.5s ease-in-out infinite;
}

@keyframes glow {
  0%, 100% { box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4); }
  50% { box-shadow: 0 8px 40px rgba(59, 130, 246, 0.6), 0 0 60px rgba(59, 130, 246, 0.2); }
}

.wave-container {
  flex: 1;
  display: flex;
  align-items: center;
}

.wave {
  display: flex;
  align-items: center;
  gap: 4px;
}

.wave span {
  display: block;
  width: 5px;
  height: 24px;
  background: #cbd5e1;
  border-radius: 4px;
  transition: all 0.3s;
}

.wave.active span {
  background: #3b82f6;
}

.wave.active span:nth-child(1) { animation: wave 0.6s ease-in-out infinite; height: 16px; }
.wave.active span:nth-child(2) { animation: wave 0.6s ease-in-out 0.1s infinite; height: 32px; }
.wave.active span:nth-child(3) { animation: wave 0.6s ease-in-out 0.2s infinite; height: 24px; }
.wave.active span:nth-child(4) { animation: wave 0.6s ease-in-out 0.3s infinite; height: 36px; }
.wave.active span:nth-child(5) { animation: wave 0.6s ease-in-out 0.4s infinite; height: 20px; }
.wave.active span:nth-child(6) { animation: wave 0.6s ease-in-out 0.5s infinite; height: 28px; }
.wave.active span:nth-child(7) { animation: wave 0.6s ease-in-out 0.6s infinite; height: 16px; }

@keyframes wave {
  0%, 100% { transform: scaleY(0.5); }
  50% { transform: scaleY(1); }
}

/* hide unused */

.no-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  background: #fef3c7;
  border-radius: 12px;
  font-size: 14px;
  color: #92400e;
  margin-bottom: 16px;
}

.audio-source {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: #f1f5f9;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

.audio-source i {
  color: #3b82f6;
}

/* Summary Section */
.summary-section {
  max-width: 800px;
  margin: 0 auto;
  padding: 32px 24px;
  width: 100%;
}

.summary-header {
  text-align: center;
  margin-bottom: 32px;
}

.summary-icon {
  font-size: 48px;
  color: #8b5cf6;
  margin-bottom: 16px;
}

.summary-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
}

.summary-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 32px;
}

.stat-item {
  background: #f8fafc;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
}

.stat-label {
  display: block;
  font-size: 14px;
  color: #64748b;
  margin-bottom: 8px;
}

.stat-value {
  display: block;
  font-size: 28px;
  font-weight: 700;
  color: #8b5cf6;
}

.summary-actions {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.btn-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-action i {
  font-size: 16px;
}

.btn-replay {
  background: #dbeafe;
  color: #2563eb;
}

.btn-replay:hover {
  background: #bfdbfe;
}

.btn-retry {
  background: #fce7f3;
  color: #db2777;
}

.btn-retry:hover {
  background: #fbcfe8;
}

.summary-review {
  background: #f8fafc;
  border-radius: 12px;
  padding: 20px;
}

.summary-review h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 16px;
}

.review-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 8px;
  background: white;
}

.review-item.correct {
  border-left: 3px solid #22c55e;
}

.review-item:not(.correct) {
  border-left: 3px solid #ef4444;
}

.review-num {
  font-weight: 600;
  color: #1e293b;
  min-width: 60px;
}

.review-q {
  flex: 1;
  font-size: 14px;
  color: #64748b;
}

.review-item .correct {
  color: #22c55e;
}

.review-item .wrong {
  color: #ef4444;
}

/* Exercise Section */
.exercise-section {
  max-width: 800px;
  margin: 0 auto;
  padding: 32px 24px;
  width: 100%;
}

.exercise-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.exercise-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
}

.exercise-header p {
  font-size: 14px;
  font-weight: 600;
  color: #3b82f6;
  background: #dbeafe;
  padding: 6px 14px;
  border-radius: 20px;
}

.exercise-card {
  background: white;
  border-radius: 20px;
  padding: 28px;
  border: 1px solid #e2e8f0;
}

.replay-audio-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.replay-audio-btn {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 1px solid #d1d5db;
  background: #f3f4f6;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #374151;
  font-size: 18px;
  transition: all 0.2s;
}

.replay-audio-btn:hover {
  background: #e5e7eb;
  color: #1f2937;
}

.question-box {
  margin-bottom: 24px;
}

.q-num {
  display: inline-block;
  padding: 4px 12px;
  background: #3b82f6;
  color: white;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 12px;
}

.question-text {
  font-size: 17px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.6;
}

/* Options */
.options {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.option-btn {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
  width: 100%;
  font-size: 15px;
  color: #334155;
  font-weight: 500;
}

.option-btn:hover:not(:disabled) {
  border-color: #3b82f6;
  background: #eff6ff;
}

.option-btn.selected {
  border-color: #3b82f6;
  background: #dbeafe;
}

.option-btn.correct {
  border-color: #22c55e;
  background: #dcfce7;
}

.option-btn.wrong {
  border-color: #ef4444;
  background: #fef2f2;
}

.opt-letter {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: #e2e8f0;
  color: #64748b;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.option-btn.selected .opt-letter {
  background: #3b82f6;
  color: white;
}

.option-btn.correct .opt-letter {
  background: #22c55e;
  color: white;
}

.option-btn.wrong .opt-letter {
  background: #ef4444;
  color: white;
}

.opt-text {
  flex: 1;
}

.correct-icon {
  color: #22c55e;
  font-size: 20px;
}

.wrong-icon {
  color: #ef4444;
  font-size: 20px;
}

/* Fill Blank */
.fill-blank {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.fill-input {
  flex: 1;
  padding: 16px 20px;
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  font-size: 16px;
  background: #f8fafc;
  transition: all 0.2s;
}

.fill-input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
}

.submit-btn {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: #3b82f6;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.submit-btn:hover {
  background: #2563eb;
}

/* Explanation */
.explanation {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #fffbeb;
  border-radius: 12px;
  border-left: 4px solid #f59e0b;
  margin-bottom: 16px;
}

.explanation > i {
  color: #f59e0b;
  font-size: 20px;
  flex-shrink: 0;
  margin-top: 2px;
}

.explanation strong {
  color: #92400e;
  display: block;
  margin-bottom: 4px;
}

.explanation p {
  color: #78350f;
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
}

/* Result Feedback */
.result-feedback {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 16px;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 700;
}

.result-feedback.correct {
  background: #dcfce7;
  color: #16a34a;
}

.result-feedback.wrong {
  background: #fef2f2;
  color: #dc2626;
}

.result-feedback i {
  font-size: 22px;
}

/* No Exercise */
.no-exercise {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
}

.no-exercise i {
  font-size: 48px;
  color: #cbd5e1;
  margin-bottom: 16px;
}

.no-exercise p {
  color: #64748b;
  font-size: 15px;
}

/* Nav Bar */
.nav-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  background: white;
  border-top: 1px solid #e2e8f0;
  gap: 16px;
}

.nav-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.nav-btn.prev {
  background: #f1f5f9;
  color: #64748b;
}

.nav-btn.prev:hover:not(:disabled) {
  background: #e2e8f0;
}

.nav-btn.prev:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.nav-btn.next {
  background: #3b82f6;
  color: white;
}

.nav-btn.next:hover {
  background: #2563eb;
}

.nav-btn.finish {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
}

.nav-btn.finish:hover {
  background: #16a34a;
}

/* Modal */
.modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 24px;
}

.modal-content {
  background: white;
  border-radius: 24px;
  padding: 40px;
  text-align: center;
  max-width: 380px;
  width: 100%;
}

.modal-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.modal-icon i {
  font-size: 36px;
  color: #3b82f6;
}

.modal-content h2 {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 8px;
}

.modal-content p {
  color: #64748b;
  font-size: 15px;
  margin-bottom: 12px;
}

.score-info {
  display: inline-block;
  padding: 10px 20px;
  background: #dbeafe;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 800;
  color: #2563eb;
  margin-bottom: 24px;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.btn-review, .btn-next {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-review {
  background: #f1f5f9;
  color: #64748b;
}

.btn-review:hover {
  background: #e2e8f0;
}

.btn-next {
  background: #3b82f6;
  color: white;
}

.btn-next:hover {
  background: #2563eb;
}

/* Responsive */
@media (max-width: 600px) {
  .header {
    padding: 16px 20px;
  }

  .header-info h1 {
    font-size: 16px;
  }

  .step-indicator {
    padding: 16px;
  }

  .step-item {
    padding: 8px 14px;
  }

  .step-label {
    display: none;
  }

  .step-line {
    width: 30px;
  }

  .player-section, .exercise-section {
    padding: 20px 16px;
  }

  .player-card, .exercise-card {
    padding: 20px;
  }

  .audio-player {
    padding: 20px;
    gap: 12px;
  }

  .play-btn {
    width: 56px;
    height: 56px;
  }

  .current-info h2 {
    font-size: 17px;
  }

  .summary-actions {
    flex-direction: column;
  }

  .nav-bar {
    padding: 12px 16px;
  }

  .nav-btn {
    padding: 12px 16px;
    font-size: 13px;
  }

  .modal-content {
    padding: 28px;
  }
}
</style>
