<template>
  <div class="tai-lieu">
    <div class="header">
      <h1>Quản Lý Tài Liệu</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Tài Liệu
      </button>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ documents.length }}</div>
        <div class="stat-label">Tổng Tài Liệu</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ activeCount }}</div>
        <div class="stat-label">Đang Hoạt Động</div>
      </div>
    </div>

    <div class="filter-section">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm tài liệu..."
        class="search-input"
      />
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredDocuments.length === 0" class="empty-state">
      <p>Không tìm thấy tài liệu nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th>Tiêu Đề</th>
          <th style="width: 100px">Loại File</th>
          <th>Tác Giả</th>
          <th style="width: 120px">Lượt Tải</th>
          <th style="width: 100px">Trạng Thái</th>
          <th style="width: 120px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="doc in filteredDocuments" :key="doc.id">
          <td>{{ doc.id }}</td>
          <td>
            <div class="title-cell">
              <div class="doc-icon-small">
                <i :class="getFileIcon(doc.file_type)"></i>
              </div>
              <div>
                <div class="doc-title">{{ doc.title }}</div>
                <div class="doc-desc">{{ doc.description || 'Không có mô tả' }}</div>
              </div>
            </div>
          </td>
          <td>
            <span class="file-type-badge" :class="'type-' + (doc.file_type || 'pdf')">
              {{ (doc.file_type || 'pdf').toUpperCase() }}
            </span>
          </td>
          <td>{{ doc.author || '-' }}</td>
          <td>{{ doc.download_count || 0 }}</td>
          <td>
            <span class="status-badge" :class="doc.is_active ? 'active' : 'inactive'">
              {{ doc.is_active ? 'Hoạt động' : 'Tắt' }}
            </span>
          </td>
          <td class="actions">
            <button class="btn-edit" @click="editDoc(doc)" title="Sửa">
              <span>Sửa</span>
            </button>
            <button class="btn-delete" @click="deleteDoc(doc.id)" title="Xóa">
              <span>Xóa</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingDoc ? 'Sửa Tài Liệu' : 'Thêm Tài Liệu Mới' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề <span class="required">*</span></label>
            <input v-model="docForm.title" type="text" placeholder="Nhập tiêu đề tài liệu" />
            <span v-if="errors.title" class="error-text">{{ errors.title[0] }}</span>
          </div>
          <div class="form-group">
            <label>Mô Tả</label>
            <textarea v-model="docForm.description" rows="3" placeholder="Nhập mô tả tài liệu"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Loại File</label>
              <select v-model="docForm.file_type">
                <option value="pdf">PDF</option>
                <option value="doc">Word</option>
                <option value="xls">Excel</option>
                <option value="ppt">PowerPoint</option>
                <option value="zip">Nén</option>
                <option value="video">Video</option>
              </select>
            </div>
            <div class="form-group">
              <label>Tác Giả</label>
              <input v-model="docForm.author" type="text" placeholder="Tên tác giả" />
            </div>
          </div>
          <div class="form-group">
            <label>URL File</label>
            <input v-model="docForm.file_url" type="text" placeholder="https://... (đường dẫn file)" />
          </div>
          <div class="form-group">
            <label>Nội Dung</label>
            <textarea v-model="docForm.content" rows="4" placeholder="Nội dung tài liệu (nếu có)"></textarea>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="docForm.is_active" />
              <span>Hoạt động</span>
            </label>
          </div>
          <div class="form-group">
            <label>Thứ Tự</label>
            <input v-model.number="docForm.order" type="number" min="0" placeholder="0" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Hủy</button>
          <button class="btn-primary" @click="saveDoc">
            {{ editingDoc ? 'Cập Nhật' : 'Thêm Mới' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="modal modal-confirm">
        <div class="modal-header">
          <h2>Xác Nhận Xóa</h2>
          <button class="btn-close" @click="showDeleteConfirm = false">&times;</button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc chắn muốn xóa tài liệu này?</p>
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
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import apiClient from '../../../services/api.js'

const loading = ref(false)
const documents = ref([])
const searchQuery = ref('')

const showModal = ref(false)
const editingDoc = ref(null)
const showDeleteConfirm = ref(false)
const deleteDocId = ref(null)
const errors = ref({})

const docForm = ref({
  title: '',
  description: '',
  file_type: 'pdf',
  file_url: '',
  content: '',
  author: '',
  is_active: true,
  order: 0
})

const activeCount = computed(() => documents.value.filter(d => d.is_active).length)

const filteredDocuments = computed(() => {
  if (!searchQuery.value) return documents.value
  const q = searchQuery.value.toLowerCase()
  return documents.value.filter(d =>
    d.title?.toLowerCase().includes(q) ||
    d.description?.toLowerCase().includes(q)
  )
})

const getFileIcon = (type) => {
  const icons = {
    pdf: 'fa-solid fa-file-pdf',
    doc: 'fa-solid fa-file-word',
    xls: 'fa-solid fa-file-excel',
    ppt: 'fa-solid fa-file-powerpoint',
    zip: 'fa-solid fa-file-zipper',
    video: 'fa-solid fa-file-video',
    default: 'fa-solid fa-file-lines'
  }
  return icons[type] || icons.default
}

const fetchDocuments = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/documents')
    documents.value = res.data?.data || []
  } catch (error) {
    console.error('Lỗi khi tải tài liệu:', error)
    documents.value = []
  } finally {
    loading.value = false
  }
}

const openAddModal = () => {
  editingDoc.value = null
  errors.value = {}
  docForm.value = {
    title: '',
    description: '',
    file_type: 'pdf',
    file_url: '',
    content: '',
    author: '',
    is_active: true,
    order: 0
  }
  showModal.value = true
}

const editDoc = (doc) => {
  editingDoc.value = doc.id
  errors.value = {}
  docForm.value = {
    title: doc.title,
    description: doc.description || '',
    file_type: doc.file_type || 'pdf',
    file_url: doc.file_url || '',
    content: doc.content || '',
    author: doc.author || '',
    is_active: !!doc.is_active,
    order: doc.order || 0
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingDoc.value = null
  errors.value = {}
}

const saveDoc = async () => {
  errors.value = {}
  try {
    const token = localStorage.getItem('auth_token')
    const config = { headers: { Authorization: `Bearer ${token}` } }

    if (editingDoc.value) {
      const res = await axios.put(`http://localhost:8000/api/documents/${editingDoc.value}`, docForm.value, config)
      const index = documents.value.findIndex(d => d.id === editingDoc.value)
      if (index !== -1) {
        documents.value[index] = res.data.data
      }
      alert('Cập nhật tài liệu thành công!')
    } else {
      const res = await axios.post('http://localhost:8000/api/documents', docForm.value, config)
      documents.value.unshift(res.data.data)
      alert('Thêm tài liệu thành công!')
    }
    closeModal()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
    }
  }
}

const deleteDoc = (id) => {
  deleteDocId.value = id
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    const token = localStorage.getItem('auth_token')
    await axios.delete(`http://localhost:8000/api/documents/${deleteDocId.value}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    documents.value = documents.value.filter(d => d.id !== deleteDocId.value)
    showDeleteConfirm.value = false
    deleteDocId.value = null
    alert('Xóa tài liệu thành công!')
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

onMounted(() => {
  fetchDocuments()
})
</script>

<style scoped>
.tai-lieu { padding: 20px; }

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

.filter-section {
  background: white;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.search-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
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
  color: #0d9488;
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
  gap: 12px;
}

.doc-icon-small {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0fdfa;
  color: #0d9488;
  font-size: 18px;
  flex-shrink: 0;
}

.doc-title {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.doc-desc {
  font-size: 12px;
  color: #999;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-type-badge {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}

.file-type-badge.type-pdf { background: #fef2f2; color: #dc2626; }
.file-type-badge.type-doc { background: #eff6ff; color: #2563eb; }
.file-type-badge.type-xls { background: #f0fdf4; color: #16a34a; }
.file-type-badge.type-ppt { background: #fff7ed; color: #ea580c; }
.file-type-badge.type-zip { background: #faf5ff; color: #9333ea; }
.file-type-badge.type-video { background: #fff1f2; color: #e11d48; }
.file-type-badge.type-default { background: #f8fafc; color: #475569; }

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.active { background: #dcfce7; color: #16a34a; }
.status-badge.inactive { background: #fee2e2; color: #dc2626; }

.actions { display: flex; gap: 6px; }

.btn-edit, .btn-delete {
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-edit { background: #10b981; color: white; }
.btn-edit:hover { background: #059669; }
.btn-delete { background: #ef4444; color: white; }
.btn-delete:hover { background: #dc2626; }

.btn-primary {
  background: #0d9488;
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

.btn-primary:hover { background: #0f766e; }

.btn-secondary {
  background: #e5e7eb;
  color: #333;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.btn-secondary:hover { background: #d1d5db; }

.btn-danger {
  background: #dc2626;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
}

.btn-danger:hover { background: #b91c1c; }

.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
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

.modal-confirm { max-width: 400px; }

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 { margin: 0; font-size: 20px; }

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
}

.modal-body { padding: 20px; }

.form-group { margin-bottom: 16px; }

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
  font-size: 14px;
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

.form-group textarea { resize: vertical; }

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #0d9488;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
}

.checkbox-label span {
  font-weight: 500;
  color: #333;
}

.required { color: #ef4444; }

.error-text {
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #eee;
}
</style>
