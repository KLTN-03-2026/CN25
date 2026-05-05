<template>
  <div class="article-detail-page">
    <div v-if="isLoading" class="loading-container">
      <div class="loader"></div>
      <p>Đang tải...</p>
    </div>

    <div v-else-if="!article" class="not-found">
      <i class="fa-solid fa-newspaper"></i>
      <h2>Không tìm thấy bài viết</h2>
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

      <article class="article-content">
        <header class="article-header">
          <div class="article-meta">
            <span v-if="article.category" class="category-tag">{{ article.category }}</span>
            <span v-if="article.is_featured" class="featured-tag">Nổi bật</span>
          </div>

          <h1>{{ article.title }}</h1>

          <div class="author-info">
            <div v-if="article.author_avatar" class="author-avatar">
              <img :src="getThumb(article.author_avatar)" :alt="article.author" />
            </div>
            <div v-else class="author-avatar-placeholder">
              <i class="fa-solid fa-user"></i>
            </div>
            <div class="author-details">
              <span class="author-name">{{ article.author || 'Không rõ tên' }}</span>
              <span class="article-stats">
                <span><i class="fa-solid fa-eye"></i> {{ article.view_count || 0 }} lượt xem</span>
                <span><i class="fa-solid fa-calendar"></i> {{ formatDate(article.created_at) }}</span>
              </span>
            </div>
          </div>
        </header>

        <div v-if="article.thumbnail" class="article-cover">
          <img :src="getThumb(article.thumbnail)" :alt="article.title" />
        </div>

        <div v-if="article.summary" class="article-summary">
          <p>{{ article.summary }}</p>
        </div>

        <div class="article-body" v-html="article.content"></div>

        <footer class="article-footer">
          <button @click="goBack" class="btn-back-bottom">
            <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
          </button>
        </footer>
      </article>
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
const article = ref(null)

const fetchArticle = async () => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const res = await axios.get(`http://localhost:8000/api/student/articles/${route.params.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    article.value = res.data?.data
  } catch (error) {
    console.error('Lỗi khi tải bài viết:', error)
    article.value = null
  } finally {
    isLoading.value = false
  }
}

const goBack = () => {
  router.back()
}

const getThumb = (thumb) => {
  if (!thumb) return ''
  if (thumb.startsWith('http')) return thumb
  return 'http://localhost:8000/uploads/' + thumb
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
  fetchArticle()
})
</script>

<style scoped>
.article-detail-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8f5ff 0%, #ffffff 100%);
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
  color: #7c3aed;
}

.article-content {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 24px 40px;
}

.article-header {
  margin-bottom: 32px;
}

.article-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.category-tag {
  padding: 6px 14px;
  background: #f5f3ff;
  color: #7c3aed;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

.featured-tag {
  padding: 6px 14px;
  background: #fef3c7;
  color: #d97706;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

.article-header h1 {
  font-size: 36px;
  font-weight: 800;
  color: #1e293b;
  margin: 0 0 24px;
  line-height: 1.3;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

.author-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
}

.author-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.author-avatar-placeholder {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #f5f3ff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #a78bfa;
  font-size: 20px;
}

.author-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.author-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 15px;
}

.article-stats {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #64748b;
}

.article-stats span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.article-cover {
  width: 100%;
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 32px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
}

.article-cover img {
  width: 100%;
  height: auto;
  display: block;
}

.article-summary {
  background: #f5f3ff;
  border-left: 4px solid #7c3aed;
  padding: 24px;
  border-radius: 12px;
  margin-bottom: 32px;
}

.article-summary p {
  font-size: 17px;
  color: #4c1d95;
  margin: 0;
  line-height: 1.7;
  font-style: italic;
}

.article-body {
  font-size: 17px;
  color: #334155;
  line-height: 1.9;
}

.article-body :deep(h1),
.article-body :deep(h2),
.article-body :deep(h3),
.article-body :deep(h4) {
  color: #1e293b;
  margin: 1.5em 0 0.5em;
  line-height: 1.3;
}

.article-body :deep(h2) { font-size: 28px; }
.article-body :deep(h3) { font-size: 22px; }
.article-body :deep(h4) { font-size: 18px; }

.article-body :deep(p) {
  margin: 1em 0;
}

.article-body :deep(ul),
.article-body :deep(ol) {
  margin: 1em 0;
  padding-left: 24px;
}

.article-body :deep(li) {
  margin: 0.5em 0;
}

.article-body :deep(code) {
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.9em;
}

.article-body :deep(pre) {
  background: #1e293b;
  color: #e2e8f0;
  padding: 20px;
  border-radius: 12px;
  overflow-x: auto;
  margin: 1.5em 0;
}

.article-body :deep(pre code) {
  background: none;
  padding: 0;
  color: inherit;
}

.article-body :deep(blockquote) {
  border-left: 4px solid #7c3aed;
  margin: 1.5em 0;
  padding: 16px 24px;
  background: #f5f3ff;
  border-radius: 0 12px 12px 0;
  font-style: italic;
}

.article-footer {
  margin-top: 48px;
  padding-top: 32px;
  border-top: 1px solid #e2e8f0;
}

.btn-back-bottom {
  background: #7c3aed;
  color: white;
  border: none;
  padding: 14px 28px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 15px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-back-bottom:hover {
  background: #6d28d9;
  transform: translateY(-2px);
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
  border-top-color: #7c3aed;
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
  .article-header h1 {
    font-size: 26px;
  }

  .article-body {
    font-size: 15px;
  }
}
</style>
