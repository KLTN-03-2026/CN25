<template>
  <div class="khoa-hoc">
    <!-- Header với tiêu đề và nút thêm mới -->
    <div class="header">
      <h1>Quản Lý Khoá Học</h1>
      <button class="btn-primary" @click="openModal()">
        <span>+</span> Thêm Khoá Học
      </button>
    </div>

    <!-- Thanh tìm kiếm và lọc -->
    <div class="filter-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm khoá học..."
        class="search-input"
      />
      <!-- Lọc theo trình độ -->
      <select v-model="filterLevel" class="filter-select">
        <option value="">Tất cả trình độ</option>
        <option value="beginner">Beginner (Sơ cấp)</option>
        <option value="intermediate">Intermediate (Trung cấp)</option>
        <option value="advanced">Advanced (Nâng cao)</option>
      </select>
      <!-- Lọc theo trạng thái -->
      <select v-model="filterStatus" class="filter-select">
        <option value="">Tất cả trạng thái</option>
        <option value="published">Đã xuất bản</option>
        <option value="draft">Nháp</option>
      </select>
    </div>

    <!-- Thống kê -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-value">{{ khoaHocList.length }}</div>
        <div class="stat-label">Tổng Khoá Học</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ khoaHocPublished }}</div>
        <div class="stat-label">Đã Xuất Bản</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ khoaHocDraft }}</div>
        <div class="stat-label">Nháp</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ tongDoanhThu }}</div>
        <div class="stat-label">Tổng Doanh Thu</div>
      </div>
    </div>

    <!-- Hiển thị lỗi -->
    <div v-if="errorMessage" class="error-message">
      {{ errorMessage }}
    </div>

    <!-- Trạng thái loading -->
    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <!-- Trạng thái trống -->
    <div v-else-if="filteredKhoaHoc.length === 0" class="empty-state">
      <p>Không tìm thấy khoá học nào.</p>
    </div>

    <!-- Danh sách khóa học dạng bảng -->
    <div v-else class="course-table-wrapper">
      <table class="course-table">
        <thead>
          <tr>
            <th class="col-checkbox">
              <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" />
            </th>
            <th class="col-id">ID</th>
            <th class="col-thumbnail">Ảnh</th>
            <th class="col-title">Tiêu Đề</th>
            <th class="col-slug">Slug</th>
            <th class="col-level">Trình Độ</th>
            <th class="col-price">Giá</th>
            <th class="col-status">Trạng Thái</th>
            <th class="col-actions">Thao Tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="course in filteredKhoaHoc" :key="course.id">
            <td class="col-checkbox">
              <input type="checkbox" :value="course.id" v-model="selectedIds" />
            </td>
            <td class="col-id">{{ course.id }}</td>
            <td class="col-thumbnail">
              <img :src="course.thumbnail || defaultThumbnail" :alt="course.title" class="thumbnail-img" />
            </td>
            <td class="col-title">
              <span class="course-title-cell" :title="course.title">{{ course.title }}</span>
            </td>
            <td class="col-slug">
              <code class="slug-code">/{{ course.slug }}</code>
            </td>
            <td class="col-level">
              <span :class="['level-badge', `level-${course.level}`]">
                {{ getLevelLabel(course.level) }}
              </span>
            </td>
            <td class="col-price">
              <span :class="['price-value', course.price == 0 ? 'free' : '']">
                {{ formatPrice(course.price) }}
              </span>
            </td>
            <td class="col-status">
              <span :class="['status-badge', course.status === 'published' ? 'status-published' : 'status-draft']">
                {{ course.status === 'published' ? 'Đã xuất bản' : 'Nháp' }}
              </span>
            </td>
            <td class="col-actions">
              <div class="action-buttons">
                <!-- Toggle publish status -->
                <button
                  class="btn-action btn-toggle"
                  :class="course.status === 'published' ? 'btn-unpublish' : 'btn-publish'"
                  @click="toggleStatus(course)"
                  :title="course.status === 'published' ? 'Hạ xuống nháp' : 'Xuất bản'"
                >
                  {{ course.status === 'published' ? '↩️' : '📤' }}
                </button>
                <!-- Nút quản lý chương -->
                <button class="btn-action btn-chapters" @click="openChapterModal(course)" title="Quản lý chương">
                  📚
                </button>
                <!-- Nút sửa -->
                <button class="btn-action btn-edit" @click="openModal(course)" title="Sửa">
                  ✏️
                </button>
                <!-- Nút xóa -->
                <button class="btn-action btn-delete" @click="deleteCourse(course.id)" title="Xóa">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Phân trang -->
    <div v-if="filteredKhoaHoc.length > 0" class="pagination-info">
      Hiển thị {{ filteredKhoaHoc.length }} / {{ khoaHocList.length }} khóa học
    </div>

    <!-- Modal thêm/sửa khóa học -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingCourse ? 'Sửa Khoá Học' : 'Thêm Khoá Học' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <!-- Tiêu đề khóa học -->
          <div class="form-group">
            <label>Tiêu Đề Khoá Học <span class="required">*</span></label>
            <input 
              v-model="formData.title" 
              type="text" 
              placeholder="VD: English for Beginners"
            />
            <small class="form-hint">Slug sẽ tự động tạo: /english-for-beginners</small>
          </div>

          <!-- Slug (tùy chỉnh) -->
          <div class="form-group">
            <label>Slug URL</label>
            <input 
              v-model="formData.slug" 
              type="text" 
              placeholder="english-for-beginners"
            />
            <small class="form-hint">Để trống để tự động tạo từ tiêu đề</small>
          </div>

          <!-- Trình độ và Giá -->
          <div class="form-row">
            <div class="form-group">
              <label>Trình Độ</label>
              <select v-model="formData.level">
                <option value="beginner">Beginner (Sơ cấp)</option>
                <option value="intermediate">Intermediate (Trung cấp)</option>
                <option value="advanced">Advanced (Nâng cao)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Giá (VNĐ)</label>
              <input 
                v-model.number="formData.price" 
                type="number" 
                min="0" 
                step="1000"
                placeholder="0 = Miễn phí"
              />
            </div>
          </div>

          <!-- Ảnh thumbnail -->
          <div class="form-group">
            <label>Ảnh Thumbnail (URL)</label>
            <input 
              v-model="formData.thumbnail" 
              type="text" 
              placeholder="https://example.com/image.jpg"
            />
            <small class="form-hint">Link ảnh đại diện khóa học</small>
          </div>

          <!-- Mô tả -->
          <div class="form-group">
            <label>Mô Tả</label>
            <textarea 
              v-model="formData.description" 
              rows="4" 
              placeholder="Nhập mô tả chi tiết về khóa học..."
            ></textarea>
          </div>

          <!-- Trạng thái -->
          <div class="form-group">
            <label>Trạng Thái</label>
            <select v-model="formData.status">
              <option value="draft">📝 Nháp (Draft)</option>
              <option value="published">📤 Đã xuất bản (Published)</option>
            </select>
          </div>

          <div class="modal-footer">
            <button class="btn-secondary" @click="closeModal">Huỷ</button>
            <button class="btn-primary" @click="saveCourse">
              {{ editingCourse ? 'Cập nhật' : 'Thêm mới' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================ -->
    <!-- MODAL: QUẢN LÝ CHAPTER -->
    <!-- ================================ -->
    <div v-if="showChapterModal" class="modal-overlay chapter-modal-overlay" @click.self="closeChapterModal">
      <div class="modal modal-xl">
        <div class="modal-header">
          <h2>📚 Quản Lý Chương - {{ selectedCourse?.title }}</h2>
          <button class="btn-close" @click="closeChapterModal">×</button>
        </div>
        <div class="modal-body">
          <div v-if="chaptersLoading" class="loading">Đang tải dữ liệu...</div>
          <div v-else class="chapter-list-modal">
            <!-- Button tạo chapters tự động (chỉ hiện khi chưa có chapters) -->
            <div v-if="chapters.length === 0" class="generate-chapters-section">
              <p class="no-chapters-msg">Khóa học này chưa có chương nào.</p>
              <button class="btn-generate-chapters" @click="generateChaptersForCourse" :disabled="generatingChapters">
                <span v-if="generatingChapters">Đang tạo...</span>
                <span v-else>🚀 Tạo 4 Chương Mặc Định</span>
              </button>
            </div>
            <div
              v-for="(chapter, index) in chapters"
              :key="chapter.id"
              class="chapter-card"
              :class="{ 'chapter-published': chapter.status === 'published' }"
            >
              <div class="chapter-info">
                <div class="chapter-order">{{ index + 1 }}</div>
                <div class="chapter-details">
                  <div class="chapter-title-row">
                    <h3 class="chapter-title">{{ chapter.title }}</h3>
                    <span class="chapter-type-badge">{{ chapterTypeLabels[chapter.type] || chapter.type }}</span>
                  </div>
                  <p class="chapter-description">{{ chapter.description || 'Không có mô tả' }}</p>
                  <div class="chapter-meta">
                    <span class="meta-item">📚 {{ (chapter.lessons_count ?? chapter.lessonsCount) || 0 }} bài học</span>
                    <span class="meta-item" :class="chapter.is_free ? 'free' : 'premium'">
                      {{ chapter.is_free ? '✓ Miễn phí' : '🔒 Premium' }}
                    </span>
                    <span class="meta-item" :class="chapter.status === 'published' ? 'status-on' : 'status-off'">
                      {{ chapter.status === 'published' ? '● Đã xuất bản' : '○ Nháp' }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="chapter-actions">
                <button class="btn-action btn-manage" @click="goToBaiHoc(chapter, selectedCourse)" title="Quản lý bài học">
                  📚 Bài học
                </button>
                <button class="btn-action btn-edit" @click="openEditChapterModal(chapter)" title="Sửa">
                  ✏️
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeChapterModal">Đóng</button>
        </div>
      </div>
    </div>

    <!-- ================================ -->
    <!-- MODAL: SỬA CHAPTER -->
    <!-- ================================ -->
    <div v-if="showEditChapterModal" class="modal-overlay" @click.self="closeEditChapterModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Sửa Chương</h2>
          <button class="btn-close" @click="closeEditChapterModal">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề</label>
            <input v-model="editingChapter.title" type="text" placeholder="VD: Từ vựng" />
          </div>
          <div class="form-group">
            <label>Mô Tả</label>
            <textarea v-model="editingChapter.description" rows="3" placeholder="Mô tả chương học..."></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Trạng Thái</label>
              <select v-model="editingChapter.status">
                <option value="published">Đã xuất bản</option>
                <option value="draft">Nháp</option>
              </select>
            </div>
            <div class="form-group">
              <label>Miễn Phí</label>
              <select v-model="editingChapter.is_free">
                <option :value="true">✓ Miễn phí</option>
                <option :value="false">🔒 Premium</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeEditChapterModal">Huỷ</button>
          <button class="btn-primary" @click="saveChapter">Lưu</button>
        </div>
      </div>
    </div>

    <!-- ================================ -->
    <!-- MODAL: QUẢN LÝ LESSONS -->
    <!-- ================================ -->
    <div v-if="showLessonModal" class="modal-overlay" @click.self="closeLessonModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <h2>📚 Bài học: {{ currentChapter?.title }}</h2>
          <button class="btn-close" @click="closeLessonModal">×</button>
        </div>
        <div class="modal-body">
          <div class="lesson-header">
            <input
              v-model="newLessonTitle"
              type="text"
              placeholder="Nhập tên bài học mới..."
              class="lesson-input"
              @keyup.enter="addLesson"
            />
            <button class="btn-primary" @click="addLesson" :disabled="!newLessonTitle.trim()">
              + Thêm bài học
            </button>
          </div>

          <div v-if="lessonsLoading" class="loading">Đang tải bài học...</div>
          <div v-else-if="lessons.length === 0" class="empty-state">
            <p>Chưa có bài học nào. Thêm bài học đầu tiên!</p>
          </div>
          <div v-else class="lesson-list">
            <div v-for="(lesson, index) in lessons" :key="lesson.id" class="lesson-item" @click="goToBaiHocWithLesson(lesson)">
              <span class="lesson-order">{{ index + 1 }}</span>
              <span class="lesson-title">{{ lesson.title }}</span>
              <div class="lesson-actions">
                <button class="btn-action btn-edit-sm" @click.stop="openEditLessonModal(lesson)" title="Sửa">✏️</button>
                <button class="btn-action btn-delete-sm" @click.stop="deleteLesson(lesson.id)" title="Xóa">🗑️</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeLessonModal">Đóng</button>
        </div>
      </div>
    </div>

    <!-- ================================ -->
    <!-- MODAL: SỬA LESSON -->
    <!-- ================================ -->
    <div v-if="showEditLessonModal" class="modal-overlay" @click.self="closeEditLessonModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Sửa Bài Học</h2>
          <button class="btn-close" @click="closeEditLessonModal">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề Bài Học</label>
            <input v-model="editingLesson.title" type="text" placeholder="Nhập tiêu đề..." />
          </div>
          <div class="form-group">
            <label>Thứ Tự</label>
            <input v-model.number="editingLesson.order" type="number" min="1" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeEditLessonModal">Huỷ</button>
          <button class="btn-primary" @click="saveLesson">Lưu</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { CourseService, ChapterService, LessonService } from '../../../services/api.js'

const router = useRouter()

// ================================
// STATE
// ================================

const loading = ref(false)
const showModal = ref(false)
const editingCourse = ref(null)
const searchQuery = ref('')
const filterLevel = ref('')
const filterStatus = ref('')
const errorMessage = ref('')
const selectedIds = ref([])

// Ảnh mặc định khi không có thumbnail
const defaultThumbnail = 'https://via.placeholder.com/400x200/4f46e5/ffffff?text=No+Image'

// Form data
const formData = ref({
  title: '',
  slug: '',
  description: '',
  thumbnail: '',
  level: 'beginner',
  price: 0,
  status: 'draft'
})

// Mapping trình độ (level)
const levelLabels = {
  'beginner': 'Beginner - Sơ cấp',
  'intermediate': 'Intermediate - Trung cấp',
  'advanced': 'Advanced - Nâng cao'
}

// Mapping loại chương
const chapterTypeLabels = {
  vocabulary: 'Từ vựng',
  grammar: 'Ngữ pháp',
  listening: 'Luyện nghe',
  speaking: 'Luyện nói',
}

// Danh sách khóa học từ API
const khoaHocList = ref([])

// ================================
// CHAPTER MANAGEMENT
// ================================
const showChapterModal = ref(false)
const chapters = ref([])
const lessons = ref([])
const chaptersLoading = ref(false)
const lessonsLoading = ref(false)
const selectedCourse = ref(null)
const showLessonModal = ref(false)
const currentChapter = ref(null)
const newLessonTitle = ref('')
const showEditChapterModal = ref(false)
const editingChapter = ref({})
const showEditLessonModal = ref(false)
const editingLesson = ref({})
const generatingChapters = ref(false)

// ================================
// COMPUTED
// ================================

// Tổng doanh thu
const tongDoanhThu = computed(() => {
  const total = khoaHocList.value
    .filter(c => c.status === 'published')
    .reduce((acc, c) => acc + (parseFloat(c.price) || 0), 0)
  return formatPrice(total)
})

// Số khóa học đã xuất bản
const khoaHocPublished = computed(() => {
  return khoaHocList.value.filter(c => c.status === 'published').length
})

// Số khóa học nháp
const khoaHocDraft = computed(() => {
  return khoaHocList.value.filter(c => c.status === 'draft').length
})

// Danh sách khóa học đã lọc
const filteredKhoaHoc = computed(() => {
  let result = khoaHocList.value

  // Lọc theo từ khóa tìm kiếm
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      item.title.toLowerCase().includes(q) ||
      item.description?.toLowerCase().includes(q) ||
      item.slug?.toLowerCase().includes(q)
    )
  }

  // Lọc theo trình độ
  if (filterLevel.value) {
    result = result.filter(item => item.level === filterLevel.value)
  }

  // Lọc theo trạng thái
  if (filterStatus.value) {
    result = result.filter(item => item.status === filterStatus.value)
  }

  return result
})

// Kiểm tra chọn tất cả
const isAllSelected = computed(() => {
  return filteredKhoaHoc.value.length > 0 && 
         selectedIds.value.length === filteredKhoaHoc.value.length
})

// Toggle chọn tất cả
const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = filteredKhoaHoc.value.map(c => c.id)
  }
}

// ================================
// METHODS
// ================================

/**
 * Format giá tiền VND
 * @param {number} price
 * @returns {string}
 */
const formatPrice = (price) => {
  const numPrice = parseFloat(price) || 0
  if (numPrice === 0) return 'Miễn phí'
  return new Intl.NumberFormat('vi-VN', { 
    style: 'currency', 
    currency: 'VND',
    maximumFractionDigits: 0
  }).format(numPrice)
}

/**
 * Lấy nhãn trình độ
 * @param {string} level
 * @returns {string}
 */
const getLevelLabel = (level) => {
  return levelLabels[level] || level || 'Chưa xác định'
}

/**
 * Load danh sách khóa học từ API
 */
const fetchCourses = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await CourseService.getAll()
    const courseData = Array.isArray(data) ? data : (data?.data || [])
    khoaHocList.value = courseData.map(course => ({
      id: course.id,
      title: course.title,
      slug: course.slug,
      description: course.description,
      thumbnail: course.thumbnail,
      level: course.level || 'beginner',
      price: parseFloat(course.price) || 0,
      status: course.status || 'draft'
    }))
  } catch (error) {
    console.error('Lỗi khi tải danh sách khóa học:', error)
    errorMessage.value = 'Không thể tải danh sách khóa học. Vui lòng kiểm tra kết nối server.'
  } finally {
    loading.value = false
  }
}

/**
 * Mở modal thêm/sửa khóa học
 * @param {Object|null} course
 */
const openModal = (course = null) => {
  if (course) {
    // Chế độ sửa
    editingCourse.value = course.id
    formData.value = {
      title: course.title,
      slug: course.slug,
      description: course.description || '',
      thumbnail: course.thumbnail || '',
      level: course.level || 'beginner',
      price: course.price || 0,
      status: course.status || 'draft'
    }
  } else {
    // Chế độ thêm mới
    editingCourse.value = null
    resetForm()
  }
  showModal.value = true
}

/**
 * Đóng modal
 */
const closeModal = () => {
  showModal.value = false
  editingCourse.value = null
  resetForm()
}

/**
 * Reset form về trạng thái ban đầu
 */
const resetForm = () => {
  formData.value = {
    title: '',
    slug: '',
    description: '',
    thumbnail: '',
    level: 'beginner',
    price: 0,
    status: 'draft'
  }
}

/**
 * Lưu khóa học (tạo mới hoặc cập nhật)
 */
const saveCourse = async () => {
  // Validate tiêu đề bắt buộc
  if (!formData.value.title.trim()) {
    alert('Vui lòng nhập tiêu đề khóa học')
    return
  }

  try {
    const data = {
      title: formData.value.title.trim(),
      slug: formData.value.slug.trim() || undefined,
      description: formData.value.description.trim() || null,
      thumbnail: formData.value.thumbnail.trim() || null,
      level: formData.value.level,
      price: parseFloat(formData.value.price) || 0,
      status: formData.value.status
    }

    if (editingCourse.value) {
      // Cập nhật khóa học
      await CourseService.update(editingCourse.value, data)
    } else {
      // Tạo mới khóa học
      await CourseService.create(data)
    }

    // Tải lại danh sách
    await fetchCourses()
    closeModal()
  } catch (error) {
    console.error('Lỗi khi lưu khóa học:', error)
    const errorMsg = error.response?.data?.message || error.response?.data?.errors?.title?.[0] || 'Đã xảy ra lỗi khi lưu khóa học.'
    alert(errorMsg)
  }
}

/**
 * Xóa khóa học
 * @param {number} id
 */
const deleteCourse = async (id) => {
  if (!confirm('Bạn có chắc chắn muốn xoá khóa học này? Hành động này không thể hoàn tác.')) {
    return
  }

  try {
    await CourseService.delete(id)
    await fetchCourses()
  } catch (error) {
    console.error('Lỗi khi xóa khóa học:', error)
    alert('Đã xảy ra lỗi khi xóa khóa học. Vui lòng thử lại.')
  }
}

/**
 * Toggle trạng thái khóa học (publish/unpublish)
 * @param {Object} course
 */
const toggleStatus = async (course) => {
  const newStatus = course.status === 'published' ? 'draft' : 'published'

  try {
    await CourseService.changeStatus(course.id, { status: newStatus })
    await fetchCourses()
  } catch (error) {
    console.error('Lỗi khi thay đổi trạng thái:', error)
    alert('Đã xảy ra lỗi khi thay đổi trạng thái. Vui lòng thử lại.')
  }
}

// ================================
// CHAPTER MANAGEMENT
// ================================

const openChapterModal = async (course) => {
  selectedCourse.value = course
  chapters.value = []
  showChapterModal.value = true
  chaptersLoading.value = true
  try {
    chapters.value = await ChapterService.getByCourse(course.id)
  } catch (error) {
    console.error('Lỗi khi tải chapters:', error)
    alert('Không thể tải danh sách chương.')
  } finally {
    chaptersLoading.value = false
  }
}

const closeChapterModal = () => {
  showChapterModal.value = false
  selectedCourse.value = null
  chapters.value = []
  lessons.value = []
  showLessonModal.value = false
  currentChapter.value = null
  newLessonTitle.value = ''
  showEditChapterModal.value = false
  editingChapter.value = {}
  showEditLessonModal.value = false
  editingLesson.value = {}
  generatingChapters.value = false
}

/**
 * Tạo 4 chapters mặc định cho khóa học
 */
const generateChaptersForCourse = async () => {
  if (!selectedCourse.value) return

  if (!confirm('Bạn có muốn tạo 4 chapters mặc định (Từ vựng, Ngữ pháp, Nghe, Nói) cho khóa học này?')) {
    return
  }

  generatingChapters.value = true
  try {
    await CourseService.generateChapters(selectedCourse.value.id)
    alert('Đã tạo 4 chapters thành công!')
    // Tải lại danh sách chapters
    await openChapterModal(selectedCourse.value)
    // Tải lại danh sách khóa học để cập nhật số chapters
    await fetchCourses()
  } catch (error) {
    console.error('Lỗi khi tạo chapters:', error)
    const errorMsg = error.response?.data?.message || 'Đã xảy ra lỗi khi tạo chapters.'
    alert(errorMsg)
  } finally {
    generatingChapters.value = false
  }
}

// Chuyển đến trang quản lý bài học với chapter được chọn
const goToBaiHoc = (chapter, course) => {
  closeChapterModal()
  // Lưu thông tin vào sessionStorage để BaiHoc component đọc
  sessionStorage.setItem('selectedCourseId', course.id)
  sessionStorage.setItem('selectedChapterId', chapter.id)
  sessionStorage.setItem('selectedCourseTitle', course.title)
  sessionStorage.setItem('selectedChapterTitle', chapter.title)
  sessionStorage.removeItem('selectedLessonId')
  sessionStorage.removeItem('selectedLessonTitle')
  // Chuyển hướng đến trang Bài Học
  router.push('/admin/bai-hoc')
}

// Chuyển đến trang quản lý bài học với chapter VÀ lesson được chọn
const goToBaiHocWithLesson = (lesson) => {
  closeLessonModal()
  closeChapterModal()
  // Lưu thông tin vào sessionStorage để BaiHoc component đọc
  sessionStorage.setItem('selectedCourseId', selectedCourse.value.id)
  sessionStorage.setItem('selectedChapterId', currentChapter.value.id)
  sessionStorage.setItem('selectedLessonId', lesson.id)
  sessionStorage.setItem('selectedCourseTitle', selectedCourse.value.title)
  sessionStorage.setItem('selectedChapterTitle', currentChapter.value.title)
  sessionStorage.setItem('selectedLessonTitle', lesson.title)
  // Chuyển hướng đến trang Bài Học
  router.push('/admin/bai-hoc')
}

const openEditChapterModal = (chapter) => {
  editingChapter.value = { ...chapter }
  showEditChapterModal.value = true
}

const closeEditChapterModal = () => {
  showEditChapterModal.value = false
  editingChapter.value = {}
}

const saveChapter = async () => {
  if (!editingChapter.value.title?.trim()) {
    alert('Vui lòng nhập tiêu đề chương')
    return
  }
  try {
    await ChapterService.update(editingChapter.value.id, {
      title: editingChapter.value.title.trim(),
      description: editingChapter.value.description || null,
      status: editingChapter.value.status,
      is_free: editingChapter.value.is_free,
    })
    await openChapterModal(selectedCourse.value)
    closeEditChapterModal()
  } catch (error) {
    console.error('Lỗi khi lưu chương:', error)
    alert('Đã xảy ra lỗi khi lưu chương.')
  }
}

const openLessonModal = async (chapter) => {
  currentChapter.value = chapter
  lessons.value = []
  showLessonModal.value = true
  lessonsLoading.value = true
  try {
    lessons.value = await ChapterService.getLessons(chapter.id)
  } catch (error) {
    console.error('Lỗi khi tải bài học:', error)
  } finally {
    lessonsLoading.value = false
  }
}

const closeLessonModal = () => {
  showLessonModal.value = false
  currentChapter.value = null
  lessons.value = []
  newLessonTitle.value = ''
  showEditLessonModal.value = false
  editingLesson.value = {}
}

const addLesson = async () => {
  if (!newLessonTitle.value.trim()) return
  try {
    await LessonService.create(currentChapter.value.id, {
      title: newLessonTitle.value.trim()
    })
    newLessonTitle.value = ''
    lessons.value = await ChapterService.getLessons(currentChapter.value.id)
    await openChapterModal(selectedCourse.value)
  } catch (error) {
    console.error('Lỗi khi thêm bài học:', error)
    alert('Đã xảy ra lỗi khi thêm bài học.')
  }
}

const openEditLessonModal = (lesson) => {
  editingLesson.value = { ...lesson }
  showEditLessonModal.value = true
}

const closeEditLessonModal = () => {
  showEditLessonModal.value = false
  editingLesson.value = {}
}

const saveLesson = async () => {
  if (!editingLesson.value.title?.trim()) {
    alert('Vui lòng nhập tiêu đề bài học')
    return
  }
  try {
    await LessonService.update(editingLesson.value.id, {
      title: editingLesson.value.title.trim(),
      order: editingLesson.value.order,
    })
    lessons.value = await ChapterService.getLessons(currentChapter.value.id)
    await openChapterModal(selectedCourse.value)
    closeEditLessonModal()
  } catch (error) {
    console.error('Lỗi khi lưu bài học:', error)
    alert('Đã xảy ra lỗi khi lưu bài học.')
  }
}

const deleteLesson = async (id) => {
  if (!confirm('Bạn có chắc muốn xoá bài học này?')) return
  try {
    await LessonService.delete(id)
    lessons.value = await ChapterService.getLessons(currentChapter.value.id)
    await openChapterModal(selectedCourse.value)
  } catch (error) {
    console.error('Lỗi khi xóa bài học:', error)
    alert('Đã xảy ra lỗi khi xóa bài học.')
  }
}

// ================================
// LIFECYCLE
// ================================

// Gọi API khi component được mount
onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
/* ================================
   LAYOUT
   ================================ */
.khoa-hoc {
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

/* ================================
   FILTER BAR
   ================================ */
.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 200px;
  max-width: 400px;
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.filter-select {
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  min-width: 180px;
  cursor: pointer;
}

/* ================================
   STATS
   ================================ */
.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

@media (max-width: 1024px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #4f46e5;
}

.stat-label {
  font-size: 14px;
  color: #666;
  margin-top: 4px;
}

/* ================================
   ERROR MESSAGE
   ================================ */
.error-message {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 12px 16px;
  border-radius: 6px;
  margin-bottom: 16px;
}

/* ================================
   LOADING & EMPTY STATE
   ================================ */
.loading, .empty-state {
  text-align: center;
  padding: 40px;
  color: #666;
}

/* ================================
   COURSE TABLE
   ================================ */
.course-table-wrapper {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.course-table {
  width: 100%;
  border-collapse: collapse;
}

.course-table thead {
  background: #f8f9fa;
}

.course-table th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #495057;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #dee2e6;
}

.course-table tbody tr {
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.15s;
}

.course-table tbody tr:hover {
  background: #f8f9fa;
}

.course-table tbody tr:last-child {
  border-bottom: none;
}

.course-table td {
  padding: 12px 16px;
  font-size: 14px;
  color: #333;
  vertical-align: middle;
}

/* Cột checkbox */
.col-checkbox {
  width: 40px;
  text-align: center;
}

.col-checkbox input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

/* Cột ID */
.col-id {
  width: 60px;
  font-weight: 600;
  color: #6c757d;
}

/* Cột ảnh thumbnail */
.col-thumbnail {
  width: 80px;
}

.thumbnail-img {
  width: 60px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e9ecef;
}

/* Cột tiêu đề */
.col-title {
  min-width: 200px;
  max-width: 300px;
}

.course-title-cell {
  display: block;
  max-width: 280px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
  color: #212529;
}

/* Cột slug */
.col-slug {
  min-width: 150px;
}

.slug-code {
  background: #f1f3f5;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  color: #495057;
  font-family: 'Courier New', monospace;
}

/* Cột trình độ */
.col-level {
  width: 140px;
}

.level-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.level-beginner {
  background: #e3f2fd;
  color: #1565c0;
}

.level-intermediate {
  background: #fff3e0;
  color: #e65100;
}

.level-advanced {
  background: #f3e5f5;
  color: #7b1fa2;
}

/* Cột giá */
.col-price {
  width: 120px;
}

.price-value {
  font-weight: 600;
  color: #d32f2f;
}

.price-value.free {
  color: #2e7d32;
}

/* Cột trạng thái */
.col-status {
  width: 120px;
}


.status-published {
  background: #d4edda;
  color: #155724;
}

.status-draft {
  background: #fff3cd;
  color: #856404;
}

/* Cột thao tác */
.col-actions {
  width: 140px;
}

.action-buttons {
  display: flex;
  gap: 6px;
}

.btn-action {
  border: none;
  padding: 6px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: opacity 0.15s;
}

.btn-action:hover {
  opacity: 0.8;
}

.btn-action.btn-publish {
  background: #28a745;
  color: white;
}

.btn-action.btn-unpublish {
  background: #6c757d;
  color: white;
}

.btn-action.btn-edit {
  background: #17a2b8;
  color: white;
}

.btn-action.btn-chapters {
  background: #7c3aed;
  color: white;
}

.btn-action.btn-delete {
  background: #dc3545;
  color: white;
}

/* Pagination info */
.pagination-info {
  padding: 12px 16px;
  text-align: right;
  font-size: 13px;
  color: #6c757d;
  background: #f8f9fa;
  border-top: 1px solid #dee2e6;
}

/* Responsive table */
@media (max-width: 1200px) {
  .col-slug,
  .course-table th:nth-child(4),
  .course-table td:nth-child(4) {
    display: none;
  }
}

@media (max-width: 768px) {
  .course-table-wrapper {
    overflow-x: auto;
  }
  
  .course-table {
    min-width: 900px;
  }
}

/* ================================
   BUTTONS
   ================================ */
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

/* ================================
   MODAL
   ================================ */
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
  max-width: 600px;
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
  padding: 0;
}

.btn-close:hover {
  color: #333;
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

/* ================================
   FORM
   ================================ */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
  }
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

.form-group .required {
  color: #ef4444;
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

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.form-hint {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #9ca3af;
}

/* ================================
   CHAPTER MODAL
   ================================ */
.chapter-modal-overlay {
  z-index: 1100;
}

.modal.modal-xl {
  max-width: 900px;
}

.modal.modal-lg {
  max-width: 700px;
}

.chapter-list-modal {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Generate chapters section */
.generate-chapters-section {
  text-align: center;
  padding: 40px 20px;
  background: #f9fafb;
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  margin-bottom: 16px;
}

.no-chapters-msg {
  color: #6b7280;
  margin-bottom: 16px;
  font-size: 15px;
}

.btn-generate-chapters {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 14px 28px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
}

.btn-generate-chapters:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
}

.btn-generate-chapters:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.chapter-card {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  transition: box-shadow 0.2s;
}

.chapter-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chapter-card.chapter-published {
  border-left: 4px solid #4f46e5;
}

.chapter-info {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  flex: 1;
}

.chapter-order {
  width: 32px;
  height: 32px;
  background: #e5e7eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #4f46e5;
  font-size: 14px;
  flex-shrink: 0;
}

.chapter-details {
  flex: 1;
}

.chapter-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 4px;
}

.chapter-title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}

.chapter-type-badge {
  background: #ede9fe;
  color: #7c3aed;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 500;
}

.chapter-description {
  margin: 0 0 6px 0;
  font-size: 13px;
  color: #6b7280;
}

.chapter-meta {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.meta-item {
  font-size: 12px;
  color: #6b7280;
}

.meta-item.free {
  color: #16a34a;
}

.meta-item.premium {
  color: #9333ea;
}

.meta-item.status-on {
  color: #16a34a;
}

.meta-item.status-off {
  color: #d97706;
}

.chapter-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.btn-action.btn-manage {
  background: #4f46e5;
  color: white;
  padding: 7px 12px;
  font-size: 13px;
  border-radius: 6px;
}

.btn-action.btn-edit-sm {
  background: #17a2b8;
  color: white;
  padding: 4px 8px;
  font-size: 12px;
  border-radius: 4px;
}

.btn-action.btn-delete-sm {
  background: #dc3545;
  color: white;
  padding: 4px 8px;
  font-size: 12px;
  border-radius: 4px;
}

/* ================================
   LESSON MODAL
   ================================ */
.lesson-header {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.lesson-input {
  flex: 1;
  padding: 10px 14px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
  font-family: inherit;
}

.lesson-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.lesson-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #f3f4f6;
}

.lesson-order {
  width: 24px;
  height: 24px;
  background: #e5e7eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  color: #4b5563;
  flex-shrink: 0;
}

.lesson-title {
  flex: 1;
  font-size: 14px;
  color: #111827;
}

.lesson-actions {
  display: flex;
  gap: 6px;
}

.btn-primary:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}
</style>
