<template>
  <div class="final-exam-player">
    <!-- Loading -->
    <div v-if="isLoading" class="loading-screen">
      <div class="spinner"></div>
      <p>Đang tải bài thi...</p>
    </div>

    <!-- No quiz -->
    <div v-else-if="!quiz" class="empty-screen">
      <div class="empty-icon">
        <i class="fa-solid fa-file-circle-x"></i>
      </div>
      <h2>Chưa có bài thi cuối khóa</h2>
      <p>Khóa học này chưa được thiết lập bài thi cuối khóa.</p>
      <button class="btn-back" @click="goBack">
        <i class="fa-solid fa-arrow-left"></i> Quay lại
      </button>
    </div>

    <!-- Intro -->
    <div v-else-if="phase === 'intro'" class="intro-screen">
      <div class="intro-card">
        <div class="intro-icon">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h1>{{ quiz.title || 'Bài Thi Cuối Khóa' }}</h1>
        <p class="intro-desc">
          Thi cuối khóa không phải để làm — mà để chứng minh bạn đủ trình độ.
          Hãy đọc kỹ đề và làm bài cẩn thận.
        </p>

        <div class="intro-meta">
          <div class="meta-item">
            <i class="fa-solid fa-list-check"></i>
            <span>{{ quiz.questions?.length || 0 }} câu hỏi</span>
          </div>
          <div class="meta-item" v-if="quiz.duration">
            <i class="fa-solid fa-clock"></i>
            <span>{{ quiz.duration }} phút</span>
          </div>
          <div class="meta-item">
            <i class="fa-solid fa-bullseye"></i>
            <span>Đạt: {{ passScore }}%</span>
          </div>
        </div>

        <!-- Thong tin thi lai -->
        <div v-if="courseProgress?.final_attempts > 0" class="attempt-info">
          <div class="attempt-badge">
            <i class="fa-solid fa-repeat"></i>
            <span>Lần thi: {{ courseProgress.final_attempts + 1 }}</span>
          </div>
          <div v-if="courseProgress.final_best_score" class="best-score">
            <i class="fa-solid fa-trophy"></i>
            <span>Điểm cao nhất: <strong>{{ courseProgress.final_best_score }}%</strong></span>
          </div>
          <div v-if="courseProgress.final_attempts > 0 && !courseProgress.final_passed" class="retry-hint">
            <i class="fa-solid fa-circle-info"></i>
            <span>Lần trước bạn chưa đạt. Hãy thử lại nhé!</span>
          </div>
        </div>

        <div class="intro-warning">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>Bài thi sẽ được tính thời gian. Không thoát trang khi đang làm bài.</span>
        </div>

        <button class="btn-start" @click="startExam">
          <i class="fa-solid fa-file-signature"></i>
          {{ courseProgress?.final_attempts > 0 ? 'Thi lại' : 'Bắt đầu thi' }}
        </button>
      </div>
    </div>

    <!-- Doing Exam -->
    <div v-else-if="phase === 'doing'" class="exam-screen">
      <!-- Top bar -->
      <div class="exam-top-bar">
        <div class="exam-title-bar">
          <button class="btn-back-exam" @click="confirmExit">
            <i class="fa-solid fa-arrow-left"></i>
          </button>
          <h1>{{ quiz.title || 'Bài Thi Cuối Khóa' }}</h1>
        </div>
        <div class="exam-timer" v-if="quiz.duration">
          <i class="fa-solid fa-clock"></i>
          <span :class="{ 'time-warning': timeLeft <= 60 }">{{ formatTime(timeLeft) }}</span>
        </div>
      </div>

      <!-- Progress -->
      <div class="exam-progress">
        <div class="prog-track">
          <div class="prog-fill" :style="{ width: ((currentIndex) / totalQuestions * 100) + '%' }"></div>
        </div>
        <span class="prog-label">{{ currentIndex + 1 }} / {{ totalQuestions }}</span>
      </div>

      <!-- Question -->
      <div class="exam-question-card">
        <div class="q-number">Câu {{ currentIndex + 1 }}</div>

        <h2 class="q-text">{{ currentQuestion.question }}</h2>

        <!-- Multiple choice -->
        <template v-if="currentQuestion.type === 'multiple_choice'">
          <div class="options-list">
            <div
              v-for="(opt, idx) in currentQuestion.options"
              :key="idx"
              class="option-btn"
              :class="{
                selected: answers[currentIndex] === opt,
                correct: submitted && checkCorrect(currentQuestion, opt),
                wrong: submitted && answers[currentIndex] === opt && !checkCorrect(currentQuestion, opt)
              }"
              @click="!submitted && setAnswer(opt)"
            >
              <span class="opt-label">{{ optionLabels[idx] }}</span>
              <span class="opt-text">{{ opt }}</span>
              <i v-if="submitted && checkCorrect(currentQuestion, opt)" class="fa-solid fa-check opt-icon correct"></i>
              <i v-if="submitted && answers[currentIndex] === opt && !checkCorrect(currentQuestion, opt)" class="fa-solid fa-xmark opt-icon wrong"></i>
            </div>
          </div>
        </template>

        <!-- Fill blank -->
        <template v-else-if="currentQuestion.type === 'fill_blank'">
          <div class="fill-area">
            <input
              v-model="answers[currentIndex]"
              type="text"
              class="fill-input"
              :class="{
                'correct-answer': submitted && isAnswerCorrect,
                'wrong-answer': submitted && !isAnswerCorrect
              }"
              :disabled="submitted"
              placeholder="Nhập đáp án..."
              @keyup.enter="!submitted && submitAnswer()"
            />
            <div v-if="submitted && !isAnswerCorrect" class="correct-hint">
              <i class="fa-solid fa-check"></i>
              Đáp án đúng: <strong>{{ currentQuestion.correct_answer }}</strong>
            </div>
          </div>
        </template>

        <!-- Listening -->
        <template v-else-if="currentQuestion.type === 'listening'">
          <div class="listening-area">
            <div class="audio-box">
              <audio
                v-if="currentQuestion.audio_url"
                :src="getAudioUrl(currentQuestion.audio_url)"
                controls
              ></audio>
            </div>
            <div class="fill-area">
              <input
                v-model="answers[currentIndex]"
                type="text"
                class="fill-input"
                :class="{
                  'correct-answer': submitted && isAnswerCorrect,
                  'wrong-answer': submitted && !isAnswerCorrect
                }"
                :disabled="submitted"
                placeholder="Nhập từ nghe được..."
              />
              <div v-if="submitted && !isAnswerCorrect" class="correct-hint">
                <i class="fa-solid fa-check"></i>
                Đáp án đúng: <strong>{{ currentQuestion.correct_answer }}</strong>
              </div>
            </div>
          </div>
        </template>

        <!-- Speaking -->
        <template v-else-if="currentQuestion.type === 'speaking'">
          <div class="speaking-area">
            <div class="sentence-card">
              <p class="sentence-text">{{ currentQuestion.sample_answer || currentQuestion.question }}</p>
            </div>
            <div class="record-section">
              <button v-if="!isRecording && !recordedUrls[currentIndex]" class="btn-record" @click="startRecording">
                <i class="fa-solid fa-microphone"></i> Ghi âm
              </button>
              <div v-if="isRecording" class="recording-state">
                <span class="rec-dot"></span>
                <span>{{ recordingTime }}s</span>
                <button class="btn-stop" @click="stopRecording"><i class="fa-solid fa-stop"></i></button>
              </div>
              <div v-if="recordedUrls[currentIndex]" class="recorded-info">
                <audio :src="recordedUrls[currentIndex]" controls></audio>
                <button class="btn-re-record" @click="clearRecording"><i class="fa-solid fa-redo"></i> Ghi lại</button>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Nav -->
      <div class="exam-nav">
        <button v-if="currentIndex > 0" class="btn-nav prev" @click="prevQuestion">
          <i class="fa-solid fa-arrow-left"></i> Câu trước
        </button>
        <div v-else></div>

        <button
          v-if="!submitted"
          class="btn-nav submit"
          :disabled="!hasAnswer"
          @click="submitAnswer"
        >
          <i class="fa-solid fa-paper-plane"></i> Trả lời
        </button>

        <button v-if="submitted" class="btn-nav next" @click="nextQuestion">
          {{ currentIndex < totalQuestions - 1 ? 'Câu tiếp' : 'Nộp bài' }}
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- Confirm Submit -->
    <div v-if="showSubmitConfirm" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-icon warning">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h2>Xác nhận nộp bài?</h2>
        <p>Bạn đã trả lời <strong>{{ answeredCount }}</strong> / {{ totalQuestions }} câu.</p>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showSubmitConfirm = false">Hủy</button>
          <button class="btn-confirm" @click="submitExam">Nộp bài</button>
        </div>
      </div>
    </div>

    <!-- Confirm Exit -->
    <div v-if="showExitConfirm" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-icon danger">
          <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <h2>Thoát bài thi?</h2>
        <p>Nếu thoát, bài thi sẽ bị hủy và không được tính điểm.</p>
        <div class="modal-actions">
          <button class="btn-cancel" @click="showExitConfirm = false">Hủy</button>
          <button class="btn-danger" @click="forceExit">Thoát</button>
        </div>
      </div>
    </div>

    <!-- Result -->
    <div v-else-if="phase === 'result'" class="result-screen">
      <div class="result-card">
        <!-- PASS -->
        <template v-if="submitResult?.passed">
          <div class="result-banner passed">
            <div class="banner-icon">
              <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="banner-text">
              <h2>Chúc mừng bạn!</h2>
              <p>Điểm của bạn: <strong>{{ submitResult.score }}%</strong></p>
            </div>
          </div>
          <div class="result-message">
            <p class="msg-pass">
              Bạn đã hoàn thành khóa học!
            </p>
          </div>
        </template>

        <!-- FAIL -->
        <template v-else>
          <div class="result-banner failed">
            <div class="banner-icon">
              <i class="fa-solid fa-book-open"></i>
            </div>
            <div class="banner-text">
              <h2>Bạn chưa đạt</h2>
              <p>Điểm của bạn: <strong>{{ submitResult?.score ?? scorePercent }}%</strong></p>
            </div>
          </div>
          <div class="result-message">
            <p class="msg-fail">
              Bạn cần đạt tối thiểu <strong>{{ passScore }}%</strong> để hoàn thành khóa học.
            </p>
            <p class="msg-fail-hint">Hãy ôn lại kiến thức và thử lại nhé!</p>
          </div>
        </template>

        <!-- Score detail -->
        <div class="score-display">
          <span class="big-score">{{ scorePercent }}</span>
          <span class="score-denom">%</span>
        </div>

        <div class="result-bar-wrap">
          <div class="result-bar-fill" :style="{ width: scorePercent + '%', background: barColor }"></div>
          <div class="result-pass-line" :style="{ left: passScore + '%' }"></div>
        </div>
        <p class="pass-threshold">Mốc đạt: {{ passScore }}%</p>

        <!-- Attempt info -->
        <div class="attempt-result">
          <div class="attempt-item">
            <i class="fa-solid fa-repeat"></i>
            <span>Lần thi: {{ submitResult?.attempt ?? 1 }}</span>
          </div>
          <div v-if="submitResult?.best_score" class="attempt-item best">
            <i class="fa-solid fa-crown"></i>
            <span>Điểm cao nhất: {{ submitResult.best_score }}%</span>
          </div>
        </div>

        <div class="result-stats">
          <span class="stat-correct"><i class="fa-solid fa-check-circle"></i> {{ correctCount }} đúng</span>
          <span class="stat-wrong"><i class="fa-solid fa-xmark-circle"></i> {{ wrongCount }} sai</span>
          <span class="stat-skipped"><i class="fa-solid fa-circle-minus"></i> {{ skippedCount }} bỏ qua</span>
        </div>

        <div class="result-actions">
          <button class="btn-retry" @click="retryExam">
            <i class="fa-solid fa-redo"></i> Làm lại
          </button>
          <button class="btn-home" @click="goBack">
            <i class="fa-solid fa-home"></i> Về trang chủ
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CourseQuizService, ProgressService } from '../../../services/api.js'

const route = useRoute()
const router = useRouter()
const courseId = computed(() => route.params.courseId)

const isLoading = ref(true)
const quiz = ref(null)
const phase = ref('intro')
const currentIndex = ref(0)
const answers = ref({})
const recordedUrls = ref({})
const submitted = ref(false)
const showSubmitConfirm = ref(false)
const showExitConfirm = ref(false)
const submitResult = ref(null)
const courseProgress = ref(null)

const timeLeft = ref(0)
const totalSeconds = ref(0)
let timerInterval = null

const isRecording = ref(false)
const recordingTime = ref(0)
let mediaRecorder = null
let recordingChunks = []
let recordingInterval = null

const optionLabels = ['A', 'B', 'C', 'D']

const totalQuestions = computed(() => quiz.value?.questions?.length || 0)
const currentQuestion = computed(() => quiz.value?.questions?.[currentIndex.value] || null)

const passScore = computed(() => quiz.value?.pass_score || 70)

const hasAnswer = computed(() => {
  const q = currentQuestion.value
  if (!q) return false
  if (q.type === 'speaking') return !!recordedUrls.value[currentIndex.value]
  return !!answers.value[currentIndex.value]
})

const isAnswerCorrect = computed(() => {
  if (!currentQuestion.value) return false
  return checkCorrect(currentQuestion.value, answers.value[currentIndex.value])
})

const correctCount = computed(() => {
  return quiz.value?.questions?.filter((q, idx) => {
    if (q.type === 'speaking') return recordedUrls.value[idx]
    return checkCorrect(q, answers.value[idx])
  }).length || 0
})

const wrongCount = computed(() => {
  return quiz.value?.questions?.filter((q, idx) => {
    if (q.type === 'speaking') return false
    if (!answers.value[idx]) return false
    return !checkCorrect(q, answers.value[idx])
  }).length || 0
})

const skippedCount = computed(() => {
  return totalQuestions.value - correctCount.value - wrongCount.value
})

const scorePercent = computed(() => {
  const max = maxScore.value
  if (max === 0) return 0
  return Math.round((correctCount.value / max) * 100)
})

const maxScore = computed(() => {
  return quiz.value?.questions?.filter(q => q.type !== 'speaking').length || 0
})

const barColor = computed(() => {
  return scorePercent.value >= passScore.value
    ? 'linear-gradient(90deg, #22c55e, #16a34a)'
    : 'linear-gradient(90deg, #ef4444, #dc2626)'
})

const answeredCount = computed(() => {
  return quiz.value?.questions?.filter((q, idx) => {
    if (q.type === 'speaking') return !!recordedUrls.value[idx]
    return !!answers.value[idx]
  }).length || 0
})

const formatTime = (seconds) => {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

// Kiểm tra đáp án đúng, hỗ trợ cả text (mới) và chữ cái A/B/C/D (cũ)
const checkCorrect = (q, answerValue) => {
  if (!q || answerValue === undefined || answerValue === null) return false
  const ans = answerValue.toString().trim().toLowerCase()
  const correct = (q.correct_answer || '').toString().trim().toLowerCase()
  if (ans === correct) return true
  const letterIndex = { 'a': 0, 'b': 1, 'c': 2, 'd': 3 }[correct]
  if (letterIndex !== undefined && q.options) {
    const textAnswer = (q.options[letterIndex] || '').toString().trim().toLowerCase()
    return ans === textAnswer
  }
  return false
}

const getAudioUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return 'http://localhost:8000' + path
}

const fetchQuiz = async () => {
  isLoading.value = true
  try {
    const data = await CourseQuizService.getByCourse(courseId.value)
    quiz.value = data
  } catch (error) {
    console.error('Lỗi tải quiz:', error)
    quiz.value = null
  } finally {
    isLoading.value = false
  }
}

const fetchCourseProgress = async () => {
  try {
    const data = await ProgressService.getCourseProgress(courseId.value)
    courseProgress.value = data.data
  } catch (error) {
    console.error('Lỗi tải tiến độ:', error)
    courseProgress.value = null
  }
}

const startExam = () => {
  phase.value = 'doing'
  answers.value = {}
  recordedUrls.value = {}
  currentIndex.value = 0
  submitted.value = false
  showSubmitConfirm.value = false
  showExitConfirm.value = false
  submitResult.value = null

  if (quiz.value?.duration) {
    totalSeconds.value = quiz.value.duration * 60
    timeLeft.value = totalSeconds.value
    timerInterval = setInterval(() => {
      timeLeft.value--
      if (timeLeft.value <= 0) {
        clearInterval(timerInterval)
        submitExam()
      }
    }, 1000)
  }
}

const setAnswer = (opt) => {
  answers.value[currentIndex.value] = opt
}

const submitAnswer = () => {
  submitted.value = true
}

const nextQuestion = () => {
  if (currentIndex.value < totalQuestions.value - 1) {
    currentIndex.value++
    submitted.value = false
  } else {
    showSubmitConfirm.value = true
  }
}

const prevQuestion = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    submitted.value = false
  }
}

const submitExam = async () => {
  clearInterval(timerInterval)
  showSubmitConfirm.value = false
  phase.value = 'result'

  try {
    const result = await ProgressService.submitFinal({
      course_id: courseId.value,
      quiz_id: quiz.value?.id,
      score: scorePercent.value,
      time_spent: totalSeconds.value - timeLeft.value,
    })
    submitResult.value = result.data
  } catch (error) {
    console.error('Lỗi nộp bài thi:', error)
    submitResult.value = { score: scorePercent.value, passed: false, attempt: 1 }
  }
}

const confirmExit = () => {
  showExitConfirm.value = true
}

const forceExit = () => {
  clearInterval(timerInterval)
  router.push(`/student/khoa-hoc/${courseId.value}`)
}

const retryExam = () => {
  fetchCourseProgress()
  phase.value = 'intro'
  currentIndex.value = 0
  answers.value = {}
  recordedUrls.value = {}
  submitted.value = false
  submitResult.value = null
}

const goBack = () => {
  router.push(`/student/khoa-hoc/${courseId.value}`)
}

const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    recordingChunks = []
    mediaRecorder.ondataavailable = (e) => recordingChunks.push(e.data)
    mediaRecorder.onstop = () => {
      const blob = new Blob(recordingChunks, { type: 'audio/webm' })
      recordedUrls.value[currentIndex.value] = URL.createObjectURL(blob)
      stream.getTracks().forEach(t => t.stop())
    }
    mediaRecorder.start()
    isRecording.value = true
    recordingTime.value = 0
    recordingInterval = setInterval(() => recordingTime.value++, 1000)
  } catch (err) {
    alert('Không thể truy cập micro.')
  }
}

const stopRecording = () => {
  if (mediaRecorder) mediaRecorder.stop()
  isRecording.value = false
  clearInterval(recordingInterval)
}

const clearRecording = () => {
  if (recordedUrls.value[currentIndex.value]) {
    URL.revokeObjectURL(recordedUrls.value[currentIndex.value])
  }
  delete recordedUrls.value[currentIndex.value]
  recordedUrls.value = { ...recordedUrls.value }
  recordingTime.value = 0
}

watch(currentIndex, () => {
  const q = currentQuestion.value
  if (q && q.type !== 'speaking') {
    submitted.value = !!answers.value[currentIndex.value]
  }
})

onMounted(async () => {
  await fetchQuiz()
  await fetchCourseProgress()
})

onUnmounted(() => {
  clearInterval(timerInterval)
  clearInterval(recordingInterval)
})
</script>

<style scoped>
.final-exam-player { min-height: 100vh; background: #f0f4ff; }

/* Loading */
.loading-screen, .empty-screen {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  min-height: 100vh; gap: 16px; text-align: center; padding: 40px;
}

.spinner {
  width: 48px; height: 48px;
  border: 4px solid #e0e7ff; border-top-color: #4f46e5;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.empty-icon {
  width: 80px; height: 80px; background: #ede9fe; border-radius: 24px;
  display: flex; align-items: center; justify-content: center;
  font-size: 36px; color: #7c3aed;
}

.empty-screen h2 { font-size: 24px; color: #1e293b; margin: 0 0 8px; }
.empty-screen p { color: #64748b; margin: 0 0 24px; }

.btn-back {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 24px; background: #4f46e5; color: white;
  border: none; border-radius: 12px; font-weight: 600; cursor: pointer;
}

/* Intro */
.intro-screen {
  display: flex; align-items: center; justify-content: center;
  min-height: 100vh; padding: 40px 20px;
}

.intro-card {
  background: white; border-radius: 24px;
  padding: 48px; max-width: 560px; width: 100%;
  text-align: center; box-shadow: 0 20px 60px rgba(79, 70, 229, 0.12);
}

.intro-icon {
  width: 80px; height: 80px;
  background: linear-gradient(135deg, #ca8a04, #f59e0b);
  border-radius: 24px; display: flex; align-items: center; justify-content: center;
  font-size: 36px; color: white; margin: 0 auto 24px;
  box-shadow: 0 10px 30px rgba(202, 138, 4, 0.3);
}

.intro-card h1 { font-size: 28px; font-weight: 900; color: #1e293b; margin: 0 0 12px; }
.intro-desc { font-size: 16px; color: #64748b; line-height: 1.6; margin: 0 0 28px; }

.intro-meta {
  display: flex; justify-content: center; gap: 24px;
  padding: 20px; background: #f8faff; border-radius: 14px; margin-bottom: 24px;
}

.meta-item {
  display: flex; align-items: center; gap: 8px;
  font-size: 15px; font-weight: 600; color: #475569;
}

.intro-warning {
  display: flex; align-items: center; justify-content: center;
  gap: 8px; font-size: 14px; color: #dc2626;
  background: #fef2f2; border-radius: 10px; padding: 12px;
  margin-bottom: 28px;
}

.btn-start {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 16px 40px;
  background: linear-gradient(135deg, #ca8a04, #f59e0b);
  color: white; border: none; border-radius: 14px;
  font-size: 17px; font-weight: 700; cursor: pointer;
  box-shadow: 0 8px 24px rgba(202, 138, 4, 0.35);
  transition: all 0.3s;
}

.btn-start:hover { transform: translateY(-3px); box-shadow: 0 12px 36px rgba(202, 138, 4, 0.45); }

/* Attempt info in intro */
.attempt-info {
  background: #fffbeb; border: 2px solid #fde68a; border-radius: 14px;
  padding: 16px; margin-bottom: 20px; text-align: left;
  display: flex; flex-direction: column; gap: 8px;
}

.attempt-badge {
  display: flex; align-items: center; gap: 8px;
  font-size: 15px; font-weight: 700; color: #92400e;
}

.best-score {
  display: flex; align-items: center; gap: 8px;
  font-size: 14px; color: #78350f;
}

.best-score strong { color: #d97706; }

.retry-hint {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; color: #b45309; background: #fef3c7;
  padding: 8px 12px; border-radius: 8px;
}

/* Exam */
.exam-screen { min-height: 100vh; }

.exam-top-bar {
  display: flex; align-items: center; justify-content: space-between;
  background: white; padding: 16px 24px;
  position: sticky; top: 0; z-index: 10;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.exam-title-bar { display: flex; align-items: center; gap: 12px; }
.exam-title-bar h1 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0; }

.btn-back-exam {
  width: 36px; height: 36px; background: #f1f5f9; border: none; border-radius: 10px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: #475569; font-size: 16px;
}

.exam-timer {
  display: flex; align-items: center; gap: 8px;
  font-size: 20px; font-weight: 800; color: #1e293b;
}

.exam-timer .time-warning { color: #ef4444; animation: blink 1s infinite; }

@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.exam-progress {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
}

.prog-track {
  flex: 1; height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;
}

.prog-fill {
  height: 100%; background: linear-gradient(90deg, #ca8a04, #f59e0b);
  border-radius: 999px; transition: width 0.4s ease;
}

.prog-label { font-size: 14px; font-weight: 700; color: #64748b; white-space: nowrap; }

.exam-question-card {
  max-width: 720px; margin: 40px auto 0; padding: 0 24px;
}

.q-number {
  font-size: 13px; font-weight: 700; color: #ca8a04;
  text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;
}

.q-text {
  font-size: 22px; font-weight: 800; color: #1e293b;
  line-height: 1.4; margin: 0 0 24px;
}

/* Options */
.options-list { display: flex; flex-direction: column; gap: 12px; }

.option-btn {
  display: flex; align-items: center; gap: 14px;
  padding: 16px 20px; background: white;
  border: 2px solid #e2e8f0; border-radius: 14px;
  cursor: pointer; transition: all 0.2s;
}

.option-btn:hover:not(.correct):not(.wrong) {
  border-color: #ca8a04; background: #fffbeb;
}

.option-btn.selected { border-color: #ca8a04; background: #fffbeb; }
.option-btn.correct { border-color: #22c55e; background: #f0fdf4; }
.option-btn.wrong { border-color: #ef4444; background: #fef2f2; }

.opt-label {
  width: 36px; height: 36px; border-radius: 10px;
  background: #f1f5f9; display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 15px; color: #ca8a04; flex-shrink: 0;
}

.option-btn.selected .opt-label { background: #ca8a04; color: white; }
.option-btn.correct .opt-label { background: #22c55e; color: white; }
.option-btn.wrong .opt-label { background: #ef4444; color: white; }

.opt-text { flex: 1; font-size: 16px; font-weight: 600; color: #1e293b; }
.opt-icon { font-size: 18px; }
.opt-icon.correct { color: #22c55e; }
.opt-icon.wrong { color: #ef4444; }

/* Fill */
.fill-area {
  background: white; border-radius: 16px; padding: 24px;
  border: 1px solid #e2e8f0;
}

.fill-input {
  width: 100%; padding: 14px 18px;
  border: 2px solid #e2e8f0; border-radius: 12px;
  font-size: 18px; font-weight: 600; outline: none;
  transition: all 0.2s; box-sizing: border-box; color: #1e293b;
}

.fill-input:focus { border-color: #ca8a04; background: #fffbeb; }
.fill-input.correct-answer { border-color: #22c55e; background: #f0fdf4; }
.fill-input.wrong-answer { border-color: #ef4444; background: #fef2f2; }

.correct-hint {
  margin-top: 12px; padding: 10px 14px;
  background: #f0fdf4; border-radius: 8px;
  font-size: 14px; color: #166534;
  display: flex; align-items: center; gap: 6px;
}

.correct-hint i { color: #22c55e; }

/* Listening */
.audio-box { margin-bottom: 16px; }
.audio-box audio { width: 100%; height: 48px; border-radius: 12px; }

/* Speaking */
.speaking-area { background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; }

.sentence-card {
  background: linear-gradient(135deg, #f0f1ff, #ede9fe);
  border-radius: 16px; padding: 28px; margin-bottom: 24px; text-align: center;
}

.sentence-text {
  font-size: 22px; font-weight: 800; color: #1e293b; margin: 0;
  line-height: 1.5; letter-spacing: 0.3px;
}

.record-section { display: flex; flex-direction: column; align-items: center; gap: 16px; }

.btn-record {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: white; border: none; border-radius: 14px;
  font-size: 15px; font-weight: 700; cursor: pointer;
  box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35);
}

.recording-state {
  display: flex; align-items: center; gap: 10px;
  font-size: 16px; font-weight: 700; color: #ef4444;
}

.rec-dot {
  width: 14px; height: 14px; background: #ef4444;
  border-radius: 50%; animation: pulse 1s infinite;
}

@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.btn-stop {
  padding: 8px 16px; background: #ef4444; color: white;
  border: none; border-radius: 8px; cursor: pointer;
  font-size: 14px; font-weight: 700;
}

.recorded-info {
  display: flex; flex-direction: column; align-items: center; gap: 12px; width: 100%;
}

.recorded-info audio { width: 100%; height: 40px; }

.btn-re-record {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 20px; background: #f1f5f9; color: #475569;
  border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;
}

/* Nav */
.exam-nav {
  display: flex; justify-content: space-between; align-items: center;
  max-width: 720px; margin: 32px auto 0; padding: 0 24px;
}

.btn-nav {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 24px; border: none; border-radius: 12px;
  font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.2s;
}

.btn-nav.prev { background: #f1f5f9; color: #475569; }
.btn-nav.prev:hover { background: #e2e8f0; }

.btn-nav.submit {
  background: #ca8a04; color: white;
  box-shadow: 0 4px 14px rgba(202, 138, 4, 0.35);
}

.btn-nav.submit:hover:not(:disabled) { background: #a16207; transform: translateY(-2px); }
.btn-nav.submit:disabled { background: #d4a017; cursor: not-allowed; box-shadow: none; }

.btn-nav.next { background: #22c55e; color: white; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35); }
.btn-nav.next:hover { background: #16a34a; transform: translateY(-2px); }

/* Modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  z-index: 100; backdrop-filter: blur(4px);
}

.modal-box {
  background: white; border-radius: 20px;
  padding: 40px; max-width: 400px; width: 90%;
  text-align: center;
}

.modal-icon {
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; margin: 0 auto 20px;
}

.modal-icon.warning { background: #fef3c7; color: #ca8a04; }
.modal-icon.danger { background: #fef2f2; color: #ef4444; }

.modal-box h2 { font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 12px; }
.modal-box p { font-size: 15px; color: #64748b; margin: 0 0 28px; }

.modal-actions { display: flex; gap: 12px; justify-content: center; }

.btn-cancel {
  flex: 1; padding: 12px; background: #f1f5f9; color: #475569;
  border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer;
}

.btn-confirm {
  flex: 1; padding: 12px; background: #22c55e; color: white;
  border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer;
}

.btn-danger {
  flex: 1; padding: 12px; background: #ef4444; color: white;
  border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer;
}

/* Result */
.result-screen {
  min-height: 100vh; padding: 60px 20px;
  background: #f0f4ff; display: flex; align-items: flex-start; justify-content: center;
}

.result-card {
  background: white; border-radius: 24px; padding: 48px;
  max-width: 560px; width: 100%; text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.1);
}

/* PASS banner */
.result-banner {
  border-radius: 16px; padding: 24px; margin-bottom: 24px;
  display: flex; align-items: center; gap: 20px;
}

.result-banner.passed {
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  border: 2px solid #22c55e;
}

.result-banner.failed {
  background: linear-gradient(135deg, #fef2f2, #fee2e2);
  border: 2px solid #ef4444;
}

.banner-icon {
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; flex-shrink: 0;
}

.result-banner.passed .banner-icon { background: #22c55e; color: white; }
.result-banner.failed .banner-icon { background: #ef4444; color: white; }

.banner-text { text-align: left; }
.banner-text h2 { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
.result-banner.passed .banner-text h2 { color: #166534; }
.result-banner.failed .banner-text h2 { color: #991b1b; }

.banner-text p { margin: 0; font-size: 16px; color: #475569; }
.banner-text strong { font-size: 20px; }

.result-message {
  margin-bottom: 20px; padding: 16px; border-radius: 12px;
}

.msg-pass {
  color: #166534; font-size: 18px; font-weight: 700; margin: 0;
}

.msg-fail {
  color: #991b1b; font-size: 16px; font-weight: 600; margin: 0 0 8px;
}

.msg-fail-hint {
  color: #b91c1c; font-size: 14px; margin: 0;
}

.score-display {
  display: flex; align-items: baseline; justify-content: center; gap: 4px;
}

.big-score { font-size: 64px; font-weight: 900; color: #ca8a04; line-height: 1; }
.score-denom { font-size: 28px; font-weight: 700; color: #94a3b8; }

.result-bar-wrap {
  height: 16px; background: #e2e8f0; border-radius: 999px;
  overflow: visible; max-width: 400px; margin: 0 auto 8px; position: relative;
}

.result-bar-fill {
  height: 100%; border-radius: 999px; transition: width 1s ease;
}

.result-pass-line {
  position: absolute; top: -4px; bottom: -4px;
  width: 3px; background: #1e293b; border-radius: 2px;
}

.pass-threshold {
  font-size: 12px; color: #94a3b8; margin: 0 0 16px; font-weight: 600;
}

/* Attempt result */
.attempt-result {
  display: flex; justify-content: center; gap: 24px;
  padding: 12px; background: #f8faff; border-radius: 10px;
  margin-bottom: 20px;
}

.attempt-item {
  display: flex; align-items: center; gap: 8px;
  font-size: 14px; font-weight: 600; color: #475569;
}

.attempt-item.best { color: #d97706; }

.result-stats {
  display: flex; justify-content: center; gap: 24px; margin-bottom: 32px;
  font-size: 15px; font-weight: 600;
}

.stat-correct { color: #22c55e; }
.stat-wrong { color: #ef4444; }
.stat-skipped { color: #94a3b8; }

.result-actions { display: flex; gap: 16px; justify-content: center; }

.btn-retry {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 28px; background: #4f46e5; color: white;
  border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer;
}

.btn-home {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 14px 28px; background: white;
  color: #475569; border: 2px solid #e2e8f0; border-radius: 12px;
  font-size: 15px; font-weight: 700; cursor: pointer;
}
</style>
