<template>
  <div class="lesson-player">
    <!-- Loading -->
    <div v-if="isLoading" class="loading-screen">
      <div class="loader"></div>
      <p>Đang tải bài học...</p>
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

    <!-- Lesson Content -->
    <template v-else-if="lesson">
      <!-- Header -->
      <div class="lesson-header">
        <div class="container">
          <div class="header-content">
            <button class="btn-back-header" @click="goBack">
              <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="lesson-info">
              <span class="chapter-name">{{ lesson.chapter?.title || 'Chương' }}</span>
              <h1 class="lesson-title">{{ lesson.title }}</h1>
            </div>
            <div class="progress-indicator">
              <span class="current-step">{{ currentStepIndex + 1 }}</span>
              <span class="separator">/</span>
              <span class="total-steps">{{ steps.length }}</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="progress-bar-container">
            <div class="progress-bar-fill" :style="{ width: progressPercent + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Step Content -->
      <div class="lesson-content">
        <div class="container">
          <!-- Empty Steps -->
          <div v-if="steps.length === 0" class="empty-content">
            <div class="empty-icon">
              <i class="fa-solid fa-book-open"></i>
            </div>
            <h3>Bài học đang được cập nhật</h3>
            <p>Nội dung bài học sẽ sớm có mặt.</p>
            <button class="btn-primary" @click="goBack">
              Quay lại khóa học
            </button>
          </div>

          <!-- Vocabulary Step -->
          <div v-else-if="currentStep.type === 'vocabulary'" class="step-content vocabulary-step">
            <div class="step-card vocabulary-card">
              <div class="word-header">
                <span class="step-label">Từ vựng</span>
                <span class="step-count">{{ currentStepIndex + 1 }} / {{ steps.length }}</span>
              </div>

              <div class="word-display">
                <h2 class="word-text">{{ currentStep.content.word }}</h2>
                <p class="word-pronunciation" v-if="currentStep.content.pronunciation">
                  {{ currentStep.content.pronunciation }}
                </p>
                <p class="word-meaning">{{ currentStep.content.meaning }}</p>
                <p class="word-example" v-if="currentStep.content.example">
                  <span class="example-label">Ví dụ:</span>
                  {{ currentStep.content.example }}
                </p>
              </div>

              <div class="audio-btn" v-if="currentStep.content.audio">
                <button class="btn-audio" @click="playAudio(currentStep.content.audio)">
                  <i class="fa-solid fa-volume-high"></i>
                  Nghe phát âm
                </button>
              </div>
            </div>
          </div>

          <!-- Grammar Step -->
          <div v-else-if="currentStep.type === 'grammar'" class="step-content grammar-step">
            <div class="step-card grammar-card">
              <div class="step-header">
                <span class="step-label">Ngữ pháp</span>
                <span class="step-count">{{ currentStepIndex + 1 }} / {{ steps.length }}</span>
              </div>

              <div class="grammar-content">
                <h2 class="grammar-title">{{ currentStep.content.title }}</h2>
                <div class="grammar-structure" v-if="currentStep.content.structure">
                  <div class="structure-label">Cấu trúc</div>
                  <div class="structure-text">{{ currentStep.content.structure }}</div>
                </div>
                <div class="grammar-explanation">
                  <div class="explanation-label">Giải thích</div>
                  <p>{{ currentStep.content.explanation }}</p>
                </div>
                <div class="grammar-example" v-if="currentStep.content.example">
                  <div class="example-label">Ví dụ</div>
                  <p class="example-text">{{ currentStep.content.example }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Listening Step -->
          <div v-else-if="currentStep.type === 'listening'" class="step-content listening-step">
            <div class="step-card listening-card">
              <div class="step-header">
                <span class="step-label">Luyện nghe</span>
                <span class="step-count">{{ currentStepIndex + 1 }} / {{ steps.length }}</span>
              </div>

              <div class="listening-content">
                <div class="audio-player-large">
                  <button class="btn-play" @click="toggleAudio" :class="{ playing: isPlaying }">
                    <i :class="isPlaying ? 'fa-solid fa-pause' : 'fa-solid fa-play'"></i>
                  </button>
                  <div class="audio-wave" v-if="isPlaying">
                    <span></span><span></span><span></span><span></span><span></span>
                  </div>
                </div>

                <div class="audio-source" v-if="currentStep.content.audio">
                  <audio ref="audioElement" :src="getAudioUrl(currentStep.content.audio)" @ended="onAudioEnded"></audio>
                </div>

                <p class="listening-question" v-if="currentStep.content.question">
                  {{ currentStep.content.question }}
                </p>

                <div class="listening-transcript" v-if="showTranscript && currentStep.content.transcript">
                  <div class="transcript-label">Bản ghi</div>
                  <p>{{ currentStep.content.transcript }}</p>
                </div>

                <button v-if="currentStep.content.transcript" class="btn-transcript" @click="showTranscript = !showTranscript">
                  <i class="fa-solid fa-book-open"></i>
                  {{ showTranscript ? 'Ẩn bản ghi' : 'Xem bản ghi' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Quiz Step -->
          <div v-else-if="currentStep.type === 'quiz'" class="step-content quiz-step">
            <div class="step-card quiz-card">
              <div class="step-header">
                <span class="step-label">Kiểm tra</span>
                <span class="step-count">{{ currentStepIndex + 1 }} / {{ steps.length }}</span>
              </div>

              <div class="quiz-content">
                <h2 class="quiz-question">{{ currentStep.content.question }}</h2>

                <div class="quiz-options">
                  <button
                    v-for="(option, optIndex) in currentStep.content.options"
                    :key="optIndex"
                    class="quiz-option"
                    :class="{
                      'selected': selectedAnswer === optIndex,
                      'correct': showResult && optIndex === correctAnswerIndex,
                      'wrong': showResult && selectedAnswer === optIndex && optIndex !== correctAnswerIndex
                    }"
                    @click="selectAnswer(optIndex)"
                    :disabled="showResult"
                  >
                    <span class="option-letter">{{ String.fromCharCode(65 + optIndex) }}</span>
                    <span class="option-text">{{ option }}</span>
                    <i v-if="showResult && optIndex === correctAnswerIndex" class="fa-solid fa-check-circle correct-icon"></i>
                    <i v-if="showResult && selectedAnswer === optIndex && optIndex !== correctAnswerIndex" class="fa-solid fa-times-circle wrong-icon"></i>
                  </button>
                </div>

                <div v-if="showResult" class="quiz-result" :class="{ correct: isCorrect }">
                  <div class="result-icon">
                    <i :class="isCorrect ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                  </div>
                  <div class="result-text">
                    <strong>{{ isCorrect ? 'Chính xác!' : 'Chưa đúng rồi!' }}</strong>
                    <p v-if="!isCorrect && currentStep.content.explanation">
                      {{ currentStep.content.explanation }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Speaking Step -->
          <div v-else-if="currentStep.type === 'speaking'" class="step-content speaking-step">
            <div class="step-card speaking-card">
              <div class="step-header">
                <span class="step-label">Luyện nói</span>
                <span class="step-count">{{ currentStepIndex + 1 }} / {{ steps.length }}</span>
              </div>

              <div class="speaking-content">
                <p class="speaking-prompt" v-if="currentStep.content.prompt">
                  {{ currentStep.content.prompt }}
                </p>

                <div class="sample-answer" v-if="currentStep.content.sample_answer">
                  <div class="sample-label">
                    <i class="fa-solid fa-lightbulb"></i>
                    Câu mẫu
                  </div>
                  <p>{{ currentStep.content.sample_answer }}</p>
                </div>

                <div class="keywords-hint" v-if="currentStep.content.keywords">
                  <div class="keywords-label">
                    <i class="fa-solid fa-key"></i>
                    Từ khóa
                  </div>
                  <div class="keywords-list">
                    <span v-for="kw in currentStep.content.keywords.split(',')" :key="kw" class="keyword-tag">
                      {{ kw.trim() }}
                    </span>
                  </div>
                </div>

                <div class="record-area">
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

                <div v-if="recordedAudio" class="recorded-preview">
                  <audio :src="recordedAudio" controls></audio>
                  <button class="btn-evaluate" @click="evaluateSpeaking">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    Đánh giá AI
                  </button>
                </div>

                <!-- Evaluation Result -->
                <div v-if="evaluationResult" class="evaluation-result" :class="evaluationResult.score >= 70 ? 'good' : 'needs-work'">
                  <div class="eval-score">
                    <span class="score-value">{{ evaluationResult.score }}</span>
                    <span class="score-max">/100</span>
                  </div>
                  <p class="eval-message">{{ evaluationResult.feedback?.message }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Default Step -->
          <div v-else class="step-content default-step">
            <div class="step-card">
              <h2>{{ currentStep.type }}</h2>
              <pre>{{ JSON.stringify(currentStep.content, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="lesson-navigation" v-if="steps.length > 0">
        <div class="container">
          <button
            class="btn-nav btn-prev"
            :disabled="currentStepIndex === 0"
            @click="prevStep"
          >
            <i class="fa-solid fa-chevron-left"></i>
            Bài trước
          </button>

          <div class="step-dots">
            <span
              v-for="(step, index) in steps"
              :key="index"
              class="dot"
              :class="{
                active: index === currentStepIndex,
                completed: index < currentStepIndex
              }"
              @click="goToStep(index)"
            ></span>
          </div>

          <button
            v-if="steps.length > 0 && currentStepIndex < steps.length - 1"
            class="btn-nav btn-next"
            @click="nextStep"
          >
            Bài tiếp theo
            <i class="fa-solid fa-chevron-right"></i>
          </button>
          <button
            v-else-if="steps.length > 0"
            class="btn-nav btn-complete"
            @click="completeLesson"
          >
            <i class="fa-solid fa-check"></i>
            Hoàn thành
          </button>
        </div>
      </div>

      <!-- Completion Modal -->
      <div v-if="showCompletionModal" class="completion-modal-overlay" @click.self="closeCompletionModal">
        <div class="completion-modal">
          <div class="completion-icon">
            <i class="fa-solid fa-trophy"></i>
          </div>
          <h2>Chúc mừng!</h2>
          <p>Bạn đã hoàn thành bài học "{{ lesson.title }}"</p>
          <div class="completion-stats">
            <div class="stat">
              <span class="stat-value">{{ steps.length }}</span>
              <span class="stat-label">Bài học</span>
            </div>
          </div>
          <div class="completion-actions">
            <button class="btn-secondary" @click="reviewLesson">
              <i class="fa-solid fa-rotate-left"></i>
              Học lại
            </button>
            <button v-if="nextLesson" class="btn-primary" @click="goToNextLesson">
              Bài tiếp theo
              <i class="fa-solid fa-arrow-right"></i>
            </button>
            <button v-else class="btn-primary" @click="goBackToCourse">
              <i class="fa-solid fa-home"></i>
              Quay về khóa học
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { LessonService, CourseService, ProgressService } from '../../../services/api.js'

const API_URL = 'http://localhost:8000'
const getAudioUrl = (audio) => {
  if (!audio) return ''
  if (audio.startsWith('http')) return audio
  // Nếu đã có /storage/ thì không thêm nữa
  if (audio.startsWith('/storage/')) return `${API_URL}${audio}`
  return `${API_URL}/storage/${audio}`
}

const route = useRoute()
const router = useRouter()

// State
const isLoading = ref(true)
const error = ref(null)
const lesson = ref(null)
const course = ref(null)
const chapters = ref([])
const steps = ref([])
const currentStepIndex = ref(0)
const showTranscript = ref(false)
const showCompletionModal = ref(false)
const startTime = ref(null)

// Audio
const audioElement = ref(null)
const isPlaying = ref(false)

// Quiz
const selectedAnswer = ref(null)
const showResult = ref(false)

// Recording
const isRecording = ref(false)
const recordedAudio = ref(null)
const recordedBlob = ref(null)
const mediaRecorder = ref(null)
const audioChunks = ref([])

// Evaluation
const evaluationResult = ref(null)
const isEvaluating = ref(false)

// Computed
const currentStep = computed(() => steps.value[currentStepIndex.value] || {})

const progressPercent = computed(() => {
  if (steps.value.length === 0) return 0
  return Math.round(((currentStepIndex.value + 1) / steps.value.length) * 100)
})

const correctAnswerIndex = computed(() => {
  if (!currentStep.value.content?.correct_answer) return -1
  return currentStep.value.content.options?.indexOf(currentStep.value.content.correct_answer) ?? -1
})

const isCorrect = computed(() => {
  return selectedAnswer.value === correctAnswerIndex.value
})

// Tìm bài tiếp theo
const nextLesson = computed(() => {
  if (!course.value || chapters.value.length === 0) return null

  const currentChapterId = lesson.value?.chapter_id
  const currentChapterIndex = chapters.value.findIndex(ch => ch.id === currentChapterId)
  if (currentChapterIndex === -1) return null

  const currentChapter = chapters.value[currentChapterIndex]
  const currentLessonOrder = lesson.value?.order || 0

  // Tìm bài tiếp theo cùng chapter
  const nextInSameChapter = currentChapter.lessons?.find(l => l.order > currentLessonOrder)
  if (nextInSameChapter) {
    return { lesson: nextInSameChapter, chapter: currentChapter }
  }

  // Tìm bài đầu tiên của chapter tiếp theo
  for (let i = currentChapterIndex + 1; i < chapters.value.length; i++) {
    const nextChapter = chapters.value[i]
    if (nextChapter.lessons?.length > 0 && nextChapter.status !== 'draft') {
      return { lesson: nextChapter.lessons[0], chapter: nextChapter }
    }
  }

  return null
})

// Methods
const fetchLesson = async () => {
  isLoading.value = true
  error.value = null
  startTime.value = Date.now()

  const lessonId = route.params.id

  if (!lessonId) {
    error.value = 'ID bài học không hợp lệ'
    isLoading.value = false
    return
  }

  try {
    const res = await LessonService.getById(lessonId)
    lesson.value = res.data || res

    // Lấy course và chapters để tìm bài tiếp theo
    if (lesson.value.chapter?.course_id) {
      const courseRes = await CourseService.getById(lesson.value.chapter.course_id)
      course.value = courseRes.data || courseRes

      const chaptersRes = await CourseService.getChapters(lesson.value.chapter.course_id)
      chapters.value = Array.isArray(chaptersRes) ? chaptersRes : (chaptersRes.data || [])
    }

    // Lưu progress bắt đầu bài học
    try {
      await ProgressService.startLesson(lessonId)
    } catch (e) {
      // ignore progress errors
    }

    steps.value = buildStepsFromLesson(lesson.value)
  } catch (err) {
    console.error('Lỗi khi tải bài học:', err)
    error.value = 'Không thể tải bài học. Vui lòng thử lại.'
  } finally {
    isLoading.value = false
  }
}

// Xây dựng steps từ dữ liệu lesson
const buildStepsFromLesson = (lesson) => {
  const steps = []

  // Thêm vocabulary steps
  if (lesson.vocabularies && lesson.vocabularies.length > 0) {
    lesson.vocabularies.forEach((vocab, index) => {
      steps.push({
        type: 'vocabulary',
        order: steps.length + 1,
        content: {
          word: vocab.word,
          pronunciation: vocab.pronunciation,
          meaning: vocab.meaning,
          example: vocab.example,
          audio: vocab.audio_url,
          image: vocab.image_url
        }
      })
    })
  }

  // Thêm grammar steps
  if (lesson.grammars && lesson.grammars.length > 0) {
    lesson.grammars.forEach((grammar) => {
      steps.push({
        type: 'grammar',
        order: steps.length + 1,
        content: {
          title: grammar.title,
          structure: grammar.structure,
          explanation: grammar.explanation,
          example: grammar.example
        }
      })
    })
  }

  // Thêm listening steps
  if (lesson.listenings && lesson.listenings.length > 0) {
    lesson.listenings.forEach((listening) => {
      steps.push({
        type: 'listening',
        order: steps.length + 1,
        content: {
          title: listening.title,
          audio: listening.audio_url,
          transcript: listening.transcript,
          question: listening.question
        }
      })
    })
  }

  // Thêm speaking steps
  if (lesson.speaking_exercises && lesson.speaking_exercises.length > 0) {
    lesson.speaking_exercises.forEach((speaking) => {
      steps.push({
        type: 'speaking',
        order: steps.length + 1,
        content: {
          type: speaking.type,
          prompt: speaking.content,
          sample_answer: speaking.sample_answer,
          keywords: speaking.keywords,
          audio: speaking.audio_url,
          image: speaking.image_url
        }
      })
    })
  }

  return steps
}

const goBack = () => {
  if (lesson.value?.chapter?.course_id) {
    router.push(`/student/khoa-hoc/${lesson.value.chapter.course_id}`)
  } else {
    router.push('/student')
  }
}

const prevStep = () => {
  if (currentStepIndex.value > 0) {
    currentStepIndex.value--
    resetStepState()
  }
}

const nextStep = () => {
  if (currentStepIndex.value < steps.value.length - 1) {
    currentStepIndex.value++
    resetStepState()
  } else {
    showCompletionModal.value = true
  }
}

const goToStep = (index) => {
  currentStepIndex.value = index
  resetStepState()
}

const resetStepState = () => {
  showResult.value = false
  selectedAnswer.value = null
  showTranscript.value = false
  stopAudio()
  isRecording.value = false
  recordedAudio.value = null
}

// Audio
const playAudio = (audioUrl) => {
  if (audioElement.value) {
    audioElement.value.src = audioUrl
    audioElement.value.play()
    isPlaying.value = true
  }
}

const toggleAudio = () => {
  if (!audioElement.value) return

  if (isPlaying.value) {
    audioElement.value.pause()
    isPlaying.value = false
  } else {
    audioElement.value.play()
    isPlaying.value = true
  }
}

const onAudioEnded = () => {
  isPlaying.value = false
}

const stopAudio = () => {
  if (audioElement.value) {
    audioElement.value.pause()
    audioElement.value.currentTime = 0
  }
  isPlaying.value = false
}

// Quiz
const selectAnswer = (index) => {
  if (showResult.value) return
  selectedAnswer.value = index
  showResult.value = true
}

// Recording
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
      recordedAudio.value = URL.createObjectURL(blob)
      stream.getTracks().forEach(track => track.stop())
    }

    mediaRecorder.value.start()
    isRecording.value = true
    evaluationResult.value = null
  } catch (err) {
    console.error('Lỗi ghi âm:', err)
    alert('Không thể truy cập microphone. Vui lòng kiểm tra quyền.')
  }
}

const stopRecording = () => {
  if (mediaRecorder.value && isRecording.value) {
    mediaRecorder.value.stop()
    isRecording.value = false
  }
}

const evaluateSpeaking = async () => {
  if (!recordedBlob.value) return

  const speakingExercises = lesson.value.speaking_exercises || []
  const currentSpeakingIndex = steps.value
    .slice(0, currentStepIndex.value + 1)
    .filter(s => s.type === 'speaking')
    .length - 1

  if (currentSpeakingIndex < 0 || currentSpeakingIndex >= speakingExercises.length) {
    alert('Không tìm thấy bài tập nói để đánh giá')
    return
  }

  const speakingExercise = speakingExercises[currentSpeakingIndex]
  isEvaluating.value = true

  try {
    const formData = new FormData()
    formData.append('audio', recordedBlob.value, 'recording.webm')

    const response = await fetch(`http://localhost:8000/api/speaking-exercises/${speakingExercise.id}/evaluate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      },
      body: formData
    })

    const result = await response.json()
    if (result.success) {
      evaluationResult.value = result
    } else {
      alert(result.error || 'Không thể đánh giá. Vui lòng thử lại.')
    }
  } catch (err) {
    console.error('Lỗi đánh giá:', err)
    alert('Không thể đánh giá. Vui lòng thử lại.')
  } finally {
    isEvaluating.value = false
  }
}

// Completion
const completeLesson = async () => {
  const timeSpent = Math.round((Date.now() - startTime.value) / 1000)
  try {
    const res = await ProgressService.completeLesson(route.params.id, { time_spent: timeSpent })
    if (res.success && res.data?.course_id) {
      const courseProgress = await ProgressService.getCourseProgress(res.data.course_id)
      if (courseProgress.success) {
        saveProgressUpdate(res.data.course_id, courseProgress.data.progress_percent)
      }
    }
  } catch (e) {
    // ignore progress errors
  }
  showCompletionModal.value = true
}

// Lưu progress update vào localStorage để KhoaHocStudent đọc khi mount
const saveProgressUpdate = (courseId, percent) => {
  try {
    const stored = JSON.parse(localStorage.getItem('progress_updates') || '{}')
    stored[courseId] = percent
    localStorage.setItem('progress_updates', JSON.stringify(stored))
  } catch (e) {}
}

// Đọc và xóa progress update từ localStorage
const getAndClearProgressUpdate = (courseId) => {
  try {
    const stored = JSON.parse(localStorage.getItem('progress_updates') || '{}')
    const percent = stored[courseId]
    delete stored[courseId]
    localStorage.setItem('progress_updates', JSON.stringify(stored))
    return percent
  } catch (e) {
    return null
  }
}

const closeCompletionModal = () => {
  showCompletionModal.value = false
}

const goBackToCourse = () => {
  showCompletionModal.value = false
  // Dùng window.location.href để reload trang Chuong và cập nhật tiến trình
  window.location.href = `/student/khoa-hoc/${course.value.id}`
}

const reviewLesson = () => {
  currentStepIndex.value = 0
  showCompletionModal.value = false
  resetStepState()
}

const goToNextLesson = async () => {
  closeCompletionModal()

  // Chuyển bài tiếp theo - KHÔNG gọi ProgressService.completeLesson() ở đây
  // vì đã gọi trong completeLesson()
  if (nextLesson.value) {
    router.push(`/student/khoa-hoc/${nextLesson.value.course_id}/ch/${nextLesson.value.chapter_id}/bai/${nextLesson.value.id}`)
  } else {
    // Không còn bài nào -> quay về khóa học
    router.push(`/student/khoa-hoc/${course.value.id}`)
  }
}

// Lifecycle
onMounted(() => {
  fetchLesson()
})

onUnmounted(() => {
  stopAudio()
})
</script>

<style scoped>
.lesson-player {
  min-height: 100vh;
  background: #f8fafc;
}

/* Loading Screen */
.loading-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  gap: 20px;
  color: #4f46e5;
}

.loader {
  width: 48px;
  height: 48px;
  border: 4px solid #e0e7ff;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error Screen */
.error-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  text-align: center;
  padding: 20px;
}

.error-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #fef2f2;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}

.error-icon i {
  font-size: 40px;
  color: #ef4444;
}

.error-screen h2 {
  font-size: 24px;
  color: #1e293b;
  margin-bottom: 8px;
}

.error-screen p {
  color: #64748b;
  margin-bottom: 24px;
}

/* Header */
.lesson-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.lesson-header .container {
  padding: 16px 24px;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.btn-back-header {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-back-header:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.lesson-info {
  flex: 1;
}

.chapter-name {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.lesson-title {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.progress-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;
}

.current-step {
  font-size: 18px;
  color: #4f46e5;
}

.separator {
  color: #cbd5e1;
}

.total-steps {
  font-size: 14px;
  color: #64748b;
}

.progress-bar-container {
  height: 4px;
  background: #e2e8f0;
  border-radius: 2px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #818cf8);
  transition: width 0.3s ease;
}

/* Content */
.lesson-content {
  padding: 40px 24px;
}

.container {
  max-width: 800px;
  margin: 0 auto;
}

/* Empty Content */
.empty-content {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  width: 100px;
  height: 100px;
  border-radius: 24px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.empty-icon i {
  font-size: 48px;
  color: #cbd5e1;
}

.empty-content h3 {
  font-size: 20px;
  color: #1e293b;
  margin-bottom: 8px;
}

.empty-content p {
  color: #64748b;
  margin-bottom: 24px;
}

/* Step Card */
.step-card {
  background: white;
  border-radius: 20px;
  padding: 32px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.step-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.step-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.step-count {
  font-size: 14px;
  color: #64748b;
}

/* Vocabulary */
.vocabulary-card .step-label {
  background: #dcfce7;
  color: #16a34a;
}

.word-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.word-display {
  text-align: center;
  padding: 40px 0;
}

.word-text {
  font-size: 48px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 8px;
}

.word-pronunciation {
  font-size: 18px;
  color: #64748b;
  font-style: italic;
  margin-bottom: 16px;
}

.word-meaning {
  font-size: 24px;
  color: #4f46e5;
  font-weight: 600;
  margin-bottom: 16px;
}

.word-example {
  font-size: 16px;
  color: #475569;
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  margin-top: 24px;
}

.example-label {
  font-weight: 600;
  color: #64748b;
  margin-right: 8px;
}

.audio-btn {
  display: flex;
  justify-content: center;
  margin-top: 24px;
}

.btn-audio {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-audio:hover {
  transform: scale(1.02);
  box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
}

/* Grammar */
.grammar-card .step-label {
  background: #fef9c3;
  color: #ca8a04;
}

.grammar-content {
  padding: 20px 0;
}

.grammar-title {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 24px;
  text-align: center;
}

.grammar-structure {
  background: linear-gradient(135deg, #fef9c3, #fef08a);
  padding: 20px 24px;
  border-radius: 12px;
  margin-bottom: 24px;
}

.structure-label {
  font-size: 12px;
  font-weight: 600;
  color: #ca8a04;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.structure-text {
  font-size: 20px;
  font-weight: 700;
  color: #713f12;
  font-family: monospace;
}

.grammar-explanation {
  margin-bottom: 24px;
}

.explanation-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.grammar-explanation p {
  font-size: 16px;
  line-height: 1.8;
  color: #334155;
}

.grammar-example {
  background: #f8fafc;
  padding: 20px;
  border-radius: 12px;
  border-left: 4px solid #ca8a04;
}

.example-text {
  font-size: 16px;
  color: #334155;
  line-height: 1.6;
  font-style: italic;
}

/* Listening */
.listening-card .step-label {
  background: #dbeafe;
  color: #2563eb;
}

.listening-content {
  text-align: center;
  padding: 20px 0;
}

.audio-player-large {
  margin-bottom: 24px;
}

.btn-play {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  border: none;
  color: white;
  font-size: 28px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

.btn-play:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 30px rgba(37, 99, 235, 0.4);
}

.btn-play.playing {
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
  50% { box-shadow: 0 0 0 15px rgba(37, 99, 235, 0); }
}

.audio-wave {
  display: flex;
  justify-content: center;
  gap: 4px;
  margin-top: 16px;
}

.audio-wave span {
  width: 4px;
  height: 20px;
  background: #3b82f6;
  border-radius: 2px;
  animation: wave 0.5s ease-in-out infinite;
}

.audio-wave span:nth-child(2) { animation-delay: 0.1s; }
.audio-wave span:nth-child(3) { animation-delay: 0.2s; }
.audio-wave span:nth-child(4) { animation-delay: 0.3s; }
.audio-wave span:nth-child(5) { animation-delay: 0.4s; }

@keyframes wave {
  0%, 100% { height: 20px; }
  50% { height: 40px; }
}

.listening-question {
  font-size: 18px;
  color: #334155;
  margin: 24px 0;
}

.listening-transcript {
  background: #f8fafc;
  padding: 20px;
  border-radius: 12px;
  text-align: left;
  margin: 20px 0;
}

.transcript-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.listening-transcript p {
  color: #334155;
  line-height: 1.6;
}

.btn-transcript {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-transcript:hover {
  background: #e2e8f0;
}

/* Quiz */
.quiz-card .step-label {
  background: #fce7f3;
  color: #db2777;
}

.quiz-content {
  padding: 20px 0;
}

.quiz-question {
  font-size: 22px;
  font-weight: 600;
  color: #1e293b;
  text-align: center;
  margin-bottom: 32px;
  line-height: 1.4;
}

.quiz-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quiz-option {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.quiz-option:hover:not(:disabled) {
  border-color: #c7d2fe;
  background: #f8faff;
}

.quiz-option.selected {
  border-color: #4f46e5;
  background: #eef2ff;
}

.quiz-option.correct {
  border-color: #22c55e;
  background: #f0fdf4;
}

.quiz-option.wrong {
  border-color: #ef4444;
  background: #fef2f2;
}

.option-letter {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #64748b;
  flex-shrink: 0;
}

.quiz-option.selected .option-letter {
  background: #4f46e5;
  color: white;
}

.quiz-option.correct .option-letter {
  background: #22c55e;
  color: white;
}

.quiz-option.wrong .option-letter {
  background: #ef4444;
  color: white;
}

.option-text {
  flex: 1;
  font-size: 16px;
  color: #334155;
}

.correct-icon {
  color: #22c55e;
  font-size: 20px;
}

.wrong-icon {
  color: #ef4444;
  font-size: 20px;
}

.quiz-result {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border-radius: 12px;
  margin-top: 24px;
}

.quiz-result.correct {
  background: #f0fdf4;
}

.quiz-result:not(.correct) {
  background: #fef2f2;
}

.result-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.quiz-result.correct .result-icon {
  background: white;
}

.quiz-result.correct .result-icon i {
  font-size: 28px;
  color: #22c55e;
}

.quiz-result:not(.correct) .result-icon {
  background: white;
}

.quiz-result:not(.correct) .result-icon i {
  font-size: 28px;
  color: #ef4444;
}

.result-text {
  flex: 1;
}

.result-text strong {
  display: block;
  font-size: 18px;
  margin-bottom: 4px;
}

.quiz-result.correct .result-text strong {
  color: #166534;
}

.quiz-result:not(.correct) .result-text strong {
  color: #991b1b;
}

.result-text p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* Speaking */
.speaking-card .step-label {
  background: #fce7f3;
  color: #db2777;
}

.speaking-content {
  text-align: center;
  padding: 20px 0;
}

.speaking-prompt {
  font-size: 18px;
  color: #334155;
  margin-bottom: 40px;
}

.record-area {
  padding: 40px 0;
}

.btn-record {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #db2777, #ec4899);
  border: none;
  color: white;
  font-size: 36px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.btn-record:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 30px rgba(219, 39, 119, 0.4);
}

.btn-record.recording {
  animation: pulse-recording 1s infinite;
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

@keyframes pulse-recording {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
}

.record-hint {
  font-size: 14px;
  color: #64748b;
}

.recorded-preview {
  margin-top: 24px;
  padding: 20px;
  background: #f8fafc;
  border-radius: 12px;
}

.speaking-content {
  text-align: left;
}

.speaking-content .sample-answer {
  background: #fdf2f8;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 20px;
}

.sample-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #db2777;
  margin-bottom: 8px;
}

.speaking-content .sample-answer p {
  font-size: 15px;
  color: #831843;
  line-height: 1.6;
  margin: 0;
}

.keywords-hint {
  margin-bottom: 24px;
}

.keywords-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 10px;
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

.speaking-content .record-area {
  padding: 20px 0;
  text-align: center;
}

.btn-evaluate {
  width: 100%;
  margin-top: 16px;
  padding: 14px;
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

.btn-evaluate:hover:not(:disabled) {
  background: linear-gradient(135deg, #16a34a, #15803d);
}

.btn-evaluate:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.evaluation-result {
  margin-top: 20px;
  padding: 20px;
  border-radius: 12px;
  text-align: center;
}

.evaluation-result.good {
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  border: 2px solid #22c55e;
}

.evaluation-result.needs-work {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border: 2px solid #f59e0b;
}

.eval-score {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 4px;
  margin-bottom: 12px;
}

.eval-score .score-value {
  font-size: 48px;
  font-weight: 800;
}

.evaluation-result.good .score-value {
  color: #16a34a;
}

.evaluation-result.needs-work .score-value {
  color: #d97706;
}

.eval-score .score-max {
  font-size: 18px;
  color: #64748b;
}

.eval-message {
  font-size: 15px;
  color: #475569;
  margin: 0;
}

/* Navigation */
.lesson-navigation {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-top: 1px solid #e2e8f0;
  padding: 16px 24px;
  z-index: 100;
}

.lesson-navigation .container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 800px;
  margin: 0 auto;
}

.btn-nav {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 15px;
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
  background: #4f46e5;
  color: white;
}

.btn-next:hover {
  background: #4338ca;
}

.btn-complete {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
}

.btn-complete:hover {
  background: #15803d;
}

.step-dots {
  display: flex;
  gap: 8px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #e2e8f0;
  cursor: pointer;
  transition: all 0.2s;
}

.dot.active {
  background: #4f46e5;
  width: 24px;
  border-radius: 5px;
}

.dot.completed {
  background: #22c55e;
}

/* Completion Modal */
.completion-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.completion-modal {
  background: white;
  border-radius: 24px;
  padding: 48px;
  text-align: center;
  max-width: 400px;
  width: 100%;
  animation: modalIn 0.3s ease;
}

@keyframes modalIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.completion-icon {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.completion-icon i {
  font-size: 48px;
  color: #ca8a04;
}

.completion-modal h2 {
  font-size: 28px;
  color: #1e293b;
  margin-bottom: 8px;
}

.completion-modal p {
  color: #64748b;
  margin-bottom: 24px;
}

.completion-stats {
  display: flex;
  justify-content: center;
  gap: 32px;
  margin-bottom: 32px;
}

.stat {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 32px;
  font-weight: 800;
  color: #4f46e5;
}

.stat-label {
  font-size: 14px;
  color: #64748b;
}

.completion-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #4338ca;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-back:hover {
  background: #4338ca;
}

/* Responsive */
@media (max-width: 768px) {
  .lesson-title {
    font-size: 16px;
  }

  .step-card {
    padding: 24px 20px;
  }

  .word-text {
    font-size: 36px;
  }

  .word-meaning {
    font-size: 20px;
  }

  .grammar-title {
    font-size: 22px;
  }

  .quiz-question {
    font-size: 18px;
  }

  .btn-nav {
    padding: 10px 16px;
    font-size: 14px;
  }

  .step-dots {
    display: none;
  }

  .completion-modal {
    padding: 32px 24px;
  }

  .completion-actions {
    flex-direction: column;
  }

  .completion-actions button {
    width: 100%;
    justify-content: center;
  }
}
</style>
