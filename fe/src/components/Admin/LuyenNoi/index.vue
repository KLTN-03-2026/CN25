<template>
  <div class="luyen-noi">
    <!-- Hidden Audio Element for playback - only render when has source -->
    <audio
      v-if="currentAudioSrc"
      ref="audioElement"
      :src="currentAudioSrc"
      @ended="onAudioEnded"
      @error="onAudioError"
    ></audio>

    <div class="header">
      <h1>Quản Lý Luyện Nói</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Bài Tập Nói
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
        <div class="filter-group">
          <select v-model="filterType" class="filter-select">
            <option value="">-- Tất cả Loại --</option>
            <option value="repeat">Repeat (Lặp lại)</option>
            <option value="read">Read (Đọc)</option>
            <option value="describe">Describe (Mô tả)</option>
            <option value="qa">Q&A (Hỏi đáp)</option>
          </select>
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm bài tập nói..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ filteredSpeakings.length }}</div>
        <div class="stat-label">Tổng Bài Tập Nói</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ lessons.length }}</div>
        <div class="stat-label">Bài Học</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredSpeakings.length === 0" class="empty-state">
      <p>Không tìm thấy bài tập nói nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th style="width: 100px">Loại</th>
          <th>Nội Dung</th>
          <th style="width: 120px">Hình Ảnh/Audio</th>
          <th style="width: 100px">Thứ Tự</th>
          <th style="width: 120px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="speaking in filteredSpeakings" :key="speaking.id">
          <td>{{ speaking.id }}</td>
          <td>
            <span :class="['type-badge', 'type-' + speaking.type]">
              {{ getTypeLabel(speaking.type) }}
            </span>
          </td>
          <td>
            <div class="content-cell">
              <span class="content-text">{{ speaking.content }}</span>
              <span v-if="speaking.type === 'qa' && speaking.sample_answer" class="sample-answer">
                💡 {{ speaking.sample_answer.length > 50 ? speaking.sample_answer.substring(0, 50) + '...' : speaking.sample_answer }}
              </span>
              <span v-if="speaking.type === 'describe' && speaking.keywords" class="keywords">
                🔑 {{ speaking.keywords }}
              </span>
            </div>
          </td>
          <td>
            <div class="media-cell">
              <!-- Repeat: Ưu tiên audio, fallback TTS -->
              <template v-if="speaking.type === 'repeat'">
                <button
                  v-if="currentPlayingId === speaking.id"
                  class="btn-play-tiny btn-stop"
                  @click="stopAudio"
                  title="Dừng"
                >
                  ⏹️
                </button>
                <button
                  v-else-if="speaking.audio_url"
                  class="btn-play-tiny btn-play-audio"
                  @click="playAudio(speaking)"
                  title="Phát audio file"
                >
                  🎧
                </button>
                <button
                  v-else
                  class="btn-play-tiny btn-play-tts"
                  @click="playWithTTS(speaking)"
                  title="Phát TTS"
                >
                  🔊
                </button>
              </template>
              <!-- Read: Chỉ có TTS -->
              <template v-else-if="speaking.type === 'read'">
                <button
                  v-if="currentPlayingId === speaking.id"
                  class="btn-play-tiny btn-stop"
                  @click="stopAudio"
                  title="Dừng"
                >
                  ⏹️
                </button>
                <button
                  v-else
                  class="btn-play-tiny btn-play-tts"
                  @click="playWithTTS(speaking)"
                  title="Phát TTS"
                >
                  🔊
                </button>
              </template>
              <!-- Describe: Xem hình ảnh -->
              <template v-else-if="speaking.type === 'describe' && speaking.image_url">
                <img :src="getImageUrl(speaking.image_url)" alt="Hình ảnh" class="preview-image" @click="previewImage(speaking.image_url)" />
              </template>
              <!-- Q&A: Phát câu hỏi bằng TTS -->
              <template v-else-if="speaking.type === 'qa'">
                <button
                  v-if="currentPlayingId === speaking.id"
                  class="btn-play-tiny btn-stop"
                  @click="stopAudio"
                  title="Dừng"
                >
                  ⏹️
                </button>
                <button
                  v-else
                  class="btn-play-tiny btn-play-tts"
                  @click="playWithTTS(speaking)"
                  title="Phát câu hỏi bằng TTS"
                >
                  ❓
                </button>
              </template>
              <span v-else class="no-media">-</span>
            </div>
          </td>
          <td>{{ speaking.order }}</td>
          <td class="actions">
            <button class="btn-edit" @click="editSpeaking(speaking)" title="Sửa">
              <span>✏️</span>
            </button>
            <button class="btn-delete" @click="deleteSpeaking(speaking.id)" title="Xóa">
              <span>🗑️</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Thêm/Sửa Speaking -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingSpeaking ? 'Sửa Bài Tập Nói' : 'Thêm Bài Tập Nói Mới' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <!-- Chọn Bài Học -->
          <div class="form-group">
            <label>Bài Học <span class="required">*</span></label>
            <select v-model="speakingForm.lesson_id">
              <option value="">-- Chọn Bài Học --</option>
              <optgroup v-for="course in courses" :key="course.id" :label="course.title">
                <option v-for="lesson in getLessonsByCourse(course.id)" :key="lesson.id" :value="lesson.id">
                  {{ lesson.title }}
                </option>
              </optgroup>
            </select>
            <span v-if="errors.lesson_id" class="error-text">{{ errors.lesson_id[0] }}</span>
          </div>

          <!-- Chọn Type -->
          <div class="form-group">
            <label>Loại Bài Tập <span class="required">*</span></label>
            <select v-model="speakingForm.type" @change="onTypeChange">
              <option value="">-- Chọn Loại --</option>
              <option value="repeat">🔁 Repeat - Lặp lại câu</option>
              <option value="read">📖 Read - Đọc đoạn văn</option>
              <option value="describe">🖼️ Describe - Mô tả hình ảnh</option>
              <option value="qa">❓ Q&A - Hỏi và đáp</option>
            </select>
            <span v-if="errors.type" class="error-text">{{ errors.type[0] }}</span>
          </div>

          <!-- ========== FORM DYNAMIC THEO TYPE ========== -->

          <!-- REPEAT: Text + Audio -->
          <template v-if="speakingForm.type === 'repeat'">
            <div class="form-group">
              <label>Nội Dung Cần Lặp Lại <span class="required">*</span></label>
              <input v-model="speakingForm.content" type="text" placeholder="Nhập câu cần lặp lại" />
              <span v-if="errors.content" class="error-text">{{ errors.content[0] }}</span>
            </div>
            <div class="form-group">
              <label>Audio File</label>
              <div class="file-upload-wrapper">
                <input type="file" @change="handleAudioUpload" accept="audio/*" class="file-input" />
                <div v-if="speakingForm.audioFile" class="file-name">
                  📁 {{ speakingForm.audioFile.name }}
                </div>
                <div v-else-if="editingSpeaking && currentEditingSpeaking?.audio_url" class="existing-file">
                  📁 File hiện tại: {{ getFileName(currentEditingSpeaking.audio_url) }}
                </div>
                <small v-else class="help-text">Upload file audio mẫu (mp3, wav, m4a)</small>
              </div>
            </div>
          </template>

          <!-- READ: Content là đoạn văn -->
          <template v-if="speakingForm.type === 'read'">
            <div class="form-group">
              <label>Nội Dung Cần Đọc <span class="required">*</span></label>
              <textarea v-model="speakingForm.content" rows="6" placeholder="Nhập đoạn văn cần đọc"></textarea>
              <span v-if="errors.content" class="error-text">{{ errors.content[0] }}</span>
            </div>
          </template>

          <!-- DESCRIBE: Image + Keywords -->
          <template v-if="speakingForm.type === 'describe'">
            <div class="form-group">
              <label>Hình Ảnh <span class="required">*</span></label>
              <div class="file-upload-wrapper image-upload">
                <input type="file" @change="handleImageUpload" accept="image/*" class="file-input" />
                <div v-if="speakingForm.imageFile" class="preview-upload">
                  <img :src="previewImageUrl" alt="Preview" class="image-preview" />
                </div>
                <div v-else-if="editingSpeaking && currentEditingSpeaking?.image_url" class="existing-image">
                  <img :src="getImageUrl(currentEditingSpeaking.image_url)" alt="Current" class="image-preview" />
                  <span>File hiện tại</span>
                </div>
                <small v-else class="help-text">Upload hình ảnh cần mô tả (jpg, png, webp)</small>
              </div>
            </div>
            <div class="form-group">
              <label>Từ Khóa Gợi Ý</label>
              <input v-model="speakingForm.keywords" type="text" placeholder="game, playing, football (phân cách bằng dấu phẩy)" />
              <small class="help-text">Các từ khóa gợi ý để học viên mô tả, phân cách bằng dấu phẩy</small>
            </div>
            <div class="form-group">
              <label>Nội Dung Mô Tả</label>
              <textarea v-model="speakingForm.content" rows="3" placeholder="Mô tả ngắn về hình ảnh (tùy chọn)"></textarea>
            </div>
          </template>

          <!-- Q&A: Question + Sample Answer -->
          <template v-if="speakingForm.type === 'qa'">
            <div class="form-group">
              <label>Câu Hỏi <span class="required">*</span></label>
              <textarea v-model="speakingForm.content" rows="3" placeholder="Nhập câu hỏi (VD: What do you do in your free time?)"></textarea>
              <span v-if="errors.content" class="error-text">{{ errors.content[0] }}</span>
            </div>
            <div class="form-group">
              <label>Câu Trả Lời Mẫu</label>
              <textarea v-model="speakingForm.sample_answer" rows="4" placeholder="Nhập câu trả lời mẫu (VD: In my free time, I usually play football with my friends...)"></textarea>
              <small class="help-text">Câu trả lời mẫu để học viên tham khảo</small>
            </div>
          </template>

          <!-- Thứ tự (chung) -->
          <div class="form-group">
            <label>Thứ Tự</label>
            <input v-model.number="speakingForm.order" type="number" min="0" placeholder="0" />
            <small class="help-text">Số càng nhỏ sẽ hiển thị trước</small>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Hủy</button>
          <button class="btn-primary" @click="saveSpeaking">
            {{ editingSpeaking ? 'Cập Nhật' : 'Thêm Mới' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Preview Image Modal -->
    <div v-if="showImagePreview" class="modal-overlay" @click.self="showImagePreview = false">
      <div class="modal image-preview-modal">
        <div class="modal-header">
          <h2>Xem Hình Ảnh</h2>
          <button class="btn-close" @click="showImagePreview = false">&times;</button>
        </div>
        <div class="modal-body">
          <img :src="previewFullUrl" alt="Preview" class="full-image" />
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
          <p>Bạn có chắc chắn muốn xóa bài tập nói này?</p>
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
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { CourseService, ChapterService, LessonService, SpeakingExerciseService } from '../../../services/api.js'

const loading = ref(false)
const courses = ref([])
const chapters = ref([])
const lessons = ref([])
const speakings = ref([])
const allSpeakings = ref([])

const CHAPTER_TYPE = 'speaking'

const BASE_URL = 'http://localhost:8000'

const selectedCourseId = ref('')
const selectedChapterId = ref('')
const selectedLessonId = ref('')
const filterType = ref('')
const searchQuery = ref('')
const isRestoringFromSession = ref(false)

const currentCourseTitle = ref('')
const currentChapterTitle = ref('')
const currentLessonTitle = ref('')

const showModal = ref(false)
const editingSpeaking = ref(null)
const currentEditingSpeaking = ref(null)
const showDeleteConfirm = ref(false)
const deleteSpeakingId = ref(null)
const errors = ref({})

const showImagePreview = ref(false)
const previewFullUrl = ref('')
const previewImageUrl = ref('')

// Audio/TTS playing state
const isPlaying = ref(false)
const currentPlayingId = ref(null)
const audioElement = ref(null)
const currentAudioSrc = ref('')

const speakingForm = ref({
  lesson_id: '',
  type: '',
  content: '',
  audio_url: '',
  image_url: '',
  keywords: '',
  sample_answer: '',
  order: 0,
  audioFile: null,
  imageFile: null
})

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

watch(selectedChapterId, async (newVal, oldVal) => {
  if (isRestoringFromSession.value) return
  if (newVal) {
    await fetchLessons(newVal)
  } else {
    // Khi xóa chapter, load tất cả lessons của tất cả chapters
    await fetchAllLessons()
    await fetchSpeakings()
  }
})

watch(selectedLessonId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal && !isRestoringFromSession.value) {
    await fetchSpeakings()
  }
})

// Computed
const filteredSpeakings = computed(() => {
  let result = allSpeakings.value

  if (selectedCourseId.value) {
    result = result.filter(s => s.lesson?.course_id === parseInt(selectedCourseId.value))
  }

  if (selectedChapterId.value) {
    result = result.filter(s => s.lesson?.chapter_id === parseInt(selectedChapterId.value))
  }

  if (selectedLessonId.value) {
    result = result.filter(s => s.lesson_id === parseInt(selectedLessonId.value))
  }

  if (filterType.value) {
    result = result.filter(s => s.type === filterType.value)
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(s =>
      (s.content && s.content.toLowerCase().includes(q)) ||
      (s.keywords && s.keywords.toLowerCase().includes(q)) ||
      (s.sample_answer && s.sample_answer.toLowerCase().includes(q))
    )
  }

  return result
})

// Methods
const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courses.value = data

    const savedCourseId = sessionStorage.getItem('luyenNoi_selectedCourseId')
    const savedChapterId = sessionStorage.getItem('luyenNoi_selectedChapterId')
    const savedLessonId = sessionStorage.getItem('luyenNoi_selectedLessonId')

    currentCourseTitle.value = sessionStorage.getItem('luyenNoi_selectedCourseTitle') || ''
    currentChapterTitle.value = sessionStorage.getItem('luyenNoi_selectedChapterTitle') || ''
    currentLessonTitle.value = sessionStorage.getItem('luyenNoi_selectedLessonTitle') || ''

    if (savedCourseId) {
      isRestoringFromSession.value = true

      const chaptersData = await ChapterService.getByCourse(savedCourseId)
      chapters.value = chaptersData.filter(c => c.type === CHAPTER_TYPE)

      selectedCourseId.value = savedCourseId
      selectedChapterId.value = savedChapterId || ''
      selectedLessonId.value = savedLessonId || ''

      sessionStorage.removeItem('luyenNoi_selectedCourseId')
      sessionStorage.removeItem('luyenNoi_selectedChapterId')
      sessionStorage.removeItem('luyenNoi_selectedCourseTitle')
      sessionStorage.removeItem('luyenNoi_selectedChapterTitle')
      sessionStorage.removeItem('luyenNoi_selectedLessonId')
      sessionStorage.removeItem('luyenNoi_selectedLessonTitle')

      await nextTick()
      isRestoringFromSession.value = false

      if (savedChapterId) {
        await fetchLessons(savedChapterId)
      }
      await fetchSpeakings()
    } else {
      // Load tất cả chapters của tất cả courses
      await fetchAllChapters()
      await fetchAllLessons()
      await fetchSpeakings()
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
    lessons.value = data.map(l => ({
      ...l,
      course_id: parseInt(selectedCourseId.value) || 0,
      chapter_id: parseInt(chapterId)
    }))
    await fetchSpeakings()
  } catch (error) {
    console.error('Lỗi khi tải bài học:', error)
  }
}

const fetchSpeakings = async () => {
  loading.value = true
  try {
    const allSpeakingData = []

    const lessonsToFetch = selectedLessonId.value
      ? lessons.value.filter(l => l.id === parseInt(selectedLessonId.value))
      : lessons.value

    for (const lesson of lessonsToFetch) {
      try {
        const data = await SpeakingExerciseService.getByLesson(lesson.id)
        const speakingWithLesson = data.map(s => ({
          ...s,
          lesson: { ...lesson, course_id: selectedCourseId.value, chapter_id: selectedChapterId.value }
        }))
        allSpeakingData.push(...speakingWithLesson)
      } catch (e) {
        // Lesson có thể không có speaking
      }
    }
    allSpeakings.value = allSpeakingData
    speakings.value = allSpeakingData
  } catch (error) {
    console.error('Lỗi khi tải bài tập nói:', error)
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

const getLessonsByCourse = (courseId) => {
  if (!courseId) return []
  return lessons.value.filter(l => l.course_id === courseId)
}

const getTypeLabel = (type) => {
  const labels = {
    'repeat': '🔁 Repeat',
    'read': '📖 Read',
    'describe': '🖼️ Describe',
    'qa': '❓ Q&A'
  }
  return labels[type] || type
}

const getFileName = (path) => {
  if (!path) return ''
  return path.split('/').pop()
}

const getImageUrl = (path) => {
  if (!path) return ''
  return path.startsWith('http') ? path : BASE_URL + path
}

const playAudio = (speaking) => {
  // Dừng audio hiện tại nếu đang phát bài khác
  if (isPlaying.value && currentPlayingId.value !== speaking.id) {
    stopAudio()
  }

  if (speaking.audio_url) {
    currentPlayingId.value = speaking.id
    currentAudioSrc.value = speaking.audio_url.startsWith('http')
      ? speaking.audio_url
      : BASE_URL + speaking.audio_url
    isPlaying.value = true

    nextTick(() => {
      if (audioElement.value) {
        audioElement.value.play().catch(err => {
          console.error('Lỗi phát audio:', err)
          alert('Không thể phát audio này.')
          isPlaying.value = false
          currentPlayingId.value = null
        })
      }
    })
  }
}

// Phát bằng TTS (Text-to-Speech)
const playWithTTS = (speaking) => {
  if (!speaking.content) return

  // Dừng TTS hiện tại nếu đang phát bài khác
  if (isPlaying.value && currentPlayingId.value !== speaking.id) {
    stopAudio()
  }

  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel()
    currentPlayingId.value = speaking.id
    isPlaying.value = true

    const utterance = new SpeechSynthesisUtterance(speaking.content)
    utterance.lang = 'en-US'
    utterance.rate = 0.9
    utterance.pitch = 1
    utterance.onend = () => {
      isPlaying.value = false
      currentPlayingId.value = null
    }
    window.speechSynthesis.speak(utterance)
  } else {
    alert('Trình duyệt không hỗ trợ Text-to-Speech')
  }
}

// Dừng audio/TTS
const stopAudio = () => {
  // Dừng HTML5 Audio
  if (audioElement.value) {
    audioElement.value.pause()
    audioElement.value.currentTime = 0
  }

  // Dừng TTS
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel()
  }

  isPlaying.value = false
  currentPlayingId.value = null
  currentAudioSrc.value = ''
}

// Audio ended callback
const onAudioEnded = () => {
  isPlaying.value = false
  currentPlayingId.value = null
}

// Audio error callback
const onAudioError = (e) => {
  console.error('Lỗi load audio:', e)
  alert('Không thể phát audio này.')
  isPlaying.value = false
  currentPlayingId.value = null
}

const previewImage = (url) => {
  previewFullUrl.value = getImageUrl(url)
  showImagePreview.value = true
}

const onTypeChange = () => {
  // Reset form khi đổi type
  speakingForm.value.content = ''
  speakingForm.value.audio_url = ''
  speakingForm.value.image_url = ''
  speakingForm.value.keywords = ''
  speakingForm.value.sample_answer = ''
  speakingForm.value.audioFile = null
  speakingForm.value.imageFile = null
  previewImageUrl.value = ''
}

const handleAudioUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    speakingForm.value.audioFile = file
  }
}

const handleImageUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    speakingForm.value.imageFile = file
    // Tạo preview URL
    const reader = new FileReader()
    reader.onload = (e) => {
      previewImageUrl.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const openAddModal = () => {
  editingSpeaking.value = null
  errors.value = {}
  speakingForm.value = {
    lesson_id: selectedLessonId.value || '',
    type: '',
    content: '',
    audio_url: '',
    image_url: '',
    keywords: '',
    sample_answer: '',
    order: 0,
    audioFile: null,
    imageFile: null
  }
  previewImageUrl.value = ''
  showModal.value = true
}

const editSpeaking = (speaking) => {
  editingSpeaking.value = speaking.id
  currentEditingSpeaking.value = speaking
  errors.value = {}
  speakingForm.value = {
    lesson_id: speaking.lesson_id,
    type: speaking.type,
    content: speaking.content || '',
    audio_url: speaking.audio_url || '',
    image_url: speaking.image_url || '',
    keywords: speaking.keywords || '',
    sample_answer: speaking.sample_answer || '',
    order: speaking.order || 0,
    audioFile: null,
    imageFile: null
  }
  previewImageUrl.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingSpeaking.value = null
  currentEditingSpeaking.value = null
  errors.value = {}
}

const saveSpeaking = async () => {
  errors.value = {}
  try {
    // Validate cơ bản
    if (!speakingForm.value.lesson_id) {
      errors.value.lesson_id = ['Vui lòng chọn bài học']
      return
    }
    if (!speakingForm.value.type) {
      errors.value.type = ['Vui lòng chọn loại bài tập']
      return
    }

    const fd = new FormData()
    fd.append('type', speakingForm.value.type)
    fd.append('content', speakingForm.value.content || '')
    fd.append('keywords', speakingForm.value.keywords || '')
    fd.append('sample_answer', speakingForm.value.sample_answer || '')
    fd.append('order', speakingForm.value.order || 0)

    if (speakingForm.value.audioFile) {
      fd.append('audio_url', speakingForm.value.audioFile)
    }
    if (speakingForm.value.imageFile) {
      fd.append('image_url', speakingForm.value.imageFile)
    }

    if (editingSpeaking.value) {
      const result = await SpeakingExerciseService.update(editingSpeaking.value, fd)
      const updatedSpeaking = {
        ...result.data,
        lesson: {
          id: result.data.lesson_id,
          course_id: parseInt(selectedCourseId.value) || (result.data.lesson?.course_id),
          chapter_id: parseInt(selectedChapterId.value) || (result.data.lesson?.chapter_id)
        }
      }
      const index = allSpeakings.value.findIndex(s => s.id === editingSpeaking.value)
      if (index !== -1) {
        allSpeakings.value[index] = updatedSpeaking
      }
      alert('Cập nhật bài tập nói thành công!')
    } else {
      const result = await SpeakingExerciseService.create(speakingForm.value.lesson_id, fd)
      const newSpeaking = {
        ...result.data,
        lesson: {
          id: result.data.lesson_id,
          course_id: parseInt(selectedCourseId.value),
          chapter_id: parseInt(selectedChapterId.value)
        }
      }
      allSpeakings.value.push(newSpeaking)
      alert('Thêm bài tập nói thành công!')
    }
    closeModal()
  } catch (error) {
    console.error('Save speaking error:', error)
    if (error.response && error.response.data && error.response.data.errors) {
      errors.value = error.response.data.errors
    } else {
      alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
    }
  }
}

const deleteSpeaking = (id) => {
  deleteSpeakingId.value = id
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    await SpeakingExerciseService.delete(deleteSpeakingId.value)
    allSpeakings.value = allSpeakings.value.filter(s => s.id !== deleteSpeakingId.value)
    showDeleteConfirm.value = false
    deleteSpeakingId.value = null
    alert('Xóa bài tập nói thành công!')
  } catch (error) {
    console.error('Delete error:', error)
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

onMounted(() => {
  fetchCourses()
})
</script>

<style scoped>
.luyen-noi {
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

.filter-group {
  display: flex;
  align-items: center;
}

.search-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
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

.type-badge {
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.type-repeat {
  background: #dbeafe;
  color: #1e40af;
}

.type-read {
  background: #d1fae5;
  color: #065f46;
}

.type-describe {
  background: #fef3c7;
  color: #92400e;
}

.type-qa {
  background: #f3e8ff;
  color: #6b21a8;
}

.content-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.content-text {
  color: #333;
  font-size: 14px;
}

.sample-answer, .keywords {
  font-size: 12px;
  color: #666;
  background: #f3f4f6;
  padding: 4px 8px;
  border-radius: 4px;
}

.media-cell {
  display: flex;
  align-items: center;
}

.media-item {
  display: flex;
  align-items: center;
}

.preview-image {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 4px;
  cursor: pointer;
  border: 1px solid #ddd;
}

.no-media {
  color: #999;
  font-size: 13px;
}

.actions {
  display: flex;
  gap: 6px;
}

.btn-play-tiny {
  background: #3b82f6;
  color: white;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-play-tiny:hover {
  background: #2563eb;
}

.btn-play-tiny.btn-play-audio {
  background: #3b82f6;
}

.btn-play-tiny.btn-play-audio:hover {
  background: #2563eb;
}

.btn-play-tiny.btn-play-tts {
  background: #8b5cf6;
}

.btn-play-tiny.btn-play-tts:hover {
  background: #7c3aed;
}

.btn-play-tiny.btn-stop {
  background: #ef4444;
}

.btn-play-tiny.btn-stop:hover {
  background: #dc2626;
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
  max-width: 600px;
  max-height: 90vh;
  overflow: auto;
}

.modal-confirm {
  max-width: 400px;
}

.image-preview-modal {
  max-width: 800px;
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

.image-upload {
  padding: 16px;
}

.preview-upload, .existing-image {
  margin-top: 12px;
}

.image-preview {
  max-width: 200px;
  max-height: 150px;
  object-fit: contain;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.existing-image span {
  display: block;
  font-size: 12px;
  color: #666;
  margin-top: 4px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}

.full-image {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}
</style>
