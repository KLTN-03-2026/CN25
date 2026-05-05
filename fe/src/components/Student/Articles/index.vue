<template>
  <div class="articles-page">
    <div class="page-header">
      <h1>Bài Viết Học Tập</h1>
      <p>Tổng hợp các bài viết hướng dẫn học tiếng Anh hay và bổ ích</p>
    </div>

    <div class="filters">
      <div class="search-box">
        <i class="fa-solid fa-search"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm bài viết..."
        />
      </div>
    </div>

    <div class="articles-container">
      <div v-if="isLoading" class="loading">
        <div class="spinner"></div>
      </div>

      <div v-else-if="filteredArticles.length === 0" class="empty">
        <i class="fa-solid fa-newspaper"></i>
        <h3>Không có bài viết</h3>
        <p>Hiện chưa có bài viết nào được cập nhật.</p>
      </div>

      <div v-else class="articles-grid">
        <div
          v-for="article in filteredArticles"
          :key="article.id"
          class="article-card"
          @click="goToDetail(article)"
        >
          <div v-if="article.thumbnail" class="article-thumb">
            <img :src="getThumb(article.thumbnail)" :alt="article.title" />
          </div>
          <div v-else class="article-thumb-placeholder">
            <i class="fa-solid fa-newspaper"></i>
          </div>
          <div class="article-body">
            <div class="article-meta">
              <span v-if="article.category" class="category-tag">{{ article.category }}</span>
              <span v-if="article.is_featured" class="featured-tag">Nổi bật</span>
            </div>
            <h3>{{ article.title }}</h3>
            <p>{{ article.summary || 'Không có tóm tắt' }}</p>
            <div class="article-footer">
              <span class="author" v-if="article.author">
                <i class="fa-solid fa-user"></i> {{ article.author }}
              </span>
              <span class="views">
                <i class="fa-solid fa-eye"></i> {{ article.view_count || 0 }}
              </span>
            </div>
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
const articles = ref([])

const fetchArticles = async () => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('auth_token')
    const res = await axios.get('http://localhost:8000/api/student/articles?active_only=true', {
      headers: { Authorization: `Bearer ${token}` }
    })
    articles.value = res.data?.data || []
  } catch (error) {
    console.error('Lỗi khi tải bài viết:', error)
    articles.value = []
  } finally {
    isLoading.value = false
  }
}

const filteredArticles = computed(() => {
  if (!searchQuery.value) return articles.value
  const query = searchQuery.value.toLowerCase()
  return articles.value.filter(a =>
    a.title?.toLowerCase().includes(query) ||
    a.summary?.toLowerCase().includes(query) ||
    a.category?.toLowerCase().includes(query)
  )
})

const goToDetail = (article) => {
  router.push(`/student/bai-viet/${article.id}`)
}

const getThumb = (thumb) => {
  if (!thumb) return ''
  if (thumb.startsWith('http')) return thumb
  return 'http://localhost:8000/uploads/' + thumb
}

onMounted(() => {
  fetchArticles()
})
</script>

<style scoped>
.articles-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8f5ff 0%, #ffffff 100%);
  padding-bottom: 80px;
}

.page-header {
  background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 50%, #a78bfa 100%);
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

.articles-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.articles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
}

.article-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 16px rgba(0, 0, 0, 0.04);
  cursor: pointer;
  transition: all 0.3s;
  border: 1px solid #f1f5f9;
}

.article-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
  border-color: #ede9fe;
}

.article-thumb {
  width: 100%;
  height: 200px;
  overflow: hidden;
}

.article-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.article-card:hover .article-thumb img {
  transform: scale(1.05);
}

.article-thumb-placeholder {
  width: 100%;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f3ff, #ede9fe);
  color: #a78bfa;
  font-size: 48px;
}

.article-body {
  padding: 24px;
}

.article-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.category-tag {
  padding: 4px 10px;
  background: #f5f3ff;
  color: #7c3aed;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.featured-tag {
  padding: 4px 10px;
  background: #fef3c7;
  color: #d97706;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.article-body h3 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 10px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-body p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 16px;
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

.author {
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.views {
  font-size: 13px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 6px;
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
  border-top-color: #7c3aed;
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

  .articles-grid {
    grid-template-columns: 1fr;
  }
}
</style>
