<template>
  <div class="practice-player">
    <!-- Loading -->
    <div v-if="isLoading" class="loading-screen">
      <div class="spinner"></div>
      <p>Đang tải bài luyện tập...</p>
    </div>

    <!-- No quizzes -->
    <div v-else-if="!quizzes || quizzes.length === 0" class="empty-screen">
      <div class="empty-icon">
        <i class="fa-solid fa-clipboard-list"></i>
      </div>
      <h2>Chưa có bài luyện tập</h2>
      <p>Khóa học này chưa có câu hỏi luyện tập nào.<br>Vui lòng liên hệ quản trị viên để thêm nội dung.</p>
      <button class="btn-back" @click="goBack">
        <i class="fa-solid fa-arrow-left"></i> Quay lại
      </button>
    </div>

    <!-- Intro Screen -->
    <div v-else-if="phase === 'intro'" class="intro-screen">
      <div class="intro-card">
        <div class="intro-icon">
          <i class="fa-solid fa-pen-nib"></i>
        </div>
        <h1>Bài Luyện Tập</h1>
        <p class="intro-desc">
          Kiểm tra kiến thức của bạn với {{ quizzes.length }} câu hỏi.
          Bài luyện tập gồm nhiều loại: trắc nghiệm, điền từ, nghe và nói.
        </p>

        <div class="intro-stats">
          <div class="intro-stat">
            <span class="stat-num">{{ quizzes.length }}</span>
            <span class="stat-lbl">Câu hỏi</span>
          </div>
          <div class="intro-divider"></div>
          <div class="intro-stat">
            <span class="stat-num">{{ multipleChoiceCount }}</span>
            <span class="stat-lbl">Trắc nghiệm</span>
          </div>
          <div class="intro-divider"></div>
          <div class="intro-stat">
            <span class="stat-num">{{ fillBlankCount }}</span>
            <span class="stat-lbl">Điền từ</span>
          </div>
          <div class="intro-divider"></div>
          <div class="intro-stat">
            <span class="stat-num">{{ listeningCount }}</span>
            <span class="stat-lbl">Nghe</span>
          </div>
          <div class="intro-divider"></div>
          <div class="intro-stat">
            <span class="stat-num">{{ speakingCount }}</span>
            <span class="stat-lbl">Nói</span>
          </div>
        </div>

        <div class="intro-rules">
          <div class="rule-item">
            <i class="fa-solid fa-circle-check"></i>
            <span>Trả lời đúng sẽ được <strong>{{ correctPoints }} điểm</strong></span>
          </div>
          <div class="rule-item">
            <i class="fa-solid fa-circle-check"></i>
            <span>Trả lời sai không bị trừ điểm</span>
          </div>
          <div class="rule-item">
            <i class="fa-solid fa-circle-check"></i>
            <span>Có thể xem kết quả sau khi hoàn thành</span>
          </div>
        </div>

        <button class="btn-start" @click="startPractice">
          <i class="fa-solid fa-play"></i>
          Bắt đầu luyện tập
        </button>
      </div>
    </div>

    <!-- Question Phase -->
    <div v-else-if="phase === 'doing'" class="question-screen">
      <!-- Progress Bar -->
      <div class="progress-bar-top">
        <div class="progress-info">
          <span class="q-counter">Câu {{ currentIndex + 1 }} / {{ quizzes.length }}</span>
          <span class="q-score">
            <i class="fa-solid fa-star"></i>
            {{ score }} điểm
          </span>
        </div>
        <div class="progress-track">
          <div class="progress-fill-top" :style="{ width: ((currentIndex) / quizzes.length * 100) + '%' }"></div>
        </div>
      </div>

      <!-- Question Card -->
      <div class="question-card">
        <div class="q-type-badge" :class="'type-' + currentQuiz.type">
          <i :class="getTypeIcon(currentQuiz.type)"></i>
          {{ getTypeLabel(currentQuiz.type) }}
        </div>

        <!-- MULTIPLE CHOICE -->
        <template v-if="currentQuiz.type === 'multiple_choice'">
          <h2 class="q-text">{{ currentQuiz.question }}</h2>
          <div class="options-list">
            <div
              v-for="(opt, idx) in currentQuiz.options"
              :key="idx"
              class="option-btn"
              :class="{
                selected: selectedAnswer === opt,
                correct: submitted && opt === currentQuiz.correct_answer,
                wrong: submitted && selectedAnswer === opt && opt !== currentQuiz.correct_answer
              }"
              @click="!submitted && selectAnswer(opt)"
            >
              <span class="opt-label">{{ optionLabels[idx] }}</span>
              <span class="opt-text">{{ opt }}</span>
              <i v-if="submitted && opt === currentQuiz.correct_answer" class="fa-solid fa-check opt-icon correct"></i>
              <i v-if="submitted && selectedAnswer === opt && opt !== currentQuiz.correct_answer" class="fa-solid fa-xmark opt-icon wrong"></i>
            </div>
          </div>
        </template>

        <!-- FILL BLANK -->
        <template v-else-if="currentQuiz.type === 'fill_blank'">
          <h2 class="q-text">{{ currentQuiz.question }}</h2>
          <div class="fill-blank-area">
            <div class="fill-preview">
              {{ getFillPreview(currentQuiz.question) }}
            </div>
            <input
              v-model="fillAnswer"
              type="text"
              class="fill-input"
              :class="{
                'correct-answer': submitted && isCorrect,
                'wrong-answer': submitted && !isCorrect
              }"
              :disabled="submitted"
              placeholder="Nhập từ cần điền..."
              @keyup.enter="!submitted && submitAnswer()"
            />
            <div v-if="submitted && !isCorrect" class="correct-answer-hint">
              <i class="fa-solid fa-check"></i>
              Đáp án đúng: <strong>{{ currentQuiz.correct_answer }}</strong>
            </div>
          </div>
        </template>

        <!-- LISTENING -->
        <template v-else-if="currentQuiz.type === 'listening'">
          <h2 class="q-text">Nghe và điền từ</h2>
          <div class="listening-area">
            <div class="audio-player-box">
              <audio
                v-if="currentQuiz.extra_data?.audio"
                ref="audioRef"
                :src="getAudioUrl(currentQuiz.extra_data.audio)"
                controls
                class="audio-control"
                @ended="audioEnded = true"
              ></audio>
              <div v-else class="no-audio">
                <i class="fa-solid fa-volume-xmark"></i>
                <span>Không có file audio</span>
              </div>
            </div>
            <p class="listening-hint">
              <i class="fa-solid fa-headphones"></i>
              Nghe kỹ và nhập từ bạn nghe được
            </p>
            <div class="fill-blank-area">
              <input
                v-model="fillAnswer"
                type="text"
                class="fill-input"
                :class="{
                  'correct-answer': submitted && isCorrect,
                  'wrong-answer': submitted && !isCorrect
                }"
                :disabled="submitted"
                placeholder="Nhập từ nghe được..."
                @keyup.enter="!submitted && submitAnswer()"
              />
              <div v-if="submitted && !isCorrect" class="correct-answer-hint">
                <i class="fa-solid fa-check"></i>
                Đáp án đúng: <strong>{{ currentQuiz.correct_answer }}</strong>
              </div>
            </div>
          </div>
        </template>

        <!-- SPEAKING -->
        <template v-else-if="currentQuiz.type === 'speaking'">
          <h2 class="q-text">Luyện phát âm</h2>
          <div class="speaking-area">
            <div class="sentence-card">
              <div class="sentence-label">
                <i class="fa-solid fa-quote-left"></i>
                Hãy đọc câu sau:
              </div>
              <p class="sentence-text">{{ currentQuiz.extra_data?.sentence || '(Không có nội dung)' }}</p>
            </div>

            <div class="record-section">
              <button
                v-if="!isRecording && !recordedUrl"
                class="btn-record"
                @click="startRecording"
              >
                <i class="fa-solid fa-microphone"></i>
                Bắt đầu ghi âm
              </button>

              <div v-if="isRecording" class="recording-state">
                <div class="recording-indicator">
                  <span class="rec-dot"></span>
                  <span class="rec-text">Đang ghi âm... {{ recordingTime }}s</span>
                </div>
                <button class="btn-stop-record" @click="stopRecording">
                  <i class="fa-solid fa-stop"></i> Dừng
                </button>
              </div>

              <div v-if="recordedUrl && !submitted" class="recorded-preview">
                <audio :src="recordedUrl" controls class="audio-control"></audio>
                <div class="record-actions">
                  <button class="btn-re-record" @click="resetRecording">
                    <i class="fa-solid fa-redo"></i> Ghi lại
                  </button>
                  <button class="btn-submit-record" @click="submitSpeaking">
                    <i class="fa-solid fa-check"></i> Gửi
                  </button>
                </div>
              </div>

              <div v-if="submitted" class="submission-confirm">
                <i class="fa-solid fa-check-circle"></i>
                <span>Đã gửi bài nói!</span>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Navigation -->
      <div class="nav-buttons">
        <button v-if="currentIndex > 0" class="btn-nav prev" @click="prevQuestion">
          <i class="fa-solid fa-arrow-left"></i>
          Câu trước
        </button>
        <div v-else></div>

        <button
          v-if="!submitted && currentQuiz.type !== 'speaking'"
          class="btn-nav submit"
          :disabled="(currentQuiz.type === 'multiple_choice' && !selectedAnswer) || (currentQuiz.type !== 'multiple_choice' && !fillAnswer.trim())"
          @click="submitAnswer"
        >
          <i class="fa-solid fa-paper-plane"></i>
          Trả lời
        </button>

        <button
          v-if="submitted || currentQuiz.type === 'speaking'"
          class="btn-nav next"
          @click="nextQuestion"
        >
          {{ currentIndex < quizzes.length - 1 ? 'Câu tiếp theo' : 'Xem kết quả' }}
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- Result Screen -->
    <div v-else-if="phase === 'result'" class="result-screen">
      <div class="result-card">
        <div class="result-icon" :class="resultClass">
          <i :class="resultIcon"></i>
        </div>
        <h1>Kết Quả Luyện Tập</h1>

        <div class="result-score-display">
          <span class="big-score">{{ score }}</span>
          <span class="score-denom">/ {{ maxScore }}</span>
        </div>
        <p class="score-percent">{{ scorePercent }}% điểm</p>

        <div class="result-bar-container">
          <div class="result-bar" :style="{ width: scorePercent + '%' }"></div>
        </div>

        <div class="result-summary">
          <div class="summary-item correct">
            <i class="fa-solid fa-check-circle"></i>
            <span>{{ correctCount }} đúng</span>
          </div>
          <div class="summary-item wrong">
            <i class="fa-solid fa-xmark-circle"></i>
            <span>{{ wrongCount }} sai</span>
          </div>
          <div class="summary-item skipped">
            <i class="fa-solid fa-circle-minus"></i>
            <span>{{ skippedCount }} bỏ qua</span>
          </div>
        </div>

        <!-- Review Answers -->
        <div class="result-review">
          <h3><i class="fa-solid fa-list-check"></i> Chi tiết đáp án</h3>
          <div
            v-for="(q, idx) in quizzes"
            :key="q.id"
            class="review-item"
            :class="getAnswerClass(idx)"
          >
            <div class="review-header">
              <span class="review-num">Câu {{ idx + 1 }}</span>
              <span class="review-type-badge" :class="'type-' + q.type">
                <i :class="getTypeIcon(q.type)"></i>
                {{ getTypeLabel(q.type) }}
              </span>
              <i :class="getAnswerIcon(idx)" class="review-status-icon"></i>
            </div>
            <p class="review-question">{{ q.question || q.extra_data?.sentence || '(Không có nội dung)' }}</p>
            <div v-if="q.type === 'multiple_choice'" class="review-options">
              <div
                v-for="(opt, oi) in q.options"
                :key="oi"
                class="review-opt"
                :class="{
                  'is-correct': opt === q.correct_answer,
                  'is-selected': answers[idx] === opt
                }"
              >
                <span class="opt-label-sm">{{ optionLabels[oi] }}</span>
                {{ opt }}
                <i v-if="opt === q.correct_answer" class="fa-solid fa-check review-opt-icon correct"></i>
                <i v-if="answers[idx] === opt && opt !== q.correct_answer" class="fa-solid fa-xmark review-opt-icon wrong"></i>
              </div>
            </div>
            <div v-if="q.type !== 'speaking'" class="review-answer">
              <span class="review-your-ans">
                Đáp án của bạn: <strong>{{ answers[idx] || '(bỏ qua)' }}</strong>
              </span>
              <span v-if="answers[idx] !== q.correct_answer" class="review-correct-ans">
                → Đúng: <strong>{{ q.correct_answer }}</strong>
              </span>
            </div>
          </div>
        </div>

        <div class="result-actions">
          <button class="btn-retry" @click="retryPractice">
            <i class="fa-solid fa-redo"></i>
            Luyện tập lại
          </button>
          <button class="btn-back-result" @click="goBack">
            <i class="fa-solid fa-arrow-left"></i>
            Quay lại khóa học
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { LessonQuizService, ProgressService } from '../../../services/api.js'

const route = useRoute()
const router = useRouter()

const courseId = computed(() => route.params.courseId)

// State
const isLoading = ref(true)
const quizzes = ref([])
const phase = ref('intro') // intro | doing | result
const currentIndex = ref(0)
const answers = ref({})
const selectedAnswer = ref('')
const fillAnswer = ref('')
const submitted = ref(false)
const score = ref(0)
const correctPoints = 10

// Speaking recording
const isRecording = ref(false)
const recordedUrl = ref('')
const recordingTime = ref(0)
const audioRef = ref(null)
const audioEnded = ref(false)
let mediaRecorder = null
let recordingInterval = null

const currentQuiz = computed(() => quizzes.value[currentIndex.value] || null)

const maxScore = computed(() => {
  return quizzes.value.filter(q => q.type !== 'speaking').length * correctPoints
})

const isCorrect = computed(() => {
  if (!currentQuiz.value) return false
  const ans = (currentQuiz.value.type === 'multiple_choice' ? selectedAnswer.value : fillAnswer.value)
    .toString()
    .trim()
    .toLowerCase()
  const correct = (currentQuiz.value.correct_answer || '').toString().trim().toLowerCase()
  return ans === correct
})

const scorePercent = computed(() => {
  if (maxScore.value === 0) return 0
  return Math.round((score.value / maxScore.value) * 100)
})

const correctCount = computed(() => {
  return quizzes.value.filter((q, idx) => {
    if (q.type === 'speaking') return answers.value[idx] === true
    const ans = (answers.value[idx] || '').toString().trim().toLowerCase()
    const correct = (q.correct_answer || '').toString().trim().toLowerCase()
    return ans === correct
  }).length
})

const wrongCount = computed(() => {
  return quizzes.value.filter((q, idx) => {
    if (q.type === 'speaking') return answers.value[idx] === false
    if (!answers.value[idx]) return false
    const ans = answers.value[idx].toString().trim().toLowerCase()
    const correct = (q.correct_answer || '').toString().trim().toLowerCase()
    return ans !== correct
  }).length
})

const skippedCount = computed(() => {
  return quizzes.value.filter((q, idx) => {
    if (q.type === 'speaking') return answers.value[idx] === undefined
    return !answers.value[idx]
  }).length
})

const resultClass = computed(() => {
  const pct = scorePercent.value
  if (pct >= 80) return 'great'
  if (pct >= 60) return 'good'
  if (pct >= 40) return 'ok'
  return 'low'
})

const resultIcon = computed(() => {
  const pct = scorePercent.value
  if (pct >= 80) return 'fa-solid fa-trophy'
  if (pct >= 60) return 'fa-solid fa-thumbs-up'
  if (pct >= 40) return 'fa-solid fa-face-meh'
  return 'fa-solid fa-book-open'
})

// Stats
const multipleChoiceCount = computed(() => quizzes.value.filter(q => q.type === 'multiple_choice').length)
const fillBlankCount = computed(() => quizzes.value.filter(q => q.type === 'fill_blank').length)
const listeningCount = computed(() => quizzes.value.filter(q => q.type === 'listening').length)
const speakingCount = computed(() => quizzes.value.filter(q => q.type === 'speaking').length)

const optionLabels = ['A', 'B', 'C', 'D']

// Methods
const getTypeLabel = (type) => {
  const map = {
    multiple_choice: 'Trắc nghiệm',
    fill_blank: 'Điền từ',
    listening: 'Nghe',
    speaking: 'Nói'
  }
  return map[type] || type
}

const getTypeIcon = (type) => {
  const map = {
    multiple_choice: 'fa-solid fa-list-check',
    fill_blank: 'fa-solid fa-pen-line',
    listening: 'fa-solid fa-headphones',
    speaking: 'fa-solid fa-microphone'
  }
  return map[type] || 'fa-solid fa-question'
}

const getFillPreview = (question) => {
  if (!question) return ''
  return question.replace(/___+/g, '______')
}

const getAudioUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return 'http://localhost:8000' + path
}

const getAnswerClass = (idx) => {
  const q = quizzes.value[idx]
  if (q.type === 'speaking') {
    if (answers.value[idx] === true) return 'is-correct'
    if (answers.value[idx] === false) return 'is-wrong'
    return 'is-skipped'
  }
  if (!answers.value[idx]) return 'is-skipped'
  const ans = (answers.value[idx] || '').toString().trim().toLowerCase()
  const correct = (q.correct_answer || '').toString().trim().toLowerCase()
  return ans === correct ? 'is-correct' : 'is-wrong'
}

const getAnswerIcon = (idx) => {
  const cls = getAnswerClass(idx)
  if (cls === 'is-correct') return 'fa-solid fa-check-circle correct'
  if (cls === 'is-wrong') return 'fa-solid fa-xmark-circle wrong'
  return 'fa-solid fa-circle-minus skipped'
}

const fetchQuizzes = async () => {
  isLoading.value = true
  try {
    const data = await LessonQuizService.getByCourse(courseId.value)
    quizzes.value = Array.isArray(data) ? data : (data.data || [])
  } catch (error) {
    console.error('Lỗi tải quiz:', error)
    quizzes.value = []
  } finally {
    isLoading.value = false
  }
}

const startPractice = () => {
  phase.value = 'doing'
  currentIndex.value = 0
  answers.value = {}
  selectedAnswer.value = ''
  fillAnswer.value = ''
  submitted.value = false
  score.value = 0
  recordedUrl.value = ''
  isRecording.value = false
}

const selectAnswer = (opt) => {
  selectedAnswer.value = opt
}

const submitAnswer = () => {
  if (currentQuiz.value.type === 'speaking') return

  submitted.value = true
  answers.value[currentIndex.value] = currentQuiz.value.type === 'multiple_choice'
    ? selectedAnswer.value
    : fillAnswer.value

  if (isCorrect.value) {
    score.value += correctPoints
  }
}

const submitSpeaking = () => {
  if (!recordedUrl.value) return
  submitted.value = true
  answers.value[currentIndex.value] = true // Speaking always counts as attempted
}

const nextQuestion = () => {
  if (currentIndex.value < quizzes.value.length - 1) {
    currentIndex.value++
    resetQuestionState()
  } else {
    savePracticeScore()
    phase.value = 'result'
  }
}

const savePracticeScore = async () => {
  try {
    await ProgressService.saveExerciseScore({
      course_id: courseId.value,
      type: 'practice',
      score: scorePercent.value,
    })
  } catch (error) {
    console.error('Lỗi lưu điểm luyện tập:', error)
  }
}

const prevQuestion = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    resetQuestionState()
  }
}

const resetQuestionState = () => {
  const prevAnswer = answers.value[currentIndex.value]
  submitted.value = !!prevAnswer
  selectedAnswer.value = ''
  fillAnswer.value = ''
  recordedUrl.value = ''
  isRecording.value = false
  audioEnded.value = false

  if (prevAnswer !== undefined) {
    if (currentQuiz.value?.type === 'multiple_choice') {
      selectedAnswer.value = prevAnswer
    } else {
      fillAnswer.value = prevAnswer
    }
  }
}

const retryPractice = () => {
  phase.value = 'intro'
  currentIndex.value = 0
  answers.value = {}
  selectedAnswer.value = ''
  fillAnswer.value = ''
  submitted.value = false
  score.value = 0
}

const goBack = () => {
  router.push(`/student/khoa-hoc/${courseId.value}`)
}

// Recording
const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    const chunks = []
    mediaRecorder.ondataavailable = (e) => chunks.push(e.data)
    mediaRecorder.onstop = () => {
      const blob = new Blob(chunks, { type: 'audio/webm' })
      recordedUrl.value = URL.createObjectURL(blob)
      stream.getTracks().forEach(t => t.stop())
    }
    mediaRecorder.start()
    isRecording.value = true
    recordingTime.value = 0
    recordingInterval = setInterval(() => {
      recordingTime.value++
    }, 1000)
  } catch (err) {
    alert('Không thể truy cập micro. Vui lòng cho phép quyền sử dụng micro.')
    console.error(err)
  }
}

const stopRecording = () => {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
  }
  isRecording.value = false
  clearInterval(recordingInterval)
}

const resetRecording = () => {
  recordedUrl.value = ''
  recordingTime.value = 0
  submitted.value = false
}

onMounted(() => {
  fetchQuizzes()
})

onUnmounted(() => {
  clearInterval(recordingInterval)
  if (recordedUrl.value) {
    URL.revokeObjectURL(recordedUrl.value)
  }
})
</script>

<style scoped>
.practice-player {
  min-height: 100vh;
  background: #f0f4ff;
  padding: 0;
}

/* ===== LOADING ===== */
.loading-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  gap: 16px;
  color: #4f46e5;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e0e7ff;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.loading-screen p {
  font-size: 16px;
  font-weight: 600;
  color: #4f46e5;
}

/* ===== EMPTY ===== */
.empty-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  text-align: center;
  padding: 40px;
}

.empty-icon {
  width: 80px;
  height: 80px;
  background: #ede9fe;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  color: #7c3aed;
  margin-bottom: 24px;
}

.empty-screen h2 {
  font-size: 24px;
  color: #1e293b;
  margin: 0 0 8px;
}

.empty-screen p {
  color: #64748b;
  margin: 0 0 24px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  font-size: 15px;
}

.btn-back:hover { background: #4338ca; }

/* ===== INTRO ===== */
.intro-screen {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 40px 20px;
}

.intro-card {
  background: white;
  border-radius: 24px;
  padding: 48px;
  max-width: 640px;
  width: 100%;
  text-align: center;
  box-shadow: 0 20px 60px rgba(79, 70, 229, 0.12);
}

.intro-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  color: white;
  margin: 0 auto 24px;
  box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
}

.intro-card h1 {
  font-size: 32px;
  font-weight: 900;
  color: #1e293b;
  margin: 0 0 12px;
}

.intro-desc {
  font-size: 16px;
  color: #64748b;
  line-height: 1.6;
  margin: 0 0 32px;
}

.intro-stats {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  margin-bottom: 32px;
  padding: 24px;
  background: #f8faff;
  border-radius: 16px;
}

.intro-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-num {
  font-size: 28px;
  font-weight: 900;
  color: #4f46e5;
}

.stat-lbl {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.intro-divider {
  width: 1px;
  height: 40px;
  background: #e2e8f0;
}

.intro-rules {
  text-align: left;
  margin-bottom: 32px;
}

.rule-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
  color: #475569;
  font-size: 15px;
}

.rule-item i {
  color: #4f46e5;
  font-size: 16px;
  flex-shrink: 0;
}

.btn-start {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 16px 40px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 17px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
  transition: all 0.3s;
}

.btn-start:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 36px rgba(79, 70, 229, 0.45);
}

/* ===== QUESTION ===== */
.question-screen {
  min-height: 100vh;
  padding: 0 0 80px;
}

.progress-bar-top {
  background: white;
  padding: 16px 24px;
  position: sticky;
  top: 0;
  z-index: 10;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.q-counter {
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
}

.q-score {
  font-size: 15px;
  font-weight: 700;
  color: #f59e0b;
  display: flex;
  align-items: center;
  gap: 5px;
}

.progress-track {
  height: 8px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill-top {
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #7c3aed);
  border-radius: 999px;
  transition: width 0.4s ease;
}

.question-card {
  max-width: 720px;
  margin: 40px auto 0;
  padding: 0 24px;
}

.q-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 24px;
}

.q-type-badge.type-multiple_choice { background: #dbeafe; color: #1d4ed8; }
.q-type-badge.type-fill_blank { background: #fef3c7; color: #92400e; }
.q-type-badge.type-listening { background: #d1fae5; color: #065f46; }
.q-type-badge.type-speaking { background: #fce7f3; color: #9d174d; }

.q-text {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.4;
  margin: 0 0 28px;
}

/* Options */
.options-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.option-btn {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.2s;
  user-select: none;
}

.option-btn:hover:not(.correct):not(.wrong) {
  border-color: #4f46e5;
  background: #f0f1ff;
}

.option-btn.selected {
  border-color: #4f46e5;
  background: #eef2ff;
}

.option-btn.correct {
  border-color: #22c55e;
  background: #f0fdf4;
}

.option-btn.wrong {
  border-color: #ef4444;
  background: #fef2f2;
}

.opt-label {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 15px;
  color: #4f46e5;
  flex-shrink: 0;
}

.option-btn.selected .opt-label { background: #4f46e5; color: white; }
.option-btn.correct .opt-label { background: #22c55e; color: white; }
.option-btn.wrong .opt-label { background: #ef4444; color: white; }

.opt-text {
  flex: 1;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.opt-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.opt-icon.correct { color: #22c55e; }
.opt-icon.wrong { color: #ef4444; }

/* Fill Blank */
.fill-blank-area {
  background: white;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #e2e8f0;
}

.fill-preview {
  font-size: 18px;
  color: #64748b;
  margin-bottom: 16px;
  line-height: 1.6;
}

.fill-input {
  width: 100%;
  padding: 16px 20px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 600;
  outline: none;
  transition: all 0.2s;
  box-sizing: border-box;
  color: #1e293b;
}

.fill-input:focus {
  border-color: #4f46e5;
  background: #f8f9ff;
}

.fill-input.correct-answer {
  border-color: #22c55e;
  background: #f0fdf4;
  color: #166534;
}

.fill-input.wrong-answer {
  border-color: #ef4444;
  background: #fef2f2;
}

.correct-answer-hint {
  margin-top: 12px;
  padding: 10px 14px;
  background: #f0fdf4;
  border-radius: 8px;
  font-size: 14px;
  color: #166534;
  display: flex;
  align-items: center;
  gap: 6px;
}

.correct-answer-hint i { color: #22c55e; }

/* Listening */
.listening-area {
  background: white;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #e2e8f0;
}

.audio-player-box {
  margin-bottom: 16px;
}

.audio-control {
  width: 100%;
  height: 48px;
  border-radius: 12px;
}

.no-audio {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 14px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 12px;
  justify-content: center;
}

.listening-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  color: #64748b;
  margin: 0 0 16px;
}

.listening-hint i { color: #f59e0b; }

/* Speaking */
.speaking-area {
  background: white;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #e2e8f0;
}

.sentence-card {
  background: linear-gradient(135deg, #f0f1ff, #ede9fe);
  border-radius: 16px;
  padding: 28px;
  margin-bottom: 24px;
  text-align: center;
}

.sentence-label {
  font-size: 13px;
  color: #7c3aed;
  font-weight: 600;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.sentence-text {
  font-size: 22px;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
  line-height: 1.5;
  letter-spacing: 0.3px;
}

.record-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.btn-record {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 16px 32px;
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35);
  transition: all 0.3s;
}

.btn-record:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(220, 38, 38, 0.45);
}

.recording-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.recording-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
}

.rec-dot {
  width: 14px;
  height: 14px;
  background: #ef4444;
  border-radius: 50%;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.2); }
}

.rec-text {
  font-size: 16px;
  font-weight: 700;
  color: #ef4444;
}

.btn-stop-record {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.recorded-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.record-actions {
  display: flex;
  gap: 12px;
}

.btn-re-record {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.btn-submit-record {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  background: #22c55e;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.submission-confirm {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 700;
  color: #22c55e;
}

/* ===== NAV BUTTONS ===== */
.nav-buttons {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 720px;
  margin: 32px auto 0;
  padding: 0 24px;
}

.btn-nav {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-nav.prev {
  background: #f1f5f9;
  color: #475569;
}

.btn-nav.prev:hover {
  background: #e2e8f0;
}

.btn-nav.submit {
  background: #4f46e5;
  color: white;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
}

.btn-nav.submit:hover:not(:disabled) {
  background: #4338ca;
  transform: translateY(-2px);
}

.btn-nav.submit:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-nav.next {
  background: #22c55e;
  color: white;
  box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
}

.btn-nav.next:hover {
  background: #16a34a;
  transform: translateY(-2px);
}

/* ===== RESULT ===== */
.result-screen {
  min-height: 100vh;
  padding: 40px 20px;
  background: #f0f4ff;
}

.result-card {
  max-width: 720px;
  margin: 0 auto;
  text-align: center;
}

.result-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  margin: 0 auto 20px;
}

.result-icon.great { background: #fef9c3; color: #ca8a04; }
.result-icon.good { background: #d1fae5; color: #059669; }
.result-icon.ok { background: #dbeafe; color: #3b82f6; }
.result-icon.low { background: #f1f5f9; color: #64748b; }

.result-card h1 {
  font-size: 28px;
  font-weight: 900;
  color: #1e293b;
  margin: 0 0 20px;
}

.result-score-display {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 4px;
  margin-bottom: 8px;
}

.big-score {
  font-size: 64px;
  font-weight: 900;
  color: #4f46e5;
  line-height: 1;
}

.score-denom {
  font-size: 28px;
  font-weight: 700;
  color: #94a3b8;
}

.score-percent {
  font-size: 18px;
  color: #64748b;
  margin: 0 0 20px;
  font-weight: 600;
}

.result-bar-container {
  height: 12px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
  max-width: 480px;
  margin: 0 auto 28px;
}

.result-bar {
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #7c3aed);
  border-radius: 999px;
  transition: width 1s ease;
}

.result-summary {
  display: flex;
  justify-content: center;
  gap: 24px;
  margin-bottom: 32px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 15px;
  font-weight: 600;
}

.summary-item.correct { color: #22c55e; }
.summary-item.wrong { color: #ef4444; }
.summary-item.skipped { color: #94a3b8; }

.result-review {
  background: white;
  border-radius: 20px;
  padding: 28px;
  text-align: left;
  margin-bottom: 28px;
}

.result-review h3 {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.result-review h3 i { color: #4f46e5; }

.review-item {
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  border: 2px solid transparent;
}

.review-item.is-correct { background: #f0fdf4; border-color: #bbf7d0; }
.review-item.is-wrong { background: #fef2f2; border-color: #fecaca; }
.review-item.is-skipped { background: #f8fafc; border-color: #e2e8f0; }

.review-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.review-num {
  font-size: 13px;
  font-weight: 700;
  color: #475569;
}

.review-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}

.review-type-badge.type-multiple_choice { background: #dbeafe; color: #1d4ed8; }
.review-type-badge.type-fill_blank { background: #fef3c7; color: #92400e; }
.review-type-badge.type-listening { background: #d1fae5; color: #065f46; }
.review-type-badge.type-speaking { background: #fce7f3; color: #9d174d; }

.review-status-icon { margin-left: auto; font-size: 18px; }
.review-status-icon.correct { color: #22c55e; }
.review-status-icon.wrong { color: #ef4444; }
.review-status-icon.skipped { color: #94a3b8; }

.review-question {
  font-size: 14px;
  color: #475569;
  margin: 0 0 10px;
  line-height: 1.5;
}

.review-options {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 8px;
}

.review-opt {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  background: white;
  color: #475569;
}

.review-opt.is-correct { background: #dcfce7; color: #166534; font-weight: 700; }
.review-opt.is-selected:not(.is-correct) { background: #fef2f2; color: #991b1b; }

.opt-label-sm {
  font-weight: 800;
  color: #94a3b8;
  min-width: 20px;
}

.review-opt-icon {
  margin-left: auto;
  font-size: 14px;
}

.review-opt-icon.correct { color: #22c55e; }
.review-opt-icon.wrong { color: #ef4444; }

.review-answer {
  display: flex;
  gap: 16px;
  font-size: 13px;
  flex-wrap: wrap;
}

.review-your-ans { color: #475569; }
.review-correct-ans { color: #22c55e; }

.result-actions {
  display: flex;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.btn-retry {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
}

.btn-retry:hover { background: #4338ca; }

.btn-back-result {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: white;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
}

.btn-back-result:hover { border-color: #4f46e5; color: #4f46e5; }
</style>
