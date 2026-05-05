<template>
  <div class="bai-tap">
    <div class="header">
      <h1>Quản Lý Bài Tập</h1>
      <button class="btn-primary" @click="showModal = true">
        <span>+</span> Thêm Bài Tập
      </button>
    </div>

    <div class="filter-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm bài tập..."
        class="search-input"
      />
      <select v-model="filterLoai" class="filter-select">
        <option value="">Tất cả loại</option>
        <option value="trac-nghiem">Trắc nghiệm</option>
        <option value="dien-tu">Điền từ</option>
        <option value="noi">Nói</option>
        <option value="viet">Viết</option>
      </select>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredBaiTap.length === 0" class="empty-state">
      <p>Không tìm thấy bài tập nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tiêu Đề</th>
          <th>Loại</th>
          <th>Bài Học</th>
          <th>Điểm</th>
          <th>Trạng Thái</th>
          <th>Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in filteredBaiTap" :key="item.id">
          <td>{{ item.id }}</td>
          <td>{{ item.title }}</td>
          <td>
            <span :class="['badge', item.type]">{{ getLoaiLabel(item.type) }}</span>
          </td>
          <td>{{ item.lesson?.title || (item.course?.title || '-') }}</td>
          <td>{{ item.points || 10 }}/100</td>
          <td>
            <span :class="['status', item.is_active ? 'active' : 'inactive']">
              {{ item.is_active ? 'Hoạt động' : 'Không hoạt động' }}
            </span>
          </td>
          <td class="actions">
            <button class="btn-edit" @click="editItem(item)">Sửa</button>
            <button class="btn-delete" @click="deleteItem(item.id)">Xoá</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingItem ? 'Sửa Bài Tập' : 'Thêm Bài Tập' }}</h2>
          <button class="btn-close" @click="showModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề</label>
            <input v-model="formData.title" type="text" placeholder="Nhập tiêu đề bài tập" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Loại Bài Tập</label>
              <select v-model="formData.type">
                <option value="trac-nghiem">Trắc nghiệm</option>
                <option value="dien-tu">Điền từ</option>
                <option value="noi">Nói</option>
                <option value="viet">Viết</option>
              </select>
            </div>
            <div class="form-group">
              <label>Bài Học</label>
              <select v-model="formData.lesson_id">
                <option value="">Chọn bài học</option>
                <option value="1">Bài 1: Giới thiệu</option>
                <option value="2">Bài 2: Bảng chữ cái</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Điểm Tối Đa</label>
              <input v-model.number="formData.points" type="number" min="1" max="100" />
            </div>
            <div class="form-group">
              <label>Thứ Tự</label>
              <input v-model.number="formData.order" type="number" min="1" />
            </div>
          </div>
          <div class="form-group">
            <label>Nội Dung / Câu Hỏi</label>
            <textarea v-model="formData.content" rows="4" placeholder="Nhập nội dung bài tập"></textarea>
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
import { ExerciseService } from '../../../services/api.js'

const loading = ref(false)
const showModal = ref(false)
const editingItem = ref(null)
const searchQuery = ref('')
const filterLoai = ref('')

const formData = ref({
  tieuDe: '',
  loai: 'trac-nghiem',
  baiHocId: '',
  baiHoc: '',
  diem: 10,
  thuTu: 1,
  noiDung: '',
  trangThai: true
})

const baiTapList = ref([])

const fetchExercises = async () => {
  loading.value = true
  try {
    const params = {}
    if (filterLoai.value) params.type = filterLoai.value
    if (searchQuery.value) params.search = searchQuery.value
    const data = await ExerciseService.getAll(params)
    baiTapList.value = Array.isArray(data) ? data : (data.data || [])
  } catch (error) {
    console.error('Lỗi khi tải bài tập:', error)
  } finally {
    loading.value = false
  }
}

const getLoaiLabel = (loai) => {
  const labels = {
    'trac-nghiem': 'Trắc nghiệm',
    'dien-tu': 'Điền từ',
    'noi': 'Nói',
    'viet': 'Viết'
  }
  return labels[loai] || loai
}

const filteredBaiTap = computed(() => {
  let result = baiTapList.value
  if (searchQuery.value) {
    result = result.filter(item =>
      (item.title || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }
  if (filterLoai.value) {
    result = result.filter(item => item.type === filterLoai.value)
  }
  return result
})

const editItem = (item) => {
  editingItem.value = item.id
  formData.value = {
    title: item.title || '',
    type: item.type || 'trac-nghiem',
    lesson_id: item.lesson_id || '',
    content: item.content || '',
    options: item.options || [],
    correct_answer: item.correct_answer || '',
    points: item.points || 10,
    order: item.order || 1,
    is_active: item.is_active !== false
  }
  showModal.value = true
}

const saveItem = async () => {
  try {
    loading.value = true
    if (editingItem.value) {
      await ExerciseService.update(editingItem.value, formData.value)
      alert('Cập nhật thành công!')
    } else {
      await ExerciseService.create(formData.value)
      alert('Thêm bài tập thành công!')
    }
    showModal.value = false
    editingItem.value = null
    resetForm()
    await fetchExercises()
  } catch (error) {
    console.error('Lỗi khi lưu:', error)
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

const deleteItem = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xoá bài tập này?')) {
    try {
      await ExerciseService.delete(id)
      await fetchExercises()
    } catch (error) {
      console.error('Lỗi khi xoá:', error)
    }
  }
}

const resetForm = () => {
  formData.value = {
    title: '',
    type: 'trac-nghiem',
    lesson_id: '',
    content: '',
    options: [],
    correct_answer: '',
    points: 10,
    order: 1,
    is_active: true
  }
}

onMounted(() => {
  fetchExercises()
})
</script>

<style scoped>
.bai-tap {
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
  min-width: 160px;
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

.badge.trac-nghiem {
  background: #dbeafe;
  color: #1e40af;
}

.badge.dien-tu {
  background: #fef3c7;
  color: #92400e;
}

.badge.noi {
  background: #fce7f3;
  color: #9d174d;
}

.badge.viet {
  background: #e0e7ff;
  color: #3730a3;
}

.status {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status.active {
  background: #d4edda;
  color: #155724;
}

.status.inactive {
  background: #f8d7da;
  color: #721c24;
}

.actions {
  display: flex;
  gap: 8px;
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
