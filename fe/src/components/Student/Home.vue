<template>
  <div class="landing">
    <!-- ===== HERO ===== -->
    <section class="hero">
      <!-- Ambient orbs -->
      <div class="glow-orb glow-1"></div>
      <div class="glow-orb glow-2"></div>
      <div class="glow-orb glow-3"></div>

      <div class="hero-inner">
        <div class="hero-tag">
          <div class="tag-dot"></div>
          Học tiếng Anh cùng AI
        </div>
        <h1 class="hero-heading">
          Nâng cao kỹ năng<br />
          <span class="heading-gradient">tiếng Anh mỗi ngày</span>
        </h1>
        <p class="hero-sub">
          Lộ trình học cá nhân hóa, bài tập thực tế, và phản hồi thông minh từ AI giúp bạn tự tin giao tiếp.
        </p>
        <div class="hero-actions">
          <router-link to="/auth/register" class="btn-primary">
            <i class="fa-solid fa-rocket"></i>
            Bắt đầu miễn phí
          </router-link>
          <router-link to="/student" class="btn-ghost">
            <i class="fa-solid fa-play"></i>
            Xem thử
          </router-link>
        </div>
      </div>
    </section>

    <!-- ===== STATS ===== -->
    <section class="stats-strip">
      <div class="container">
        <div class="stats-row">
          <div class="stat-item">
            <div class="stat-icon"><i class="fa-solid fa-book-open"></i></div>
            <strong>{{ publicStats?.total_courses ? formatNum(publicStats.total_courses) : '...' }}</strong>
            <span>Khóa học</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <strong>{{ publicStats?.total_students ? formatNum(publicStats.total_students) : '...' }}</strong>
            <span>Học viên</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <strong>{{ Number(publicStats?.avg_rating || 4.9).toFixed(1) }}/5</strong>
            <span>Đánh giá</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-item">
            <div class="stat-icon"><i class="fa-solid fa-award"></i></div>
            <strong>{{ publicStats?.total_certificates ? formatNum(publicStats.total_certificates) : '68' }}</strong>
            <span>Chứng chỉ</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FEATURED COURSES ===== -->
    <section class="featured-section">
      <div class="container">
        <div class="section-head">
          <div class="section-label">
            <i class="fa-solid fa-fire"></i>
            Nổi bật
          </div>
          <h2>Khóa học nổi bật</h2>
          <p class="section-desc">Những khóa học được học viên lựa chọn nhiều nhất, đáng để thử nhất.</p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="featured-grid">
          <div v-for="i in 6" :key="i" class="skeleton-card">
            <div class="skel-img"></div>
            <div class="skel-body">
              <div class="skel-line w-70"></div>
              <div class="skel-line w-90"></div>
              <div class="skel-line w-50"></div>
            </div>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="loadError" class="state-box error-box">
          <i class="fa-solid fa-circle-exclamation"></i>
          <p>Không thể tải khóa học.</p>
          <button class="btn-retry" @click="retryLoad">
            <i class="fa-solid fa-rotate-right"></i> Thử lại
          </button>
        </div>

        <!-- Featured Courses Grid -->
        <div v-else-if="featuredCourses.length" class="featured-grid">
          <div
            v-for="course in featuredCourses"
            :key="course.id"
            class="course-card"
            @click="goToCourse(course)"
          >
            <div class="course-thumb">
              <img
                :src="getThumbnail(course.thumbnail)"
                :alt="course.title"
                @error="e => e.target.src = defaultThumb"
              />
              <div class="thumb-overlay"></div>
              <span class="level-tag" :class="'level-' + course.level">
                <i class="fa-solid fa-signal"></i>
                {{ formatLevel(course.level) }}
              </span>
              <div class="course-hover-overlay">
                <i class="fa-solid fa-arrow-right"></i>
                Xem khóa học
              </div>
            </div>
            <div class="course-info">
              <h3>{{ course.title }}</h3>
              <p>{{ truncate(course.description, 90) }}</p>
              <!-- Rich course meta -->
              <div class="course-meta-row">
                <div class="course-meta">
                  <span><i class="fa-solid fa-star"></i> {{ course.avg_rating ? Number(course.avg_rating).toFixed(1) : '4.8' }}</span>
                  <span><i class="fa-solid fa-user"></i> {{ course.students_count || course.enrolled_count || '1.2k' }}</span>
                  <span><i class="fa-solid fa-clock"></i> {{ course.duration || '6h' }}</span>
                </div>
              </div>
              <div class="course-footer">
                <div class="course-meta-left">
                  <span class="course-price">
                    <span v-if="formatPrice(course.price) === 'Miễn phí'" class="free-badge">
                      <i class="fa-solid fa-gift"></i> Miễn phí
                    </span>
                    <span v-else>{{ formatPrice(course.price) }}</span>
                  </span>
                </div>
                <span class="course-chapters">
                  <i class="fa-solid fa-layer-group"></i>
                  {{ course.chapters_count || 0 }} chương
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty -->
        <div v-else class="state-box empty-box">
          <i class="fa-solid fa-inbox"></i>
          <p>Chưa có khóa học nào.</p>
        </div>
      </div>
    </section>

    <!-- ===== FEATURES ===== -->
    <section class="features-section">
      <div class="glow-bg-feature"></div>
      <div class="container">
        <div class="section-head">
          <div class="section-label">
            <i class="fa-solid fa-sparkles"></i>
            Tính năng
          </div>
          <h2>Tại sao chọn chúng tôi</h2>
          <p class="section-desc">Kết hợp công nghệ AI tiên tiến với phương pháp giáo dục hiệu quả nhất.</p>
        </div>
        <div class="features-grid">
          <div
            v-for="(f, i) in features"
            :key="f.title"
            class="feature-card"
            :style="{ '--delay': i * 0.1 + 's' }"
          >
            <div class="feature-icon" :style="{ background: f.bg }">
              <i :class="f.icon" :style="{ color: f.color }"></i>
            </div>
            <h3>{{ f.title }}</h3>
            <p>{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== TESTIMONIALS ===== -->
    <section class="testimonials-section">
      <div class="container">
        <div class="section-head">
          <div class="section-label">
            <i class="fa-solid fa-heart"></i>
            Đánh giá
          </div>
          <h2>Học viên nói gì về chúng tôi</h2>
          <p class="section-desc">Hàng nghìn học viên đã thay đổi kỹ năng tiếng Anh nhờ DTU LingoAI.</p>
        </div>

        <!-- Loading -->
        <div v-if="reviewsLoading" class="testimonials-grid">
          <div v-for="i in 3" :key="i" class="skeleton-testimonial">
            <div class="skel-stars"></div>
            <div class="skel-text"></div>
            <div class="skel-author"></div>
          </div>
        </div>

        <!-- Reviews Grid -->
        <div v-else-if="featuredReviews.length" class="testimonials-grid">
          <div
            v-for="review in featuredReviews"
            :key="review.id"
            class="testimonial-card"
          >
            <div class="tc-stars">
              <i v-for="s in 5" :key="s" :class="s <= review.rating ? 'fa-solid fa-star' : 'fa-regular fa-star'"></i>
            </div>
            <p class="tc-text">"{{ truncate(review.content, 150) }}"</p>
            <div class="tc-author">
              <div class="tc-avatar">
                <img v-if="review.user?.avatar" :src="review.user.avatar" :alt="review.user.name" />
                <span v-else>{{ (review.user?.name || 'U').charAt(0) }}</span>
              </div>
              <div class="tc-info">
                <strong>{{ review.user?.name || 'Học viên ẩn danh' }}</strong>
                <span>{{ review.course?.title || '' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty -->
        <div v-else class="state-box empty-box">
          <i class="fa-solid fa-inbox"></i>
          <p>Chưa có đánh giá nào.</p>
        </div>
      </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-card">
          <div class="cta-glow cta-glow-1"></div>
          <div class="cta-glow cta-glow-2"></div>
          <div class="cta-content">
            <div class="cta-icon">
              <i class="fa-solid fa-fire"></i>
            </div>
            <h2>10,000+ học viên đã cải thiện tiếng Anh</h2>
            <p>Bạn là người tiếp theo?</p>
            <div class="cta-urgency">
              <span class="urgency-badge"><i class="fa-solid fa-gift"></i> Miễn phí hôm nay</span>
            </div>
            <router-link to="/auth/register" class="btn-cta">
              <i class="fa-solid fa-arrow-right"></i>
              Đăng ký ngay
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { CourseService, PublicService, ReviewService } from '../../services/api'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const loadError = ref(false)
const featuredCourses = ref([])
const featuredReviews = ref([])
const reviewsLoading = ref(true)
const publicStats = ref(null)
const defaultThumb = 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800&h=450&fit=crop'

const features = [
  { icon: 'fa-solid fa-brain', bg: 'linear-gradient(135deg, #ede9fe, #f3e8ff)', color: '#7c3aed', title: 'AI cá nhân hóa', desc: 'Lộ trình học được điều chỉnh theo năng lực và mục tiêu riêng của bạn.' },
  { icon: 'fa-solid fa-comments', bg: 'linear-gradient(135deg, #d1fae5, #ecfdf5)', color: '#059669', title: 'Luyện giao tiếp', desc: 'Tập nói và nghe với phản hồi tức thì từ AI thông minh.' },
  { icon: 'fa-solid fa-chart-line', bg: 'linear-gradient(135deg, #fef3c7, #fffbeb)', color: '#d97706', title: 'Theo dõi tiến độ', desc: 'Dashboard trực quan giúp bạn biết mình tiến bộ ra sao mỗi ngày.' },
  { icon: 'fa-solid fa-certificate', bg: 'linear-gradient(135deg, #fce7f3, #fdf2f8)', color: '#db2777', title: 'Chứng chỉ', desc: 'Nhận chứng chỉ có giá trị khi hoàn thành khóa học.' },
  { icon: 'fa-solid fa-clock', bg: 'linear-gradient(135deg, #dbeafe, #eff6ff)', color: '#3b82f6', title: 'Học mọi lúc', desc: 'Bài học ngắn 15-20 phút, phù hợp với lịch trình bận rộn.' },
  { icon: 'fa-solid fa-shield-halved', bg: 'linear-gradient(135deg, #e0f2fe, #f0f9ff)', color: '#0284c7', title: 'An toàn dữ liệu', desc: 'Thông tin cá nhân của bạn được bảo mật tuyệt đối.' },
]

const fetchFeaturedReviews = async () => {
  reviewsLoading.value = true
  try {
    const res = await ReviewService.getFeatured()
    featuredReviews.value = res?.data || res || []
  } catch (err) {
    console.error('Error fetching reviews:', err)
    featuredReviews.value = []
  } finally {
    reviewsLoading.value = false
  }
}

let autoSlide = null
let scrollObserver = null

const fetchFeaturedCourses = async () => {
  loading.value = true
  loadError.value = false
  try {
    const res = await CourseService.getAll({ published_only: true, featured_only: true })
    const courses = res.data?.data ?? res.data ?? res ?? []
    console.log('Featured courses:', courses)
    featuredCourses.value = Array.isArray(courses) ? courses : []
  } catch (err) {
    console.error('Error fetching featured courses:', err)
    loadError.value = true
  } finally {
    loading.value = false
  }
}

const fetchPublicStats = async () => {
  try {
    const res = await PublicService.getStats()
    if (res.success && res.data) {
      publicStats.value = res.data
    }
  } catch {
    // fail silently
  }
}

const retryLoad = () => fetchFeaturedCourses()

const goToCourse = (course) => {
  if (authStore.isAuthenticated) {
    router.push(`/student/khoa-hoc/${course.id}`)
  } else {
    router.push('/auth/register')
  }
}

const formatLevel = (level) => {
  const map = { beginner: 'Sơ cấp', intermediate: 'Trung cấp', advanced: 'Nâng cao' }
  return map[level] || level || ''
}

const truncate = (text, len) => {
  if (!text) return ''
  return text.length > len ? text.slice(0, len) + '...' : text
}

const formatPrice = (price) => {
  const num = Number(price)
  if (num === 0 || isNaN(num)) return 'Miễn phí'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', minimumFractionDigits: 0 }).format(num)
}

const formatNum = (num) => {
  const n = Number(num)
  if (isNaN(n)) return '...'
  if (n >= 1000) return (n / 1000).toFixed(1) + 'K'
  return n.toString()
}

const getThumbnail = (thumb) => {
  if (!thumb) return defaultThumb
  if (thumb.startsWith('http')) return thumb
  return 'http://localhost:8000/uploads/' + thumb
}

const setupScrollAnimations = () => {
  scrollObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view')
        }
      })
    },
    { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
  )

  document.querySelectorAll('.animate-on-scroll').forEach((el) => {
    scrollObserver.observe(el)
  })
}

onMounted(async () => {
  await authStore.initAuth()
  await Promise.all([fetchFeaturedCourses(), fetchFeaturedReviews(), fetchPublicStats()])

  setTimeout(() => {
    setupScrollAnimations()
  }, 300)
})

onUnmounted(() => {
  if (autoSlide) clearInterval(autoSlide)
  if (scrollObserver) scrollObserver.disconnect()
})
</script>

<style scoped>
/* ===== RESET & BASE ===== */
.landing {
  background: #f8fafc;
  color: #334155;
  font-family: 'Inter', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  overflow-x: hidden;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
a { text-decoration: none; color: inherit; }

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  position: relative;
  z-index: 2;
}

/* ===== HERO ===== */
.hero {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 64px;
  max-width: 1200px;
  margin: 0 auto;
  padding: 80px 24px 64px;
  min-height: 620px;
  overflow: hidden;
  background: linear-gradient(165deg, #f0f4ff 0%, #fafbff 40%, #f8fafc 100%);
}

/* Glow orbs */
.glow-orb {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  z-index: 0;
}
.glow-1 {
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.12), transparent 70%);
  top: -200px;
  left: -200px;
}
.glow-2 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.08), transparent 70%);
  top: 0;
  right: -100px;
}
.glow-3 {
  width: 350px;
  height: 350px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.06), transparent 70%);
  bottom: -100px;
  left: 35%;
}

.hero-inner {
  flex: 1;
  position: relative;
  z-index: 2;
}

.hero-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px 8px 10px;
  background: rgba(99, 102, 241, 0.08);
  border: 1px solid rgba(99, 102, 241, 0.15);
  color: #6366f1;
  border-radius: 100px;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 24px;
}

.tag-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #6366f1;
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.6);
  animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}

.hero-heading {
  font-size: 52px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.12;
  letter-spacing: -1.5px;
  margin-bottom: 20px;
}

.heading-gradient {
  background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-sub {
  font-size: 18px;
  color: #64748b;
  line-height: 1.7;
  margin-bottom: 36px;
  max-width: 500px;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 700;
  transition: all 0.3s;
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);
  border: none;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.btn-primary::after {
  content: '';
  position: absolute;
  width: 200%;
  height: 200%;
  background: rgba(255, 255, 255, 0.2);
  transform: scale(0);
  border-radius: 50%;
  transition: transform 0.5s;
  top: 50%;
  left: 50%;
}
.btn-primary:active::after {
  transform: scale(1);
}
.btn-primary:hover {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(99, 102, 241, 0.45);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 24px;
  color: #6366f1;
  font-size: 15px;
  font-weight: 600;
  border-radius: 14px;
  transition: all 0.3s;
  border: 1.5px solid rgba(99, 102, 241, 0.25);
  background: rgba(99, 102, 241, 0.03);
  position: relative;
  overflow: hidden;
}
.btn-ghost:hover {
  background: rgba(99, 102, 241, 0.06);
  border-color: rgba(99, 102, 241, 0.4);
}

/* ===== STATS STRIP ===== */
.stats-strip {
  background: white;
  border-top: 1px solid #f1f5f9;
  border-bottom: 1px solid #f1f5f9;
  padding: 32px 0;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.stats-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 56px;
  gap: 4px;
}

.stat-icon {
  font-size: 20px;
  color: #6366f1;
  margin-bottom: 4px;
}

.stat-item strong {
  font-size: 30px;
  font-weight: 900;
  color: #0f172a;
  letter-spacing: -1px;
}

.stat-item span {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.stat-divider {
  width: 1px;
  height: 48px;
  background: #f1f5f9;
}

/* ===== SECTION HEAD ===== */
.section-head {
  text-align: center;
  margin-bottom: 48px;
}

.section-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: rgba(99, 102, 241, 0.08);
  border: 1px solid rgba(99, 102, 241, 0.15);
  color: #6366f1;
  border-radius: 100px;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 14px;
}

.section-head h2 {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.5px;
  margin-bottom: 12px;
}

.section-desc {
  font-size: 16px;
  color: #64748b;
  max-width: 520px;
  margin: 0 auto;
  line-height: 1.6;
}

/* ===== FEATURED COURSES ===== */
.featured-section {
  padding: 100px 0;
  background: white;
}

.featured-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

/* Skeleton */
.skeleton-card {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.skel-img {
  height: 200px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skel-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skel-line {
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.w-50 { width: 50%; }
.w-70 { width: 70%; }
.w-90 { width: 90%; }

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* State boxes */
.state-box {
  text-align: center;
  padding: 60px 32px;
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 20px;
  max-width: 480px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.state-box i {
  font-size: 40px;
  color: #cbd5e1;
}

.error-box i { color: #ef4444; }
.error-box { border-color: #fee2e2; background: #fef2f2; }

.state-box p {
  font-size: 15px;
  color: #64748b;
}

.btn-retry {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 20px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}
.btn-retry:hover { background: #dc2626; }

/* Course Cards */
.course-card {
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 20px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.course-card:hover {
  transform: translateY(-12px) scale(1.03);
  border-color: rgba(99, 102, 241, 0.25);
  box-shadow: 0 24px 60px rgba(99, 102, 241, 0.1), 0 8px 20px rgba(0, 0, 0, 0.06);
}

.course-thumb {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.course-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s;
}

.course-card:hover .course-thumb img {
  transform: scale(1.08);
}

.thumb-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(15, 23, 42, 0.3) 0%, transparent 60%);
  pointer-events: none;
  z-index: 1;
}

.level-tag {
  position: absolute;
  top: 12px;
  left: 12px;
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  color: white;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 5px;
  backdrop-filter: blur(10px);
}

.level-tag i { font-size: 10px; }

.level-beginner { background: rgba(34, 197, 94, 0.85) !important; }
.level-intermediate { background: rgba(245, 158, 11, 0.85) !important; }
.level-advanced { background: rgba(239, 68, 68, 0.85) !important; }

.course-hover-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(99, 102, 241, 0.88);
  color: white;
  font-size: 14px;
  font-weight: 700;
  opacity: 0;
  transition: opacity 0.3s;
  z-index: 3;
  backdrop-filter: blur(4px);
}

.course-card:hover .course-hover-overlay {
  opacity: 1;
}

.course-info {
  padding: 22px;
}

.course-info h3 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.course-info p {
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 12px;
}

/* Rich course meta */
.course-meta-row {
  margin-bottom: 14px;
}

.course-meta {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.course-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
}

.course-meta span i {
  font-size: 11px;
  color: #f59e0b;
}

.course-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}

.course-price {
  font-size: 18px;
  font-weight: 800;
  color: #dc2626;
}

.free-badge {
  color: #059669;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.course-chapters {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: #64748b;
}

/* ===== FEATURES ===== */
.features-section {
  padding: 100px 0;
  position: relative;
  background: linear-gradient(180deg, #f8fafc 0%, white 100%);
}

.glow-bg-feature {
  position: absolute;
  width: 700px;
  height: 700px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.05), transparent 70%);
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.feature-card {
  padding: 28px 24px;
  background: white;
  border: 1px solid #f1f5f9;
  border-radius: 20px;
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.feature-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  opacity: 0;
  transition: opacity 0.3s;
}

.feature-card:hover {
  transform: translateY(-8px);
  border-color: rgba(99, 102, 241, 0.15);
  box-shadow: 0 20px 40px rgba(99, 102, 241, 0.1);
}

.feature-card:hover::before {
  opacity: 1;
}

.feature-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin-bottom: 18px;
}

.feature-card h3 {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}

.feature-card p {
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
}

/* ===== TESTIMONIALS ===== */
.testimonials-section {
  padding: 100px 0;
  background: white;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.testimonial-card {
  padding: 28px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 20px;
  transition: all 0.35s;
  position: relative;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 220px;
}

.testimonial-card:hover {
  transform: translateY(-6px);
  border-color: rgba(99, 102, 241, 0.15);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
  background: white;
}

.tc-stars {
  display: flex;
  gap: 3px;
  margin-bottom: 16px;
}
.tc-stars i {
  font-size: 12px;
  color: #f59e0b;
}

.tc-text {
  font-size: 14px;
  color: #475569;
  line-height: 1.7;
  margin-bottom: 20px;
  font-style: italic;
  flex-grow: 1;
}

.tc-author {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: auto;
}

.tc-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 800;
  color: white;
  flex-shrink: 0;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  overflow: hidden;
}

.tc-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.tc-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.tc-info strong {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tc-info span {
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Skeleton for testimonials */
.skeleton-testimonial {
  padding: 28px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 220px;
}

.skel-stars {
  height: 14px;
  width: 80px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  margin-bottom: 16px;
}

.skel-text {
  height: 80px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
  margin-bottom: 20px;
}

.skel-author {
  height: 44px;
  width: 160px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}

/* ===== CTA ===== */
.cta-section {
  padding: 0 0 100px;
}

.cta-card {
  background: linear-gradient(135deg, #4f46e5, #7c3aed, #6366f1);
  border-radius: 28px;
  padding: 72px 48px;
  text-align: center;
  position: relative;
  overflow: hidden;
  box-shadow: 0 24px 80px rgba(99, 102, 241, 0.3);
  transition: all 0.35s;
}

.cta-card:hover {
  transform: scale(1.01);
  box-shadow: 0 32px 100px rgba(99, 102, 241, 0.4);
}

.cta-glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}
.cta-glow-1 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.12), transparent 70%);
  top: -100px;
  right: -100px;
}
.cta-glow-2 {
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.08), transparent 70%);
  bottom: -80px;
  left: -80px;
}

.cta-content {
  position: relative;
  z-index: 2;
}

.cta-icon {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  margin: 0 auto 24px;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.cta-card h2 {
  font-size: 36px;
  font-weight: 900;
  color: white;
  margin-bottom: 14px;
  letter-spacing: -0.5px;
}

.cta-card p {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 20px;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.cta-urgency {
  margin-bottom: 24px;
}

.urgency-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 20px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 100px;
  font-size: 14px;
  font-weight: 700;
  color: white;
  backdrop-filter: blur(8px);
}

.btn-cta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 16px 36px;
  background: white;
  color: #4f46e5;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  transition: all 0.3s;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  position: relative;
  overflow: hidden;
}
.btn-cta::after {
  content: '';
  position: absolute;
  width: 200%;
  height: 200%;
  background: rgba(99, 102, 241, 0.15);
  transform: scale(0);
  border-radius: 50%;
  transition: transform 0.5s;
  top: 50%;
  left: 50%;
}
.btn-cta:active::after {
  transform: scale(1);
}
.btn-cta:hover {
  background: #f8fafc;
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  .hero { flex-direction: column; padding: 60px 24px 48px; text-align: center; gap: 40px; min-height: auto; }
  .hero-sub { margin: 0 auto 36px; }
  .hero-actions { justify-content: center; }
  .trust-inner { flex-direction: column; gap: 16px; }
  .stats-row { flex-wrap: wrap; gap: 16px; }
  .stat-divider { display: none; }
  .stat-item { padding: 0 32px; }
  .featured-grid { grid-template-columns: repeat(2, 1fr); }
  .features-grid { grid-template-columns: repeat(2, 1fr); }
  .testimonials-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .hero-heading { font-size: 36px; }
  .heading-gradient { font-size: 36px; }
  .hero-sub { font-size: 16px; }
  .stats-row { gap: 12px; }
  .stat-item strong { font-size: 24px; }
  .featured-section, .features-section, .testimonials-section { padding: 60px 0; }
  .section-head h2 { font-size: 26px; }
  .featured-grid { grid-template-columns: 1fr; }
  .features-grid { grid-template-columns: 1fr; }
  .testimonials-grid { grid-template-columns: 1fr; }
  .cta-card { padding: 48px 24px; }
  .cta-card h2 { font-size: 28px; }
}

@media (max-width: 480px) {
  .hero-heading { font-size: 28px; }
  .heading-gradient { font-size: 28px; }
  .hero-actions { flex-direction: column; width: 100%; }
  .btn-primary, .btn-ghost, .btn-cta { width: 100%; justify-content: center; }
  .stats-row { grid-template-columns: 1fr 1fr; }
}
</style>
