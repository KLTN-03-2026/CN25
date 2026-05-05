<template>
  <div class="tien-do-hoc">
    <div class="header">
      <h1>Quản Lý Tiến Độ Học Tập</h1>
    </div>

    <div class="filter-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm theo tên học viên..."
        class="search-input"
      />
      <select v-model="filterKhoaHoc" class="filter-select">
        <option value="">Tất cả khoá học</option>
        <option v-for="course in courseList" :key="course.id" :value="course.id">{{ course.title }}</option>
      </select>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-value">{{ hocVienList.length }}</div>
        <div class="stat-label">Tổng Học Viên</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ soBaiHocTrungBinh }}</div>
        <div class="stat-label">Bài Học TB / HV</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ soBaiTapHoanThanh }}</div>
        <div class="stat-label">Bài Tập Hoàn Thành</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ thoiGianHocTrungBinh }}</div>
        <div class="stat-label">Giờ Học TB</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredHocVien.length === 0" class="empty-state">
      <p>Không tìm thấy học viên nào.</p>
    </div>

    <div v-else class="student-list">
      <div v-for="item in filteredHocVien" :key="item.id" class="student-card">
        <div class="student-header">
          <div class="student-info">
            <div class="avatar-placeholder">{{ (item.user_name || '?').charAt(0).toUpperCase() }}</div>
            <div>
              <div class="student-name">{{ item.user_name }}</div>
              <div class="student-email">{{ item.user_email }}</div>
            </div>
          </div>
          <div class="student-stats-mini">
            <div class="mini-stat">
              <span class="mini-value">{{ item.course_title }}</span>
              <span class="mini-label">Khoá học</span>
            </div>
          </div>
        </div>
        <div class="student-progress">
          <div class="progress-header">
            <span>Tiến độ khoá học</span>
            <span class="progress-percent">{{ item.progress_percent }}%</span>
          </div>
          <div class="progress-bar-container">
            <div class="progress-bar-fill" :style="{ width: item.progress_percent + '%' }"></div>
          </div>
        </div>
        <div class="student-details">
          <div class="detail-item">
            <span class="detail-icon">📚</span>
            <span class="detail-text">{{ item.bai_da_hoc }}/{{ item.tong_bai }} bài học</span>
          </div>
          <div class="detail-item">
            <span class="detail-icon">⏱️</span>
            <span class="detail-text">{{ item.thoi_gian_hoc_formatted || '0s' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-icon">📝</span>
            <span class="detail-text">{{ item.practice_score ? item.practice_score + 'đ LT' : '-' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-icon">🏆</span>
            <span class="detail-text">{{ item.final_score ? item.final_score + 'đ thi' : '-' }}</span>
          </div>
        </div>
        <div class="student-footer">
          <span class="last-activity">Đăng ký: {{ item.enrolled_at ? new Date(item.enrolled_at).toLocaleDateString('vi-VN') : '-' }}</span>
          <button class="btn-detail" @click="viewDetail(item)">Chi tiết</button>
        </div>
      </div>
    </div>

    <div v-if="showDetail" class="modal-overlay" @click.self="showDetail = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Chi Tiết Học Tập - {{ selectedItem?.user_name }}</h2>
          <button class="btn-close" @click="showDetail = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="detail-section">
            <h3>Thông Tin Khoá Học</h3>
            <div class="detail-row">
              <span class="detail-label">Khoá học:</span>
              <span class="detail-value">{{ selectedItem?.course_title }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Tiến độ:</span>
              <span class="detail-value">{{ selectedItem?.progress_percent }}%</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Ngày đăng ký:</span>
              <span class="detail-value">{{ selectedItem?.enrolled_at ? new Date(selectedItem.enrolled_at).toLocaleDateString('vi-VN') : '-' }}</span>
            </div>
          </div>
          <div class="detail-section">
            <h3>Thống Kê Học Tập</h3>
            <div class="detail-row">
              <span class="detail-label">Bài học đã học:</span>
              <span class="detail-value">{{ selectedItem?.bai_da_hoc }} / {{ selectedItem?.tong_bai }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Thời gian học:</span>
              <span class="detail-value">{{ selectedItem?.thoi_gian_hoc_formatted || '-' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Điểm luyện tập:</span>
              <span class="detail-value">{{ selectedItem?.practice_score ? selectedItem.practice_score + '/100' : '-' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Điểm thi cuối khoá:</span>
              <span class="detail-value">{{ selectedItem?.final_score ? selectedItem.final_score + '/100' : '-' }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showDetail = false">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ProgressService, CourseService } from '../../../services/api.js'

const loading = ref(false)
const searchQuery = ref('')
const filterKhoaHoc = ref('')
const showDetail = ref(false)
const selectedItem = ref(null)
const courseList = ref([])
const hocVienList = ref([])

const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courseList.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (error) {
    console.error('Lỗi khi tải danh sách khóa học:', error)
  }
}

const fetchProgress = async () => {
  loading.value = true
  try {
    const params = {}
    if (filterKhoaHoc.value) params.course_id = filterKhoaHoc.value
    if (searchQuery.value) params.search = searchQuery.value
    const data = await ProgressService.getAllProgress(params)
    hocVienList.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (error) {
    console.error('Lỗi khi tải tiến độ:', error)
  } finally {
    loading.value = false
  }
}

const soBaiHocTrungBinh = computed(() => {
  if (hocVienList.value.length === 0) return 0
  const sum = hocVienList.value.reduce((acc, i) => acc + i.bai_da_hoc, 0)
  return Math.round(sum / hocVienList.value.length)
})

const soBaiTapHoanThanh = computed(() =>
  hocVienList.value.reduce((acc, i) => acc + (i.practice_score ? 1 : 0), 0)
)

const thoiGianHocTrungBinh = computed(() => {
  if (hocVienList.value.length === 0) return 0
  const sum = hocVienList.value.reduce((acc, i) => acc + (i.thoi_gian_hoc || 0), 0)
  return Math.round((sum / hocVienList.value.length) / 60)
})

const filteredHocVien = computed(() => {
  let result = hocVienList.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      (item.user_name || '').toLowerCase().includes(q) ||
      (item.user_email || '').toLowerCase().includes(q)
    )
  }
  if (filterKhoaHoc.value) {
    result = result.filter(item => item.course_id == filterKhoaHoc.value)
  }
  return result
})

const viewDetail = (item) => {
  selectedItem.value = item
  showDetail.value = true
}

onMounted(() => {
  fetchCourses()
  fetchProgress()
})
</script>

<style scoped>
.tien-do-hoc { padding: 20px; }
.header { margin-bottom: 20px; }
.header h1 { font-size: 24px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; margin-bottom: 20px; }
.search-input { flex: 1; max-width: 400px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
.filter-select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 180px; }
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
.stat-value { font-size: 28px; font-weight: 700; color: #4f46e5; }
.stat-label { font-size: 14px; color: #666; margin-top: 4px; }
.loading, .empty-state { text-align: center; padding: 40px; color: #666; }
.student-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
.student-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.student-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.student-info { display: flex; align-items: center; gap: 12px; }
.avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
.avatar-placeholder { width: 48px; height: 48px; border-radius: 50%; background: #4f46e5; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; }
.student-name { font-weight: 600; color: #333; font-size: 15px; }
.student-email { font-size: 12px; color: #999; }
.mini-stat { text-align: right; }
.mini-value { display: block; font-size: 12px; font-weight: 600; color: #4f46e5; }
.mini-label { display: block; font-size: 11px; color: #999; }
.student-progress { margin-bottom: 16px; }
.progress-header { display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 6px; }
.progress-percent { font-weight: 600; color: #4f46e5; }
.progress-bar-container { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: linear-gradient(90deg, #4f46e5, #7c3aed); border-radius: 4px; transition: width 0.3s ease; }
.student-details { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; padding: 12px; background: #f8f9fa; border-radius: 8px; }
.detail-item { display: flex; align-items: center; gap: 6px; }
.detail-icon { font-size: 14px; }
.detail-text { font-size: 13px; color: #666; }
.student-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #eee; }
.last-activity { font-size: 12px; color: #999; }
.btn-detail { background: #4f46e5; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
.btn-detail:hover { background: #4338ca; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: white; border-radius: 12px; width: 90%; max-width: 500px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #eee; }
.modal-header h2 { margin: 0; font-size: 18px; }
.btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
.modal-body { padding: 20px; }
.detail-section { margin-bottom: 20px; }
.detail-section:last-child { margin-bottom: 0; }
.detail-section h3 { font-size: 14px; font-weight: 600; color: #333; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 0.5px; }
.detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
.detail-row:last-child { border-bottom: none; }
.detail-label { font-size: 13px; color: #666; }
.detail-value { font-size: 13px; font-weight: 500; color: #333; }
.modal-footer { display: flex; justify-content: flex-end; padding: 20px; border-top: 1px solid #eee; }
.btn-secondary { background: #e5e7eb; color: #333; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-secondary:hover { background: #d1d5db; }
</style>
