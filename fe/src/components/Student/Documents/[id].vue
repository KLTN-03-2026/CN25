<template>
  <div class="document-detail-page">
    <div v-if="isLoading" class="loading-container">
      <div class="loader"></div>
      <p>Đang tải...</p>
    </div>

    <div v-else-if="!document" class="not-found">
      <i class="fa-solid fa-file-circle-xmark"></i>
      <h2>Không tìm thấy tài liệu</h2>
      <button @click="goBack" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Quay lại
      </button>
    </div>

    <template v-else>
      <div class="detail-header">
        <button @click="goBack" class="btn-back">
          <i class="fa-solid fa-arrow-left"></i> Quay lại
        </button>
      </div>

      <div class="detail-content">
        <div class="detail-card">
          <div class="doc-icon-large">
            <i :class="getFileIcon(document.file_type)"></i>
          </div>

          <h1>{{ document.title }}</h1>

          <div class="doc-meta">
            <span class="doc-type" :class="'type-' + document.file_type">
              {{ getTypeLabel(document.file_type) }}
            </span>
            <span v-if="document.author">
              <i class="fa-solid fa-user"></i> {{ document.author }}
            </span>
            <span v-if="document.download_count">
              <i class="fa-solid fa-download"></i> {{ document.download_count }} lượt tải
            </span>
          </div>

          <p v-if="document.description" class="doc-description">
            {{ document.description }}
          </p>

          <div v-if="document.content" class="doc-content" v-html="document.content"></div>

          <div v-if="document.file_url" class="doc-actions">
            <a :href="document.file_url" target="_blank" class="btn-download">
              <i class="fa-solid fa-download"></i> Tải xuống file
            </a>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const document = ref(null)

const fetchDocument = async () => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const res = await axios.get(`http://localhost:8000/api/student/documents/${route.params.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    document.value = res.data?.data
  } catch (error) {
    console.error('Lỗi khi tải tài liệu:', error)
    document.value = null
  } finally {
    isLoading.value = false
  }
}

const goBack = () => {
  router.back()
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

onMounted(() => {
  fetchDocument()
})
</script>

<style scoped>
.document-detail-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f1f5f9 0%, #ffffff 100%);
  padding-bottom: 80px;
}

.detail-header {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px 24px;
}

.btn-back {
  background: white;
  border: 1px solid #e2e8f0;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  transition: all 0.2s;
}

.btn-back:hover {
  background: #f8fafc;
  color: #0d9488;
}

.detail-content {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 24px 40px;
}

.detail-card {
  background: white;
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
}

.doc-icon-large {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
  color: #0d9488;
  font-size: 36px;
  margin-bottom: 24px;
}

.detail-card h1 {
  font-size: 28px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 16px;
  line-height: 1.3;
}

.doc-meta {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 20px;
  color: #64748b;
  font-size: 14px;
}

.doc-meta span {
  display: flex;
  align-items: center;
  gap: 6px;
}

.doc-type {
  padding: 4px 12px;
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

.doc-description {
  font-size: 16px;
  color: #64748b;
  line-height: 1.7;
  margin-bottom: 24px;
  padding: 20px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid #0d9488;
}

.doc-content {
  font-size: 15px;
  color: #334155;
  line-height: 1.8;
  margin-bottom: 24px;
}

.doc-actions {
  margin-top: 24px;
}

.btn-download {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #0d9488;
  color: white;
  padding: 14px 28px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  font-size: 15px;
  transition: all 0.2s;
}

.btn-download:hover {
  background: #0f766e;
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(13, 148, 136, 0.3);
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 16px;
}

.loader {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-container p {
  color: #64748b;
  font-size: 14px;
}

.not-found {
  text-align: center;
  padding: 80px 24px;
}

.not-found i {
  font-size: 64px;
  color: #cbd5e1;
  margin-bottom: 24px;
}

.not-found h2 {
  font-size: 24px;
  color: #64748b;
  margin: 0 0 24px;
}

@media (max-width: 768px) {
  .detail-card {
    padding: 24px;
  }

  .detail-card h1 {
    font-size: 22px;
  }

  .doc-meta {
    flex-wrap: wrap;
    gap: 12px;
  }
}
</style>
