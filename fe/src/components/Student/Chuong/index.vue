<template>
  <div class="chapter-page">
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="spinner"></div>
      <span>Đang tải dữ liệu...</span>
    </div>

    <!-- Not Found -->
    <div v-else-if="!course" class="not-found">
      <i class="fa-solid fa-folder-xmark"></i>
      <h2>Không tìm thấy khóa học</h2>
      <p>Khóa học bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.</p>
      <button class="btn-back" @click="goBack">
        <i class="fa-solid fa-arrow-left"></i>
        Quay lại danh sách khóa học
      </button>
    </div>

    <!-- Main Content -->
    <template v-else>
      <!-- Breadcrumb -->
      <div class="breadcrumb-bar">
        <div class="container">
          <nav class="breadcrumb">
            <router-link to="/student" class="breadcrumb-link">
              <i class="fa-solid fa-home"></i>
              Khóa học
            </router-link>
            <i class="fa-solid fa-chevron-right breadcrumb-sep"></i>
            <span class="breadcrumb-current">{{ course.title }}</span>
          </nav>
        </div>
      </div>

      <!-- Course Header -->
      <section class="course-header">
        <div class="container">
          <div class="header-content">
            <div class="header-info">
              <!-- Level Badge -->
              <span class="level-badge" :class="course.level">
                <i :class="getLevelIcon(course.level)"></i>
                {{ getLevelLabel(course.level) }}
              </span>

              <h1 class="course-title">{{ course.title }}</h1>
              <p class="course-desc">{{ course.description }}</p>

              <!-- Course Meta -->
              <div class="course-meta">
                <div class="meta-item">
                  <i class="fa-solid fa-book-open"></i>
                  <span>{{ chapters.length }} Chương</span>
                </div>
                <div class="meta-item">
                  <i class="fa-solid fa-layer-group"></i>
                  <span>{{ totalLessons }} Bài học</span>
                </div>
                <div class="meta-item">
                  <i class="fa-solid fa-star"></i>
                  <span>{{ course.rating || 4.8 }} Đánh giá</span>
                </div>
                <div class="meta-item">
                  <i class="fa-solid fa-users"></i>
                  <span>{{ formatNumber(course.students_count || 0) }} Học viên</span>
                </div>
              </div>

              <!-- Progress -->
              <div class="course-progress" v-if="userProgress > 0">
                <div class="progress-info">
                  <span>Tiến độ của bạn</span>
                  <span class="progress-percent">{{ userProgress }}%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: userProgress + '%' }"></div>
                </div>
              </div>
            </div>

            <!-- Course Thumbnail -->
            <div class="header-thumb">
              <img :src="getThumbnailUrl(course.thumbnail)" :alt="course.title" />
              <div class="thumb-overlay">
                <button v-if="userProgress > 0" class="btn-continue" @click="continueCourse">
                  <i class="fa-solid fa-play"></i>
                  Tiếp tục học
                </button>
                <button v-else class="btn-continue" @click="handleEnrollHeader">
                  <i class="fa-solid fa-play"></i>
                  Học ngay
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Chapters List -->
      <section class="chapters-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">
              <i class="fa-solid fa-list-ul"></i>
              Nội dung khóa học
            </h2>
            <span class="chapter-count">{{ chapters.length }} chương</span>
          </div>

          <!-- Empty Chapters -->
          <div v-if="chapters.length === 0" class="empty-chapters">
            <i class="fa-solid fa-folder-open"></i>
            <h3>Khóa học chưa có nội dung</h3>
            <p>Chương học đang được cập nhật, vui lòng quay lại sau.</p>
          </div>

          <!-- Chapters Accordion -->
          <div v-else class="chapters-list">
            <div
              v-for="(chapter, index) in chapters"
              :key="chapter.id"
              class="chapter-item"
              :class="{
                'is-open': openChapterId === chapter.id,
                'is-locked': chapter.status === 'draft'
              }"
            >
              <!-- Chapter Header -->
              <div class="chapter-header" @click="toggleChapter(chapter)">
                <div class="chapter-left">
                  <div class="chapter-number" :class="chapter.type">
                    <span v-if="openChapterId !== chapter.id">{{ index + 1 }}</span>
                    <i v-else class="fa-solid fa-minus"></i>
                  </div>
                  <div class="chapter-info">
                    <h3 class="chapter-title">{{ chapter.title }}</h3>
                    <p class="chapter-subtitle" v-if="chapter.description">
                      {{ chapter.description }}
                    </p>
                    <div class="chapter-meta">
                      <span class="chapter-type-tag" :class="chapter.type">
                        <i :class="getChapterTypeIcon(chapter.type)"></i>
                        {{ getChapterTypeLabel(chapter.type) }}
                      </span>
                      <span class="chapter-lessons">
                        <i class="fa-solid fa-file-lines"></i>
                        {{ chapter.lessons_count || 0 }} bài
                      </span>
                    </div>
                  </div>
                </div>

                <div class="chapter-right">
                  <!-- Progress for this chapter -->
                  <div class="chapter-progress-mini" v-if="getChapterProgress(chapter) > 0">
                    <div class="mini-progress-bar">
                      <div class="mini-progress-fill" :style="{ width: getChapterProgress(chapter) + '%' }"></div>
                    </div>
                    <span class="mini-progress-text">{{ getChapterProgress(chapter) }}%</span>
                  </div>

                  <!-- Lock Icon for Draft -->
                  <i v-if="chapter.status === 'draft'" class="fa-solid fa-lock chapter-lock"></i>

                  <!-- Expand Arrow -->
                  <i
                    class="fa-solid fa-chevron-down chapter-arrow"
                    :class="{ rotated: openChapterId === chapter.id }"
                  ></i>
                </div>
              </div>

              <!-- Chapter Lessons (Collapsible) -->
              <transition name="slide-down">
                <div v-if="openChapterId === chapter.id" class="chapter-lessons-list">
                  <!-- Loading Lessons -->
                  <div v-if="isLoadingLessons[chapter.id]" class="lessons-loading">
                    <div class="loading-spinner"></div>
                    <span>Đang tải bài học...</span>
                  </div>

                  <!-- Lessons List -->
                  <template v-else>
                    <div class="lessons-header">
                      <span class="lessons-count">{{ chapter.lessons?.length || 0 }} bài học</span>
                    </div>

                    <div
                      v-for="(lesson, lessonIndex) in chapter.lessons"
                      :key="lesson.id"
                      class="lesson-item"
                      :class="{
                        'is-completed': isLessonCompleted(lesson.id),
                        'is-locked': chapter.status === 'draft'
                      }"
                      @click="goToLesson(chapter, lesson)"
                    >
                      <div class="lesson-left">
                        <!-- Lesson Number Badge -->
                        <div class="lesson-number" :class="{ 'completed': isLessonCompleted(lesson.id) }">
                          <span v-if="isLessonCompleted(lesson.id)">
                            <i class="fa-solid fa-check"></i>
                          </span>
                          <span v-else>{{ lessonIndex + 1 }}</span>
                        </div>

                        <div class="lesson-info">
                          <span class="lesson-title">{{ lesson.title }}</span>
                          <div class="lesson-meta">
                            <span class="lesson-type-badge" :class="chapter.type">
                              <i :class="getChapterTypeIcon(chapter.type)"></i>
                              {{ getChapterTypeLabel(chapter.type) }}
                            </span>
                          </div>
                        </div>
                      </div>

                      <div class="lesson-right">
                        <!-- Đã hoàn thành -->
                        <span v-if="isLessonCompleted(lesson.id)" class="status-completed">
                          <i class="fa-solid fa-check-circle"></i>
                          Hoàn thành
                        </span>

                        <!-- Button bên phải -->
                        <button
                          v-if="isLessonCompleted(lesson.id)"
                          class="btn-hoc completed"
                          @click.stop="goToLesson(chapter, lesson)"
                        >
                          Học lại
                          <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button
                          v-else-if="isLessonStarted(lesson.id)"
                          class="btn-hoc completed"
                          @click.stop="goToLesson(chapter, lesson)"
                        >
                          Học tiếp
                          <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button
                          v-else
                          class="btn-hoc"
                          @click.stop="handleEnroll(chapter, lesson)"
                        >
                          <i class="fa-solid fa-play"></i>
                          Học
                        </button>
                      </div>
                    </div>

                    <!-- Empty Lessons in Chapter -->
                    <div v-if="!chapter.lessons || chapter.lessons.length === 0" class="no-lessons">
                      <div class="no-lessons-icon">
                        <i class="fa-solid fa-folder-open"></i>
                      </div>
                      <p>Chương này chưa có bài học nào</p>
                      <span>Vui lòng quay lại sau</span>
                    </div>
                  </template>
                </div>
              </transition>
            </div>
          </div>
        </div>
      </section>

      <!-- Luyen Tap Section -->
      <section class="practice-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">
              <i class="fa-solid fa-pen-nib"></i>
              Luyện tập
            </h2>
            <span class="chapter-count">Kiểm tra tổng hợp</span>
          </div>

          <div class="practice-items">
            <div
              class="practice-item"
              :class="{
                'is-unlocked': canDoPractice,
                'is-locked': !canDoPractice,
                'is-empty': !courseProgress
              }"
            >
              <div class="practice-icon">
                <i class="fa-solid fa-pen-nib"></i>
              </div>
              <div class="practice-info">
                <h3>Bài Luyện Tập</h3>
                <p v-if="!courseProgress">Vui lòng đăng ký khóa học để mở khóa bài luyện tập</p>
                <p v-else-if="!canDoPractice">Hoàn thành các chương học để mở khóa bài luyện tập</p>
                <p v-else>Sẵn sàng kiểm tra kiến thức tổng hợp từ 4 chương</p>
              </div>
              <div class="practice-status">
                <button
                  v-if="canDoPractice"
                  class="btn-practice"
                  @click="goToPractice"
                >
                  <i class="fa-solid fa-play"></i>
                  Bắt đầu
                </button>
                <div v-else class="locked-state">
                  <i class="fa-solid fa-lock"></i>
                  <span v-if="!courseProgress">Chưa đăng ký</span>
                  <span v-else>Hoàn thành chương để mở khóa</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Thi Cuoi Khoa Section -->
      <section class="quiz-final-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">
              <i class="fa-solid fa-graduation-cap"></i>
              Thi Cuối Khóa
            </h2>
            <span class="chapter-count">Đánh giá cuối khóa</span>
          </div>

          <!-- CHUA MO KHOA - can hoan thanh luyen tap -->
          <div v-if="!courseProgress" class="quiz-final-card locked">
            <div class="quiz-final-icon">
              <i class="fa-solid fa-lock"></i>
            </div>
            <div class="quiz-final-info">
              <h3>Bài Thi Cuối Khóa</h3>
              <p>Vui lòng đăng ký khóa học để mở khóa bài thi cuối khóa.</p>
            </div>
            <div class="quiz-final-action">
              <button class="btn-quiz-final disabled" disabled>
                <i class="fa-solid fa-lock"></i>
                <span>Chưa đăng ký</span>
              </button>
            </div>
          </div>

          <!-- DA MO KHOA - nhung chua thi hoac chua dat -->
          <div v-else-if="courseProgress && !courseProgress.final_passed" class="quiz-final-card unlocked">
            <div class="quiz-final-icon">
              <i class="fa-solid fa-file-signature"></i>
            </div>
            <div class="quiz-final-info">
              <h3>Bài Thi Cuối Khóa</h3>
              <p v-if="!courseProgress.is_final_unlocked">
                Hoàn thành bài luyện tập để mở khóa bài thi cuối khóa.
              </p>
              <p v-else-if="courseProgress.final_attempts > 0">
                Bạn đã thi {{ courseProgress.final_attempts }} lần.
                Điểm cao nhất: <strong>{{ courseProgress.final_best_score }}%</strong>
              </p>
              <p v-else>Sẵn sàng kiểm tra kiến thức tổng hợp cuối khóa?</p>
            </div>
            <div class="quiz-final-action">
              <template v-if="courseProgress.is_final_unlocked">
                <button class="btn-quiz-final" @click="goToFinalExam">
                  <i class="fa-solid fa-file-signature"></i>
                  <span v-if="courseProgress.final_attempts > 0">Thi lại</span>
                  <span v-else>Bắt đầu thi</span>
                </button>
                <span v-if="courseProgress.final_attempts > 0" class="quiz-final-hint best">
                  <i class="fa-solid fa-crown"></i>
                  Điểm cao nhất: {{ courseProgress.final_best_score }}%
                </span>
              </template>
              <template v-else>
                <button class="btn-quiz-final locked-btn" disabled>
                  <i class="fa-solid fa-lock"></i>
                  <span>Chưa mở khóa</span>
                </button>
                <span class="quiz-final-hint">
                  <i class="fa-solid fa-info-circle"></i>
                  Cần hoàn thành bài luyện tập
                </span>
              </template>
            </div>
          </div>

          <!-- DA HOAN THANH - pass roi -->
          <div v-else class="quiz-final-card completed">
            <div class="quiz-final-icon">
              <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="quiz-final-info">
              <h3>Bài Thi Cuối Khóa</h3>
              <p v-if="courseProgress.final_attempts > 0">
                Bạn đã hoàn thành khóa học với điểm cao nhất
                <strong>{{ courseProgress.final_best_score }}%</strong> (lần thi thứ {{ courseProgress.final_attempts }})
              </p>
              <p v-else>Bạn đã hoàn thành bài thi cuối khóa!</p>
            </div>
            <div class="quiz-final-action">
              <button class="btn-quiz-final completed-btn" @click="goToFinalExam">
                <i class="fa-solid fa-trophy"></i>
                <span>Xem lại bài thi</span>
              </button>
              <span class="quiz-final-hint completed-hint">
                <i class="fa-solid fa-check-circle"></i>
                Hoàn thành khóa học
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- Reviews Section -->
      <section class="reviews-section">
        <div class="container">
          <CourseReviews
            ref="reviewsRef"
            :courseId="course.id"
            :isEnrolled="isEnrolled"
          />
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CourseService, ChapterService, ProgressService } from '../../../services/api.js'
import { useAuthStore } from '../../../stores/auth'
import CourseReviews from '../CourseReviews.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// State
const isLoading = ref(true)
const isLoadingLessons = ref({})
const course = ref(null)
const chapters = ref([])
const openChapterId = ref(null)
const chapterLessonsCache = ref({})
const isEnrolled = ref(false)
const completedLessons = ref(new Set())
const startedLessons = ref(new Set())
const userProgress = ref(0)
const courseProgress = ref(null)
const reviewsRef = ref(null)

const totalLessons = computed(() => {
  return chapters.value.reduce((sum, ch) => sum + (ch.lessons_count || 0), 0)
})

const completedChapters = computed(() => {
  if (!courseProgress.value) return 0
  return courseProgress.value.chapters?.filter(ch => ch.progress_percent === 100).length || 0
})

const hasCourseQuiz = computed(() => {
  return chapters.value.length > 0
})

// Luyen tap: mo khi hoan thanh tat ca 4 chuong (is_practice_unlocked)
const canDoPractice = computed(() => {
  if (!courseProgress.value) return false
  return courseProgress.value.is_practice_unlocked === true
})

// Thi cuoi khoa: mo khi hoan thanh luyen tap (is_final_unlocked)
const canTakeFinalExam = computed(() => {
  if (!courseProgress.value) return false
  return courseProgress.value.is_final_unlocked === true
})

const fetchData = async () => {
  isLoading.value = true
  const courseId = route.params.id

  if (!courseId) {
    isLoading.value = false
    return
  }

  try {
    const courseRes = await CourseService.getById(courseId)
    course.value = courseRes.data || courseRes

    const chaptersRes = await ChapterService.getByCourse(courseId)
    chapters.value = Array.isArray(chaptersRes) ? chaptersRes : (chaptersRes.data || [])

    // Lấy progress nếu đã đăng nhập
    if (authStore.isAuthenticated) {
      await fetchProgress(courseId)
    }
  } catch (error) {
    console.error('Lỗi khi tải dữ liệu:', error)
    course.value = null
    chapters.value = []
  } finally {
    isLoading.value = false
  }
}

const fetchProgress = async (courseId) => {
  try {
    const res = await ProgressService.getCourseProgress(courseId)
    if (res.success && res.data) {
      courseProgress.value = res.data
      userProgress.value = res.data.progress || 0
      isEnrolled.value = true

      // Lay danh sach bai hoc da hoan thanh
      const completed = new Set(res.data.completed_lesson_ids || [])
      completedLessons.value = completed
    } else {
      // User chua dang ky khoa hoc nay
      isEnrolled.value = false
      courseProgress.value = null
      userProgress.value = 0
    }
  } catch (error) {
    // Neu loi 404/403 (chua dang ky), set isEnrolled = false
    // Nhung van cho phep xem noi dung khoa hoc (co the thanh toan dang cho xu ly)
    isEnrolled.value = false
    courseProgress.value = null
    userProgress.value = 0
    console.log('Chua dang ky hoac chua thanh toan khoa hoc nay')
  }
}

const handleEnroll = async (chapter, lesson) => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
    return
  }

  // Kiem tra lai enrollment tu server (phong truong hop admin vua duyet thanh toan)
  try {
    const res = await CourseService.getById(course.value.id)
    const freshEnrolled = res.is_enrolled || res.data?.is_enrolled
    if (freshEnrolled) {
      // Da dang ky roi -> vao bai hoc ngay
      isEnrolled.value = true
      router.push(`/student/khoa-hoc/${course.value.id}/ch/${chapter.id}/bai/${lesson.id}`)
      return
    }
  } catch (e) {
    console.error('Loi khi kiem tra enrollment:', e)
  }

  // Chua dang ky -> chuyen sang trang thanh toan
  router.push(`/student/thanh-toan/${course.value.id}`)
}

const handleEnrollHeader = async () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
    return
  }

  // Kiem tra lai enrollment tu server (phong truong hop admin vua duyet thanh toan)
  try {
    const res = await CourseService.getById(course.value.id)
    const freshEnrolled = res.is_enrolled || res.data?.is_enrolled
    if (freshEnrolled) {
      // Da dang ky roi -> vao bai hoc ngay
      isEnrolled.value = true
      continueCourse()
      return
    }
  } catch (e) {
    console.error('Loi khi kiem tra enrollment:', e)
  }

  // Chua dang ky -> chuyen sang trang thanh toan
  router.push(`/student/thanh-toan/${course.value.id}`)
}

const toggleChapter = async (chapter) => {
  if (chapter.status === 'draft') return

  if (openChapterId.value === chapter.id) {
    openChapterId.value = null
    return
  }

  if (!chapterLessonsCache.value[chapter.id]) {
    isLoadingLessons.value[chapter.id] = true
    try {
      const lessonsRes = await ChapterService.getLessons(chapter.id)
      chapterLessonsCache.value[chapter.id] = Array.isArray(lessonsRes) ? lessonsRes : (lessonsRes.data || [])
      chapter.lessons = chapterLessonsCache.value[chapter.id]
    } catch (error) {
      console.error('Lỗi khi tải lessons:', error)
      chapter.lessons = []
    } finally {
      isLoadingLessons.value[chapter.id] = false
    }
  } else {
    chapter.lessons = chapterLessonsCache.value[chapter.id]
  }

  openChapterId.value = chapter.id
}

const goToLesson = (chapter, lesson) => {
  if (chapter.status === 'draft') return
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
    return
  }
  // Kiem tra enrollment (phong truong hop admin vua duyet thanh toan)
  if (!isEnrolled.value) {
    router.push(`/student/thanh-toan/${course.value.id}`)
    return
  }
  router.push(`/student/khoa-hoc/${course.value.id}/ch/${chapter.id}/bai/${lesson.id}`)
}

const goToPractice = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
    return
  }
  router.push(`/student/khoa-hoc/${course.value.id}/luyen-tap`)
}

const goToFinalExam = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
    return
  }
  // Chuyen den trang thi cuoi khoa (CourseQuiz)
  router.push(`/student/khoa-hoc/${course.value.id}/thi-cuoi-khoa`)
}

const goBack = () => {
  router.push('/student')
}

const continueCourse = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
    return
  }

  for (const chapter of chapters.value) {
    if (chapter.status === 'draft') continue
    const lessons = chapterLessonsCache.value[chapter.id] || chapter.lessons || []
    for (const lesson of lessons) {
      if (!completedLessons.value.has(lesson.id)) {
        router.push(`/student/khoa-hoc/${course.value.id}/ch/${chapter.id}/bai/${lesson.id}`)
        return
      }
    }
  }
  router.go(0)
}

const getLevelLabel = (level) => {
  const labels = {
    beginner: 'Sơ cấp',
    intermediate: 'Trung cấp',
    advanced: 'Nâng cao'
  }
  return labels[level] || level
}

const getLevelIcon = (level) => {
  const icons = {
    beginner: 'fa-solid fa-seedling',
    intermediate: 'fa-solid fa-tree',
    advanced: 'fa-solid fa-mountain'
  }
  return icons[level] || 'fa-solid fa-graduation-cap'
}

const getChapterTypeIcon = (type) => {
  const icons = {
    vocabulary: 'fa-solid fa-spell-check',
    grammar: 'fa-solid fa-book',
    listening: 'fa-solid fa-headphones',
    speaking: 'fa-solid fa-microphone'
  }
  return icons[type] || 'fa-solid fa-book-open'
}

const getChapterTypeLabel = (type) => {
  const labels = {
    vocabulary: 'Từ Vựng',
    grammar: 'Ngữ Pháp',
    listening: 'Luyện Nghe',
    speaking: 'Luyện Nói'
  }
  return labels[type] || 'Bài học'
}

const getChapterProgress = (chapter) => {
  if (!courseProgress.value) return 0
  const cp = courseProgress.value.chapters?.find(c => c.chapter_id === chapter.id)
  return cp?.progress_percent || 0
}

const isLessonCompleted = (lessonId) => {
  return completedLessons.value.has(lessonId)
}

const isLessonStarted = (lessonId) => {
  return startedLessons.value.has(lessonId)
}

const getThumbnailUrl = (thumbnail) => {
  if (!thumbnail) return 'https://via.placeholder.com/600x400?text=No+Image'
  if (thumbnail.startsWith('http')) return thumbnail
  return 'http://localhost:8000/uploads/' + thumbnail
}

const formatNumber = (num) => {
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

onMounted(() => {
  fetchData()
})

watch(() => route.params.id, () => {
  openChapterId.value = null
  fetchData()
})
</script>

<style scoped>
/* ===== PAGE WRAPPER ===== */
.chapter-page {
  background: #f8fafc;
  min-height: 100vh;
  position: relative;
}

/* ===== LOADING ===== */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  z-index: 1000;
  color: #4f46e5;
  font-weight: 600;
}

.spinner {
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

/* ===== NOT FOUND ===== */
.not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
  text-align: center;
  padding: 40px 20px;
}

.not-found i {
  font-size: 64px;
  color: #cbd5e1;
  margin-bottom: 24px;
}

.not-found h2 {
  font-size: 24px;
  color: #1e293b;
  margin-bottom: 12px;
}

.not-found p {
  color: #64748b;
  margin-bottom: 32px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-back:hover {
  background: #4338ca;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
}

/* ===== BREADCRUMB ===== */
.breadcrumb-bar {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 16px 0;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.breadcrumb-link {
  color: #64748b;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: color 0.2s;
}

.breadcrumb-link:hover {
  color: #4f46e5;
}

.breadcrumb-sep {
  color: #cbd5e1;
  font-size: 12px;
}

.breadcrumb-current {
  color: #1e293b;
  font-weight: 600;
}

/* ===== COURSE HEADER ===== */
.course-header {
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
  color: white;
  padding: 60px 0 80px;
  position: relative;
  overflow: hidden;
}

.course-header::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background:
    radial-gradient(circle at 20% 50%, rgba(129, 140, 248, 0.3) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(167, 139, 250, 0.2) 0%, transparent 40%);
  pointer-events: none;
}

.course-header .container {
  position: relative;
  z-index: 1;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 60px;
}

.header-info {
  flex: 1;
}

.level-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 16px;
}

.level-badge.beginner {
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.level-badge.intermediate {
  background: rgba(234, 179, 8, 0.2);
  color: #facc15;
  border: 1px solid rgba(234, 179, 8, 0.3);
}

.level-badge.advanced {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.course-title {
  font-size: 42px;
  font-weight: 900;
  line-height: 1.15;
  margin-bottom: 16px;
  letter-spacing: -0.5px;
}

.course-desc {
  font-size: 16px;
  line-height: 1.7;
  opacity: 0.85;
  margin-bottom: 28px;
  max-width: 600px;
}

.course-meta {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 24px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  opacity: 0.9;
}

.meta-item i {
  font-size: 16px;
  opacity: 0.8;
}

.course-progress {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 16px 20px;
  backdrop-filter: blur(10px);
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  font-size: 14px;
  font-weight: 600;
}

.progress-percent {
  color: #4ade80;
}

.progress-bar {
  height: 8px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4ade80, #22c55e);
  border-radius: 999px;
  transition: width 0.5s ease;
}

.header-thumb {
  width: 400px;
  height: 280px;
  border-radius: 20px;
  overflow: hidden;
  position: relative;
  flex-shrink: 0;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.header-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumb-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(to bottom, transparent 40%, rgba(0,0,0,0.7));
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 24px;
  opacity: 0;
  transition: opacity 0.3s;
}

.header-thumb:hover .thumb-overlay {
  opacity: 1;
}

.btn-continue {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 28px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-continue:hover {
  background: #4338ca;
  transform: scale(1.05);
  box-shadow: 0 8px 30px rgba(79, 70, 229, 0.5);
}

/* ===== CHAPTERS SECTION ===== */
.chapters-section {
  padding: 60px 0 80px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title i {
  color: #4f46e5;
}

.chapter-count {
  font-size: 14px;
  color: #64748b;
  background: #f1f5f9;
  padding: 6px 16px;
  border-radius: 999px;
  font-weight: 600;
}

/* ===== EMPTY STATE ===== */
.empty-chapters {
  text-align: center;
  padding: 80px 20px;
  background: white;
  border-radius: 20px;
  border: 2px dashed #e2e8f0;
}

.empty-chapters i {
  font-size: 56px;
  color: #cbd5e1;
  margin-bottom: 20px;
}

.empty-chapters h3 {
  font-size: 20px;
  color: #475569;
  margin-bottom: 8px;
}

.empty-chapters p {
  color: #94a3b8;
}

/* ===== CHAPTERS LIST ===== */
.chapters-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.chapter-item {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  transition: all 0.3s;
}

.chapter-item:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.chapter-item.is-open {
  border-color: #c7d2fe;
  box-shadow: 0 4px 25px rgba(79, 70, 229, 0.1);
}

.chapter-item.is-locked {
  opacity: 0.7;
}

/* Chapter Header */
.chapter-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 28px;
  cursor: pointer;
  user-select: none;
}

.chapter-item.is-locked .chapter-header {
  cursor: not-allowed;
}

.chapter-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.chapter-number {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 800;
  flex-shrink: 0;
  transition: all 0.3s;
}

.chapter-number.vocabulary {
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  color: #16a34a;
}

.chapter-number.grammar {
  background: linear-gradient(135deg, #fef9c3, #fef08a);
  color: #ca8a04;
}

.chapter-number.listening {
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  color: #2563eb;
}

.chapter-number.speaking {
  background: linear-gradient(135deg, #fce7f3, #fbcfe8);
  color: #db2777;
}

.chapter-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.chapter-title {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.chapter-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

.chapter-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 4px;
}

.chapter-type-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

.chapter-type-tag.vocabulary {
  background: #dcfce7;
  color: #16a34a;
}

.chapter-type-tag.grammar {
  background: #fef9c3;
  color: #ca8a04;
}

.chapter-type-tag.listening {
  background: #dbeafe;
  color: #2563eb;
}

.chapter-type-tag.speaking {
  background: #fce7f3;
  color: #db2777;
}

.chapter-lessons {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: #94a3b8;
}

.chapter-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.chapter-progress-mini {
  display: flex;
  align-items: center;
  gap: 8px;
}

.mini-progress-bar {
  width: 60px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}

.mini-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #818cf8);
  border-radius: 999px;
  transition: width 0.5s ease;
}

.mini-progress-text {
  font-size: 12px;
  font-weight: 700;
  color: #4f46e5;
}

.chapter-lock {
  color: #94a3b8;
  font-size: 16px;
}

.chapter-arrow {
  color: #94a3b8;
  font-size: 14px;
  transition: transform 0.3s;
}

.chapter-arrow.rotated {
  transform: rotate(180deg);
  color: #4f46e5;
}

/* ===== LESSONS LIST ===== */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 2000px;
}

.chapter-lessons-list {
  border-top: 1px solid #f1f5f9;
  background: linear-gradient(to bottom, #fafbfc, #ffffff);
}

.lessons-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.lessons-count {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.lessons-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px;
  color: #64748b;
  font-size: 14px;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e2e8f0;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.lesson-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-bottom: 1px solid #f1f5f9;
}

.lesson-item:last-child {
  border-bottom: none;
}

.lesson-item:hover {
  background: linear-gradient(90deg, #f0fdf4 0%, #ffffff 100%);
}

.lesson-item.is-locked {
  opacity: 0.5;
  cursor: not-allowed;
}

.lesson-item.is-locked:hover {
  background: transparent;
}

.lesson-item.is-completed .lesson-title {
  color: #16a34a;
}

.lesson-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.lesson-number {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  background: #f1f5f9;
  color: #64748b;
  transition: all 0.2s;
  flex-shrink: 0;
}

.lesson-number.completed {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
}

.lesson-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.lesson-title {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}

.lesson-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.lesson-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.lesson-type-badge.vocabulary {
  background: #dcfce7;
  color: #16a34a;
}

.lesson-type-badge.grammar {
  background: #fef9c3;
  color: #ca8a04;
}

.lesson-type-badge.listening {
  background: #dbeafe;
  color: #2563eb;
}

.lesson-type-badge.speaking {
  background: #fce7f3;
  color: #db2777;
}

.lesson-type-badge i {
  font-size: 10px;
}

.lesson-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-hoc {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-hoc:hover {
  background: #4338ca;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.btn-hoc.completed {
  background: #f1f5f9;
  color: #64748b;
}

.btn-hoc.completed:hover {
  background: #e2e8f0;
  box-shadow: none;
  transform: none;
}

.status-completed {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: white;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
}

.no-lessons {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48px 24px;
  text-align: center;
}

.no-lessons-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.no-lessons-icon i {
  font-size: 28px;
  color: #cbd5e1;
}

.no-lessons p {
  font-size: 15px;
  font-weight: 600;
  color: #475569;
  margin: 0 0 4px;
}

.no-lessons span {
  font-size: 13px;
  color: #94a3b8;
}

/* ===== CONTAINER ===== */
.container {
  max-width: 1280px;
  margin: auto;
  padding: 0 24px;
}

/* ===== PRACTICE SECTION ===== */
.practice-section {
  padding: 0 0 60px;
}

.practice-items {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.practice-item {
  display: flex;
  align-items: center;
  gap: 24px;
  background: white;
  border-radius: 18px;
  padding: 24px 28px;
  border: 2px solid #e2e8f0;
  transition: all 0.3s;
}

.practice-item.is-unlocked {
  border-color: #bfdbfe;
  background: linear-gradient(135deg, #eff6ff, #ffffff);
}

.practice-item.is-unlocked:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(79, 70, 229, 0.1);
  border-color: #4f46e5;
}

.practice-item.is-locked {
  opacity: 0.7;
}

.practice-item.is-empty {
  opacity: 0.85;
  border-color: #e2e8f0;
}

.practice-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.practice-icon i {
  font-size: 26px;
  color: white;
}

.practice-info {
  flex: 1;
}

.practice-info h3 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px;
}

.practice-info p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.practice-status {
  display: flex;
  align-items: center;
}

.btn-practice {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-practice:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(245, 158, 11, 0.45);
}

.locked-state {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 14px;
  font-weight: 600;
}

.locked-state i {
  font-size: 16px;
}

/* ===== QUIZ FINAL SECTION ===== */
.quiz-final-section {
  padding: 0 0 80px;
}

.quiz-final-card {
  display: flex;
  align-items: center;
  gap: 24px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-radius: 20px;
  padding: 28px 36px;
  border: 2px solid #fcd34d;
  transition: all 0.3s;
}

.quiz-final-card.locked {
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
  border-color: #cbd5e1;
}

.quiz-final-card.unlocked {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-color: #fcd34d;
}

.quiz-final-card.completed {
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  border-color: #86efac;
}

.quiz-final-icon {
  width: 64px;
  height: 64px;
  background: white;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

.quiz-final-icon i {
  font-size: 28px;
}

.quiz-final-card.locked .quiz-final-icon i { color: #94a3b8; }
.quiz-final-card.unlocked .quiz-final-icon i { color: #ca8a04; }
.quiz-final-card.completed .quiz-final-icon i { color: #22c55e; }

.quiz-final-info {
  flex: 1;
}

.quiz-final-info h3 {
  font-size: 20px;
  font-weight: 800;
  margin: 0 0 6px;
}

.quiz-final-card.locked .quiz-final-info h3 { color: #64748b; }
.quiz-final-card.unlocked .quiz-final-info h3 { color: #78350f; }
.quiz-final-card.completed .quiz-final-info h3 { color: #166534; }

.quiz-final-info p {
  font-size: 14px;
  margin: 0;
  line-height: 1.5;
}

.quiz-final-card.locked .quiz-final-info p { color: #94a3b8; }
.quiz-final-card.unlocked .quiz-final-info p { color: #92400e; }
.quiz-final-card.completed .quiz-final-info p { color: #166534; }

.quiz-final-info strong { font-size: 16px; }

.quiz-final-action {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.btn-quiz-final {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: #ca8a04;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s;
  white-space: nowrap;
}

.btn-quiz-final:not(:disabled):hover {
  background: #a16207;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(202, 138, 4, 0.3);
}

.btn-quiz-final.disabled,
.btn-quiz-final:disabled,
.btn-quiz-final.locked-btn {
  background: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.btn-quiz-final.completed-btn {
  background: #22c55e;
}

.btn-quiz-final.completed-btn:hover {
  background: #16a34a;
  box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
}

.quiz-final-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  text-align: center;
}

.quiz-final-hint { color: #92400e; }
.quiz-final-hint.best { color: #d97706; font-weight: 600; }
.quiz-final-hint.completed-hint { color: #166534; font-weight: 600; }

.quiz-final-hint i { font-size: 12px; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    gap: 40px;
  }

  .header-thumb {
    width: 100%;
    max-width: 500px;
    height: 300px;
  }

  .course-title {
    font-size: 32px;
  }

  .course-meta {
    flex-wrap: wrap;
    gap: 16px;
  }
}

@media (max-width: 768px) {
  .course-header {
    padding: 40px 0 60px;
  }

  .course-title {
    font-size: 26px;
  }

  .chapters-section {
    padding: 40px 0 60px;
  }

  .chapter-header {
    padding: 20px;
  }

  .lesson-item {
    padding: 14px 20px 14px 80px;
  }

  .quiz-card {
    flex-direction: column;
    text-align: center;
    padding: 24px;
  }

  .quiz-action {
    width: 100%;
  }

  .btn-quiz {
    width: 100%;
    justify-content: center;
  }

  .chapter-progress-mini {
    display: none;
  }
}

@media (max-width: 480px) {
  .breadcrumb {
    font-size: 12px;
  }

  .course-meta {
    gap: 12px;
  }

  .meta-item {
    font-size: 12px;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .chapter-left {
    gap: 14px;
  }

  .chapter-number {
    width: 44px;
    height: 44px;
    font-size: 16px;
    border-radius: 12px;
  }

  .chapter-title {
    font-size: 15px;
  }

  .lesson-item {
    padding: 12px 16px 12px 70px;
  }
}

/* Reviews Section */
.reviews-section {
  padding: 0 0 80px;
}

.reviews-section .container {
  max-width: 1280px;
  margin: auto;
  padding: 0 24px;
}
</style>
