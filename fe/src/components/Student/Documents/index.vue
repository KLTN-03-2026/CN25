<template>
  <div class="documents-page">
    <div class="page-header">
      <h1>Tài Liệu Học Tập</h1>
      <p>Tổng hợp tài liệu, giáo trình và hướng dẫn học tiếng Anh hiệu quả</p>
    </div>

    <div class="filters">
      <div class="search-box">
        <i class="fa-solid fa-search"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm tài liệu..."
        />
      </div>
    </div>

    <div class="document-container">
      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
      </div>

      <div v-else-if="filteredDocuments.length === 0" class="empty">
        <i class="fa-solid fa-file-lines"></i>
        <h3>Không có tài liệu</h3>
        <p>Hiện chưa có tài liệu nào được cập nhật.</p>
      </div>

      <div v-else class="document-grid">
        <div
          v-for="doc in filteredDocuments"
          :key="doc.id"
          class="document-card"
          @click="goToDetail(doc)"
        >
          <div class="doc-icon">
            <i :class="getFileIcon(doc.file_type)"></i>
          </div>
          <div class="doc-body">
            <h3>{{ doc.title }}</h3>
            <p>{{ doc.description || 'Không có mô tả' }}</p>
            <div class="doc-meta">
              <span class="doc-type" :class="'type-' + doc.file_type">
                {{ getTypeLabel(doc.file_type) }}
              </span>
              <span class="doc-date">
                <i class="fa-regular fa-calendar"></i>
                {{ formatDate(doc.created_at) }}
              </span>
            </div>
          </div>
          <div class="doc-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const isLoading = ref(false)
const searchQuery = ref('')
const documents = ref([])

const fetchDocuments = async () => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const res = await axios.get('http://localhost:8000/api/student/documents?active_only=true', {
      headers: { Authorization: `Bearer ${token}` }
    })
    documents.value = res.data?.data || []
  } catch (error) {
    console.error('Lỗi khi tải tài liệu:', error)
    documents.value = []
  } finally {
    isLoading.value = false
  }
}

const filteredDocuments = computed(() => {
  if (!searchQuery.value) return documents.value
  const query = searchQuery.value.toLowerCase()
  return documents.value.filter(d =>
    d.title?.toLowerCase().includes(query) ||
    d.description?.toLowerCase().includes(query)
  )
})

const goToDetail = (doc) => {
  router.push(`/student/tai-lieu/${doc.id}`)
}

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

const getTypeLabel = (type) => {
  const labels = {
    pdf: 'PDF',
    doc: 'Word',
    xls: 'Excel',
    ppt: 'PowerPoint',
    zip: 'Nén',
    video: 'Video',
    default: 'Tài liệu'
  }
  return labels[type] || labels.default
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

onMounted(() => {
  fetchDocuments()
})
</script>

<style scoped>
.documents-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f1f5f9 0%, #ffffff 100%);
  padding-bottom: 80px;
}

.page-header {
  background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
  padding: 60px 24px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.page-header::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  opacity: 0.3;
}

.page-header h1 {
  font-size: 42px;
  font-weight: 900;
  color: white;
  margin: 0 0 12px;
  position: relative;
  letter-spacing: -0.5px;
}

.page-header p {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.85);
  margin: 0;
  position: relative;
}

.filters {
  max-width: 1200px;
  margin: -30px auto 40px;
  padding: 0 24px;
  position: relative;
  z-index: 10;
}

.search-box {
  background: white;
  border-radius: 16px;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.search-box i {
  color: #94a3b8;
  font-size: 16px;
}

.search-box input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #1e293b;
  background: transparent;
  font-family: inherit;
}

.search-box input::placeholder {
  color: #94a3b8;
}

.document-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 24px;
}

.document-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.document-card {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  cursor: pointer;
  transition: all 0.3s;
  border: 1px solid #f1f5f9;
}

.document-card:hover {
  transform: translateX(6px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  border-color: #ccfbf1;
}

.doc-icon {
  width: 56px;
  height: 56px;
  min-width: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
  color: #0d9488;
  font-size: 24px;
}

.doc-body {
  flex: 1;
}

.doc-body h3 {
  font-size: 17px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 6px;
  line-height: 1.3;
}

.doc-body p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 10px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.doc-meta {
  display: flex;
  align-items: center;
  gap: 14px;
}

.doc-type {
  padding: 3px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.type-pdf { background: #fef2f2; color: #dc2626; }
.type-doc { background: #eff6ff; color: #2563eb; }
.type-xls { background: #f0fdf4; color: #16a34a; }
.type-ppt { background: #fff7ed; color: #ea580c; }
.type-zip { background: #faf5ff; color: #9333ea; }
.type-video { background: #fff1f2; color: #e11d48; }
.type-default { background: #f8fafc; color: #475569; }

.doc-date {
  font-size: 12px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 4px;
}

.doc-arrow {
  color: #cbd5e1;
  font-size: 18px;
  transition: all 0.3s;
}

.document-card:hover .doc-arrow {
  color: #0d9488;
  transform: translateX(4px);
}

.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.spinner {
  width: 44px;
  height: 44px;
  border: 4px solid #e2e8f0;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty {
  text-align: center;
  padding: 80px 24px;
}

.empty i {
  font-size: 56px;
  color: #cbd5e1;
  margin-bottom: 20px;
  display: block;
}

.empty h3 {
  font-size: 20px;
  font-weight: 700;
  color: #64748b;
  margin: 0 0 8px;
}

.empty p {
  font-size: 14px;
  color: #94a3b8;
  margin: 0;
}

@media (max-width: 768px) {
  .page-header {
    padding: 40px 20px;
  }

  .page-header h1 {
    font-size: 28px;
  }

  .document-card {
    padding: 16px;
    gap: 14px;
  }

  .doc-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    font-size: 20px;
  }

  .doc-body h3 {
    font-size: 15px;
  }
}
</style>
