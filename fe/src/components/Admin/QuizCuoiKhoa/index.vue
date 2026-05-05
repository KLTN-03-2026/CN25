<template>
  <div class="quiz-cuoi-khoa">
    <div class="header">
      <h1>Quản Lý Bài Thi Cuối Khóa</h1>
      <button class="btn-primary" @click="openCourseModal">
        <span>+</span> Chọn Khóa Học
      </button>
    </div>

    <div v-if="selectedCourse" class="quiz-info-card">
      <div class="course-badge">
        <span class="badge-label">Khóa học:</span>
        <span class="badge-value">{{ selectedCourse.title }}</span>
      </div>
      <div class="quiz-actions-top">
        <button v-if="!quiz" class="btn-create-quiz" @click="openQuizModal">
          + Tạo Bài Thi Cuối Khóa
        </button>
        <div v-else class="quiz-meta">
          <span class="meta-item">⏱️ {{ quiz.duration || 0 }} phút</span>
          <span class="meta-item">📊 Điểm đạt: {{ quiz.pass_score }}%</span>
          <span class="meta-item">📝 {{ (quiz.questions || []).length }} câu hỏi</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="!selectedCourse" class="empty-state">
      <p>Vui lòng chọn khóa học để quản lý bài thi cuối khóa.</p>
    </div>

    <div v-else class="quiz-content">

      <!-- Quiz not found -->
      <div v-if="!quiz && !quizLoading" class="empty-state">
        <p>Chưa có bài thi cuối khóa cho khóa học này.</p>
        <button class="btn-create-quiz" @click="openQuizModal">
          + Tạo Bài Thi Cuối Khóa
        </button>
      </div>

      <!-- Quiz exists -->
      <div v-else-if="quiz" class="quiz-panel">

        <!-- Quiz Settings -->
        <div class="quiz-settings-card">
          <div class="card-header">
            <h3>Cài Đặt Bài Thi</h3>
            <button class="btn-edit-sm" @click="openEditQuizModal">✏️ Sửa</button>
          </div>
          <div class="card-body">
            <div class="settings-row">
              <div class="setting-item">
                <label>Tiêu đề:</label>
                <span>{{ quiz.title }}</span>
              </div>
              <div class="setting-item">
                <label>Thời gian:</label>
                <span>{{ quiz.duration || 'Không giới hạn' }} phút</span>
              </div>
              <div class="setting-item">
                <label>Điểm đạt:</label>
                <span>{{ quiz.pass_score }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Question Form -->
        <div class="add-question-card">
          <div class="card-header">
            <h3>Thêm Câu Hỏi Mới</h3>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label>Loại Câu Hỏi</label>
              <select v-model="formQuestion.type">
                <option value="multiple_choice">Trắc nghiệm</option>
                <option value="fill_blank">Điền từ</option>
                <option value="listening">Nghe</option>
                <option value="speaking">Nói</option>
              </select>
            </div>

            <div class="form-group">
              <label>Câu Hỏi</label>
              <textarea
                v-model="formQuestion.question"
                rows="3"
                placeholder="Nhập nội dung câu hỏi..."
              ></textarea>
            </div>

            <!-- Trắc nghiệm options -->
            <div v-if="formQuestion.type === 'multiple_choice'" class="options-group">
              <label>Các Lựa Chọn (A, B, C, D)</label>
              <div class="option-row" v-for="(opt, idx) in formQuestion.options" :key="idx">
                <span class="option-label">{{ optionLabels[idx] }}</span>
                <input
                  v-model="formQuestion.options[idx]"
                  type="text"
                  :placeholder="`Nhập đáp án ${optionLabels[idx]}`"
                />
              </div>
              <div class="form-group">
                <label>Đáp Án Đúng</label>
                <select v-model="formQuestion.correct_answer">
                  <option value="">-- Chọn đáp án đúng --</option>
                  <option v-for="(opt, idx) in formQuestion.options" :key="idx" :value="opt">
                    {{ optionLabels[idx] }}: {{ opt || '(trống)' }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Điền từ -->
            <div v-if="formQuestion.type === 'fill_blank'" class="fill-blank-group">
              <div class="form-group">
                <label>Đáp Án Đúng</label>
                <input
                  v-model="formQuestion.correct_answer"
                  type="text"
                  placeholder="Nhập đáp án đúng"
                />
              </div>
            </div>

            <!-- Nghe -->
            <div v-if="formQuestion.type === 'listening'" class="listening-group">
              <div class="form-group">
                <label>Upload Audio File</label>
                <div class="file-upload-wrapper">
                  <input
                    type="file"
                    @change="handleAudioFileUpload"
                    accept="audio/*"
                    class="file-input"
                    ref="audioFileRef"
                  />
                  <div v-if="formQuestion.audioFile" class="file-name">
                    📁 {{ formQuestion.audioFile.name }}
                  </div>
                  <div v-else-if="formQuestion.audio_url && !formQuestion.audioFile" class="existing-file">
                    📁 File hiện tại: {{ getFileName(formQuestion.audio_url) }}
                  </div>
                  <small v-else class="help-text">Chấp nhận: mp3, wav, m4a, ogg, webm (tối đa 50MB)</small>
                </div>
              </div>
              <div class="form-group">
                <label>Hoặc nhập Link Audio</label>
                <input
                  v-model="formQuestion.audio_url"
                  type="text"
                  placeholder="https://example.com/audio.mp3"
                />
              </div>
              <div class="form-group">
                <label>Đáp Án Đúng</label>
                <input
                  v-model="formQuestion.correct_answer"
                  type="text"
                  placeholder="Nhập đáp án đúng"
                />
              </div>
            </div>

            <!-- Nói -->
            <div v-if="formQuestion.type === 'speaking'" class="speaking-group">
              <div class="form-group">
                <label>Câu Mẫu (Sample Answer)</label>
                <textarea
                  v-model="formQuestion.sample_answer"
                  rows="3"
                  placeholder="Nhập câu trả lời mẫu..."
                ></textarea>
              </div>
              <div class="form-group">
                <label>Đáp Án Đúng (để trống cho bài nói)</label>
                <input
                  v-model="formQuestion.correct_answer"
                  type="text"
                  placeholder="Để trống nếu là bài nói chấm điểm"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Thứ Tự</label>
              <input v-model.number="formQuestion.order" type="number" min="1" />
            </div>

            <button class="btn-add-question" @click="addQuestion" :disabled="savingQuestion">
              {{ savingQuestion ? 'Đang thêm...' : '+ Thêm Câu Hỏi' }}
            </button>
          </div>
        </div>

        <!-- Questions List -->
        <div v-if="(quiz.questions || []).length > 0" class="questions-list-card">
          <div class="card-header">
            <h3>Danh Sách Câu Hỏi ({{ (quiz.questions || []).length }})</h3>
          </div>
          <div class="card-body">
            <div
              v-for="(q, index) in quiz.questions"
              :key="q.id"
              class="question-item"
            >
              <div class="question-header">
                <span class="question-number">Câu {{ index + 1 }}</span>
                <span :class="['question-type-badge', q.type]">
                  {{ getQuestionTypeLabel(q.type) }}
                </span>
                <div class="question-actions">
                  <button class="btn-delete-sm" @click="deleteQuestion(q.id)">🗑️ Xóa</button>
                </div>
              </div>
              <div class="question-content">
                <p class="question-text">{{ q.question }}</p>
                <div v-if="q.type === 'multiple_choice' && q.options" class="question-options">
                  <div
                    v-for="(opt, idx) in q.options"
                    :key="idx"
                    class="option-display"
                    :class="{ correct: opt === q.correct_answer }"
                  >
                    <span class="option-letter">{{ optionLabels[idx] }}.</span>
                    <span>{{ opt }}</span>
                    <span v-if="opt === q.correct_answer" class="correct-mark">✓</span>
                  </div>
                </div>
                <div v-if="q.type === 'fill_blank'" class="fill-answer-display">
                  <strong>Đáp án:</strong> {{ q.correct_answer }}
                </div>
                <div v-if="q.type === 'listening'" class="listening-display">
                  <strong>🎧 Audio:</strong>
                  <audio v-if="q.audio_url" :src="q.audio_url" controls class="audio-player"></audio>
                  <span v-else class="no-audio">Chưa có audio</span>
                  <div v-if="q.correct_answer" class="answer-info">
                    <strong>Đáp án:</strong> {{ q.correct_answer }}
                  </div>
                </div>
                <div v-if="q.type === 'speaking'" class="speaking-display">
                  <strong>🎤 Câu mẫu:</strong>
                  <p class="sample-answer-text">{{ q.sample_answer || 'Chưa có câu mẫu' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="empty-questions">
          <p>Chưa có câu hỏi nào. Hãy thêm câu hỏi bên trên!</p>
        </div>

      </div>
    </div>

    <!-- Modal: Chọn khóa học -->
    <div v-if="showCourseModal" class="modal-overlay" @click.self="showCourseModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Chọn Khóa Học</h2>
          <button class="btn-close" @click="showCourseModal = false">×</button>
        </div>
        <div class="modal-body">
          <div v-if="coursesLoading" class="loading">Đang tải danh sách khóa học...</div>
          <div v-else class="course-list-modal">
            <div
              v-for="course in courseList"
              :key="course.id"
              class="course-item"
              :class="{ active: selectedCourse?.id === course.id }"
              @click="selectCourse(course)"
            >
              <span class="course-title">{{ course.title }}</span>
              <span :class="['course-status', course.status]">
                {{ course.status === 'published' ? 'Đã xuất bản' : 'Nháp' }}
              </span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showCourseModal = false">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Modal: Tạo / Sửa Quiz -->
    <div v-if="showQuizModal" class="modal-overlay" @click.self="showQuizModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingQuiz ? 'Sửa Bài Thi' : 'Tạo Bài Thi Cuối Khóa' }}</h2>
          <button class="btn-close" @click="showQuizModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề Bài Thi</label>
            <input v-model="quizForm.title" type="text" placeholder="VD: Final Test" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Thời Gian (phút)</label>
              <input v-model.number="quizForm.duration" type="number" min="1" placeholder="15" />
            </div>
            <div class="form-group">
              <label>Điểm Đạt (%)</label>
              <input v-model.number="quizForm.pass_score" type="number" min="0" max="100" placeholder="50" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showQuizModal = false">Huỷ</button>
          <button class="btn-primary" @click="saveQuiz" :disabled="savingQuiz">
            {{ savingQuiz ? 'Đang lưu...' : (editingQuiz ? 'Cập nhật' : 'Tạo mới') }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { CourseService, CourseQuizService, CourseQuizQuestionService } from '../../../services/api.js'

const optionLabels = ['A', 'B', 'C', 'D']
const audioFileRef = ref(null)
const AUDIO_BASE_URL = 'http://localhost:8000'

// State
const selectedCourse = ref(null)
const quiz = ref(null)
const quizLoading = ref(false)
const loading = ref(false)
const showCourseModal = ref(false)
const showQuizModal = ref(false)
const editingQuiz = ref(false)
const savingQuiz = ref(false)
const savingQuestion = ref(false)
const coursesLoading = ref(false)

const courseList = ref([])

const quizForm = ref({
  title: 'Bài thi cuối khóa',
  duration: 15,
  pass_score: 50
})

const formQuestion = ref({
  type: 'multiple_choice',
  question: '',
  options: ['', '', '', ''],
  correct_answer: '',
  audio_url: '',
  audioFile: null,
  sample_answer: '',
  order: 1
})

// Load courses
const getQuestionTypeLabel = (type) => {
  const labels = {
    'multiple_choice': 'Trắc nghiệm',
    'fill_blank': 'Điền từ',
    'listening': 'Nghe',
    'speaking': 'Nói'
  }
  return labels[type] || type
}

const getFileName = (path) => {
  if (!path) return ''
  return path.split('/').pop() || path.split('\\').pop() || path
}

const handleAudioFileUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    formQuestion.value.audioFile = file
  }
}

const loadCourses = async () => {
  coursesLoading.value = true
  try {
    courseList.value = await CourseService.getAll()
  } catch (error) {
    console.error('Lỗi khi tải danh sách khóa học:', error)
  } finally {
    coursesLoading.value = false
  }
}

// Load quiz for selected course
const loadQuiz = async (courseId) => {
  if (!courseId) return
  quizLoading.value = true
  quiz.value = null
  try {
    quiz.value = await CourseQuizService.getByCourse(courseId)
  } catch (error) {
    if (error.response?.status === 404) {
      quiz.value = null
    } else {
      console.error('Lỗi khi tải quiz:', error)
    }
  } finally {
    quizLoading.value = false
  }
}

// Select course
const selectCourse = (course) => {
  selectedCourse.value = course
  showCourseModal.value = false
  loadQuiz(course.id)
}

// Open modals
const openCourseModal = () => {
  loadCourses()
  showCourseModal.value = true
}

const openQuizModal = () => {
  editingQuiz.value = false
  quizForm.value = {
    title: 'Bài thi cuối khóa',
    duration: 15,
    pass_score: 50
  }
  showQuizModal.value = true
}

const openEditQuizModal = () => {
  if (!quiz.value) return
  editingQuiz.value = true
  quizForm.value = {
    title: quiz.value.title,
    duration: quiz.value.duration,
    pass_score: quiz.value.pass_score
  }
  showQuizModal.value = true
}

// Save quiz
const saveQuiz = async () => {
  if (!quizForm.value.title.trim()) {
    alert('Vui lòng nhập tiêu đề bài thi')
    return
  }

  savingQuiz.value = true
  try {
    if (editingQuiz.value) {
      await CourseQuizService.update(quiz.value.id, quizForm.value)
    } else {
      await CourseQuizService.create(selectedCourse.value.id, quizForm.value)
    }
    showQuizModal.value = false
    await loadQuiz(selectedCourse.value.id)
  } catch (error) {
    console.error('Lỗi khi lưu quiz:', error)
    alert('Đã xảy ra lỗi khi lưu bài thi.')
  } finally {
    savingQuiz.value = false
  }
}

// Reset question form
const resetQuestionForm = () => {
  formQuestion.value = {
    type: 'multiple_choice',
    question: '',
    options: ['', '', '', ''],
    correct_answer: '',
    audio_url: '',
    audioFile: null,
    sample_answer: '',
    order: (quiz.value?.questions?.length || 0) + 1
  }
  if (audioFileRef.value) {
    audioFileRef.value.value = ''
  }
}

// Add question
const addQuestion = async () => {
  if (!formQuestion.value.question.trim()) {
    alert('Vui lòng nhập nội dung câu hỏi')
    return
  }
  if (formQuestion.value.type === 'multiple_choice' && !formQuestion.value.correct_answer) {
    alert('Vui lòng chọn đáp án đúng cho câu hỏi trắc nghiệm')
    return
  }
  if (formQuestion.value.type === 'fill_blank' && !formQuestion.value.correct_answer.trim()) {
    alert('Vui lòng nhập đáp án cho câu hỏi điền từ')
    return
  }

  savingQuestion.value = true
  try {
    const isListeningWithFile = formQuestion.value.type === 'listening' && formQuestion.value.audioFile

    let payload
    if (isListeningWithFile) {
      const fd = new FormData()
      fd.append('type', formQuestion.value.type)
      fd.append('question', formQuestion.value.question.trim())
      fd.append('correct_answer', formQuestion.value.correct_answer || '')
      fd.append('order', formQuestion.value.order)
      if (formQuestion.value.audio_url) {
        fd.append('audio_url', formQuestion.value.audio_url)
      }
      fd.append('audio', formQuestion.value.audioFile)
      if (formQuestion.value.sample_answer) {
        fd.append('sample_answer', formQuestion.value.sample_answer)
      }
      payload = fd
    } else {
      payload = {
        type: formQuestion.value.type,
        question: formQuestion.value.question.trim(),
        options: formQuestion.value.type === 'multiple_choice'
          ? [...formQuestion.value.options]
          : null,
        correct_answer: formQuestion.value.correct_answer,
        audio_url: formQuestion.value.type === 'listening'
          ? formQuestion.value.audio_url
          : null,
        sample_answer: formQuestion.value.type === 'speaking'
          ? formQuestion.value.sample_answer
          : null,
        order: formQuestion.value.order
      }
    }

    await CourseQuizQuestionService.create(quiz.value.id, payload, isListeningWithFile)
    resetQuestionForm()
    await loadQuiz(selectedCourse.value.id)
  } catch (error) {
    console.error('Lỗi khi thêm câu hỏi:', error)
    alert('Đã xảy ra lỗi khi thêm câu hỏi.')
  } finally {
    savingQuestion.value = false
  }
}

// Delete question
const deleteQuestion = async (id) => {
  if (!confirm('Bạn có chắc muốn xóa câu hỏi này?')) return
  try {
    await CourseQuizQuestionService.delete(id)
    await loadQuiz(selectedCourse.value.id)
  } catch (error) {
    console.error('Lỗi khi xóa câu hỏi:', error)
    alert('Đã xảy ra lỗi khi xóa câu hỏi.')
  }
}

// Watch selected course change
watch(selectedCourse, (newVal) => {
  if (newVal) {
    sessionStorage.setItem('quizSelectedCourseId', newVal.id)
  } else {
    sessionStorage.removeItem('quizSelectedCourseId')
  }
})

onMounted(() => {
  const savedCourseId = sessionStorage.getItem('quizSelectedCourseId')
  if (savedCourseId) {
    loadCourses().then(() => {
      const found = courseList.value.find(c => c.id == savedCourseId)
      if (found) {
        selectedCourse.value = found
        loadQuiz(found.id)
      }
    })
  }
})
</script>

<style scoped>
.quiz-cuoi-khoa {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.quiz-info-card {
  background: white;
  border-radius: 10px;
  padding: 16px 20px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.course-badge {
  display: flex;
  align-items: center;
  gap: 8px;
}

.badge-label {
  font-weight: 600;
  color: #666;
}

.badge-value {
  font-weight: 700;
  color: #4f46e5;
}

.quiz-meta {
  display: flex;
  gap: 16px;
}

.meta-item {
  font-size: 14px;
  color: #555;
  background: #f1f5f9;
  padding: 6px 12px;
  border-radius: 20px;
}

.quiz-actions-top {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-create-quiz {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.btn-create-quiz:hover {
  background: #4338ca;
}

.loading, .empty-state {
  text-align: center;
  padding: 40px;
  color: #666;
}

.quiz-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Quiz panel */
.quiz-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.quiz-settings-card,
.add-question-card,
.questions-list-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.card-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.card-body {
  padding: 20px;
}

.settings-row {
  display: flex;
  gap: 32px;
  flex-wrap: wrap;
}

.setting-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.setting-item label {
  font-size: 12px;
  color: #888;
  font-weight: 500;
}

.setting-item span {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

/* Form */
.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
  font-family: inherit;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4f46e5;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* Options */
.options-group {
  margin-bottom: 16px;
}

.option-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.option-label {
  width: 28px;
  height: 28px;
  background: #e5e7eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
  color: #4f46e5;
  flex-shrink: 0;
}

.option-row input {
  flex: 1;
}

.fill-blank-group {
  margin-bottom: 16px;
}

.listening-group,
.speaking-group {
  margin-bottom: 16px;
}

.audio-player {
  width: 100%;
  margin-top: 8px;
  height: 40px;
}

.no-audio {
  color: #ef4444;
  font-size: 13px;
  font-style: italic;
}

.listening-display {
  padding: 10px 12px;
  background: #fdf4ff;
  border-radius: 6px;
  border: 1px solid #e9d5ff;
  font-size: 14px;
  margin-top: 8px;
}

.listening-display .answer-info {
  margin-top: 6px;
  color: #065f46;
}

.speaking-display {
  padding: 10px 12px;
  background: #fff7ed;
  border-radius: 6px;
  border: 1px solid #fed7aa;
  font-size: 14px;
  margin-top: 8px;
}

.sample-answer-text {
  margin: 6px 0 0 0;
  font-style: italic;
  color: #92400e;
}

.btn-add-question {
  background: #059669;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 15px;
  font-weight: 500;
  width: 100%;
}

.btn-add-question:hover {
  background: #047857;
}

.btn-add-question:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

/* Questions list */
.question-item {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  background: #fafafa;
}

.question-item:last-child {
  margin-bottom: 0;
}

.question-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.question-number {
  font-weight: 700;
  color: #4f46e5;
  font-size: 14px;
}

.question-type-badge {
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.question-type-badge.multiple_choice {
  background: #dbeafe;
  color: #1e40af;
}

.question-type-badge.fill_blank {
  background: #fef3c7;
  color: #92400e;
}

.question-type-badge.listening {
  background: #fdf4ff;
  color: #7e22ce;
}

.question-type-badge.speaking {
  background: #fff7ed;
  color: #c2410c;
}

.question-actions {
  margin-left: auto;
}

.question-content {
  padding-left: 4px;
}

.question-text {
  font-size: 15px;
  color: #222;
  margin: 0 0 12px 0;
  font-weight: 500;
}

.question-options {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.option-display {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  font-size: 14px;
}

.option-display.correct {
  background: #ecfdf5;
  border-color: #10b981;
}

.option-letter {
  font-weight: 700;
  color: #4f46e5;
  min-width: 20px;
}

.correct-mark {
  margin-left: auto;
  color: #10b981;
  font-weight: 700;
}

.fill-answer-display {
  padding: 8px 12px;
  background: #ecfdf5;
  border-radius: 6px;
  border: 1px solid #10b981;
  font-size: 14px;
  color: #065f46;
}

.empty-questions {
  text-align: center;
  padding: 30px;
  color: #888;
  background: white;
  border-radius: 10px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}

.btn-primary {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary:hover {
  background: #4338ca;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e5e7eb;
  color: #333;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.btn-secondary:hover {
  background: #d1d5db;
}

.btn-edit-sm {
  background: #10b981;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.btn-delete-sm {
  background: #ef4444;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

/* Course list modal */
.course-list-modal {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.course-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
}

.course-item:hover {
  background: #f1f5f9;
  border-color: #4f46e5;
}

.course-item.active {
  background: #eef2ff;
  border-color: #4f46e5;
}

.course-title {
  font-weight: 500;
  color: #333;
}

.course-status {
  font-size: 12px;
  padding: 3px 10px;
  border-radius: 12px;
  font-weight: 500;
}

.course-status.published {
  background: #d1fae5;
  color: #065f46;
}

.course-status.draft {
  background: #fef3c7;
  color: #92400e;
}
</style>
