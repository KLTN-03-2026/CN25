<template>
  <div class="tien-do">
    <div class="header">
      <h1>Quản Lý Tiến Độ</h1>
    </div>

    <div class="filter-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm theo tên người dùng, khoá học..."
        class="search-input"
      />
      <select v-model="filterKhoaHoc" class="filter-select">
        <option value="">Tất cả khoá học</option>
        <option v-for="course in courseList" :key="course.id" :value="course.id">{{ course.title }}</option>
      </select>
      <select v-model="filterTinhTrang" class="filter-select">
        <option value="">Tất cả tình trạng</option>
        <option value="hoan-thanh">Hoàn thành</option>
        <option value="dang-hoc">Đang học</option>
        <option value="chua-bat-dau">Chưa bắt đầu</option>
      </select>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-value">{{ tienDoList.length }}</div>
        <div class="stat-label">Tổng Tiến Độ</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ tiLeHoanThanh }}%</div>
        <div class="stat-label">Tỷ Lệ Hoàn Thành TB</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ dangHoc }}</div>
        <div class="stat-label">Đang Học</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ hoanThanh }}</div>
        <div class="stat-label">Hoàn Thành</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredTienDo.length === 0" class="empty-state">
      <p>Không tìm thấy tiến độ nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Người Dùng</th>
          <th>Khoá Học</th>
          <th>Bài Học Đã Học</th>
          <th>Tiến Độ</th>
          <th>Trạng Thái</th>
          <th>Cập Nhật</th>
          <th>Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in filteredTienDo" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.user_name }}</td>
          <td>{{ item.course_title }}</td>
          <td>{{ item.bai_da_hoc }} / {{ item.tong_bai }}</td>
          <td>
            <div class="progress-wrapper">
              <div class="progress-bar">
                <div
                  class="progress-fill"
                  :style="{ width: item.progress_percent + '%' }"
                  :class="getProgressClass(item.progress_percent)"
                ></div>
              </div>
              <span class="progress-text">{{ item.progress_percent }}%</span>
            </div>
          </td>
          <td>
            <span :class="['status-badge', item.tinh_trang]">{{ getTinhTrangLabel(item.tinh_trang) }}</span>
          </td>
          <td>{{ item.enrolled_at ? new Date(item.enrolled_at).toLocaleDateString('vi-VN') : '-' }}</td>
          <td class="actions">
            <button class="btn-view" @click="viewDetail(item)">Chi tiết</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showDetail" class="modal-overlay" @click.self="showDetail = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Chi Tiết Tiến Độ</h2>
          <button class="btn-close" @click="showDetail = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="detail-row">
            <span class="detail-label">Người dùng:</span>
            <span class="detail-value">{{ selectedItem?.user_name }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Email:</span>
            <span class="detail-value">{{ selectedItem?.user_email }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Khoá học:</span>
            <span class="detail-value">{{ selectedItem?.course_title }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Bài học đã học:</span>
            <span class="detail-value">{{ selectedItem?.bai_da_hoc }} / {{ selectedItem?.tong_bai }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Tiến độ:</span>
            <span class="detail-value">{{ selectedItem?.progress_percent }}%</span>
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
          <div class="detail-row">
            <span class="detail-label">Ngày đăng ký:</span>
            <span class="detail-value">{{ selectedItem?.enrolled_at ? new Date(selectedItem.enrolled_at).toLocaleDateString('vi-VN') : '-' }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Trạng thái:</span>
            <span class="detail-value">{{ getTinhTrangLabel(selectedItem?.tinh_trang) }}</span>
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
const filterTinhTrang = ref('')
const showDetail = ref(false)
const selectedItem = ref(null)
const courseList = ref([])
const tienDoList = ref([])

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
    tienDoList.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (error) {
    console.error('Lỗi khi tải tiến độ:', error)
  } finally {
    loading.value = false
  }
}

const tiLeHoanThanh = computed(() => {
  if (tienDoList.value.length === 0) return 0
  const sum = tienDoList.value.reduce((acc, i) => acc + i.progress_percent, 0)
  return Math.round(sum / tienDoList.value.length)
})

const dangHoc = computed(() => tienDoList.value.filter(i => i.tinh_trang === 'dang-hoc').length)
const hoanThanh = computed(() => tienDoList.value.filter(i => i.tinh_trang === 'hoan-thanh').length)

const getTinhTrangLabel = (tt) => {
  const labels = { 'hoan-thanh': 'Hoàn thành', 'dang-hoc': 'Đang học', 'chua-bat-dau': 'Chưa bắt đầu' }
  return labels[tt] || tt || '-'
}

const getProgressClass = (percent) => {
  if (percent >= 80) return 'good'
  if (percent >= 40) return 'medium'
  return 'low'
}

const filteredTienDo = computed(() => {
  let result = tienDoList.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      (item.user_name || '').toLowerCase().includes(q) ||
      (item.course_title || '').toLowerCase().includes(q)
    )
  }
  if (filterTinhTrang.value) {
    result = result.filter(item => item.tinh_trang === filterTinhTrang.value)
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
.tien-do { padding: 20px; }
.header { margin-bottom: 20px; }
.header h1 { font-size: 24px; font-weight: 600; margin: 0; }
.filter-bar { display: flex; gap: 12px; margin-bottom: 20px; }
.search-input { flex: 1; max-width: 400px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
.filter-select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 150px; }
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
.stat-value { font-size: 28px; font-weight: 700; color: #4f46e5; }
.stat-label { font-size: 14px; color: #666; margin-top: 4px; }
.loading, .empty-state { text-align: center; padding: 40px; color: #666; }
.data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
.data-table th { background: #f8f9fa; font-weight: 600; color: #333; }
.data-table tr:hover { background: #f8f9fa; }
.progress-wrapper { display: flex; align-items: center; gap: 10px; }
.progress-bar { flex: 1; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; min-width: 100px; }
.progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }
.progress-fill.good { background: #10b981; }
.progress-fill.medium { background: #f59e0b; }
.progress-fill.low { background: #ef4444; }
.progress-text { font-size: 13px; font-weight: 600; min-width: 40px; color: #333; }
.status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.status-badge.hoan-thanh { background: #d4edda; color: #155724; }
.status-badge.dang-hoc { background: #dbeafe; color: #1e40af; }
.status-badge.chua-bat-dau { background: #f3f4f6; color: #6b7280; }
.actions { display: flex; gap: 8px; }
.btn-view { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
.btn-view:hover { background: #2563eb; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal { background: white; border-radius: 12px; width: 90%; max-width: 500px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #eee; }
.modal-header h2 { margin: 0; font-size: 20px; }
.btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
.modal-body { padding: 20px; }
.detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
.detail-row:last-child { border-bottom: none; }
.detail-label { font-weight: 500; color: #666; }
.detail-value { color: #333; text-align: right; }
.modal-footer { display: flex; justify-content: flex-end; padding: 20px; border-top: 1px solid #eee; }
.btn-secondary { background: #e5e7eb; color: #333; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.btn-secondary:hover { background: #d1d5db; }
</style>
