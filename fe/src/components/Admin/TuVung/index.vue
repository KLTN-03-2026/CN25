<template>
  <div class="tu-vung">
    <div class="header">
      <h1>Quản Lý Từ Vựng</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Từ Vựng
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
          placeholder="Tìm kiếm từ vựng..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ vocabularies.length }}</div>
        <div class="stat-label">Tổng Từ Vựng</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ lessons.length }}</div>
        <div class="stat-label">Bài Học</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredVocabularies.length === 0" class="empty-state">
      <p>Không tìm thấy từ vựng nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th>Từ Vựng</th>
          <th>Phiên Âm</th>
          <th>Nghĩa</th>
          <th>Bài Học</th>
          <th style="width: 120px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="vocab in filteredVocabularies" :key="vocab.id">
          <td>{{ vocab.id }}</td>
          <td>
            <div class="word-cell">
              <div class="word-text">{{ vocab.word }}</div>
            </div>
          </td>
          <td>
            <span class="phonetic">{{ vocab.pronunciation || '-' }}</span>
          </td>
          <td>{{ vocab.meaning }}</td>
          <td>
            <span class="lesson-badge">{{ vocab.lesson?.title || '-' }}</span>
          </td>
          <td class="actions">
            <button class="btn-speak" @click="speakWord(vocab.word)" title="Phát âm">
              🔊
            </button>
            <button class="btn-edit" @click="editVocab(vocab)" title="Sửa">
              <span>✏️</span>
            </button>
            <button class="btn-delete" @click="deleteVocab(vocab.id)" title="Xóa">
              <span>🗑️</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Thêm/Sửa Từ Vựng -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingVocab ? 'Sửa Từ Vựng' : 'Thêm Từ Vựng Mới' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Bài Học <span class="required">*</span></label>
            <select v-model="vocabForm.lesson_id">
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
            <label>Từ Vựng <span class="required">*</span></label>
            <input v-model="vocabForm.word" type="text" placeholder="Nhập từ vựng tiếng Anh" />
            <span v-if="errors.word" class="error-text">{{ errors.word[0] }}</span>
          </div>
          <div class="form-group">
            <label>Nghĩa <span class="required">*</span></label>
            <input v-model="vocabForm.meaning" type="text" placeholder="Nhập nghĩa tiếng Việt" />
            <span v-if="errors.meaning" class="error-text">{{ errors.meaning[0] }}</span>
          </div>
          <div class="form-group">
            <label>Phiên Âm</label>
            <input v-model="vocabForm.pronunciation" type="text" placeholder="Ví dụ: /həˈloʊ/" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Hủy</button>
          <button class="btn-primary" @click="saveVocab">
            {{ editingVocab ? 'Cập Nhật' : 'Thêm Mới' }}
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
          <p>Bạn có chắc chắn muốn xóa từ vựng này?</p>
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
import { CourseService, ChapterService, LessonService, VocabularyService } from '../../../services/api.js'

const loading = ref(false)
const courses = ref([])
const chapters = ref([])
const lessons = ref([])
const vocabularies = ref([])
const allVocabularies = ref([])

// Loại chapter mà trang này quản lý
const CHAPTER_TYPE = 'vocabulary'

const selectedCourseId = ref('')
const selectedChapterId = ref('')
const selectedLessonId = ref('')
const searchQuery = ref('')
const isRestoringFromSession = ref(false)

const showModal = ref(false)
const editingVocab = ref(null)
const showDeleteConfirm = ref(false)
const deleteVocabId = ref(null)
const errors = ref({})

const vocabForm = ref({
  lesson_id: '',
  word: '',
  meaning: '',
  pronunciation: ''
})

// Computed
const filteredVocabularies = computed(() => {
  let result = allVocabularies.value

  // Lọc theo khóa học (thông qua lesson)
  if (selectedCourseId.value) {
    result = result.filter(v => v.lesson?.course_id === parseInt(selectedCourseId.value))
  }

  // Lọc theo chương
  if (selectedChapterId.value) {
    result = result.filter(v => v.lesson?.chapter_id === parseInt(selectedChapterId.value))
  }

  // Lọc theo bài học
  if (selectedLessonId.value) {
    result = result.filter(v => v.lesson_id === parseInt(selectedLessonId.value))
  }

  // Lọc theo từ khóa tìm kiếm
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(v =>
      v.word.toLowerCase().includes(q) ||
      v.meaning.toLowerCase().includes(q) ||
      (v.pronunciation && v.pronunciation.toLowerCase().includes(q))
    )
  }

  return result
})

// Methods
const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courses.value = data

    // Kiểm tra xem có dữ liệu từ sessionStorage không (từ trang Bài Học chuyển sang)
    const savedCourseId = sessionStorage.getItem('tuVung_selectedCourseId')
    const savedChapterId = sessionStorage.getItem('tuVung_selectedChapterId')
    const savedLessonId = sessionStorage.getItem('tuVung_selectedLessonId')

    if (savedCourseId) {
      // Đánh dấu đang restore từ session để tránh fetchVocabularies 2 lần
      isRestoringFromSession.value = true

      // Load chapters trước
      const chaptersData = await ChapterService.getByCourse(savedCourseId)
      chapters.value = chaptersData.filter(c => c.type === CHAPTER_TYPE)

      // Set các giá trị đã lưu
      selectedCourseId.value = savedCourseId
      selectedChapterId.value = savedChapterId || ''
      selectedLessonId.value = savedLessonId || ''

      // Xóa sessionStorage
      sessionStorage.removeItem('tuVung_selectedCourseId')
      sessionStorage.removeItem('tuVung_selectedChapterId')
      sessionStorage.removeItem('tuVung_selectedCourseTitle')
      sessionStorage.removeItem('tuVung_selectedChapterTitle')
      sessionStorage.removeItem('tuVung_selectedLessonId')
      sessionStorage.removeItem('tuVung_selectedLessonTitle')

      await nextTick()
      isRestoringFromSession.value = false

      // Load lessons và vocabularies
      if (savedChapterId) {
        await fetchLessonsByChapter(savedChapterId)
      }
      await fetchVocabularies()
    } else {
      // Load tất cả chapters của tất cả courses
      await fetchAllChapters()
      await fetchAllLessons()
      await fetchVocabularies()
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
    // Lọc chỉ lấy chapter có type = 'vocabulary'
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
      // Lọc chỉ lấy chapter có type = 'vocabulary'
      const vocabChapters = chaptersData.filter(c => c.type === CHAPTER_TYPE)
      for (const chapter of vocabChapters) {
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

const fetchVocabularies = async () => {
  loading.value = true
  try {
    const allVocab = []
    
    // Lấy danh sách lessons cần fetch
    let lessonsToFetch = lessons.value
    
    // Nếu có chọn chapter cụ thể, lọc lessons theo chapter đó
    if (selectedChapterId.value) {
      lessonsToFetch = lessonsToFetch.filter(l => l.chapter_id === parseInt(selectedChapterId.value))
    }
    
    for (const lesson of lessonsToFetch) {
      try {
        const data = await VocabularyService.getByLesson(lesson.id)
        const vocabWithLesson = data.map(v => ({ ...v, lesson }))
        allVocab.push(...vocabWithLesson)
      } catch (e) {
        // Lesson có thể không có từ vựng
      }
    }
    allVocabularies.value = allVocab
    vocabularies.value = allVocab
  } catch (error) {
    console.error('Lỗi khi tải từ vựng:', error)
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
    await fetchVocabularies()
  }
})

// Watch để reload vocabularies khi selectedLessonId thay đổi (sau khi restore từ session)
watch(selectedLessonId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    // Nếu đang restore từ session, vocabularies đã được load trong fetchCourses
    // Chỉ cần fetch lại nếu user tự thay đổi lesson
    if (!isRestoringFromSession.value) {
      await fetchVocabularies()
    }
  }
})

// Helper: lấy lessons theo course
const getLessonsByCourse = (courseId) => {
  return lessons.value.filter(l => l.course_id === courseId)
}

const openAddModal = () => {
  editingVocab.value = null
  errors.value = {}
  vocabForm.value = {
    lesson_id: selectedLessonId.value || '',
    word: '',
    meaning: '',
    pronunciation: ''
  }
  showModal.value = true
}

const editVocab = (vocab) => {
  editingVocab.value = vocab.id
  errors.value = {}
  vocabForm.value = {
    lesson_id: vocab.lesson_id,
    word: vocab.word,
    meaning: vocab.meaning,
    pronunciation: vocab.pronunciation || ''
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingVocab.value = null
  errors.value = {}
}

const saveVocab = async () => {
  errors.value = {}
  try {
    if (editingVocab.value) {
      const result = await VocabularyService.update(editingVocab.value, vocabForm.value)
      const index = allVocabularies.value.findIndex(v => v.id === editingVocab.value)
      if (index !== -1) {
        const lesson = lessons.value.find(l => l.id === result.data.lesson_id)
        allVocabularies.value[index] = { ...result.data, lesson }
      }
      alert('Cập nhật từ vựng thành công!')
    } else {
      if (!vocabForm.value.lesson_id) {
        errors.value.lesson_id = ['Vui lòng chọn bài học']
        return
      }
      const result = await VocabularyService.create(vocabForm.value.lesson_id, vocabForm.value)
      const lesson = lessons.value.find(l => l.id === result.data.lesson_id)
      allVocabularies.value.push({ ...result.data, lesson })
      alert('Thêm từ vựng thành công!')
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

const deleteVocab = (id) => {
  deleteVocabId.value = id
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    await VocabularyService.delete(deleteVocabId.value)
    allVocabularies.value = allVocabularies.value.filter(v => v.id !== deleteVocabId.value)
    showDeleteConfirm.value = false
    deleteVocabId.value = null
    alert('Xóa từ vựng thành công!')
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

// Text-to-Speech function
const speakWord = (word) => {
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel()
    const utterance = new SpeechSynthesisUtterance(word)
    utterance.lang = 'en-US'
    utterance.rate = 0.9
    utterance.pitch = 1
    window.speechSynthesis.speak(utterance)
  } else {
    alert('Trình duyệt không hỗ trợ Text-to-Speech')
  }
}

onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
.tu-vung {
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

.word-cell {
  display: flex;
  flex-direction: column;
}

.word-text {
  font-weight: 600;
  color: #333;
  font-size: 15px;
}

.phonetic {
  font-family: 'Courier New', monospace;
  color: #666;
  font-size: 13px;
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

.btn-speak {
  background: #10b981;
  color: white;
  border: none;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-speak:hover {
  background: #059669;
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
}

.form-group input:focus,
.form-group select:focus {
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
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}
</style>
