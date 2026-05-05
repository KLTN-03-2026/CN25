<template>
  <div class="speaking-player">
    <!-- Loading -->
    <div v-if="isLoading" class="loading-screen">
      <div class="loader"></div>
      <p>Đang tải bài luyện nói...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-screen">
      <div class="error-icon">
        <i class="fa-solid fa-circle-exclamation"></i>
      </div>
      <h2>Không tìm thấy bài học</h2>
      <p>{{ error }}</p>
      <button class="btn-back" @click="goBack">
        <i class="fa-solid fa-arrow-left"></i>
        Quay lại
      </button>
    </div>

    <!-- Content -->
    <div v-else-if="speakings.length > 0" class="content-wrapper">
      <!-- Header -->
      <div class="player-header">
        <button class="btn-back" @click="goBack">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="header-info">
          <span class="badge">Luyện nói</span>
          <h1>{{ lesson?.title || 'Bài học' }}</h1>
        </div>
        <div class="progress-badge">
          {{ currentIndex + 1 }} / {{ speakings.length }}
        </div>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <!-- Speaking Card -->
        <div class="speaking-card">
          <div class="speaking-header">
            <span class="speaking-number">{{ currentIndex + 1 }}</span>
            <span class="type-badge">{{ getTypeLabel(currentSpeaking.type) }}</span>
          </div>

          <!-- Prompt -->
          <div class="prompt-section">
            <p class="prompt-text">{{ currentSpeaking.content }}</p>
          </div>

          <!-- Sample Answer -->
          <div class="sample-section" v-if="currentSpeaking.sample_answer">
            <div class="sample-box">
              <span class="label">
                <i class="fa-solid fa-lightbulb"></i>
                Câu mẫu
              </span>
              <p>{{ currentSpeaking.sample_answer }}</p>
            </div>
          </div>

          <!-- Keywords -->
          <div class="keywords-section" v-if="currentSpeaking.keywords">
            <span class="label">
              <i class="fa-solid fa-key"></i>
              Từ khóa
            </span>
            <div class="keywords-list">
              <span
                v-for="kw in currentSpeaking.keywords.split(',')"
                :key="kw"
                class="keyword-tag"
              >
                {{ kw.trim() }}
              </span>
            </div>
          </div>

          <!-- Recording -->
          <div class="recording-section">
            <button
              class="btn-record"
              :class="{ recording: isRecording }"
              @click="toggleRecording"
            >
              <i :class="isRecording ? 'fa-solid fa-stop' : 'fa-solid fa-microphone'"></i>
            </button>
            <p class="record-hint">
              {{ isRecording ? 'Đang ghi âm...' : 'Nhấn để ghi âm' }}
            </p>
          </div>

          <!-- Real-time Transcript -->
          <div v-if="interimTranscript" class="transcript-preview">
            <span class="transcript-label">
              <i class="fa-solid fa-comment-dots"></i>
              Bạn đang nói:
            </span>
            <p class="transcript-text">{{ interimTranscript }}</p>
          </div>

          <!-- Recorded Audio -->
          <div v-if="recordedUrl" class="recorded-section">
            <div class="recorded-header">
              <span class="label">
                <i class="fa-solid fa-play-circle"></i>
                Bản ghi của bạn
              </span>
              <button class="btn-delete" @click="deleteRecording">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
            <audio :src="recordedUrl" controls class="recorded-audio"></audio>

            <!-- Submit Button -->
            <button
              class="btn-submit"
              :disabled="isEvaluating"
              @click="submitForEvaluation"
            >
              <span v-if="isEvaluating">
                <i class="fa-solid fa-spinner fa-spin"></i>
                Đang đánh giá...
              </span>
              <span v-else>
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                Gửi đánh giá AI
              </span>
            </button>
          </div>
        </div>

        <!-- Speaking List -->
        <div class="speaking-list">
          <h3>Danh sách bài luyện nói</h3>
          <div class="speaking-grid">
            <button
              v-for="(item, index) in speakings"
              :key="item.id"
              class="speaking-item"
              :class="{ active: index === currentIndex, learned: index < currentIndex }"
              @click="goToSpeaking(index)"
            >
              <span class="item-number">{{ index + 1 }}</span>
              <span class="item-type">{{ getTypeLabel(item.type) }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="bottom-nav">
        <button
          class="btn-nav btn-prev"
          :disabled="currentIndex === 0"
          @click="prevSpeaking"
        >
          <i class="fa-solid fa-chevron-left"></i>
          Bài trước
        </button>

        <div class="nav-dots">
          <span
            v-for="(item, index) in speakings"
            :key="item.id"
            class="dot"
            :class="{ active: index === currentIndex }"
            @click="goToSpeaking(index)"
          ></span>
        </div>

        <button
          v-if="currentIndex < speakings.length - 1"
          class="btn-nav btn-next"
          @click="nextSpeaking"
        >
          Bài tiếp
          <i class="fa-solid fa-chevron-right"></i>
        </button>
        <button
          v-else
          class="btn-nav btn-complete"
          @click="finishLesson"
        >
          <i class="fa-solid fa-check"></i>
          Hoàn thành
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">
        <i class="fa-solid fa-microphone"></i>
      </div>
      <h3>Chưa có bài luyện nói</h3>
      <p>Bài học này chưa có nội dung luyện nói.</p>
      <button class="btn-back-empty" @click="goBack">Quay lại</button>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccess" class="modal-overlay">
      <div class="success-modal">
        <div class="success-icon">
          <i class="fa-solid fa-microphone-alt"></i>
        </div>
        <h2>Chúc mừng!</h2>
        <p>Bạn đã hoàn thành {{ speakings.length }} bài luyện nói</p>
        <div class="success-actions">
          <button class="btn-review" @click="reviewAgain">
            <i class="fa-solid fa-rotate-left"></i>
            Luyện lại
          </button>
          <button class="btn-continue" @click="goNextLesson">
            Tiếp tục
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- AI Evaluation Result Modal -->
    <div v-if="showEvaluationResult" class="modal-overlay" @click.self="closeEvaluationResult">
      <div class="evaluation-modal">
        <button class="btn-close" @click="closeEvaluationResult">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Score Circle -->
        <div class="score-section">
          <div class="score-circle" :class="evaluationResult.score >= 70 ? 'good' : 'needs-work'">
            <span class="score-value">{{ evaluationResult.score }}</span>
            <span class="score-max">/100</span>
          </div>
          <div class="score-label" :class="evaluationResult.score >= 90 ? 'excellent' : evaluationResult.score >= 70 ? 'good' : 'needs-work'">
            <i :class="evaluationResult.score >= 90 ? 'fa-solid fa-trophy' : evaluationResult.score >= 70 ? 'fa-solid fa-thumbs-up' : 'fa-solid fa-heart'"></i>
            {{ evaluationResult.score >= 90 ? 'Xuất sắc!' : evaluationResult.score >= 70 ? 'Tốt lắm!' : evaluationResult.score >= 50 ? 'Khá ổn!' : 'Cần luyện thêm!' }}
          </div>
        </div>

        <!-- Feedback Message -->
        <div class="feedback-message">
          <p>{{ evaluationResult.feedback?.message }}</p>
        </div>

        <!-- Transcription -->
        <div class="transcription-section">
          <div class="transcription-box">
            <span class="transcription-label">
              <i class="fa-solid fa-microphone-lines"></i>
              Bạn đã nói
            </span>
            <p class="transcription-text">{{ evaluationResult.user_text }}</p>
          </div>
          <div class="expected-box">
            <span class="transcription-label">
              <i class="fa-solid fa-bullseye"></i>
              Câu mẫu
            </span>
            <p class="transcription-text">{{ evaluationResult.expected_answer }}</p>
          </div>
        </div>

        <!-- Missing Keywords -->
        <div v-if="evaluationResult.feedback?.missing_keywords?.length > 0" class="missing-section">
          <span class="missing-label">
            <i class="fa-solid fa-exclamation-circle"></i>
            Từ khóa còn thiếu
          </span>
          <div class="missing-tags">
            <span v-for="kw in evaluationResult.feedback.missing_keywords" :key="kw" class="missing-tag">
              {{ kw }}
            </span>
          </div>
        </div>

        <!-- Suggestions -->
        <div v-if="evaluationResult.feedback?.suggestions?.length > 0" class="suggestions-section">
          <span class="suggestions-label">
            <i class="fa-solid fa-lightbulb"></i>
            Gợi ý cải thiện
          </span>
          <ul class="suggestions-list">
            <li v-for="(sug, index) in evaluationResult.feedback.suggestions" :key="index">
              {{ sug }}
            </li>
          </ul>
        </div>

        <!-- Actions -->
        <div class="evaluation-actions">
          <button class="btn-retry" @click="retrySpeaking">
            <i class="fa-solid fa-rotate-left"></i>
            Thử lại
          </button>
          <button class="btn-next" @click="nextAfterEvaluation">
            {{ currentIndex < speakings.length - 1 ? 'Bài tiếp' : 'Hoàn thành' }}
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
import apiClient from '../../../services/api.js'
import { SpeakingExerciseService, ProgressService } from '../../../services/api.js'

const route = useRoute()
const router = useRouter()

// State
const isLoading = ref(true)
const error = ref(null)
const lesson = ref(null)
const speakings = ref([])
const currentIndex = ref(0)
const isRecording = ref(false)
const recordedBlob = ref(null)
const recordedUrl = ref(null)
const showSuccess = ref(false)
const mediaRecorder = ref(null)
const isEvaluating = ref(false)
const showEvaluationResult = ref(false)
const evaluationResult = ref(null)
const audioChunks = ref([])
const interimTranscript = ref('')
const speechRecognitionActive = ref(false)
const startTime = ref(Date.now())

let timerInterval = null
const elapsedSeconds = ref(0)

const updateTimer = () => {
  if (startTime.value) {
    elapsedSeconds.value = Math.round((Date.now() - startTime.value) / 1000)
  }
}

// Computed
const currentSpeaking = computed(() => speakings.value[currentIndex.value] || {})

// Methods
const fetchData = async () => {
  isLoading.value = true
  error.value = null

  const lessonId = route.params.id

  try {
    const data = await SpeakingExerciseService.getByLesson(lessonId)
    const lessonRes = await apiClient.get(`/lessons/${lessonId}`)
    lesson.value = lessonRes.data
    speakings.value = data || []
  } catch (err) {
    console.error('Lỗi khi tải dữ liệu:', err)
    error.value = 'Không thể tải bài học. Vui lòng thử lại.'
  } finally {
    isLoading.value = false
  }
}

const getTypeLabel = (type) => {
  const labels = {
    'repeat': 'Lặp lại',
    'read': 'Đọc to',
    'qa': 'Hỏi đáp',
    'describe': 'Mô tả'
  }
  return labels[type] || 'Luyện nói'
}

const toggleRecording = async () => {
  if (isRecording.value) {
    stopRecording()
  } else {
    await startRecording()
  }
}

const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder.value = new MediaRecorder(stream, { mimeType: 'audio/webm' })

    audioChunks.value = []

    mediaRecorder.value.ondataavailable = (e) => {
      if (e.data.size > 0) {
        audioChunks.value.push(e.data)
      }
    }

    mediaRecorder.value.onstop = () => {
      const blob = new Blob(audioChunks.value, { type: 'audio/webm' })
      recordedBlob.value = blob
      recordedUrl.value = URL.createObjectURL(blob)
      stream.getTracks().forEach(track => track.stop())
    }

    mediaRecorder.value.start()
    isRecording.value = true

    // Bắt đầu Web Speech API để nhận diện real-time
    startSpeechRecognition()
  } catch (err) {
    console.error('Lỗi ghi âm:', err)
    alert('Không thể truy cập microphone. Vui lòng kiểm tra quyền.')
  }
}

let recognition = null
const startSpeechRecognition = () => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
  if (!SpeechRecognition) {
    console.warn('Web Speech API not supported')
    return
  }

  recognition = new SpeechRecognition()
  recognition.continuous = true
  recognition.interimResults = true
  recognition.lang = 'vi-VN'

  recognition.onresult = (event) => {
    let finalTranscript = ''
    let interimTranscript_text = ''

    for (let i = event.resultIndex; i < event.results.length; i++) {
      const transcript = event.results[i][0].transcript
      if (event.results[i].isFinal) {
        finalTranscript += transcript
      } else {
        interimTranscript_text += transcript
      }
    }

    interimTranscript.value = finalTranscript + interimTranscript_text
  }

  recognition.onerror = (event) => {
    console.warn('Speech recognition error:', event.error)
  }

  recognition.onend = () => {
    if (isRecording.value && speechRecognitionActive.value) {
      try { recognition.start() } catch (e) {}
    }
  }

  try {
    recognition.start()
    speechRecognitionActive.value = true
  } catch (e) {
    console.warn('Could not start speech recognition:', e)
  }
}

const stopRecording = () => {
  if (mediaRecorder.value && isRecording.value) {
    mediaRecorder.value.stop()
    isRecording.value = false
    speechRecognitionActive.value = false
    if (recognition) {
      try { recognition.stop() } catch (e) {}
    }
  }
}

const deleteRecording = () => {
  if (recordedUrl.value) {
    URL.revokeObjectURL(recordedUrl.value)
  }
  recordedBlob.value = null
  recordedUrl.value = null
  interimTranscript.value = ''
  evaluationResult.value = null
  showEvaluationResult.value = false
}

const submitForEvaluation = async () => {
  if (!recordedBlob.value && !interimTranscript.value) return

  isEvaluating.value = true

  try {
    let result

    // Ưu tiên dùng Web Speech API transcription (nhanh, miễn phí)
    if (interimTranscript.value && interimTranscript.value.trim().length > 0) {
      const userText = interimTranscript.value.trim()
      result = {
        success: true,
        score: calculateLocalScore(userText, currentSpeaking.value.sample_answer || currentSpeaking.value.content, currentSpeaking.value.keywords),
        user_text: userText,
        expected_answer: currentSpeaking.value.sample_answer || currentSpeaking.value.content,
        feedback: getLocalFeedback(userText, currentSpeaking.value.sample_answer || currentSpeaking.value.content, currentSpeaking.value.type, currentSpeaking.value.keywords),
        word_match: calculateWordMatch(userText, currentSpeaking.value.sample_answer || currentSpeaking.value.content)
      }
    } else if (recordedBlob.value) {
      // Fallback: gửi lên backend dùng Whisper
      result = await SpeakingExerciseService.evaluate(currentSpeaking.value.id, recordedBlob.value)
    } else {
      alert('Vui lòng ghi âm hoặc nói để đánh giá.')
      return
    }

    evaluationResult.value = result
    showEvaluationResult.value = true
  } catch (err) {
    console.error('Lỗi đánh giá:', err)
    alert(err.response?.data?.error || 'Không thể đánh giá. Vui lòng thử lại.')
  } finally {
    isEvaluating.value = false
  }
}

// Tính điểm cục bộ (không cần backend)
const calculateLocalScore = (userText, expectedAnswer, keywords) => {
  const userWords = normalizeText(userText.toLowerCase())
  const expectedWords = normalizeText(expectedAnswer.toLowerCase())

  const matched = userWords.filter(w => expectedWords.includes(w))
  const wordMatchScore = expectedWords.length > 0 ? (matched.length / expectedWords.length) * 60 : 0

  let keywordScore = 0
  if (keywords) {
    const keywordList = keywords.split(',').map(k => k.trim().toLowerCase())
    const keywordFound = keywordList.filter(k => userText.toLowerCase().includes(k)).length
    keywordScore = keywordList.length > 0 ? (keywordFound / keywordList.length) * 40 : 0
  }

  return Math.min(100, Math.round(wordMatchScore + keywordScore))
}

const normalizeText = (text) => {
  return text.replace(/[^\w\s]/gi, '').split(/\s+/).filter(w => w.length > 0)
}

const calculateWordMatch = (userText, expectedAnswer) => {
  const userWords = normalizeText(userText.toLowerCase())
  const expectedWords = normalizeText(expectedAnswer.toLowerCase())
  const matched = userWords.filter(w => expectedWords.includes(w))
  const missing = expectedWords.filter(w => !userWords.includes(w))

  return {
    matched,
    missing: missing.slice(0, 5),
    match_rate: expectedWords.length > 0 ? Math.round((matched.length / expectedWords.length) * 100) : 0
  }
}

const getLocalFeedback = (userText, expectedAnswer, type, keywords) => {
  const score = calculateLocalScore(userText, expectedAnswer, keywords)
  const wordMatch = calculateWordMatch(userText, expectedAnswer)

  let level, message
  if (score >= 90) {
    level = 'excellent'
    message = 'Xuất sắc! Bạn phát âm rất chuẩn xác.'
  } else if (score >= 70) {
    level = 'good'
    message = 'Tốt lắm! Cần cải thiện một chút về ngữ điệu và tốc độ.'
  } else if (score >= 50) {
    level = 'fair'
    message = 'Khá ổn! Hãy chú ý đến từ khóa và cách phát âm.'
  } else {
    level = 'poor'
    message = 'Cần luyện tập thêm. Hãy nghe câu mẫu và thử lại nhé!'
  }

  const suggestions = []
  if (score < 90) suggestions.push('Nghe câu mẫu và chú ý cách phát âm từng từ.')
  if (score < 70) suggestions.push('Tập nói chậm rãi, rõ ràng từng câu.')
  if (wordMatch.missing.length > 3) suggestions.push('Hãy tập trung vào các từ: ' + wordMatch.missing.slice(0, 3).join(', '))

  if (type === 'repeat') suggestions.push('Nhớ lặp lại đúng ngữ điệu như câu mẫu.')
  else if (type === 'read') suggestions.push('Đọc to rõ ràng, chú ý dấu câu.')
  else if (type === 'qa') suggestions.push('Trả lời đủ ý và đúng trọng tâm câu hỏi.')

  return { level, message, missing_keywords: wordMatch.missing, suggestions }
}

const retrySpeaking = () => {
  showEvaluationResult.value = false
  evaluationResult.value = null
  deleteRecording()
}

const nextAfterEvaluation = () => {
  showEvaluationResult.value = false
  evaluationResult.value = null
  if (currentIndex.value < speakings.value.length - 1) {
    nextSpeaking()
  } else {
    finishLesson()
  }
}

const closeEvaluationResult = () => {
  showEvaluationResult.value = false
}

const prevSpeaking = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    deleteRecording()
  }
}

const nextSpeaking = () => {
  if (currentIndex.value < speakings.value.length - 1) {
    currentIndex.value++
    deleteRecording()
  }
}

const goToSpeaking = (index) => {
  currentIndex.value = index
  deleteRecording()
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
  deleteRecording()
  showSuccess.value = false
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
  if (mediaRecorder.value && isRecording.value) {
    mediaRecorder.value.stop()
  }
  if (recognition) {
    try { recognition.stop() } catch (e) {}
  }
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.speaking-player {
  min-height: 100vh;
  background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
  display: flex;
  flex-direction: column;
}

/* Loading */
.loading-screen {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.loader {
  width: 48px;
  height: 48px;
  border: 4px solid #fbcfe8;
  border-top-color: #db2777;
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
  padding: 20px;
}

.error-icon, .empty-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #fef2f2;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.error-icon i, .empty-icon i {
  font-size: 36px;
  color: #ef4444;
}

.empty-icon {
  background: #fce7f3;
}

.empty-icon i {
  color: #db2777;
}

.error-screen h2, .empty-state h3 {
  font-size: 20px;
  color: #1e293b;
  margin-bottom: 8px;
}

.error-screen p, .empty-state p {
  color: #64748b;
  margin-bottom: 16px;
}

/* Header */
.player-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
}

.btn-back {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: none;
  background: #fce7f3;
  color: #db2777;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-back:hover {
  background: #fbcfe8;
}

.btn-back i {
  font-size: 18px;
}

.header-info {
  flex: 1;
}

.header-info .badge {
  display: inline-block;
  padding: 4px 10px;
  background: #fce7f3;
  color: #db2777;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.header-info h1 {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.progress-badge {
  padding: 8px 14px;
  background: #fce7f3;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 700;
  color: #db2777;
}

/* Main Content */
.content-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding-bottom: 80px;
}

.main-content {
  flex: 1;
  padding: 20px;
  max-width: 600px;
  margin: 0 auto;
  width: 100%;
}

/* Speaking Card */
.speaking-card {
  background: white;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 20px;
}

.speaking-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.speaking-number {
  width: 36px;
  height: 36px;
  line-height: 36px;
  background: linear-gradient(135deg, #db2777, #ec4899);
  color: white;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 700;
  text-align: center;
}

.type-badge {
  padding: 6px 12px;
  background: #fce7f3;
  color: #db2777;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

/* Prompt */
.prompt-section {
  margin-bottom: 20px;
}

.prompt-text {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.5;
  margin: 0;
}

/* Sample */
.sample-section {
  margin-bottom: 20px;
}

.sample-box {
  background: #fdf2f8;
  border-radius: 12px;
  padding: 16px;
}

.sample-box .label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #db2777;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.sample-box p {
  font-size: 15px;
  color: #831843;
  line-height: 1.6;
  margin: 0;
}

/* Keywords */
.keywords-section {
  margin-bottom: 20px;
}

.keywords-section .label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.keywords-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.keyword-tag {
  padding: 6px 12px;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 20px;
  font-size: 13px;
}

/* Recording */
.recording-section {
  text-align: center;
  padding: 24px 0;
}

.btn-record {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #db2777, #ec4899);
  border: none;
  color: white;
  font-size: 28px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  box-shadow: 0 4px 20px rgba(219, 39, 119, 0.4);
  margin-bottom: 12px;
}

.btn-record:hover {
  transform: scale(1.05);
}

.btn-record.recording {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
}

.record-hint {
  font-size: 14px;
  color: #64748b;
}

/* Transcript Preview */
.transcript-preview {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
  padding: 12px 16px;
  margin-top: 12px;
  animation: fadeIn 0.3s ease;
}

.transcript-preview .transcript-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #0369a1;
  margin-bottom: 6px;
}

.transcript-preview .transcript-text {
  font-size: 15px;
  color: #0c4a6e;
  line-height: 1.5;
  margin: 0;
}

/* Recorded */
.recorded-section {
  padding-top: 20px;
  border-top: 1px dashed #e2e8f0;
}

.recorded-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.recorded-header .label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
}

.recorded-header .label i {
  color: #22c55e;
}

.btn-delete {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #fef2f2;
  border: none;
  color: #ef4444;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-delete:hover {
  background: #fee2e2;
}

.recorded-audio {
  width: 100%;
}

/* Speaking List */
.speaking-list {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.speaking-list h3 {
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 12px;
}

.speaking-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.speaking-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f8fafc;
  border: 2px solid transparent;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.speaking-item:hover {
  background: #fce7f3;
}

.speaking-item.active {
  background: #fce7f3;
  border-color: #db2777;
}

.speaking-item.learned {
  background: #fdf2f8;
}

.item-number {
  width: 28px;
  height: 28px;
  line-height: 28px;
  background: #e2e8f0;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-align: center;
}

.speaking-item.active .item-number {
  background: #db2777;
  color: white;
}

.item-type {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
}

/* Bottom Navigation */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  background: white;
  border-top: 1px solid #e2e8f0;
  gap: 12px;
}

.btn-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-prev {
  background: #f1f5f9;
  color: #64748b;
}

.btn-prev:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-prev:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-next {
  background: #db2777;
  color: white;
}

.btn-next:hover {
  background: #be185d;
}

.btn-complete {
  background: linear-gradient(135deg, #db2777, #ec4899);
  color: white;
}

.btn-complete:hover {
  background: #be185d;
}

.nav-dots {
  display: flex;
  gap: 6px;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #d1d5db;
  cursor: pointer;
  transition: all 0.2s;
}

.dot.active {
  background: #db2777;
  width: 24px;
  border-radius: 4px;
}

/* Success Modal */
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
}

.success-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #fce7f3;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.success-icon i {
  font-size: 40px;
  color: #db2777;
}

.success-modal h2 {
  font-size: 24px;
  color: #1e293b;
  margin-bottom: 8px;
}

.success-modal p {
  color: #64748b;
  margin-bottom: 24px;
}

.success-actions {
  display: flex;
  gap: 12px;
}

.btn-review, .btn-continue {
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
  transition: all 0.2s;
  border: none;
}

.btn-review {
  background: #f1f5f9;
  color: #64748b;
}

.btn-review:hover {
  background: #e2e8f0;
}

.btn-continue {
  background: #db2777;
  color: white;
}

.btn-continue:hover {
  background: #be185d;
}

.btn-back-empty {
  padding: 12px 24px;
  background: #db2777;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

/* Submit Button */
.btn-submit {
  width: 100%;
  padding: 14px;
  margin-top: 16px;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-submit:hover:not(:disabled) {
  background: linear-gradient(135deg, #16a34a, #15803d);
  transform: translateY(-1px);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Evaluation Modal */
.evaluation-modal {
  background: white;
  border-radius: 24px;
  padding: 32px;
  text-align: center;
  max-width: 420px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
}

.btn-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f1f5f9;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #e2e8f0;
}

.btn-close i {
  font-size: 16px;
  color: #64748b;
}

/* Score Section */
.score-section {
  margin-bottom: 24px;
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  border: 6px solid;
}

.score-circle.good {
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  border-color: #22c55e;
}

.score-circle.needs-work {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-color: #f59e0b;
}

.score-value {
  font-size: 42px;
  font-weight: 800;
  line-height: 1;
}

.score-circle.good .score-value {
  color: #16a34a;
}

.score-circle.needs-work .score-value {
  color: #d97706;
}

.score-max {
  font-size: 14px;
  color: #64748b;
}

.score-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
}

.score-label.excellent {
  background: #dcfce7;
  color: #16a34a;
}

.score-label.good {
  background: #dbeafe;
  color: #2563eb;
}

.score-label.needs-work {
  background: #fef3c7;
  color: #d97706;
}

/* Feedback Message */
.feedback-message {
  background: #f8fafc;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 20px;
}

.feedback-message p {
  color: #475569;
  font-size: 15px;
  line-height: 1.5;
  margin: 0;
}

/* Transcription Section */
.transcription-section {
  text-align: left;
  margin-bottom: 20px;
}

.transcription-box, .expected-box {
  background: #f8fafc;
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 10px;
}

.expected-box {
  background: #f0fdf4;
}

.transcription-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.transcription-box .transcription-label {
  color: #64748b;
}

.expected-box .transcription-label {
  color: #16a34a;
}

.transcription-text {
  font-size: 14px;
  line-height: 1.6;
  color: #1e293b;
  margin: 0;
}

/* Missing Keywords */
.missing-section {
  text-align: left;
  margin-bottom: 20px;
}

.missing-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #dc2626;
  margin-bottom: 10px;
}

.missing-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.missing-tag {
  padding: 6px 12px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 20px;
  font-size: 13px;
}

/* Suggestions */
.suggestions-section {
  text-align: left;
  margin-bottom: 24px;
}

.suggestions-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #7c3aed;
  margin-bottom: 10px;
}

.suggestions-list {
  padding-left: 20px;
  margin: 0;
}

.suggestions-list li {
  font-size: 14px;
  color: #475569;
  line-height: 1.6;
  margin-bottom: 6px;
}

/* Evaluation Actions */
.evaluation-actions {
  display: flex;
  gap: 12px;
}

.btn-retry, .btn-next {
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
  transition: all 0.2s;
  border: none;
}

.btn-retry {
  background: #f1f5f9;
  color: #64748b;
}

.btn-retry:hover {
  background: #e2e8f0;
}

.btn-next {
  background: #db2777;
  color: white;
}

.btn-next:hover {
  background: #be185d;
}

/* Responsive */
@media (max-width: 480px) {
  .nav-dots {
    display: none;
  }
}
</style>
