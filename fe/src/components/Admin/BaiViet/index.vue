<template>
  <div class="bai-viet">
    <div class="header">
      <h1>Quản Lý Bài Viết</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Bài Viết
      </button>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">{{ articles.length }}</div>
        <div class="stat-label">Tổng Bài Viết</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ featuredCount }}</div>
        <div class="stat-label">Nổi Bật</div>
      </div>
    </div>

    <div class="filter-section">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm bài viết..."
        class="search-input"
      />
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredArticles.length === 0" class="empty-state">
      <p>Không tìm thấy bài viết nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th style="width: 50px">ID</th>
          <th>Tiêu Đề</th>
          <th style="width: 130px">Danh Mục</th>
          <th style="width: 120px">Tác Giả</th>
          <th style="width: 80px">Nổi Bật</th>
          <th style="width: 100px">Trạng Thái</th>
          <th style="width: 120px">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="article in filteredArticles" :key="article.id">
          <td>{{ article.id }}</td>
          <td>
            <div class="title-cell">
              <div class="article-thumb" v-if="article.thumbnail">
                <img :src="getThumb(article.thumbnail)" :alt="article.title" />
              </div>
              <div class="article-thumb-placeholder" v-else>
                <i class="fa-solid fa-newspaper"></i>
              </div>
              <div>
                <div class="article-title">{{ article.title }}</div>
                <div class="article-summary">{{ article.summary || 'Không có tóm tắt' }}</div>
              </div>
            </div>
          </td>
          <td>
            <span class="category-badge">{{ article.category || '-' }}</span>
          </td>
          <td>{{ article.author || '-' }}</td>
          <td>
            <span v-if="article.is_featured" class="featured-badge">Nổi bật</span>
            <span v-else class="no-badge">-</span>
          </td>
          <td>
            <span class="status-badge" :class="article.is_active ? 'active' : 'inactive'">
              {{ article.is_active ? 'Hoạt động' : 'Tắt' }}
            </span>
          </td>
          <td class="actions">
            <button class="btn-edit" @click="editArticle(article)" title="Sửa">
              <span>Sửa</span>
            </button>
            <button class="btn-delete" @click="deleteArticle(article.id)" title="Xóa">
              <span>Xóa</span>
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>{{ editingArticle ? 'Sửa Bài Viết' : 'Thêm Bài Viết Mới' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tiêu Đề <span class="required">*</span></label>
            <input v-model="articleForm.title" type="text" placeholder="Nhập tiêu đề bài viết" />
            <span v-if="errors.title" class="error-text">{{ errors.title[0] }}</span>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Danh Mục</label>
              <input v-model="articleForm.category" type="text" placeholder="Ví dụ: Ngữ pháp, Từ vựng..." />
            </div>
            <div class="form-group">
              <label>Tác Giả</label>
              <input v-model="articleForm.author" type="text" placeholder="Tên tác giả" />
            </div>
          </div>
          <div class="form-group">
            <label>Tóm Tắt</label>
            <textarea v-model="articleForm.summary" rows="2" placeholder="Nhập tóm tắt bài viết"></textarea>
          </div>
          <div class="form-group">
            <label>Nội Dung</label>
            <textarea v-model="articleForm.content" rows="10" placeholder="Nhập nội dung bài viết (hỗ trợ HTML)"></textarea>
          </div>
          <div class="form-group">
            <label>Thumbnail URL</label>
            <input v-model="articleForm.thumbnail" type="text" placeholder="https://... (link ảnh)" />
          </div>
          <div class="form-group">
            <label>Slug</label>
            <input v-model="articleForm.slug" type="text" placeholder="duong-dan-bai-viet (tự động tạo nếu trống)" />
          </div>
          <div class="form-row-check">
            <label class="checkbox-label">
              <input type="checkbox" v-model="articleForm.is_featured" />
              <span>Bài viết nổi bật</span>
            </label>
            <label class="checkbox-label">
              <input type="checkbox" v-model="articleForm.is_active" />
              <span>Hoạt động</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Hủy</button>
          <button class="btn-primary" @click="saveArticle">
            {{ editingArticle ? 'Cập Nhật' : 'Thêm Mới' }}
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
          <p>Bạn có chắc chắn muốn xóa bài viết này?</p>
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

const loading = ref(false)
const articles = ref([])
const searchQuery = ref('')

const showModal = ref(false)
const editingArticle = ref(null)
const showDeleteConfirm = ref(false)
const deleteArticleId = ref(null)
const errors = ref({})

const articleForm = ref({
  title: '',
  slug: '',
  summary: '',
  content: '',
  thumbnail: '',
  category: '',
  author: '',
  is_featured: false,
  is_active: true
})

const featuredCount = computed(() => articles.value.filter(a => a.is_featured).length)

const filteredArticles = computed(() => {
  if (!searchQuery.value) return articles.value
  const q = searchQuery.value.toLowerCase()
  return articles.value.filter(a =>
    a.title?.toLowerCase().includes(q) ||
    a.summary?.toLowerCase().includes(q) ||
    a.category?.toLowerCase().includes(q)
  )
})

const getThumb = (thumb) => {
  if (!thumb) return ''
  if (thumb.startsWith('http')) return thumb
  return 'http://localhost:8000/uploads/' + thumb
}

const fetchArticles = async () => {
  loading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const res = await axios.get('http://localhost:8000/api/articles', {
      headers: { Authorization: `Bearer ${token}` }
    })
    articles.value = res.data?.data || []
  } catch (error) {
    console.error('Lỗi khi tải bài viết:', error)
    articles.value = []
  } finally {
    loading.value = false
  }
}

const openAddModal = () => {
  editingArticle.value = null
  errors.value = {}
  articleForm.value = {
    title: '',
    slug: '',
    summary: '',
    content: '',
    thumbnail: '',
    category: '',
    author: '',
    is_featured: false,
    is_active: true
  }
  showModal.value = true
}

const editArticle = (article) => {
  editingArticle.value = article.id
  errors.value = {}
  articleForm.value = {
    title: article.title,
    slug: article.slug || '',
    summary: article.summary || '',
    content: article.content || '',
    thumbnail: article.thumbnail || '',
    category: article.category || '',
    author: article.author || '',
    is_featured: !!article.is_featured,
    is_active: !!article.is_active
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingArticle.value = null
  errors.value = {}
}

const saveArticle = async () => {
  errors.value = {}
  try {
    const token = localStorage.getItem('auth_token')
    const config = { headers: { Authorization: `Bearer ${token}` } }

    if (editingArticle.value) {
      const res = await axios.put(`http://localhost:8000/api/articles/${editingArticle.value}`, articleForm.value, config)
      const index = articles.value.findIndex(a => a.id === editingArticle.value)
      if (index !== -1) {
        articles.value[index] = res.data.data
      }
      alert('Cập nhật bài viết thành công!')
    } else {
      const res = await axios.post('http://localhost:8000/api/articles', articleForm.value, config)
      articles.value.unshift(res.data.data)
      alert('Thêm bài viết thành công!')
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

const deleteArticle = (id) => {
  deleteArticleId.value = id
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    const token = localStorage.getItem('auth_token')
    await axios.delete(`http://localhost:8000/api/articles/${deleteArticleId.value}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    articles.value = articles.value.filter(a => a.id !== deleteArticleId.value)
    showDeleteConfirm.value = false
    deleteArticleId.value = null
    alert('Xóa bài viết thành công!')
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

onMounted(() => {
  fetchArticles()
})
</script>

<style scoped>
.bai-viet { padding: 20px; }

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
  color: #7c3aed;
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

.article-thumb {
  width: 56px;
  height: 40px;
  border-radius: 6px;
  overflow: hidden;
  flex-shrink: 0;
}

.article-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.article-thumb-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f3ff;
  color: #a78bfa;
  font-size: 16px;
  flex-shrink: 0;
}

.article-title {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.article-summary {
  font-size: 12px;
  color: #999;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-badge {
  padding: 3px 10px;
  background: #f5f3ff;
  color: #7c3aed;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.featured-badge {
  padding: 3px 8px;
  background: #fef3c7;
  color: #d97706;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.no-badge {
  color: #ccc;
  font-size: 12px;
}

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
  background: #7c3aed;
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

.btn-primary:hover { background: #6d28d9; }

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

.modal-large { max-width: 700px; }
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
  border-color: #7c3aed;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-row-check {
  display: flex;
  gap: 24px;
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
