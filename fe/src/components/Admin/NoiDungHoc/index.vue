<template>
  <div class="noi-dung-hoc">
    <div class="header">
      <h1>Quản Lý Nội Dung Học</h1>
      <button class="btn-primary" @click="showModal = true">
        <span>+</span> Thêm Nội Dung
      </button>
    </div>

    <div class="filter-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm nội dung học..."
        class="search-input"
      />
      <select v-model="filterLoai" class="filter-select">
        <option value="">Tất cả loại</option>
        <option value="bai-giang">Bài giảng</option>
        <option value="video">Video</option>
        <option value="tai-lieu">Tài liệu</option>
        <option value="audio">Audio</option>
      </select>
      <select v-model="filterKhoaHoc" class="filter-select">
        <option value="">Tất cả khoá học</option>
        <option v-for="course in courseList" :key="course.id" :value="course.id">{{ course.title }}</option>
      </select>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-value">{{ noiDungList.length }}</div>
        <div class="stat-label">Tổng Nội Dung</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ noiDungVideo }}</div>
        <div class="stat-label">Video</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ noiDungTaiLieu }}</div>
        <div class="stat-label">Tài Liệu</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredNoiDung.length === 0" class="empty-state">
      <p>Không tìm thấy nội dung nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tiêu Đề</th>
          <th>Loại</th>
          <th>Khoá Học</th>
          <th>Thời Lượng</th>
          <th>Trạng Thái</th>
          <th>Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in filteredNoiDung" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.title }}</td>
          <td>
            <span :class="['badge', item.type]">{{ getLoaiLabel(item.type) }}</span>
          </td>
          <td>{{ item.course?.title || '-' }}</td>
          <td>{{ item.duration || '-' }}</td>
          <td>
            <span :class="['status', item.is_active ? 'active' : 'inactive']">
              {{ item.is_active ? 'Hoạt động' : 'Không hoạt động' }}
            </span>
          </td>
          <td class="actions">
            <button class="btn-view" @click="viewContent(item)">Xem</button>
            <button class="btn-edit" @click="editItem(item)">Sửa</button>
            <button class="btn-delete" @click="deleteItem(item.id)">Xoá</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingItem ? 'Sửa Nội Dung' : 'Thêm Nội Dung' }}</h2>
          <button class="btn-close" @click="showModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề</label>
            <input v-model="formData.title" type="text" placeholder="Nhập tiêu đề nội dung" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Loại Nội Dung</label>
              <select v-model="formData.type">
                <option value="bai-giang">Bài giảng</option>
                <option value="video">Video</option>
                <option value="tai-lieu">Tài liệu</option>
                <option value="audio">Audio</option>
              </select>
            </div>
            <div class="form-group">
              <label>Khoá Học</label>
              <select v-model="formData.course_id">
                <option value="">Chọn khoá học</option>
                <option v-for="course in courseList" :key="course.id" :value="course.id">{{ course.title }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Link Nội Dung</label>
            <input v-model="formData.content_url" type="text" placeholder="https://..." />
          </div>
          <div class="form-group">
            <label>Mô Tả</label>
            <textarea v-model="formData.description" rows="3" placeholder="Nhập mô tả nội dung"></textarea>
          </div>
          <div class="form-group">
            <label>
              <input v-model="formData.is_active" type="checkbox" :checked="formData.is_active" />
              Hoạt động
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showModal = false">Huỷ</button>
          <button class="btn-primary" @click="saveItem">{{ editingItem ? 'Cập nhật' : 'Thêm mới' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ContentService, CourseService } from '../../../services/api.js'

const loading = ref(false)
const showModal = ref(false)
const editingItem = ref(null)
const searchQuery = ref('')
const filterLoai = ref('')
const filterKhoaHoc = ref('')
const courseList = ref([])

const formData = ref({
  title: '',
  type: 'video',
  course_id: '',
  content_url: '',
  description: '',
  duration: '',
  is_active: true
})

const noiDungList = ref([])

const fetchCourses = async () => {
  try {
    const data = await CourseService.getAll()
    courseList.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (error) {
    console.error('Lỗi khi tải danh sách khóa học:', error)
  }
}

const fetchContents = async () => {
  loading.value = true
  try {
    const params = {}
    if (filterLoai.value) params.type = filterLoai.value
    if (filterKhoaHoc.value) params.course_id = filterKhoaHoc.value
    if (searchQuery.value) params.search = searchQuery.value
    const data = await ContentService.getAll(params)
    noiDungList.value = Array.isArray(data) ? data : (data.data || [])
  } catch (error) {
    console.error('Lỗi khi tải nội dung:', error)
  } finally {
    loading.value = false
  }
}

const noiDungVideo = computed(() => noiDungList.value.filter(i => i.type === 'video').length)
const noiDungTaiLieu = computed(() => noiDungList.value.filter(i => i.type === 'tai-lieu').length)

const getLoaiLabel = (loai) => {
  const labels = { 'bai-giang': 'Bài giảng', 'video': 'Video', 'tai-lieu': 'Tài liệu', 'audio': 'Audio' }
  return labels[loai] || loai
}

const filteredNoiDung = computed(() => {
  let result = noiDungList.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      (item.title || '').toLowerCase().includes(q) ||
      (item.course?.title || '').toLowerCase().includes(q)
    )
  }
  if (filterLoai.value) {
    result = result.filter(item => item.type === filterLoai.value)
  }
  return result
})

const viewContent = (item) => {
  console.log('Xem nội dung:', item)
}

const editItem = (item) => {
  editingItem.value = item.id
  formData.value = {
    title: item.title || '',
    type: item.type || 'video',
    course_id: item.course_id || '',
    content_url: item.content_url || '',
    description: item.description || '',
    duration: item.duration || '',
    is_active: item.is_active !== false
  }
  showModal.value = true
}

const saveItem = async () => {
  try {
    loading.value = true
    if (editingItem.value) {
      await ContentService.update(editingItem.value, formData.value)
      alert('Cập nhật thành công!')
    } else {
      await ContentService.create(formData.value)
      alert('Thêm nội dung thành công!')
    }
    showModal.value = false
    editingItem.value = null
    resetForm()
    await fetchContents()
  } catch (error) {
    console.error('Lỗi khi lưu:', error)
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

const deleteItem = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xoá nội dung này?')) {
    try {
      await ContentService.delete(id)
      await fetchContents()
    } catch (error) {
      console.error('Lỗi khi xoá:', error)
    }
  }
}

const resetForm = () => {
  formData.value = {
    title: '',
    type: 'video',
    course_id: '',
    content_url: '',
    description: '',
    duration: '',
    is_active: true
  }
}

onMounted(() => {
  fetchCourses()
  fetchContents()
})
</script>

<style scoped>
.noi-dung-hoc {
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
  grid-template-columns: repeat(3, 1fr);
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

.badge.bai-giang { background: #dbeafe; color: #1e40af; }
.badge.video { background: #fce7f3; color: #9d174d; }
.badge.tai-lieu { background: #fef3c7; color: #92400e; }
.badge.audio { background: #e0e7ff; color: #3730a3; }

.status {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status.active { background: #d4edda; color: #155724; }
.status.inactive { background: #f8d7da; color: #721c24; }

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

.btn-edit {
  background: #10b981;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}

.btn-edit:hover {
  background: #059669;
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
}

.modal-body {
  padding: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
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

.form-group input[type="checkbox"] {
  margin-right: 8px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}
</style>
