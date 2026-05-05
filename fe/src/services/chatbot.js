import apiClient from './api.js'

export const ChatbotService = {
    chat: async (message, lessonId = null) => {
        const response = await apiClient.post('/chatbot/chat', {
            message,
            lesson_id: lessonId,
        })
        return response.data
    },

    getHistory: async () => {
        const response = await apiClient.get('/chatbot/history')
        return response.data
    },

    clearHistory: async () => {
        const response = await apiClient.delete('/chatbot/history')
        return response.data
    },

    getStatus: async () => {
        const response = await apiClient.get('/chatbot/status')
        return response.data
    },
}
