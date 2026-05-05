<template>
  <div class="lesson-router">
    <div v-if="isLoading" class="loading">
      <div class="loader"></div>
    </div>
    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { LessonService } from '../../../services/api.js'

const route = useRoute()
const router = useRouter()

const isLoading = ref(true)
const error = ref(null)

onMounted(async () => {
  const lessonId = route.params.id
  
  try {
    const res = await LessonService.getById(lessonId)
    const lesson = res.data || res
    const chapterType = lesson.chapter?.type || 'vocabulary'

    // Redirect to appropriate player based on chapter type
    switch (chapterType) {
      case 'vocabulary':
        router.replace(`/student/vocabulary/${lessonId}`)
        break
      case 'grammar':
        router.replace(`/student/grammar/${lessonId}`)
        break
      case 'listening':
        router.replace(`/student/listening/${lessonId}`)
        break
      case 'speaking':
        router.replace(`/student/speaking/${lessonId}`)
        break
      default:
        router.replace(`/student/vocabulary/${lessonId}`)
    }
  } catch (err) {
    console.error('Lỗi khi tải bài học:', err)
    error.value = 'Không thể tải bài học'
    isLoading.value = false
  }
})
</script>

<style scoped>
.lesson-router {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading {
  text-align: center;
}

.loader {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error {
  color: #ef4444;
}
</style>
