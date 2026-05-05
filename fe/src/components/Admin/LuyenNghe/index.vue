<template>
  <div class="luyen-nghe">
    <!-- Global Audio Player (ẩn) -->
    <audio
      v-if="currentAudioSrc"
      ref="globalAudioRef"
      :src="currentAudioSrc"
      @timeupdate="onTimeUpdate"
      @loadedmetadata="onLoadedMetadata"
      @ended="onAudioEnded"
      @error="onAudioError"
    ></audio>

    <div class="header">
      <h1>Quản Lý Luyện Nghe</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Bài Nghe
      </button>
    </div>

    <!-- Breadcrumb & Info -->
    <div v-if="currentCourseTitle || currentChapterTitle || currentLessonTitle" class="breadcrumb-info">
      <span v-if="currentCourseTitle">📚 {{ currentCourseTitle }}</span>
      <span v-if="currentChapterTitle"> / {{ currentChapterTitle }}</span>
      <span v-if="currentLessonTitle"> / {{ currentLessonTitle }}</span>
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
          placeholder="Tìm kiếm bài nghe..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ listenings.length }}</div>
        <div class="stat-label">Tổng Bài Nghe</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ lessons.length }}</div>
        <div class="stat-label">Bài Học</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredListenings.length === 0" class="empty-state">
      <p>Không tìm thấy bài nghe nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th>Tiêu Đề</th>
          <th>Nghe Thử</th>
          <th>Script (TTS)</th>
          <th style="width: 120px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="listening in filteredListenings" :key="listening.id">
          <td>{{ listening.id }}</td>
          <td>
            <span class="title-text">{{ listening.title }}</span>
          </td>
          <td>
            <!-- Audio Player -->
            <div class="audio-player">
              <div v-if="currentPlayingId === listening.id" class="player-controls active">
                <button class="player-btn" @click="togglePlay">
                  {{ isPlaying ? '⏸️' : '▶️' }}
                </button>
                <div class="player-progress">
                  <div class="progress-bar" @click="seekAudio">
                    <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
                  </div>
                  <div class="time-display">
                    {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
                  </div>
                </div>
                <button class="player-btn stop-btn" @click="stopAudio" title="Dừng">⏹️</button>
              </div>
              
              <div v-else class="player-controls">
                <button 
                  class="player-btn play-btn" 
                  @click="playListening(listening)" 
                  :disabled="!listening?.audio && !listening?.script"
                  :title="listening?.audio ? 'Phát audio file' : (listening?.script ? 'Phát TTS' : 'Không có nội dung')"
                >
                  {{ listening?.audio ? '🎵' : (listening?.script ? '🔊' : '➖') }}
                </button>
                <span class="player-hint">
                  {{ listening?.audio ? 'Audio' : (listening?.script ? 'TTS' : 'Trống') }}
                </span>
              </div>
            </div>
          </td>
          <td>
            <div class="script-cell">
              <span v-if="listening?.script" class="script-preview" :title="listening.script">
                {{ listening.script.length > 60 ? listening.script.substring(0, 60) + '...' : listening.script }}
              </span>
              <span v-else class="no-script">-</span>
              <button 
                v-if="listening?.script" 
                class="btn-speak-tiny" 
                @click="speakScript(listening.script)" 
                title="Nghe thử TTS"
              >
                🔊
              </button>
            </div>
          </td>
          <td class="actions">
            <button class="btn-exercise" @click="openExerciseModal(listening)" title="Quản lý bài tập">
              📝
            </button>
            <button class="btn-edit" @click="editListening(listening)" title="Sửa">
              <span>✏️</span>
            </button>
            <button class="btn-delete" @click="deleteListening(listening.id)" title="Xóa">
              <span>🗑️</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Thêm/Sửa Listening -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingListening ? 'Sửa Bài Nghe' : 'Thêm Bài Nghe Mới' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Bài Học <span class="required">*</span></label>
            <select v-model="listeningForm.lesson_id">
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
            <input v-model="listeningForm.title" type="text" placeholder="Nhập tiêu đề bài nghe" />
            <span v-if="errors.title" class="error-text">{{ errors.title[0] }}</span>
          </div>
          <div class="form-group">
            <label>Script (TTS)</label>
            <textarea v-model="listeningForm.script" rows="4" placeholder="Nhập nội dung để phát TTS (Text-to-Speech)"></textarea>
            <small class="help-text">Nội dung này sẽ được chuyển thành giọng nói tự động.</small>
          </div>
          <div class="form-group">
            <label>Audio File</label>
            <div class="file-upload-wrapper">
              <input type="file" @change="handleFileUpload" accept="audio/*" class="file-input" />
              <div v-if="listeningForm.audioFile" class="file-name">
                📁 {{ listeningForm.audioFile.name }}
              </div>
              <div v-else-if="editingListening && currentEditingListening?.audio" class="existing-file">
                📁 File hiện tại: {{ getFileName(currentEditingListening.audio) }}
              </div>
              <small v-else class="help-text">Chấp nhận: mp3, wav, m4a, ogg, webm (tối đa 50MB)</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Hủy</button>
          <button class="btn-primary" @click="saveListening">
            {{ editingListening ? 'Cập Nhật' : 'Thêm Mới' }}
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
          <p>Bạn có chắc chắn muốn xóa bài nghe này?</p>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showDeleteConfirm = false">Hủy</button>
          <button class="btn-danger" @click="confirmDelete">Xóa</button>
        </div>
      </div>
    </div>

    <!-- Modal Quản Lý Bài Tập -->
    <div v-if="showExerciseModal" class="modal-overlay" @click.self="closeExerciseModal">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>📝 Bài Tập: {{ currentListeningForExercise?.title }}</h2>
          <button class="btn-close" @click="closeExerciseModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="exercise-header">
            <button class="btn-primary" @click="openAddExercise">
              <span>+</span> Thêm Câu Hỏi
            </button>
            <span class="exercise-count">{{ exercises.length }} câu hỏi</span>
          </div>

          <!-- Form Thêm/Sửa Câu Hỏi -->
          <div v-if="showExerciseForm" class="exercise-form">
            <h3>{{ editingExercise ? 'Sửa Câu Hỏi' : 'Thêm Câu Hỏi Mới' }}</h3>
            <div class="form-group">
              <label>Câu Hỏi <span class="required">*</span></label>
              <textarea v-model="exerciseForm.question" rows="2" placeholder="Nhập câu hỏi..."></textarea>
              <span v-if="exerciseErrors.question" class="error-text">{{ exerciseErrors.question[0] }}</span>
            </div>
            <div class="form-group">
              <label>Loại Câu Hỏi</label>
              <select v-model="exerciseForm.type" @change="onTypeChange">
                <option value="multiple_choice">Trắc nghiệm</option>
                <option value="fill_blank">Điền từ</option>
                <option value="true_false">Đúng/Sai</option>
              </select>
            </div>
            
            <!-- Options cho Trắc nghiệm -->
            <div v-if="exerciseForm.type === 'multiple_choice'" class="options-group">
              <label> Các Lựa Chọn</label>
              <div v-for="(opt, idx) in exerciseForm.options" :key="idx" class="option-row">
                <input type="radio" :name="'correct'" :checked="exerciseForm.correct_answer === opt" @change="exerciseForm.correct_answer = opt" />
                <input v-model="exerciseForm.options[idx]" type="text" :placeholder="'Lựa chọn ' + (idx + 1)" />
              </div>
            </div>

            <!-- Options cho Đúng/Sai -->
            <div v-if="exerciseForm.type === 'true_false'" class="options-group">
              <label>Đáp Án Đúng</label>
              <div class="radio-group">
                <label class="radio-label">
                  <input type="radio" v-model="exerciseForm.correct_answer" value="true" />
                  <span>Đúng (True)</span>
                </label>
                <label class="radio-label">
                  <input type="radio" v-model="exerciseForm.correct_answer" value="false" />
                  <span>Sai (False)</span>
                </label>
              </div>
            </div>

            <!-- Đáp án cho Điền từ -->
            <div v-if="exerciseForm.type === 'fill_blank'" class="form-group">
              <label>Đáp Án Đúng <span class="required">*</span></label>
              <input v-model="exerciseForm.correct_answer" type="text" placeholder="Nhập từ/cụm từ đúng" />
            </div>

            <div class="form-group">
              <label>Giải Thích</label>
              <textarea v-model="exerciseForm.explanation" rows="2" placeholder="Giải thích đáp án..."></textarea>
            </div>
            <div class="form-actions">
              <button class="btn-secondary" @click="cancelExerciseForm">Hủy</button>
              <button class="btn-primary" @click="saveExercise">{{ editingExercise ? 'Cập Nhật' : 'Thêm Mới' }}</button>
            </div>
          </div>

          <!-- Danh sách câu hỏi -->
          <div v-else class="exercise-list">
            <div v-if="exercises.length === 0" class="empty-exercises">
              <p>Chưa có câu hỏi nào. Click "Thêm Câu Hỏi" để bắt đầu.</p>
            </div>
            <div v-else class="exercise-items">
              <div v-for="(ex, idx) in exercises" :key="ex.id" class="exercise-item">
                <div class="exercise-item-header">
                  <span class="exercise-number">Câu {{ idx + 1 }}</span>
                  <span class="exercise-type">{{ getTypeLabel(ex.type) }}</span>
                </div>
                <div class="exercise-question">{{ ex.question }}</div>
                <div v-if="ex.type === 'multiple_choice' && ex.options" class="exercise-options">
                  <div v-for="(opt, i) in ex.options" :key="i" :class="['option', { correct: opt === ex.correct_answer }]">
                    {{ opt }}
                  </div>
                </div>
                <div v-if="ex.type === 'true_false'" class="exercise-answer">
                  Đáp án: <strong>{{ ex.correct_answer === 'true' ? 'Đúng' : 'Sai' }}</strong>
                </div>
                <div v-if="ex.type === 'fill_blank'" class="exercise-answer">
                  Đáp án: <strong>{{ ex.correct_answer }}</strong>
                </div>
                <div v-if="ex.explanation" class="exercise-explanation">
                  💡 {{ ex.explanation }}
                </div>
                <div class="exercise-actions">
                  <button class="btn-edit-small" @click="editExercise(ex)">Sửa</button>
                  <button class="btn-delete-small" @click="deleteExercise(ex.id)">Xóa</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { CourseService, ChapterService, LessonService, ListeningService, ListeningExerciseService } from '../../../services/api.js'

const loading = ref(false)
const courses = ref([])
const chapters = ref([])
const lessons = ref([])
const listenings = ref([])
const allListenings = ref([])

// Exercise State
const showExerciseModal = ref(false)
const currentListeningForExercise = ref(null)
const exercises = ref([])
const showExerciseForm = ref(false)
const editingExercise = ref(null)
const exerciseErrors = ref({})

const exerciseForm = ref({
  question: '',
  type: 'multiple_choice',
  options: ['', '', '', ''],
  correct_answer: '',
  explanation: '',
})

// Loại chapter mà trang này quản lý
const CHAPTER_TYPE = 'listening'

// Base URL cho audio
const AUDIO_BASE_URL = 'http://localhost:8000'

const selectedCourseId = ref('')
const selectedChapterId = ref('')
const selectedLessonId = ref('')
const searchQuery = ref('')
const isRestoringFromSession = ref(false)

// Thông tin hiển thị từ sessionStorage
const currentCourseTitle = ref('')
const currentChapterTitle = ref('')
const currentLessonTitle = ref('')

const showModal = ref(false)
const editingListening = ref(null)
const currentEditingListening = ref(null) // Lưu object đang sửa
const showDeleteConfirm = ref(false)
const deleteListeningId = ref(null)
const errors = ref({})

const listeningForm = ref({
  lesson_id: '',
  title: '',
  script: '',
  audioFile: null
})

// Audio Player State
const globalAudioRef = ref(null)
const currentPlayingId = ref(null)
const currentAudioSrc = ref('')
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const progressPercent = ref(0)
const currentListening = ref(null)

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
    await fetchLessons(newVal)
  } else {
    // Khi xóa chapter, load tất cả lessons của tất cả chapters
    await fetchAllLessons()
    await fetchListenings()
  }
})

// Watch để reload listenings khi selectedLessonId thay đổi
watch(selectedLessonId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal && !isRestoringFromSession.value) {
    await fetchListenings()
  }
})

// Computed
const filteredListenings = computed(() => {
  let result = allListenings.value

  // Lọc theo khóa học
  if (selectedCourseId.value) {
    result = result.filter(l => l.lesson?.course_id === parseInt(selectedCourseId.value))
  }

  // Lọc theo chương
  if (selectedChapterId.value) {
    result = result.filter(l => l.lesson?.chapter_id === parseInt(selectedChapterId.value))
  }

  // Lọc theo bài học
  if (selectedLessonId.value) {
    result = result.filter(l => l.lesson_id === parseInt(selectedLessonId.value))
  }

  // Lọc theo từ khóa tìm kiếm
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(l =>
      (l.title && l.title.toLowerCase().includes(q)) ||
      (l.script && l.script.toLowerCase().includes(q))
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
    const savedCourseId = sessionStorage.getItem('luyenNghe_selectedCourseId')
    const savedChapterId = sessionStorage.getItem('luyenNghe_selectedChapterId')
    const savedLessonId = sessionStorage.getItem('luyenNghe_selectedLessonId')

    // Lấy thông tin hiển thị
    currentCourseTitle.value = sessionStorage.getItem('luyenNghe_selectedCourseTitle') || ''
    currentChapterTitle.value = sessionStorage.getItem('luyenNghe_selectedChapterTitle') || ''
    currentLessonTitle.value = sessionStorage.getItem('luyenNghe_selectedLessonTitle') || ''

    if (savedCourseId) {
      isRestoringFromSession.value = true

      // Load chapters trước
      const chaptersData = await ChapterService.getByCourse(savedCourseId)
      chapters.value = chaptersData.filter(c => c.type === CHAPTER_TYPE)

      // Set các giá trị đã lưu
      selectedCourseId.value = savedCourseId
      selectedChapterId.value = savedChapterId || ''
      selectedLessonId.value = savedLessonId || ''

      // Xóa sessionStorage
      sessionStorage.removeItem('luyenNghe_selectedCourseId')
      sessionStorage.removeItem('luyenNghe_selectedChapterId')
      sessionStorage.removeItem('luyenNghe_selectedCourseTitle')
      sessionStorage.removeItem('luyenNghe_selectedChapterTitle')
      sessionStorage.removeItem('luyenNghe_selectedLessonId')
      sessionStorage.removeItem('luyenNghe_selectedLessonTitle')

      await nextTick()
      isRestoringFromSession.value = false

      // Load lessons và listenings
      if (savedChapterId) {
        await fetchLessons(savedChapterId)
      }
      await fetchListenings()
    } else {
      // Load tất cả chapters của tất cả courses
      await fetchAllChapters()
      await fetchAllLessons()
      await fetchListenings()
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

const fetchAllLessons = async () => {
  try {
    const allLessons = []
    for (const chapter of chapters.value) {
      const lessonsData = await LessonService.getByChapter(chapter.id)
      allLessons.push(...lessonsData.map(l => ({
        ...l,
        course_id: parseInt(selectedCourseId.value) || 0,
        chapter_id: chapter.id
      })))
    }
    lessons.value = allLessons
  } catch (error) {
    console.error('Lỗi khi tải bài học:', error)
  }
}

const fetchChapters = async (courseId) => {
  if (!courseId) {
    chapters.value = []
    return
  }
  try {
    const data = await ChapterService.getByCourse(courseId)
    // Lọc chỉ lấy chapter có type = 'listening'
    chapters.value = data.filter(c => c.type === CHAPTER_TYPE)
  } catch (error) {
    console.error('Lỗi khi tải chương:', error)
  }
}

const fetchLessons = async (chapterId) => {
  if (!chapterId) {
    // Nếu không có chapterId, load tất cả lessons của tất cả chapters
    await fetchAllLessons()
    return
  }
  try {
    const data = await LessonService.getByChapter(chapterId)
    // Thêm course_id vào mỗi lesson để filter theo course
    lessons.value = data.map(l => ({
      ...l,
      course_id: parseInt(selectedCourseId.value) || 0,
      chapter_id: parseInt(chapterId)
    }))
    await fetchListenings()
  } catch (error) {
    console.error('Lỗi khi tải bài học:', error)
  }
}

const fetchListenings = async () => {
  loading.value = true
  try {
    const allListeningData = []
    
    // Lấy danh sách lessons cần fetch
    const lessonsToFetch = selectedLessonId.value 
      ? lessons.value.filter(l => l.id === parseInt(selectedLessonId.value))
      : lessons.value

    for (const lesson of lessonsToFetch) {
      try {
        const data = await ListeningService.getByLesson(lesson.id)
        const listeningWithLesson = data.map(l => ({ 
          ...l, 
          lesson: { ...lesson, course_id: selectedCourseId.value, chapter_id: selectedChapterId.value }
        }))
        allListeningData.push(...listeningWithLesson)
      } catch (e) {
        // Lesson có thể không có listening
      }
    }
    allListenings.value = allListeningData
    listenings.value = allListeningData
  } catch (error) {
    console.error('Lỗi khi tải bài nghe:', error)
  } finally {
    loading.value = false
  }
}

const onCourseChange = () => {
  selectedChapterId.value = ''
  selectedLessonId.value = ''
  lessons.value = []
  fetchChapters(selectedCourseId.value)
}

const onChapterChange = () => {
  selectedLessonId.value = ''
  fetchLessons(selectedChapterId.value)
}

const onLessonChange = () => {
  // Filter được xử lý bằng computed
}

// Helper: lấy lessons theo course
const getLessonsByCourse = (courseId) => {
  if (!courseId) return []
  return lessons.value.filter(l => l.course_id === courseId)
}

// Helper: lấy tên file từ path
const getFileName = (path) => {
  if (!path) return ''
  return path.split('/').pop()
}

const openAddModal = () => {
  editingListening.value = null
  errors.value = {}
  listeningForm.value = {
    lesson_id: selectedLessonId.value || '',
    title: '',
    script: '',
    audioFile: null
  }
  showModal.value = true
}

const editListening = (listening) => {
  console.log('Edit listening:', listening)
  editingListening.value = listening.id
  currentEditingListening.value = listening // Lưu object để template dùng
  errors.value = {}
  listeningForm.value = {
    lesson_id: listening.lesson_id,
    title: listening.title,
    script: listening.script || '',
    audioFile: null
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingListening.value = null
  currentEditingListening.value = null
  errors.value = {}
}

const handleFileUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    listeningForm.value.audioFile = file
  }
}

const saveListening = async () => {
  errors.value = {}
  try {
    const fd = new FormData()
    fd.append('title', listeningForm.value.title)
    fd.append('script', listeningForm.value.script)

    if (listeningForm.value.audioFile) {
      fd.append('audio', listeningForm.value.audioFile)
    }

    if (editingListening.value) {
      console.log('Updating listening ID:', editingListening.value, 'with data:', listeningForm.value)
      const result = await ListeningService.update(editingListening.value, fd)
      console.log('Update result:', result)
      // Tạo lesson object với course_id và chapter_id
      const updatedListening = {
        ...result.data,
        lesson: {
          id: result.data.lesson_id,
          course_id: parseInt(selectedCourseId.value) || (result.data.lesson?.course_id),
          chapter_id: parseInt(selectedChapterId.value) || (result.data.lesson?.chapter_id)
        }
      }
      const index = allListenings.value.findIndex(l => l.id === editingListening.value)
      console.log('Index found:', index)
      if (index !== -1) {
        allListenings.value[index] = updatedListening
      }
      alert('Cập nhật bài nghe thành công!')
    } else {
      if (!listeningForm.value.lesson_id) {
        errors.value.lesson_id = ['Vui lòng chọn bài học']
        return
      }
      const result = await ListeningService.create(listeningForm.value.lesson_id, fd)
      const newListening = {
        ...result.data,
        lesson: {
          id: result.data.lesson_id,
          course_id: parseInt(selectedCourseId.value),
          chapter_id: parseInt(selectedChapterId.value)
        }
      }
      allListenings.value.push(newListening)
      alert('Thêm bài nghe thành công!')
    }
    closeModal()
  } catch (error) {
    console.error('Save listening error:', error)
    if (error.response && error.response.data && error.response.data.errors) {
      errors.value = error.response.data.errors
    } else {
      alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
    }
  }
}

const deleteListening = (id) => {
  deleteListeningId.value = id
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    console.log('Deleting listening ID:', deleteListeningId.value)
    await ListeningService.delete(deleteListeningId.value)
    allListenings.value = allListenings.value.filter(l => l.id !== deleteListeningId.value)
    showDeleteConfirm.value = false
    deleteListeningId.value = null
    alert('Xóa bài nghe thành công!')
  } catch (error) {
    console.error('Delete error:', error)
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

// Text-to-Speech cho script
const speakScript = (script) => {
  if (!script) return
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel()
    const utterance = new SpeechSynthesisUtterance(script)
    utterance.lang = 'en-US'
    utterance.rate = 0.9
    utterance.pitch = 1
    window.speechSynthesis.speak(utterance)
  } else {
    alert('Trình duyệt không hỗ trợ Text-to-Speech')
  }
}

// Format thời gian
const formatTime = (seconds) => {
  if (isNaN(seconds) || seconds === 0) return '0:00'
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

// Audio Player Functions
const playListening = (listening) => {
  if (!listening) return
  
  // Dừng audio hiện tại nếu đang phát bài khác
  if (currentPlayingId.value !== null && currentPlayingId.value !== listening.id) {
    stopAudio()
  }
  
  if (listening.audio) {
    // Ưu tiên phát audio file - ghép full URL
    currentListening.value = listening
    currentPlayingId.value = listening.id
    // Ghép URL đầy đủ nếu audio bắt đầu bằng /storage/
    currentAudioSrc.value = listening.audio.startsWith('http') 
      ? listening.audio 
      : AUDIO_BASE_URL + listening.audio
    nextTick(() => {
      if (globalAudioRef.value) {
        globalAudioRef.value.play().catch(err => {
          console.error('Lỗi phát audio:', err)
        })
        isPlaying.value = true
      }
    })
  } else if (listening.script) {
    // Fallback TTS
    currentListening.value = listening
    currentPlayingId.value = listening.id
    currentAudioSrc.value = ''
    speakScript(listening.script)
  } else {
    alert('Bài nghe này chưa có nội dung để phát.')
  }
}

const togglePlay = () => {
  if (!globalAudioRef.value) return
  
  if (isPlaying.value) {
    globalAudioRef.value.pause()
    isPlaying.value = false
  } else {
    globalAudioRef.value.play().catch(err => {
      console.error('Lỗi phát audio:', err)
    })
    isPlaying.value = true
  }
}

const stopAudio = () => {
  if (globalAudioRef.value) {
    globalAudioRef.value.pause()
    globalAudioRef.value.currentTime = 0
  }
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel()
  }
  currentPlayingId.value = null
  currentAudioSrc.value = ''
  currentListening.value = null
  isPlaying.value = false
  currentTime.value = 0
  duration.value = 0
  progressPercent.value = 0
}

const onTimeUpdate = () => {
  if (globalAudioRef.value) {
    currentTime.value = globalAudioRef.value.currentTime
    progressPercent.value = duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0
  }
}

const onLoadedMetadata = () => {
  if (globalAudioRef.value) {
    duration.value = globalAudioRef.value.duration
  }
}

const onAudioEnded = () => {
  isPlaying.value = false
  currentTime.value = 0
  progressPercent.value = 0
}

const onAudioError = (e) => {
  console.error('Lỗi load audio:', e)
  alert('Không thể phát audio này.')
  stopAudio()
}

const seekAudio = (e) => {
  if (!globalAudioRef.value || duration.value === 0) return
  const rect = e.target.getBoundingClientRect()
  const percent = (e.clientX - rect.left) / rect.width
  globalAudioRef.value.currentTime = percent * duration.value
}

// ============ EXERCISE FUNCTIONS ============

const getTypeLabel = (type) => {
  const labels = {
    'multiple_choice': 'Trắc nghiệm',
    'fill_blank': 'Điền từ',
    'true_false': 'Đúng/Sai'
  }
  return labels[type] || type
}

const openExerciseModal = async (listening) => {
  currentListeningForExercise.value = listening
  exercises.value = []
  showExerciseForm.value = false
  showExerciseModal.value = true
  
  // Fetch exercises
  await fetchExercises(listening.id)
}

const closeExerciseModal = () => {
  showExerciseModal.value = false
  currentListeningForExercise.value = null
  exercises.value = []
  showExerciseForm.value = false
  editingExercise.value = null
  exerciseErrors.value = {}
}

const fetchExercises = async (listeningId) => {
  try {
    const data = await ListeningExerciseService.getByListening(listeningId)
    exercises.value = data
  } catch (error) {
    console.error('Lỗi khi tải bài tập:', error)
  }
}

const openAddExercise = () => {
  editingExercise.value = null
  exerciseErrors.value = {}
  exerciseForm.value = {
    question: '',
    type: 'multiple_choice',
    options: ['', '', '', ''],
    correct_answer: '',
    explanation: '',
  }
  showExerciseForm.value = true
}

const editExercise = (exercise) => {
  editingExercise.value = exercise.id
  exerciseErrors.value = {}
  exerciseForm.value = {
    question: exercise.question,
    type: exercise.type,
    options: exercise.options || ['', '', '', ''],
    correct_answer: exercise.correct_answer,
    explanation: exercise.explanation || '',
  }
  showExerciseForm.value = true
}

const cancelExerciseForm = () => {
  showExerciseForm.value = false
  editingExercise.value = null
  exerciseErrors.value = {}
}

const onTypeChange = () => {
  exerciseForm.value.correct_answer = ''
  if (exerciseForm.value.type === 'multiple_choice') {
    exerciseForm.value.options = ['', '', '', '']
  }
}

const saveExercise = async () => {
  exerciseErrors.value = {}
  try {
    // Validate
    if (!exerciseForm.value.question) {
      exerciseErrors.value.question = ['Vui lòng nhập câu hỏi']
      return
    }
    
    if (exerciseForm.value.type === 'multiple_choice') {
      const validOptions = exerciseForm.value.options.filter(o => o.trim())
      if (validOptions.length < 2) {
        exerciseErrors.value.question = ['Cần ít nhất 2 lựa chọn']
        return
      }
      if (!exerciseForm.value.correct_answer) {
        exerciseErrors.value.question = ['Vui lòng chọn đáp án đúng']
        return
      }
    }

    const data = {
      question: exerciseForm.value.question,
      type: exerciseForm.value.type,
      options: exerciseForm.value.type === 'multiple_choice' 
        ? exerciseForm.value.options.filter(o => o.trim()) 
        : null,
      correct_answer: exerciseForm.value.correct_answer,
      explanation: exerciseForm.value.explanation,
    }

    if (editingExercise.value) {
      const result = await ListeningExerciseService.update(editingExercise.value, data)
      const index = exercises.value.findIndex(e => e.id === editingExercise.value)
      if (index !== -1) {
        exercises.value[index] = result.data
      }
      alert('Cập nhật câu hỏi thành công!')
    } else {
      const result = await ListeningExerciseService.create(
        currentListeningForExercise.value.id, 
        data
      )
      exercises.value.push(result.data)
      alert('Thêm câu hỏi thành công!')
    }
    
    cancelExerciseForm()
  } catch (error) {
    if (error.response && error.response.data && error.response.data.errors) {
      exerciseErrors.value = error.response.data.errors
    } else {
      alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
    }
  }
}

const deleteExercise = async (id) => {
  if (!confirm('Bạn có chắc chắn muốn xóa câu hỏi này?')) return
  
  try {
    await ListeningExerciseService.delete(id)
    exercises.value = exercises.value.filter(e => e.id !== id)
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
.luyen-nghe {
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

.breadcrumb-info {
  background: #fef3c7;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
  color: #92400e;
  font-weight: 500;
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

.title-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.title-text {
  font-weight: 600;
  color: #333;
}

.script-preview {
  color: #666;
  font-size: 13px;
}

.audio-badge {
  padding: 4px 8px;
  background: #d1fae5;
  color: #065f46;
  border-radius: 4px;
  font-size: 12px;
}

.no-audio {
  color: #999;
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

.btn-speak, .btn-play {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-speak:hover, .btn-play:hover {
  background: #2563eb;
}

.btn-speak:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-edit {
  background: #10b981;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-edit:hover {
  background: #059669;
}

.btn-delete {
  background: #ef4444;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
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
}

.help-text {
  display: block;
  color: #666;
  font-size: 12px;
  margin-top: 4px;
}

.file-upload-wrapper {
  border: 2px dashed #ddd;
  border-radius: 6px;
  padding: 20px;
  text-align: center;
  background: #f9f9f9;
}

.file-input {
  margin-bottom: 8px;
}

.file-name, .existing-file {
  color: #10b981;
  font-size: 13px;
  margin-top: 8px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}

/* Audio Player Styles */
.audio-player {
  display: flex;
  align-items: center;
  gap: 8px;
}

.player-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.player-controls.active {
  flex: 1;
}

.player-btn {
  background: #4f46e5;
  color: white;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.2s;
}

.player-btn:hover {
  background: #4338ca;
  transform: scale(1.05);
}

.player-btn.play-btn {
  width: 32px;
  height: 32px;
  font-size: 14px;
}

.player-btn.play-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
  transform: none;
}

.player-btn.stop-btn {
  background: #ef4444;
  width: 28px;
  height: 28px;
  font-size: 12px;
}

.player-btn.stop-btn:hover {
  background: #dc2626;
}

.player-progress {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.progress-bar {
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  cursor: pointer;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #7c3aed);
  border-radius: 3px;
  transition: width 0.1s linear;
}

.time-display {
  font-size: 11px;
  color: #666;
  text-align: center;
}

.player-hint {
  font-size: 12px;
  color: #888;
}

.script-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.script-preview {
  color: #666;
  font-size: 13px;
}

.no-script {
  color: #999;
  font-size: 13px;
}

.btn-speak-tiny {
  background: #f59e0b;
  color: white;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-speak-tiny:hover {
  background: #d97706;
}

/* Modal size */
.modal-large {
  max-width: 800px;
}

/* Exercise Modal Styles */
.exercise-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #eee;
}

.exercise-count {
  color: #666;
  font-size: 14px;
}

.exercise-form {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;
}

.exercise-form h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #333;
}

.options-group {
  margin-bottom: 16px;
}

.options-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
}

.option-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.option-row input[type="text"] {
  flex: 1;
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

.radio-label span {
  margin-left: 6px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 16px;
}

.exercise-list {
  max-height: 500px;
  overflow-y: auto;
}

.empty-exercises {
  text-align: center;
  padding: 40px;
  color: #666;
}

.exercise-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.exercise-item {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.exercise-item-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.exercise-number {
  font-weight: 600;
  color: #4f46e5;
}

.exercise-type {
  padding: 2px 8px;
  background: #e0e7ff;
  color: #4338ca;
  border-radius: 4px;
  font-size: 12px;
}

.exercise-question {
  font-size: 14px;
  color: #333;
  margin-bottom: 8px;
}

.exercise-options {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin: 8px 0;
}

.exercise-options .option {
  padding: 6px 12px;
  background: #f3f4f6;
  border-radius: 4px;
  font-size: 13px;
}

.exercise-options .option.correct {
  background: #d1fae5;
  color: #065f46;
  font-weight: 500;
}

.exercise-answer {
  font-size: 13px;
  color: #666;
  margin-top: 8px;
}

.exercise-answer strong {
  color: #059669;
}

.exercise-explanation {
  font-size: 12px;
  color: #666;
  margin-top: 8px;
  padding: 8px;
  background: #fef3c7;
  border-radius: 4px;
}

.exercise-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #eee;
}

.btn-edit-small, .btn-delete-small {
  padding: 4px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-edit-small {
  background: #10b981;
  color: white;
}

.btn-edit-small:hover {
  background: #059669;
}

.btn-delete-small {
  background: #ef4444;
  color: white;
}

.btn-delete-small:hover {
  background: #dc2626;
}

.btn-exercise {
  background: #8b5cf6;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-exercise:hover {
  background: #7c3aed;
}
</style>
