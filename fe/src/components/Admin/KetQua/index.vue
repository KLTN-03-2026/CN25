<template>
  <div class="ket-qua">
    <div class="header">
      <h1>Quản Lý Kết Quả</h1>
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
      <select v-model="filterLoai" class="filter-select">
        <option value="">Tất cả loại</option>
        <option value="kiem-tra">Kiểm tra</option>
        <option value="bai-tap">Bài tập</option>
        <option value="thi">Thi</option>
      </select>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-value">{{ tongSo }}</div>
        <div class="stat-label">Tổng Kết Quả</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ datTyLe }}%</div>
        <div class="stat-label">Tỷ Lệ Đạt</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ diemTrungBinh.toFixed(1) }}</div>
        <div class="stat-label">Điểm Trung Bình</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ diemCaoNhat }}</div>
        <div class="stat-label">Điểm Cao Nhất</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredKetQua.length === 0" class="empty-state">
      <p>Không tìm thấy kết quả nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Người Dùng</th>
          <th>Khoá Học / Bài Học</th>
          <th>Loại</th>
          <th>Điểm</th>
          <th>Thời Gian</th>
          <th>Ngày Làm</th>
          <th>Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in filteredKetQua" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.user?.name || '-' }}</td>
          <td>{{ item.course?.title || (item.lesson_id ? 'Bài học #' + item.lesson_id : '-') }}</td>
          <td>
            <span :class="['badge', item.type]">{{ getLoaiLabel(item.type) }}</span>
          </td>
          <td>
            <span :class="['score', item.passed ? 'pass' : 'fail']">
              {{ item.percentage || 0 }}/100
            </span>
          </td>
          <td>{{ item.time_spent ? item.time_spent + ' phút' : '-' }}</td>
          <td>{{ item.created_at ? new Date(item.created_at).toLocaleDateString('vi-VN') : '-' }}</td>
          <td class="actions">
            <button class="btn-view" @click="viewDetail(item)">Chi tiết</button>
            <button class="btn-delete" @click="deleteItem(item.id)">Xoá</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showDetail" class="modal-overlay" @click.self="showDetail = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Chi Tiết Kết Quả</h2>
          <button class="btn-close" @click="showDetail = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="detail-row">
            <span class="detail-label">Người dùng:</span>
            <span class="detail-value">{{ selectedItem?.hoTen }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Email:</span>
            <span class="detail-value">{{ selectedItem?.email }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Khoá học:</span>
            <span class="detail-value">{{ selectedItem?.khoaHoc }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Loại:</span>
            <span class="detail-value">{{ getLoaiLabel(selectedItem?.loai) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Điểm số:</span>
            <span class="detail-value">{{ selectedItem?.phanTram }}/100 ({{ selectedItem?.diem }}/{{ selectedItem?.diemToiDa }})</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Kết quả:</span>
            <span :class="['status-badge', selectedItem?.dat ? 'pass' : 'fail']">
              {{ selectedItem?.dat ? 'Đạt' : 'Không đạt' }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Lần thi:</span>
            <span class="detail-value">{{ selectedItem?.lanThi }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Thời gian làm:</span>
            <span class="detail-value">{{ selectedItem?.thoiGian }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Ngày làm:</span>
            <span class="detail-value">{{ selectedItem?.ngayLam }}</span>
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
import { ResultService, CourseService } from '../../../services/api.js'

const loading = ref(false)
const searchQuery = ref('')
const filterKhoaHoc = ref('')
const filterLoai = ref('')
const showDetail = ref(false)
const selectedItem = ref(null)
const courseList = ref([])

const ketQuaList = ref([])

const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courseList.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (error) {
    console.error('Lỗi khi tải danh sách khóa học:', error)
  }
}

const fetchResults = async () => {
  loading.value = true
  try {
    const params = {}
    if (filterKhoaHoc.value) params.course_id = filterKhoaHoc.value
    if (filterLoai.value) params.type = filterLoai.value
    if (searchQuery.value) params.search = searchQuery.value
    params.per_page = 100
    const data = await ResultService.getAll(params)
    ketQuaList.value = data.data || data || []
  } catch (error) {
    console.error('Lỗi khi tải kết quả:', error)
  } finally {
    loading.value = false
  }
}

const tongSo = computed(() => ketQuaList.value.length)
const datTyLe = computed(() => {
  if (ketQuaList.value.length === 0) return 0
  const dat = ketQuaList.value.filter(i => i.passed).length
  return Math.round((dat / ketQuaList.value.length) * 100)
})
const diemTrungBinh = computed(() => {
  if (ketQuaList.value.length === 0) return 0
  const sum = ketQuaList.value.reduce((acc, i) => acc + (i.percentage || 0), 0)
  return sum / ketQuaList.value.length
})
const diemCaoNhat = computed(() => {
  if (ketQuaList.value.length === 0) return 0
  return Math.max(...ketQuaList.value.map(i => i.percentage || 0))
})

const getLoaiLabel = (loai) => {
  const labels = { 'kiem-tra': 'Kiểm tra', 'bai-tap': 'Bài tập', 'thi': 'Thi' }
  return labels[loai] || loai || '-'
}

const filteredKetQua = computed(() => {
  let result = ketQuaList.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      (item.user?.name || '').toLowerCase().includes(q) ||
      (item.course?.title || '').toLowerCase().includes(q)
    )
  }
  if (filterLoai.value) {
    result = result.filter(item => item.type === filterLoai.value)
  }
  return result
})

const viewDetail = (item) => {
  selectedItem.value = {
    hoTen: item.user?.name || '-',
    email: item.user?.email || '-',
    khoaHoc: item.course?.title || (item.lesson_id ? 'Bài học #' + item.lesson_id : '-'),
    loai: item.type,
    diem: item.score || 0,
    diemToiDa: item.max_score || 100,
    phanTram: item.percentage || 0,
    dat: item.passed,
    thoiGian: item.time_spent || '-',
    ngayLam: item.created_at ? new Date(item.created_at).toLocaleDateString('vi-VN', {
      day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    }) : '-',
    lanThi: item.attempt || 1
  }
  showDetail.value = true
}

const deleteItem = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xoá kết quả này?')) {
    try {
      await ResultService.delete(id)
      await fetchResults()
    } catch (error) {
      console.error('Lỗi khi xoá:', error)
    }
  }
}

onMounted(() => {
  fetchCourses()
  fetchResults()
})
</script>

<style scoped>
.ket-qua {
  padding: 20px;
}

.header {
  margin-bottom: 20px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.search-input {
  flex: 1;
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
  min-width: 150px;
}

.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 20px;
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
  font-size: 14px;
  color: #666;
  margin-top: 4px;
}

.loading, .empty-state {
  text-align: center;
  padding: 40px;
  color: #666;
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

.badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.badge.kiem-tra { background: #dbeafe; color: #1e40af; }
.badge.bai-tap { background: #d1fae5; color: #065f46; }
.badge.thi { background: #fef3c7; color: #92400e; }

.score {
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 13px;
}

.score.pass {
  background: #d4edda;
  color: #155724;
}

.score.fail {
  background: #f8d7da;
  color: #721c24;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-view {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.btn-view:hover {
  background: #2563eb;
}

.btn-delete {
  background: #ef4444;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.btn-delete:hover {
  background: #dc2626;
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
  max-width: 500px;
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

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #666;
}

.detail-value {
  color: #333;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 500;
}

.status-badge.pass {
  background: #d4edda;
  color: #155724;
}

.status-badge.fail {
  background: #f8d7da;
  color: #721c24;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 20px;
  border-top: 1px solid #eee;
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
</style>
