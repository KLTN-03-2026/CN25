import axios from 'axios'

const API_BASE_URL = 'http://localhost:8000/api'

const apiClient = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    timeout: 300000
})

apiClient.interceptors.request.use(
    config => {
        const token = localStorage.getItem('auth_token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
    error => {
        return Promise.reject(error)
    }
)

apiClient.interceptors.response.use(
    response => response,
    error => {
        if (error.response) {
            console.error('API Error:', error.response.status, error.response.data)
        } else if (error.request) {
            console.error('API Error: No response received', error.request)
        } else {
            console.error('API Error:', error.message)
        }
        return Promise.reject(error)
    }
)

export const CourseService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/courses', { params })
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/courses/${id}`)
        return response.data
    },
    getBySlug: async (slug) => {
        const response = await apiClient.get(`/courses/slug/${slug}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/courses', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/courses/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/courses/${id}`)
        return response.data
    },
    changeStatus: async (id, data) => {
        const response = await apiClient.post(`/courses/${id}/change-status`, data)
        return response.data
    },
    generateChapters: async (id) => {
        const response = await apiClient.post(`/courses/${id}/generate-chapters`)
        return response.data
    }
}

export const ChapterService = {
    getByCourse: async (courseId) => {
        const response = await apiClient.get(`/courses/${courseId}/chapters`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/chapters/${id}`)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/chapters/${id}`, data)
        return response.data
    },
    getLessons: async (chapterId) => {
        const response = await apiClient.get(`/chapters/${chapterId}/lessons`)
        return response.data
    }
}

export const LessonService = {
    getByChapter: async (chapterId) => {
        const response = await apiClient.get(`/chapters/${chapterId}/lessons`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/lessons/${id}`)
        return response.data
    },
    create: async (chapterId, data) => {
        const response = await apiClient.post(`/chapters/${chapterId}/lessons`, data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/lessons/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/lessons/${id}`)
        return response.data
    }
}

export const VocabularyService = {
    getByLesson: async (lessonId) => {
        const response = await apiClient.get(`/lessons/${lessonId}/vocabularies`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/vocabularies/${id}`)
        return response.data
    },
    create: async (lessonId, data) => {
        const response = await apiClient.post(`/lessons/${lessonId}/vocabularies`, data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/vocabularies/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/vocabularies/${id}`)
        return response.data
    }
}

export const GrammarService = {
    getByLesson: async (lessonId) => {
        const response = await apiClient.get(`/lessons/${lessonId}/grammars`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/grammars/${id}`)
        return response.data
    },
    create: async (lessonId, data) => {
        const response = await apiClient.post(`/lessons/${lessonId}/grammars`, data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/grammars/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/grammars/${id}`)
        return response.data
    }
}

export const ListeningService = {
    getByLesson: async (lessonId) => {
        const response = await apiClient.get(`/lessons/${lessonId}/listenings`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/listenings/${id}`)
        return response.data
    },
    create: async (lessonId, data) => {
        const response = await apiClient.post(`/lessons/${lessonId}/listenings`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/listenings/${id}`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/listenings/${id}`)
        return response.data
    }
}

export const ListeningExerciseService = {
    getByListening: async (listeningId) => {
        const response = await apiClient.get(`/listenings/${listeningId}/exercises`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/listening-exercises/${id}`)
        return response.data
    },
    create: async (listeningId, data) => {
        const response = await apiClient.post(`/listenings/${listeningId}/exercises`, data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/listening-exercises/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/listening-exercises/${id}`)
        return response.data
    }
}

export const SpeakingExerciseService = {
    getByLesson: async (lessonId) => {
        const response = await apiClient.get(`/lessons/${lessonId}/speaking-exercises`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/speaking-exercises/${id}`)
        return response.data
    },
    create: async (lessonId, data) => {
        const response = await apiClient.post(`/lessons/${lessonId}/speaking-exercises`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/speaking-exercises/${id}`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/speaking-exercises/${id}`)
        return response.data
    },
    evaluate: async (id, audioBlob) => {
        const formData = new FormData()
        formData.append('audio', audioBlob, 'recording.webm')
        const response = await apiClient.post(`/speaking-exercises/${id}/evaluate`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    }
}

export const LessonQuizService = {
    getByCourse: async (courseId) => {
        const response = await apiClient.get(`/courses/${courseId}/quizzes`)
        return response.data
    },
    getByLesson: async (lessonId) => {
        const response = await apiClient.get(`/lessons/${lessonId}/quizzes`)
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/lesson-quizzes/${id}`)
        return response.data
    },
    createFormData: async (courseId, formData) => {
        const response = await apiClient.post(`/courses/${courseId}/quizzes`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    },
    create: async (courseId, data) => {
        const response = await apiClient.post(`/courses/${courseId}/quizzes`, data)
        return response.data
    },
    updateFormData: async (id, formData) => {
        const response = await apiClient.post(`/lesson-quizzes/${id}?_method=PUT`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/lesson-quizzes/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/lesson-quizzes/${id}`)
        return response.data
    }
}

export const CourseQuizService = {
    create: async (courseId, data) => {
        const response = await apiClient.post(`/courses/${courseId}/course-quiz`, data)
        return response.data
    },
    getByCourse: async (courseId) => {
        const response = await apiClient.get(`/courses/${courseId}/course-quiz`)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/course-quizzes/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/course-quizzes/${id}`)
        return response.data
    }
}

export const CourseQuizQuestionService = {
    create: async (quizId, data, isFormData = false) => {
        if (isFormData) {
            const response = await apiClient.post(`/course-quizzes/${quizId}/questions`, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            return response.data
        }
        const response = await apiClient.post(`/course-quizzes/${quizId}/questions`, data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/course-quiz-questions/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/course-quiz-questions/${id}`)
        return response.data
    }
}

export const AuthService = {
    register: async (data) => {
        const response = await apiClient.post('/auth/register', data)
        return response.data
    },
    login: async (data) => {
        const response = await apiClient.post('/auth/login', data)
        return response.data
    },
    me: async () => {
        const response = await apiClient.get('/auth/me')
        return response.data
    },
    logout: async () => {
        const response = await apiClient.post('/auth/logout')
        return response.data
    },
    uploadAvatar: async (file) => {
        const formData = new FormData()
        formData.append('avatar', file)
        const response = await apiClient.post('/auth/upload-avatar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        return response.data
    }
}

export const ProgressService = {
    getDashboard: async () => {
        const response = await apiClient.get('/progress/dashboard')
        return response.data
    },
    getStats: async () => {
        const response = await apiClient.get('/progress/stats')
        return response.data
    },
    getMyCourses: async () => {
        const response = await apiClient.get('/progress/my-courses')
        return response.data
    },
    getCourseProgress: async (courseId) => {
        const response = await apiClient.get(`/progress/course/${courseId}`)
        return response.data
    },
    enroll: async (courseId) => {
        const response = await apiClient.post(`/progress/enroll/${courseId}`)
        return response.data
    },
    startLesson: async (lessonId) => {
        const response = await apiClient.post(`/progress/lesson/${lessonId}/start`)
        return response.data
    },
    completeLesson: async (lessonId, data = {}) => {
        const response = await apiClient.post(`/progress/lesson/${lessonId}/complete`, data)
        return response.data
    },
    getLessonProgress: async (lessonId) => {
        const response = await apiClient.get(`/progress/lesson/${lessonId}`)
        return response.data
    },
    getLearningHistory: async (params = {}) => {
        const response = await apiClient.get('/progress/history', { params })
        return response.data
    },
    saveExerciseScore: async (data) => {
        const response = await apiClient.post('/progress/exercise-score', {
            course_id: data.course_id,
            type: data.type,
            score: data.score,
            time_spent: data.time_spent || 0,
        })
        return response.data
    },
    submitFinal: async (data) => {
        const response = await apiClient.post('/progress/submit-final', {
            course_id: data.course_id,
            quiz_id: data.quiz_id,
            score: data.score,
            time_spent: data.time_spent || 0,
        })
        return response.data
    },
    getAllProgress: async (params = {}) => {
        const response = await apiClient.get('/progress/all', { params })
        return response.data
    }
}

export const PublicService = {
    getStats: async () => {
        const baseURL = apiClient.defaults.baseURL || 'http://localhost:8000/api'
        const response = await fetch(`${baseURL}/public/stats`)
        const data = await response.json()
        return data
    }
}

export const UserService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/users', { params })
        return response.data
    },
    getStats: async () => {
        const response = await apiClient.get('/users/stats')
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/users/${id}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/users', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/users/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/users/${id}`)
        return response.data
    }
}

export const PaymentService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/payments', { params })
        return response.data
    },
    getStats: async () => {
        const response = await apiClient.get('/payments/stats')
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/payments/${id}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/payments', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/payments/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/payments/${id}`)
        return response.data
    },
    approvePayment: async (id) => {
        const response = await apiClient.post(`/payments/${id}/approve`)
        return response.data
    },
    rejectPayment: async (id, data = {}) => {
        const response = await apiClient.post(`/payments/${id}/reject`, data)
        return response.data
    }
}

export const ReviewService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/reviews', { params })
        return response.data
    },
    getFeatured: async () => {
        const response = await apiClient.get('/reviews/featured')
        return response.data
    },
    getStats: async () => {
        const response = await apiClient.get('/reviews/stats')
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/reviews/${id}`)
        return response.data
    },
    getByCourse: async (courseId, params = {}) => {
        const response = await apiClient.get(`/courses/${courseId}/reviews`, { params })
        return response.data
    },
    getMyReview: async (courseId) => {
        const response = await apiClient.get(`/reviews/my-review/${courseId}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/reviews', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/reviews/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/reviews/${id}`)
        return response.data
    },
    reply: async (id, data) => {
        const response = await apiClient.post(`/reviews/${id}/reply`, data)
        return response.data
    },
    approve: async (id) => {
        const response = await apiClient.post(`/reviews/${id}/approve`)
        return response.data
    },
    hide: async (id) => {
        const response = await apiClient.post(`/reviews/${id}/hide`)
        return response.data
    }
}

export const ResultService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/results', { params })
        return response.data
    },
    getStats: async (params = {}) => {
        const response = await apiClient.get('/results/stats', { params })
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/results/${id}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/results', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/results/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/results/${id}`)
        return response.data
    }
}

export const ContentService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/contents', { params })
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/contents/${id}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/contents', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/contents/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/contents/${id}`)
        return response.data
    }
}

export const ExerciseService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/exercises', { params })
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/exercises/${id}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/exercises', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/exercises/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/exercises/${id}`)
        return response.data
    }
}

export const SettingService = {
    getAll: async () => {
        const response = await apiClient.get('/settings')
        return response.data
    },
    save: async (data) => {
        const response = await apiClient.post('/settings', data)
        return response.data
    },
    get: async (key) => {
        const response = await apiClient.get(`/settings/${key}`)
        return response.data
    },
    update: async (key, data) => {
        const response = await apiClient.put(`/settings/${key}`, data)
        return response.data
    }
}

export const DocumentService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/student/documents', { params })
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/student/documents/${id}`)
        return response.data
    },
    create: async (data) => {
        const response = await apiClient.post('/documents', data)
        return response.data
    },
    update: async (id, data) => {
        const response = await apiClient.put(`/documents/${id}`, data)
        return response.data
    },
    delete: async (id) => {
        const response = await apiClient.delete(`/documents/${id}`)
        return response.data
    }
}

export const ArticleService = {
    getAll: async (params = {}) => {
        const response = await apiClient.get('/articles', { params })
        return response.data
    },
    getById: async (id) => {
        const response = await apiClient.get(`/articles/${id}`)
        return response.data
    },
    getLatest: async (limit = 4) => {
        const response = await apiClient.get('/articles/latest', { params: { limit } })
        return response.data
    }
}

export const SearchService = {
    search: async (params = {}) => {
        const response = await apiClient.get('/search', { params })
        return response.data
    }
}

export default apiClient
