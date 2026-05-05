<template>
  <div class="ngu-phap">
    <div class="header">
      <h1>Quản Lý Ngữ Pháp</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Ngữ Pháp
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
          <label>Chương</label>
          <select v-model="selectedChapterId" @change="onChapterChange" class="filter-select" :disabled="!selectedCourseId">
            <option value="">-- Tất cả Chương --</option>
            <option v-for="chapter in chapters" :key="chapter.id" :value="chapter.id">
              {{ chapter.title }} ({{ chapter.type }})
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Bài Học</label>
          <select v-model="selectedLessonId" @change="onLessonChange" class="filter-select" :disabled="!selectedChapterId">
            <option value="">-- Tất cả Bài Học --</option>
            <option v-for="lesson in lessons" :key="lesson.id" :value="lesson.id">
              {{ lesson.title }}
            </option>
          </select>
        </div>
      </div>
      <div class="filter-row">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm ngữ pháp..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ grammars.length }}</div>
        <div class="stat-label">Tổng Ngữ Pháp</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ lessons.length }}</div>
        <div class="stat-label">Bài Học</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredGrammars.length === 0" class="empty-state">
      <p>Không tìm thấy ngữ pháp nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th>Tiêu Đề</th>
          <th>Cấu Trúc</th>
          <th>Giải Thích</th>
          <th>Ví Dụ</th>
          <th>YouTube</th>
          <th>Bài Học</th>
          <th style="width: 120px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="grammar in filteredGrammars" :key="grammar.id">
          <td>{{ grammar.id }}</td>
          <td>
            <div class="title-cell">
              <span class="title-text">{{ grammar.title }}</span>
            </div>
          </td>
          <td>
            <div class="structure-cell" :title="grammar.structure">
              {{ grammar.structure ? truncateText(grammar.structure, 50) : '-' }}
            </div>
          </td>
          <td>
            <div class="explanation-cell" :title="grammar.explanation">
              {{ truncateText(grammar.explanation, 80) }}
            </div>
          </td>
          <td>
            <div class="example-cell" :title="grammar.example">
              {{ grammar.example ? truncateText(grammar.example, 60) : '-' }}
            </div>
          </td>
          <td>
            <a v-if="grammar.youtube_url" :href="grammar.youtube_url" target="_blank" class="youtube-link">
              <i class="fa-brands fa-youtube"></i> Xem
            </a>
            <span v-else>-</span>
          </td>
          <td>
            <span class="lesson-badge">{{ grammar.lesson?.title || '-' }}</span>
          </td>
          <td class="actions">
            <button class="btn-edit" @click="editGrammar(grammar)" title="Sửa">
              <span>✏️</span>
            </button>
            <button class="btn-copy" @click="copyGrammar(grammar)" title="Sao chép">
              <span>📋</span>
            </button>
            <button class="btn-delete" @click="deleteGrammar(grammar.id)" title="Xóa">
              <span>🗑️</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Thêm/Sửa Ngữ Pháp -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingGrammar ? 'Sửa Ngữ Pháp' : 'Thêm Ngữ Pháp Mới' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Bài Học <span class="required">*</span></label>
            <select v-model="grammarForm.lesson_id">
              <option value="">-- Chọn Bài Học --</option>
              <optgroup v-for="course in courses" :key="course.id" :label="course.title">
                <option v-for="lesson in getLessonsByCourse(course.id)" :key="lesson.id" :value="lesson.id">
                  {{ lesson.title }}
                </option>
              </optgroup>
            </select>
            <span v-if="errors.lesson_id" class="error-text">{{ errors.lesson_id[0] }}</span>
          </div>
          <div class="form-group">
            <label>Tiêu Đề <span class="required">*</span></label>
            <input v-model="grammarForm.title" type="text" placeholder="Ví dụ: Present Simple" />
            <span v-if="errors.title" class="error-text">{{ errors.title[0] }}</span>
          </div>
          <div class="form-group">
            <label>Giải Thích <span class="required">*</span></label>
            <textarea v-model="grammarForm.explanation" rows="4" placeholder="Nhập giải thích ngữ pháp..."></textarea>
            <span v-if="errors.explanation" class="error-text">{{ errors.explanation[0] }}</span>
          </div>
          <div class="form-group">
            <label>Cấu Trúc</label>
            <input v-model="grammarForm.structure" type="text" placeholder="Ví dụ: S + V(s/es) + O" />
            <span v-if="errors.structure" class="error-text">{{ errors.structure[0] }}</span>
          </div>
          <div class="form-group">
            <label>Ví Dụ</label>
            <textarea v-model="grammarForm.example" rows="3" placeholder="Nhập ví dụ câu..."></textarea>
          </div>
          <div class="form-group">
            <label>Link YouTube</label>
            <input v-model="grammarForm.youtube_url" type="text" placeholder="https://www.youtube.com/watch?v=..." />
          </div>
          <div class="form-group">
            <label>Dấu hiệu nhận biết</label>
            <textarea v-model="grammarForm.signals" rows="2" placeholder="Nhập dấu hiệu nhận biết..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Hủy</button>
          <button class="btn-primary" @click="saveGrammar">
            {{ editingGrammar ? 'Cập Nhật' : 'Thêm Mới' }}
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
          <p>Bạn có chắc chắn muốn xóa ngữ pháp này?</p>
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
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { CourseService, ChapterService, LessonService, GrammarService } from '../../../services/api.js'

const loading = ref(false)
const courses = ref([])
const chapters = ref([])
const lessons = ref([])
const grammars = ref([])
const allGrammars = ref([])

// Loại chapter mà trang này quản lý
const CHAPTER_TYPE = 'grammar'

const selectedCourseId = ref('')
const selectedChapterId = ref('')
const selectedLessonId = ref('')
const searchQuery = ref('')
const isRestoringFromSession = ref(false)

const showModal = ref(false)
const editingGrammar = ref(null)
const showDeleteConfirm = ref(false)
const deleteGrammarId = ref(null)
const errors = ref({})

const grammarForm = ref({
  lesson_id: '',
  title: '',
  explanation: '',
  structure: '',
  example: '',
  youtube_url: '',
  signals: ''
})

// Computed
const filteredGrammars = computed(() => {
  let result = allGrammars.value

  // Lọc theo khóa học (thông qua lesson)
  if (selectedCourseId.value) {
    result = result.filter(g => g.lesson?.course_id === parseInt(selectedCourseId.value))
  }

  // Lọc theo chương
  if (selectedChapterId.value) {
    result = result.filter(g => g.lesson?.chapter_id === parseInt(selectedChapterId.value))
  }

  // Lọc theo bài học
  if (selectedLessonId.value) {
    result = result.filter(g => g.lesson_id === parseInt(selectedLessonId.value))
  }

  // Lọc theo từ khóa tìm kiếm
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(g =>
      g.title.toLowerCase().includes(q) ||
      g.explanation.toLowerCase().includes(q) ||
      (g.example && g.example.toLowerCase().includes(q))
    )
  }

  return result
})

// Helper: truncate text
const truncateText = (text, maxLength) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

// Methods
const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courses.value = data

    // Kiểm tra xem có dữ liệu từ sessionStorage không (từ trang Bài Học chuyển sang)
    const savedCourseId = sessionStorage.getItem('nguPhap_selectedCourseId')
    const savedChapterId = sessionStorage.getItem('nguPhap_selectedChapterId')
    const savedLessonId = sessionStorage.getItem('nguPhap_selectedLessonId')

    if (savedCourseId) {
      // Đánh dấu đang restore từ session để tránh fetchGrammars 2 lần
      isRestoringFromSession.value = true

      // Load chapters trước
      const chaptersData = await ChapterService.getByCourse(savedCourseId)
      chapters.value = chaptersData.filter(c => c.type === CHAPTER_TYPE)

      // Set course
      selectedCourseId.value = savedCourseId
      selectedChapterId.value = savedChapterId || ''
      selectedLessonId.value = savedLessonId || ''

      // Xóa sessionStorage
      sessionStorage.removeItem('nguPhap_selectedCourseId')
      sessionStorage.removeItem('nguPhap_selectedChapterId')
      sessionStorage.removeItem('nguPhap_selectedCourseTitle')
      sessionStorage.removeItem('nguPhap_selectedChapterTitle')
      sessionStorage.removeItem('nguPhap_selectedLessonId')
      sessionStorage.removeItem('nguPhap_selectedLessonTitle')

      await nextTick()
      isRestoringFromSession.value = false

      // Load lessons và grammars
      if (savedChapterId) {
        await fetchLessonsByChapter(savedChapterId)
      }
      await fetchGrammars()
    } else {
      // Load tất cả chapters của tất cả courses
      await fetchAllChapters()
      await fetchAllLessons()
      await fetchGrammars()
    }
  } catch (error) {
    console.error('Lỗi khi tải khóa học:', error)
    isRestoringFromSession.value = false
  }
}

const fetchAllChapters = async () => {
  try {
    const allChapters = []
    for (const course of courses.value) {
      const chaptersData = await ChapterService.getByCourse(course.id)
      const filteredChapters = chaptersData.filter(c => c.type === CHAPTER_TYPE)
      allChapters.push(...filteredChapters)
    }
    chapters.value = allChapters
  } catch (error) {
    console.error('Lỗi khi tải chương:', error)
  }
}

const fetchChapters = async (courseId) => {
  if (!courseId) {
    chapters.value = []
    return
  }
  try {
    const data = await ChapterService.getByCourse(courseId)
    // Lọc chỉ lấy chapter có type = 'grammar'
    chapters.value = data.filter(c => c.type === CHAPTER_TYPE)
  } catch (error) {
    console.error('Lỗi khi tải chương:', error)
  }
}

const fetchAllLessons = async () => {
  try {
    const allLessons = []
    for (const course of courses.value) {
      const chaptersData = await ChapterService.getByCourse(course.id)
      // Lọc chỉ lấy chapter có type = 'grammar'
      const grammarChapters = chaptersData.filter(c => c.type === CHAPTER_TYPE)
      for (const chapter of grammarChapters) {
        const lessonsData = await LessonService.getByChapter(chapter.id)
        allLessons.push(...lessonsData.map(l => ({ ...l, course_id: course.id, chapter_id: chapter.id })))
      }
    }
    lessons.value = allLessons
  } catch (error) {
    console.error('Lỗi khi tải bài học:', error)
  }
}

const fetchLessonsByChapter = async (chapterId) => {
  if (!chapterId) {
    lessons.value = []
    return
  }
  try {
    const data = await LessonService.getByChapter(chapterId)
    lessons.value = data.map(l => ({
      ...l,
      course_id: parseInt(selectedCourseId.value),
      chapter_id: parseInt(chapterId)
    }))
  } catch (error) {
    console.error('Lỗi khi tải bài học:', error)
  }
}

const fetchGrammars = async () => {
  loading.value = true
  try {
    const allGrammarsList = []
    
    // Lấy danh sách lessons cần fetch
    let lessonsToFetch = lessons.value
    
    // Nếu có chọn chapter cụ thể, lọc lessons theo chapter đó
    if (selectedChapterId.value) {
      lessonsToFetch = lessonsToFetch.filter(l => l.chapter_id === parseInt(selectedChapterId.value))
    }
    
    for (const lesson of lessonsToFetch) {
      try {
        const data = await GrammarService.getByLesson(lesson.id)
        const grammarsWithLesson = data.map(g => ({ ...g, lesson }))
        allGrammarsList.push(...grammarsWithLesson)
      } catch (e) {
        // Lesson có thể không có ngữ pháp
      }
    }
    allGrammars.value = allGrammarsList
    grammars.value = allGrammarsList
  } catch (error) {
    console.error('Lỗi khi tải ngữ pháp:', error)
  } finally {
    loading.value = false
  }
}

const onCourseChange = () => {
  selectedChapterId.value = ''
  selectedLessonId.value = ''
  lessons.value = []
  // Load chapters của course mới
  fetchChapters(selectedCourseId.value)
}

const onChapterChange = () => {
  selectedLessonId.value = ''
  // Load lessons của chapter mới
  fetchLessonsByChapter(selectedChapterId.value)
}

const onLessonChange = () => {
  // Filter được xử lý bằng computed
}

// Watch để tự động tải dữ liệu khi selectedCourseId thay đổi
watch(selectedCourseId, async (newVal) => {
  if (isRestoringFromSession.value) return
  if (newVal) {
    await fetchChapters(newVal)
  } else {
    chapters.value = []
    selectedChapterId.value = ''
    lessons.value = []
    selectedLessonId.value = ''
  }
})

// Watch để tự động tải lessons khi selectedChapterId thay đổi
watch(selectedChapterId, async (newVal, oldVal) => {
  if (isRestoringFromSession.value) return
  if (newVal) {
    await fetchLessonsByChapter(newVal)
  } else {
    // Khi xóa chapter, load tất cả lessons của tất cả chapters
    await fetchAllLessons()
    await fetchGrammars()
  }
})

// Watch để reload grammars khi selectedLessonId thay đổi (sau khi restore từ session)
watch(selectedLessonId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    // Nếu đang restore từ session, grammars đã được load trong fetchCourses
    // Chỉ cần fetch lại nếu user tự thay đổi lesson
    if (!isRestoringFromSession.value) {
      await fetchGrammars()
    }
  }
})

// Helper: lấy lessons theo course
const getLessonsByCourse = (courseId) => {
  return lessons.value.filter(l => l.course_id === courseId)
}

const openAddModal = () => {
  editingGrammar.value = null
  errors.value = {}
  grammarForm.value = {
    lesson_id: selectedLessonId.value || '',
    title: '',
    explanation: '',
    structure: '',
    example: '',
    youtube_url: '',
    signals: ''
  }
  showModal.value = true
}

const editGrammar = (grammar) => {
  editingGrammar.value = grammar.id
  errors.value = {}
  grammarForm.value = {
    lesson_id: grammar.lesson_id,
    title: grammar.title,
    explanation: grammar.explanation,
    structure: grammar.structure || '',
    example: grammar.example || '',
    youtube_url: grammar.youtube_url || '',
    signals: grammar.signals || ''
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingGrammar.value = null
  errors.value = {}
}

const saveGrammar = async () => {
  errors.value = {}
  try {
    if (editingGrammar.value) {
      const result = await GrammarService.update(editingGrammar.value, grammarForm.value)
      const index = allGrammars.value.findIndex(g => g.id === editingGrammar.value)
      if (index !== -1) {
        const lesson = lessons.value.find(l => l.id === result.data.lesson_id)
        allGrammars.value[index] = { ...result.data, lesson }
      }
      alert('Cập nhật ngữ pháp thành công!')
    } else {
      if (!grammarForm.value.lesson_id) {
        errors.value.lesson_id = ['Vui lòng chọn bài học']
        return
      }
      const result = await GrammarService.create(grammarForm.value.lesson_id, grammarForm.value)
      const lesson = lessons.value.find(l => l.id === result.data.lesson_id)
      allGrammars.value.push({ ...result.data, lesson })
      alert('Thêm ngữ pháp thành công!')
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

const deleteGrammar = (id) => {
  deleteGrammarId.value = id
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    await GrammarService.delete(deleteGrammarId.value)
    allGrammars.value = allGrammars.value.filter(g => g.id !== deleteGrammarId.value)
    showDeleteConfirm.value = false
    deleteGrammarId.value = null
    alert('Xóa ngữ pháp thành công!')
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

// Copy to clipboard
const copyGrammar = (grammar) => {
  const text = `${grammar.title}\nCấu trúc: ${grammar.structure || '-'}\n${grammar.explanation}\nVí dụ: ${grammar.example || '-'}`
  navigator.clipboard.writeText(text).then(() => {
    alert('Đã sao chép ngữ pháp!')
  }).catch(() => {
    alert('Không thể sao chép')
  })
}

onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
.ngu-phap {
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
}

.form-group {
  margin-bottom: 0;
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
  grid-template-columns: repeat(2, 1fr);
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
  color: #8b5cf6;
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

.title-cell {
  display: flex;
  flex-direction: column;
}

.title-text {
  font-weight: 600;
  color: #333;
  font-size: 15px;
}

.structure-cell {
  color: #7c3aed;
  font-size: 13px;
  font-family: monospace;
  font-weight: 500;
  max-width: 150px;
}

.explanation-cell,
.example-cell {
  color: #666;
  font-size: 13px;
  max-width: 300px;
  line-height: 1.4;
}

.youtube-link {
  color: #ef4444;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.youtube-link:hover {
  text-decoration: underline;
}

.lesson-badge {
  padding: 4px 8px;
  background: #ede9fe;
  color: #7c3aed;
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

.btn-copy {
  background: #f59e0b;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-copy:hover {
  background: #d97706;
}

.btn-primary {
  background: #8b5cf6;
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
  background: #7c3aed;
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

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
}

.form-group input[type="text"],
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
  border-color: #8b5cf6;
}

.required {
  color: #ef4444;
}

.error-text {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}
</style>
