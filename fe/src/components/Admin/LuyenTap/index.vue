<template>
  <div class="luyen-tap">
    <div class="header">
      <h1>Quản Lý Luyện Tập</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Câu Hỏi
      </button>
    </div>

    <!-- Filter -->
    <div class="filter-section">
      <div class="filter-row">
        <div class="form-group">
          <label>Khóa Học</label>
          <select v-model="selectedCourseId" @change="onCourseChange" class="filter-select">
            <option value="">-- Tất cả Khóa Học --</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">
              {{ course.title }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Loại Câu Hỏi</label>
          <select v-model="selectedType" class="filter-select">
            <option value="">-- Tất cả Loại --</option>
            <option value="multiple_choice">Trắc nghiệm</option>
            <option value="fill_blank">Điền từ</option>
            <option value="listening">Nghe</option>
            <option value="speaking">Nói</option>
          </select>
        </div>
      </div>
      <div class="filter-row">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm câu hỏi..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ allQuizzes.length }}</div>
        <div class="stat-label">Tổng Câu Hỏi</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ typeCount('multiple_choice') }}</div>
        <div class="stat-label">Trắc Nghiệm</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ typeCount('fill_blank') }}</div>
        <div class="stat-label">Điền Từ</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ typeCount('listening') }}</div>
        <div class="stat-label">Nghe</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ typeCount('speaking') }}</div>
        <div class="stat-label">Nói</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredQuizzes.length === 0" class="empty-state">
      <p>Không tìm thấy câu hỏi nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th style="width: 120px">Loại</th>
          <th>Câu Hỏi</th>
          <th style="width: 120px">Khóa Học</th>
          <th style="width: 100px">Thứ Tự</th>
          <th style="width: 120px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="quiz in filteredQuizzes" :key="quiz.id">
          <td>{{ quiz.id }}</td>
          <td>
            <span :class="['type-badge', 'type-' + quiz.type]">
              {{ typeLabel(quiz.type) }}
            </span>
          </td>
          <td>
            <div class="question-cell">
              <span class="question-text">{{ quiz.question }}</span>
              <span v-if="quiz.type === 'multiple_choice' && quiz.options" class="options-preview">
                {{ quiz.options.length }} đáp án
              </span>
              <span v-if="quiz.type === 'listening' && quiz.extra_data?.audio" class="has-audio">
                🎧 Có audio
              </span>
              <span v-if="quiz.type === 'speaking' && quiz.extra_data?.sentence" class="has-sentence">
                📝 {{ quiz.extra_data.sentence.substring(0, 40) }}{{ quiz.extra_data.sentence.length > 40 ? '...' : '' }}
              </span>
            </div>
          </td>
          <td>
            <span class="lesson-badge">{{ quiz.course?.title || '-' }}</span>
          </td>
          <td>{{ quiz.order }}</td>
          <td class="actions">
            <button class="btn-edit" @click="editQuiz(quiz)" title="Sửa">
              <span>✏️</span>
            </button>
            <button class="btn-delete" @click="deleteQuiz(quiz.id)" title="Xóa">
              <span>🗑️</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Thêm/Sửa Quiz -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>{{ editingQuiz ? 'Sửa Câu Hỏi' : 'Thêm Câu Hỏi Mới' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Khóa Học <span class="required">*</span></label>
              <select v-model="quizForm.course_id">
                <option value="">-- Chọn Khóa Học --</option>
                <option v-for="course in courses" :key="course.id" :value="course.id">
                  {{ course.title }}
                </option>
              </select>
              <span v-if="errors.course_id" class="error-text">{{ errors.course_id[0] }}</span>
            </div>
            <div class="form-group">
              <label>Loại Câu Hỏi <span class="required">*</span></label>
              <select v-model="quizForm.type" @change="onTypeChange">
                <option value="multiple_choice">Trắc nghiệm</option>
                <option value="fill_blank">Điền từ</option>
                <option value="listening">Nghe</option>
                <option value="speaking">Nói</option>
              </select>
              <span v-if="errors.type" class="error-text">{{ errors.type[0] }}</span>
            </div>
            <div class="form-group" style="max-width: 120px;">
              <label>Thứ Tự</label>
              <input v-model.number="quizForm.order" type="number" min="1" placeholder="1" />
            </div>
          </div>

          <div v-if="quizForm.type !== 'speaking'" class="form-group">
            <label>Câu Hỏi <span class="required">*</span></label>
            <textarea
              v-model="quizForm.question"
              rows="3"
              placeholder="Nhập câu hỏi..."
            ></textarea>
            <span v-if="errors.question" class="error-text">{{ errors.question[0] }}</span>
          </div>

          <!-- Trắc nghiệm -->
          <div v-if="quizForm.type === 'multiple_choice'" class="type-form-section">
            <div class="section-title">Đáp án trắc nghiệm</div>
            <div class="options-grid">
              <div v-for="(opt, idx) in quizForm.options" :key="idx" class="option-item">
                <span class="option-label">{{ optionLabels[idx] }}</span>
                <input
                  v-model="quizForm.options[idx]"
                  type="text"
                  :placeholder="'Đáp án ' + optionLabels[idx]"
                />
              </div>
            </div>
            <div class="form-group">
              <label>Đáp án đúng <span class="required">*</span></label>
              <select v-model="quizForm.correct_answer">
                <option value="">-- Chọn đáp án đúng --</option>
                <option v-for="(opt, idx) in quizForm.options" :key="idx" :value="opt">
                  {{ optionLabels[idx] }}: {{ opt || '(trống)' }}
                </option>
              </select>
              <span v-if="errors.correct_answer" class="error-text">{{ errors.correct_answer[0] }}</span>
            </div>
          </div>

          <!-- Điền từ -->
          <div v-if="quizForm.type === 'fill_blank'" class="type-form-section">
            <div class="section-title">Câu hỏi điền từ</div>
            <div class="fill-blank-tip">
              Sử dụng <code>___</code> để đánh dấu chỗ trống trong câu hỏi phía trên.
            </div>
            <div class="form-group">
              <label>Đáp án đúng <span class="required">*</span></label>
              <input
                v-model="quizForm.correct_answer"
                type="text"
                placeholder="Nhập từ cần điền (không dấu ___)"
              />
              <span v-if="errors.correct_answer" class="error-text">{{ errors.correct_answer[0] }}</span>
            </div>
          </div>

          <!-- Nghe -->
          <div v-if="quizForm.type === 'listening'" class="type-form-section">
            <div class="section-title">Bài nghe</div>
            <div class="form-group">
              <label>File Audio</label>
              <div class="audio-upload-area">
                <input
                  type="file"
                  accept="audio/*"
                  @change="handleAudioChange"
                  id="audio-input"
                />
                <label for="audio-input" class="file-label">
                  {{ audioFileName || '📁 Chọn file audio...' }}
                </label>
                <span v-if="audioFileName" class="audio-clear" @click="clearAudio">✕ Xóa</span>
              </div>
              <div v-if="quizForm.extra_data?.audio && !audioFile" class="current-audio">
                <label>File hiện tại:</label>
                <audio :src="getFullAudioUrl(quizForm.extra_data.audio)" controls class="audio-player"></audio>
              </div>
              <span v-if="errors.extra_data" class="error-text">{{ errors.extra_data[0] }}</span>
            </div>
            <div class="form-group">
              <label>Đáp án đúng <span class="required">*</span></label>
              <input
                v-model="quizForm.correct_answer"
                type="text"
                placeholder="Nhập đáp án (từ/ cụm từ nghe được)"
              />
              <span v-if="errors.correct_answer" class="error-text">{{ errors.correct_answer[0] }}</span>
            </div>
          </div>

          <!-- Nói -->
          <div v-if="quizForm.type === 'speaking'" class="type-form-section">
            <div class="section-title">Bài nói</div>
            <div class="form-group">
              <label>Câu cần nói <span class="required">*</span></label>
              <textarea
                v-model="quizForm.extra_data.sentence"
                rows="2"
                placeholder="Nhập câu tiếng Anh cần luyện phát âm"
              ></textarea>
              <span v-if="errors.extra_data" class="error-text">{{ errors.extra_data[0] }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Hủy</button>
          <button class="btn-primary" @click="saveQuiz">
            {{ editingQuiz ? 'Cập Nhật' : 'Thêm Mới' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="modal modal-confirm">
        <div class="modal-header">
          <h2>Xác Nhận Xóa</h2>
          <button class="btn-close" @click="showDeleteConfirm = false">&times;</button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa câu hỏi này?</p>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showDeleteConfirm = false">Hủy</button>
          <button class="btn-danger" @click="confirmDelete">Xóa</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { CourseService, LessonQuizService } from '../../../services/api.js'

const loading = ref(false)
const courses = ref([])
const allQuizzes = ref([])

const selectedCourseId = ref('')
const selectedType = ref('')
const searchQuery = ref('')

const showModal = ref(false)
const editingQuiz = ref(null)
const showDeleteConfirm = ref(false)
const deleteQuizId = ref(null)
const errors = ref({})

const audioFile = ref(null)
const audioFileName = ref('')

const optionLabels = ['A', 'B', 'C', 'D']

const quizForm = ref({
  course_id: '',
  type: 'multiple_choice',
  question: '',
  options: ['', '', '', ''],
  correct_answer: '',
  extra_data: {},
  order: 1
})

// Computed
const filteredQuizzes = computed(() => {
  let result = allQuizzes.value

  if (selectedCourseId.value) {
    result = result.filter(q => q.course_id === parseInt(selectedCourseId.value))
  }

  if (selectedType.value) {
    result = result.filter(q => q.type === selectedType.value)
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(v =>
      v.question.toLowerCase().includes(q) ||
      (v.correct_answer && v.correct_answer.toLowerCase().includes(q))
    )
  }

  return result
})

const typeCount = (type) => {
  return allQuizzes.value.filter(q => q.type === type).length
}

const typeLabel = (type) => {
  const labels = {
    multiple_choice: 'Trắc nghiệm',
    fill_blank: 'Điền từ',
    listening: 'Nghe',
    speaking: 'Nói'
  }
  return labels[type] || type
}

const getFullAudioUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return 'http://localhost:8000' + path
}

// Methods
const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courses.value = data
    await fetchAllQuizzes()
  } catch (error) {
    console.error('Lỗi khi tải khóa học:', error)
  }
}

const fetchAllQuizzes = async () => {
  loading.value = true
  try {
    const allQuiz = []
    for (const course of courses.value) {
      try {
        const data = await LessonQuizService.getByCourse(course.id)
        allQuiz.push(...data)
      } catch (e) {
        // Course có thể không có quiz
      }
    }
    allQuizzes.value = allQuiz
  } catch (error) {
    console.error('Lỗi khi tải quiz:', error)
  } finally {
    loading.value = false
  }
}

const fetchQuizzesByCourse = async (courseId) => {
  loading.value = true
  try {
    const data = await LessonQuizService.getByCourse(courseId)
    allQuizzes.value = data
  } catch (error) {
    console.error('Lỗi khi tải quiz:', error)
  } finally {
    loading.value = false
  }
}

const onCourseChange = () => {
  if (selectedCourseId.value) {
    fetchQuizzesByCourse(selectedCourseId.value)
  } else {
    fetchAllQuizzes()
  }
}

const onTypeChange = () => {
  if (quizForm.value.type !== 'multiple_choice') {
    quizForm.value.options = ['', '', '', '']
  }
  if (quizForm.value.type !== 'listening') {
    audioFile.value = null
    audioFileName.value = ''
  }
  if (quizForm.value.type !== 'speaking' && quizForm.value.type !== 'listening') {
    quizForm.value.extra_data = {}
  }
}

const handleAudioChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    audioFile.value = file
    audioFileName.value = file.name
    quizForm.value.extra_data = {
      ...quizForm.value.extra_data,
      audio: file
    }
  }
}

const clearAudio = () => {
  audioFile.value = null
  audioFileName.value = ''
  if (quizForm.value.extra_data) {
    delete quizForm.value.extra_data.audio
  }
  document.getElementById('audio-input').value = ''
}

const openAddModal = () => {
  editingQuiz.value = null
  errors.value = {}
  audioFile.value = null
  audioFileName.value = ''
  quizForm.value = {
    course_id: selectedCourseId.value || '',
    type: 'multiple_choice',
    question: '',
    options: ['', '', '', ''],
    correct_answer: '',
    extra_data: {},
    order: 1
  }
  showModal.value = true
}

const editQuiz = (quiz) => {
  editingQuiz.value = quiz.id
  errors.value = {}
  audioFile.value = null
  audioFileName.value = ''

  const options = quiz.options || ['', '', '', '']
  while (options.length < 4) options.push('')

  quizForm.value = {
    course_id: quiz.course_id,
    type: quiz.type,
    question: quiz.question,
    options: options.slice(0, 4),
    correct_answer: quiz.correct_answer || '',
    extra_data: quiz.extra_data || {},
    order: quiz.order || 1
  }

  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingQuiz.value = null
  errors.value = {}
  audioFile.value = null
  audioFileName.value = ''
}

const saveQuiz = async () => {
  errors.value = {}
  try {
    if (!quizForm.value.course_id) {
      errors.value.course_id = ['Vui lòng chọn khóa học']
      return
    }

    if (quizForm.value.type !== 'speaking' && !quizForm.value.question?.trim()) {
      errors.value.question = ['Vui lòng nhập câu hỏi']
      return
    }

    const hasAudio = quizForm.value.type === 'listening' && audioFile.value
    const payload = hasAudio ? new FormData() : {}

    if (hasAudio) {
      payload.append('course_id', quizForm.value.course_id)
      payload.append('type', quizForm.value.type)
      payload.append('question', quizForm.value.question || '')
      payload.append('order', quizForm.value.order || 1)
      payload.append('correct_answer', quizForm.value.correct_answer || '')
      if (quizForm.value.extra_data) {
        payload.append('extra_data', JSON.stringify(quizForm.value.extra_data))
      }
      payload.append('audio', audioFile.value)
    } else {
      payload.course_id = quizForm.value.course_id
      payload.type = quizForm.value.type
      payload.question = quizForm.value.type === 'speaking' ? '' : quizForm.value.question
      payload.order = quizForm.value.order || 1
      payload.correct_answer = quizForm.value.correct_answer || null
      payload.extra_data = quizForm.value.extra_data || null
    }

    if (quizForm.value.type === 'multiple_choice') {
      const filteredOptions = quizForm.value.options.filter(o => o.trim() !== '')
      if (filteredOptions.length < 2) {
        errors.value.options = ['Cần ít nhất 2 đáp án']
        return
      }
      if (hasAudio) {
        payload.append('options', JSON.stringify(filteredOptions))
      } else {
        payload.options = filteredOptions
      }
      if (!quizForm.value.correct_answer) {
        errors.value.correct_answer = ['Vui lòng chọn đáp án đúng']
        return
      }
    }

    if (quizForm.value.type === 'speaking') {
      if (!quizForm.value.extra_data?.sentence?.trim()) {
        errors.value.extra_data = ['Vui lòng nhập câu cần nói']
        return
      }
    }

    let result
    if (editingQuiz.value) {
      result = hasAudio
        ? await LessonQuizService.updateFormData(editingQuiz.value, payload)
        : await LessonQuizService.update(editingQuiz.value, payload)
      const index = allQuizzes.value.findIndex(q => q.id === editingQuiz.value)
      if (index !== -1) {
        allQuizzes.value[index] = { ...result.data, course_id: quizForm.value.course_id }
      }
      alert('Cập nhật câu hỏi thành công!')
    } else {
      result = hasAudio
        ? await LessonQuizService.createFormData(quizForm.value.course_id, payload)
        : await LessonQuizService.create(quizForm.value.course_id, payload)
      allQuizzes.value.push({ ...result.data })
      alert('Thêm câu hỏi thành công!')
    }
    closeModal()
  } catch (error) {
    if (error.response && error.response.data && error.response.data.errors) {
      errors.value = error.response.data.errors
    } else {
      alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
    }
  }
}

const deleteQuiz = (id) => {
  deleteQuizId.value = id
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    await LessonQuizService.delete(deleteQuizId.value)
    allQuizzes.value = allQuizzes.value.filter(q => q.id !== deleteQuizId.value)
    showDeleteConfirm.value = false
    deleteQuizId.value = null
    alert('Xóa câu hỏi thành công!')
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
.luyen-tap {
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

.filter-section {
  background: white;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
  margin-bottom: 12px;
}

.filter-row:last-child {
  margin-bottom: 0;
}

.search-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
}

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

.filter-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #4f46e5;
}

.stat-label {
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

.loading, .empty-state {
  text-align: center;
  padding: 40px;
  color: #666;
  background: white;
  border-radius: 8px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table th,
.data-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #333;
}

.data-table tr:hover {
  background: #f8f9fa;
}

.type-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.type-multiple_choice {
  background: #dbeafe;
  color: #1d4ed8;
}

.type-fill_blank {
  background: #fef3c7;
  color: #92400e;
}

.type-listening {
  background: #d1fae5;
  color: #065f46;
}

.type-speaking {
  background: #fce7f3;
  color: #9d174d;
}

.question-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.question-text {
  font-weight: 500;
  color: #333;
}

.options-preview {
  font-size: 12px;
  color: #6b7280;
}

.has-audio, .has-sentence {
  font-size: 12px;
  color: #6b7280;
}

.lesson-badge {
  padding: 4px 8px;
  background: #e0e7ff;
  color: #4338ca;
  border-radius: 4px;
  font-size: 12px;
}

.actions {
  display: flex;
  gap: 6px;
}

.btn-edit, .btn-delete {
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-edit {
  background: #10b981;
  color: white;
}

.btn-edit:hover {
  background: #059669;
}

.btn-delete {
  background: #ef4444;
  color: white;
}

.btn-delete:hover {
  background: #dc2626;
}

.btn-primary {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-primary:hover {
  background: #4338ca;
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

.btn-danger {
  background: #dc2626;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
}

.btn-danger:hover {
  background: #b91c1c;
}

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
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 550px;
  max-height: 90vh;
  overflow: auto;
}

.modal-lg {
  max-width: 700px;
}

.modal-confirm {
  max-width: 400px;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr 120px;
  gap: 16px;
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
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4f46e5;
}

.required {
  color: #ef4444;
}

.error-text {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
  display: block;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}

.type-form-section {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
}

.section-title {
  font-weight: 600;
  color: #374151;
  margin-bottom: 12px;
  font-size: 14px;
}

.options-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}

.option-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.option-label {
  font-weight: 700;
  color: #4f46e5;
  min-width: 24px;
}

.option-item input {
  flex: 1;
}

.fill-blank-tip {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: 6px;
  padding: 10px 12px;
  font-size: 13px;
  color: #92400e;
  margin-bottom: 16px;
}

.fill-blank-tip code {
  background: #fde68a;
  padding: 2px 6px;
  border-radius: 3px;
  font-family: monospace;
}

.audio-upload-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

.audio-upload-area input[type="file"] {
  display: none;
}

.file-label {
  flex: 1;
  padding: 10px 12px;
  border: 2px dashed #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  color: #6b7280;
  background: #f9fafb;
  transition: all 0.2s;
}

.file-label:hover {
  border-color: #4f46e5;
  background: #eef2ff;
}

.audio-clear {
  color: #ef4444;
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
}

.audio-clear:hover {
  text-decoration: underline;
}

.current-audio {
  margin-top: 12px;
}

.current-audio label {
  display: block;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 6px;
}

.audio-player {
  width: 100%;
  max-width: 400px;
  height: 40px;
}
</style>
