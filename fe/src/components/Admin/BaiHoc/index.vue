<template>
  <div class="bai-hoc">
    <div class="header">
      <h1>Quản Lý Bài Học</h1>
      <button class="btn-primary" @click="openLessonModal()">
        <span>+</span> Thêm Bài Học
      </button>
    </div>

    <!-- Filter: Chọn Course và Chapter -->
    <div class="filter-section">
      <div class="filter-row">
        <div class="form-group">
          <label>Khóa Học</label>
          <select v-model="selectedCourseId" @change="onCourseChange" class="filter-select">
            <option value="">-- Chọn Khóa Học --</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">
              {{ course.title }}
            </option>
          </select>
        </div>
        <div class="form-group">
          <label>Chương</label>
          <select v-model="selectedChapterId" @change="fetchLessons" class="filter-select" :disabled="!selectedCourseId">
            <option value="">-- Chọn Chương --</option>
            <option v-for="chapter in chapters" :key="chapter.id" :value="chapter.id">
              {{ chapter.title }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div v-if="selectedChapterId" class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ filteredLessons.length }}</div>
        <div class="stat-label">Tổng Bài Học</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ publishedCount }}</div>
        <div class="stat-label">Đã Xuất Bản</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ draftCount }}</div>
        <div class="stat-label">Nháp</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ publishedPercent }}</div>
        <div class="stat-label">% Hoàn Thành</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="!selectedChapterId" class="empty-state">
      <p>Vui lòng chọn khóa học và chương để xem bài học.</p>
    </div>

    <div v-else-if="filteredLessons.length === 0" class="empty-state">
      <p>Chưa có bài học nào trong chương này.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th>Tiêu Đề</th>
          <th style="width: 120px">Slug</th>
          <th style="width: 80px">Thứ Tự</th>
          <th style="width: 100px">Trạng Thái</th>
          <th style="width: 150px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="lesson in filteredLessons" :key="lesson.id">
          <td>{{ lesson.id }}</td>
          <td>
            <div class="lesson-title">
              <strong>{{ lesson.title }}</strong>
              <span v-if="lesson.description" class="lesson-desc">{{ truncateText(lesson.description, 80) }}</span>
            </div>
          </td>
          <td class="slug-cell">{{ lesson.slug }}</td>
          <td>{{ lesson.order }}</td>
          <td>
            <span :class="['status-badge', lesson.status]">
              {{ lesson.status === 'published' ? 'Đã xuất bản' : 'Nháp' }}
            </span>
          </td>
          <td class="actions">
            <button
              v-if="currentChapter?.type === 'vocabulary'"
              class="btn-link-vocab"
              @click="goToTuVung(lesson)"
              title="Đến trang Từ Vựng"
            >
              <span>📚</span>
            </button>
            <button
              v-else-if="currentChapter?.type === 'grammar'"
              class="btn-link-grammar"
              @click="goToNguPhap(lesson)"
              title="Đến trang Ngữ Pháp"
            >
              <span>📖</span>
            </button>
            <button
              v-else-if="currentChapter?.type === 'listening'"
              class="btn-link-listening"
              @click="goToLuyenNghe(lesson)"
              title="Đến trang Luyện Nghe"
            >
              <span>🎧</span>
            </button>
            <button
              v-else-if="currentChapter?.type === 'speaking'"
              class="btn-link-speaking"
              @click="goToLuyenNoi(lesson)"
              title="Đến trang Luyện Nói"
            >
              <span>🎤</span>
            </button>
            <button class="btn-edit" @click="editLesson(lesson)" title="Sửa">
              <span>✏️</span>
            </button>
            <button class="btn-toggle" @click="toggleStatus(lesson)" :title="lesson.status === 'published' ? 'Chuyển sang nháp' : 'Xuất bản'">
              <span>{{ lesson.status === 'published' ? '📄' : '✅' }}</span>
            </button>
            <button class="btn-delete" @click="deleteLesson(lesson.id)" title="Xóa">
              <span>🗑️</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Thêm/Sửa Bài Học -->
    <div v-if="showLessonModal" class="modal-overlay" @click.self="closeLessonModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingLesson ? 'Sửa Bài Học' : 'Thêm Bài Học Mới' }}</h2>
          <button class="btn-close" @click="closeLessonModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề <span class="required">*</span></label>
            <input v-model="lessonForm.title" type="text" placeholder="Nhập tiêu đề bài học" />
            <span v-if="errors.title" class="error-text">{{ errors.title[0] }}</span>
          </div>
          <div class="form-group">
            <label>Slug</label>
            <input v-model="lessonForm.slug" type="text" placeholder="Để trống sẽ tự tạo từ tiêu đề" />
            <span v-if="errors.slug" class="error-text">{{ errors.slug[0] }}</span>
          </div>
          <div class="form-group">
            <label>Mô Tả</label>
            <textarea v-model="lessonForm.description" rows="3" placeholder="Nhập mô tả bài học"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Chương</label>
              <select v-model="lessonForm.chapter_id" :disabled="!selectedCourseId">
                <option value="">-- Chọn Chương --</option>
                <option v-for="chapter in chapters" :key="chapter.id" :value="chapter.id">
                  {{ chapter.title }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Thứ Tự</label>
              <input v-model.number="lessonForm.order" type="number" min="1" placeholder="1" />
            </div>
          </div>
          <div class="form-group">
            <label>Trạng Thái</label>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" v-model="lessonForm.status" value="draft" />
                <span class="radio-text">Nháp</span>
              </label>
              <label class="radio-label">
                <input type="radio" v-model="lessonForm.status" value="published" />
                <span class="radio-text">Xuất Bản</span>
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeLessonModal">Hủy</button>
          <button class="btn-primary" @click="saveLesson">
            {{ editingLesson ? 'Cập Nhật' : 'Thêm Mới' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="modal modal-confirm">
        <div class="modal-header">
          <h2>Xác Nhận Xóa</h2>
          <button class="btn-close" @click="showDeleteConfirm = false">&times;</button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa bài học này?</p>
          <p class="warning-text">Hành động này sẽ xóa tất cả các bước học liên quan.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showDeleteConfirm = false">Hủy</button>
          <button class="btn-danger" @click="confirmDeleteLesson">Xóa</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { CourseService, ChapterService, LessonService } from '../../../services/api.js'

const router = useRouter()

// State
const loading = ref(false)
const courses = ref([])
const chapters = ref([])
const lessons = ref([])
const selectedCourseId = ref('')
const selectedChapterId = ref('')
const selectedLessonId = ref('')
const showLessonModal = ref(false)
const editingLesson = ref(null)
const showDeleteConfirm = ref(false)
const deleteLessonId = ref(null)
const isRestoringFromSession = ref(false)

const lessonForm = ref({
  title: '',
  slug: '',
  description: '',
  chapter_id: '',
  order: 1,
  status: 'draft'
})

const errors = ref({})

// Computed
const filteredLessons = computed(() => {
  return lessons.value
})

const currentChapter = computed(() => {
  return chapters.value.find(c => c.id === parseInt(selectedChapterId.value))
})

const publishedCount = computed(() => {
  return lessons.value.filter(l => l.status === 'published').length
})

const draftCount = computed(() => {
  return lessons.value.filter(l => l.status === 'draft').length
})

const publishedPercent = computed(() => {
  const total = lessons.value.length
  if (total === 0) return 0
  return Math.round((publishedCount.value / total) * 100)
})

// Watch để tự động tải dữ liệu khi selectedCourseId thay đổi
watch(selectedCourseId, async (newVal) => {
  if (isRestoringFromSession.value) return // Bỏ qua nếu đang restore từ session

  if (newVal) {
    await fetchChapters(newVal)
  } else {
    chapters.value = []
    selectedChapterId.value = ''
    lessons.value = []
  }
})

// Watch để tự động tải lessons khi selectedChapterId thay đổi
watch(selectedChapterId, async (newVal) => {
  if (isRestoringFromSession.value) return // Bỏ qua nếu đang restore từ session
  if (newVal) {
    await fetchLessons()
  } else {
    lessons.value = []
  }
})

// Methods
const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courses.value = data

    // Kiểm tra xem có dữ liệu từ sessionStorage không (từ trang Khóa Học chuyển sang)
    const savedCourseId = sessionStorage.getItem('selectedCourseId')
    const savedChapterId = sessionStorage.getItem('selectedChapterId')
    const savedLessonId = sessionStorage.getItem('selectedLessonId')

    if (savedCourseId) {
      // Đánh dấu đang restore để tránh re-fetch
      isRestoringFromSession.value = true

      // Load chapters trước
      const chaptersData = await ChapterService.getByCourse(savedCourseId)
      chapters.value = chaptersData

      // Set course và chapter - watch sẽ tự động gọi fetchLessons()
      selectedCourseId.value = savedCourseId
      selectedChapterId.value = savedChapterId || ''

      // Xóa sessionStorage
      sessionStorage.removeItem('selectedCourseId')
      sessionStorage.removeItem('selectedChapterId')
      sessionStorage.removeItem('selectedCourseTitle')
      sessionStorage.removeItem('selectedChapterTitle')

      // Reset cờ trước để watch có thể chạy
      await nextTick()
      isRestoringFromSession.value = false

      // Gọi trực tiếp fetchLessons để đảm bảo lessons được load
      if (savedChapterId) {
        await fetchLessons()
      }
    }
  } catch (error) {
    console.error('Lỗi khi tải khóa học:', error)
    isRestoringFromSession.value = false
  }
}

const fetchChapters = async (courseId) => {
  if (!courseId) {
    chapters.value = []
    return
  }
  try {
    const data = await ChapterService.getByCourse(courseId)
    chapters.value = data
  } catch (error) {
    console.error('Lỗi khi tải chương:', error)
  }
}

const fetchLessons = async () => {
  if (!selectedChapterId.value) {
    lessons.value = []
    return
  }
  loading.value = true
  try {
    const data = await LessonService.getByChapter(selectedChapterId.value)
    lessons.value = data
  } catch (error) {
    console.error('Lỗi khi tải bài học:', error)
    lessons.value = []
  } finally {
    loading.value = false
  }
}

const onCourseChange = () => {
  selectedChapterId.value = ''
  lessons.value = []
  fetchChapters(selectedCourseId.value)
}

const openLessonModal = () => {
  editingLesson.value = null
  errors.value = {}
  lessonForm.value = {
    title: '',
    slug: '',
    description: '',
    chapter_id: selectedChapterId.value,
    order: lessons.value.length + 1,
    status: 'draft'
  }
  showLessonModal.value = true
}

const editLesson = (lesson) => {
  editingLesson.value = lesson.id
  errors.value = {}
  lessonForm.value = {
    title: lesson.title,
    slug: lesson.slug || '',
    description: lesson.description || '',
    chapter_id: lesson.chapter_id,
    order: lesson.order,
    status: lesson.status
  }
  showLessonModal.value = true
}

const closeLessonModal = () => {
  showLessonModal.value = false
  editingLesson.value = null
  errors.value = {}
}

const saveLesson = async () => {
  errors.value = {}
  try {
    if (editingLesson.value) {
      const result = await LessonService.update(editingLesson.value, lessonForm.value)
      const index = lessons.value.findIndex(l => l.id === editingLesson.value)
      if (index !== -1) {
        lessons.value[index] = result.data
      }
      alert('Cập nhật bài học thành công!')
    } else {
      const chapterId = lessonForm.value.chapter_id || selectedChapterId.value
      const result = await LessonService.create(chapterId, lessonForm.value)
      lessons.value.push(result.data)
      alert('Thêm bài học thành công!')
    }
    closeLessonModal()
  } catch (error) {
    if (error.response && error.response.data && error.response.data.errors) {
      errors.value = error.response.data.errors
    } else {
      alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
    }
  }
}

const deleteLesson = (id) => {
  deleteLessonId.value = id
  showDeleteConfirm.value = true
}

const confirmDeleteLesson = async () => {
  try {
    await LessonService.delete(deleteLessonId.value)
    lessons.value = lessons.value.filter(l => l.id !== deleteLessonId.value)
    showDeleteConfirm.value = false
    deleteLessonId.value = null
    alert('Xóa bài học thành công!')
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

const toggleStatus = async (lesson) => {
  const newStatus = lesson.status === 'published' ? 'draft' : 'published'
  try {
    await LessonService.update(lesson.id, { status: newStatus })
    lesson.status = newStatus
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

// Chuyển đến trang Quản Lý Từ Vựng với bài học được chọn
const goToTuVung = (lesson) => {
  // Lưu thông tin vào sessionStorage để TuVung component đọc
  sessionStorage.setItem('tuVung_selectedCourseId', selectedCourseId.value)
  sessionStorage.setItem('tuVung_selectedChapterId', selectedChapterId.value)
  sessionStorage.setItem('tuVung_selectedLessonId', lesson.id)

  // Lấy thông tin course và chapter để hiển thị
  const course = courses.value.find(c => c.id === parseInt(selectedCourseId.value))
  const chapter = chapters.value.find(c => c.id === parseInt(selectedChapterId.value))

  if (course) {
    sessionStorage.setItem('tuVung_selectedCourseTitle', course.title)
  }
  if (chapter) {
    sessionStorage.setItem('tuVung_selectedChapterTitle', chapter.title)
  }
  sessionStorage.setItem('tuVung_selectedLessonTitle', lesson.title)

  // Chuyển hướng đến trang Từ Vựng
  router.push('/admin/tu-vung')
}

// Chuyển đến trang Quản Lý Ngữ Pháp với bài học được chọn
const goToNguPhap = (lesson) => {
  // Lưu thông tin vào sessionStorage để NguPhap component đọc
  sessionStorage.setItem('nguPhap_selectedCourseId', selectedCourseId.value)
  sessionStorage.setItem('nguPhap_selectedChapterId', selectedChapterId.value)
  sessionStorage.setItem('nguPhap_selectedLessonId', lesson.id)

  // Lấy thông tin course và chapter để hiển thị
  const course = courses.value.find(c => c.id === parseInt(selectedCourseId.value))
  const chapter = chapters.value.find(c => c.id === parseInt(selectedChapterId.value))

  if (course) {
    sessionStorage.setItem('nguPhap_selectedCourseTitle', course.title)
  }
  if (chapter) {
    sessionStorage.setItem('nguPhap_selectedChapterTitle', chapter.title)
  }
  sessionStorage.setItem('nguPhap_selectedLessonTitle', lesson.title)

  // Chuyển hướng đến trang Ngữ Pháp
  router.push('/admin/ngu-phap')
}

// Chuyển đến trang Quản Lý Luyện Nghe với bài học được chọn
const goToLuyenNghe = (lesson) => {
  // Lưu thông tin vào sessionStorage để LuyenNghe component đọc
  sessionStorage.setItem('luyenNghe_selectedCourseId', selectedCourseId.value)
  sessionStorage.setItem('luyenNghe_selectedChapterId', selectedChapterId.value)
  sessionStorage.setItem('luyenNghe_selectedLessonId', lesson.id)

  // Lấy thông tin course và chapter để hiển thị
  const course = courses.value.find(c => c.id === parseInt(selectedCourseId.value))
  const chapter = chapters.value.find(c => c.id === parseInt(selectedChapterId.value))

  if (course) {
    sessionStorage.setItem('luyenNghe_selectedCourseTitle', course.title)
  }
  if (chapter) {
    sessionStorage.setItem('luyenNghe_selectedChapterTitle', chapter.title)
  }
  sessionStorage.setItem('luyenNghe_selectedLessonTitle', lesson.title)

  // Chuyển hướng đến trang Luyện Nghe
  router.push('/admin/luyen-nghe')
}

// Chuyển đến trang Quản Lý Luyện Nói với bài học được chọn
const goToLuyenNoi = (lesson) => {
  // Lưu thông tin vào sessionStorage để LuyenNoi component đọc
  sessionStorage.setItem('luyenNoi_selectedCourseId', selectedCourseId.value)
  sessionStorage.setItem('luyenNoi_selectedChapterId', selectedChapterId.value)
  sessionStorage.setItem('luyenNoi_selectedLessonId', lesson.id)

  // Lấy thông tin course và chapter để hiển thị
  const course = courses.value.find(c => c.id === parseInt(selectedCourseId.value))
  const chapter = chapters.value.find(c => c.id === parseInt(selectedChapterId.value))

  if (course) {
    sessionStorage.setItem('luyenNoi_selectedCourseTitle', course.title)
  }
  if (chapter) {
    sessionStorage.setItem('luyenNoi_selectedChapterTitle', chapter.title)
  }
  sessionStorage.setItem('luyenNoi_selectedLessonTitle', lesson.title)

  // Chuyển hướng đến trang Luyện Nói
  router.push('/admin/luyen-noi')
}

// Lifecycle
onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
.bai-hoc {
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
  grid-template-columns: 1fr 1fr;
  gap: 16px;
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
  grid-template-columns: repeat(4, 1fr);
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

.lesson-title {
  display: flex;
  flex-direction: column;
}

.lesson-title strong {
  font-size: 14px;
}

.lesson-desc {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
}

.slug-cell {
  font-size: 12px;
  color: #888;
  font-family: monospace;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.published {
  background: #d4edda;
  color: #155724;
}

.status-badge.draft {
  background: #fff3cd;
  color: #856404;
}

.actions {
  display: flex;
  gap: 6px;
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

.btn-edit, .btn-toggle, .btn-delete {
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

.btn-toggle {
  background: #6366f1;
  color: white;
}

.btn-toggle:hover {
  background: #4f46e5;
}

.btn-delete {
  background: #ef4444;
  color: white;
}

.btn-delete:hover {
  background: #dc2626;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.radio-group {
  display: flex;
  gap: 20px;
}

.radio-label {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.radio-text {
  margin-left: 6px;
}

.required {
  color: #ef4444;
}

.error-text {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
}

.warning-text {
  color: #dc2626;
  font-size: 14px;
  margin-top: 8px;
}

.btn-link-vocab {
  background: #06b6d4;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-link-vocab:hover {
  background: #0891b2;
}

.btn-link-grammar {
  background: #8b5cf6;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-link-grammar:hover {
  background: #7c3aed;
}

.btn-link-listening {
  background: #f59e0b;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-link-listening:hover {
  background: #d97706;
}

.btn-link-speaking {
  background: #ec4899;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-link-speaking:hover {
  background: #db2777;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}
</style>