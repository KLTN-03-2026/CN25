<template>
  <div class="chatbot-wrapper">
    <!-- Floating Button -->
    <button
      v-if="!isOpen"
      @click="openChat"
      class="chatbot-fab"
      title="Trợ lý AI"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 8V4H8"/>
        <rect width="16" height="12" x="4" y="8" rx="2"/>
        <path d="M2 14h2"/>
        <path d="M20 14h2"/>
        <path d="M15 13v2"/>
        <path d="M9 13v2"/>
      </svg>
      <span class="fab-badge" v-if="unreadCount > 0">{{ unreadCount }}</span>
    </button>

    <!-- Chat Window -->
    <div v-if="isOpen" class="chatbot-window" :class="{ 'animate-slide-up': isOpen }">
      <!-- Header -->
      <div class="chatbot-header">
        <div class="chatbot-header-info">
          <div class="chatbot-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8V4H8"/>
              <rect width="16" height="12" x="4" y="8" rx="2"/>
              <path d="M2 14h2"/>
              <path d="M20 14h2"/>
              <path d="M15 13v2"/>
              <path d="M9 13v2"/>
            </svg>
          </div>
          <div>
            <h3>Trợ lý AI</h3>
            <span class="status-indicator">
              <span class="status-dot"></span>
              Gemini-powered
            </span>
          </div>
        </div>
        <div class="chatbot-header-actions">
          <button @click="clearChat" class="btn-icon" title="Xóa lịch sử">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 6h18"/>
              <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
              <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
            </svg>
          </button>
          <button @click="closeChat" class="btn-icon" title="Đóng">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 6 6 18"/>
              <path d="m6 6 12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Messages -->
      <div class="chatbot-messages" ref="messagesContainer">
        <!-- Welcome -->
        <div v-if="messages.length === 0" class="welcome-message">
          <div class="welcome-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8V4H8"/>
              <rect width="16" height="12" x="4" y="8" rx="2"/>
              <path d="M2 14h2"/>
              <path d="M20 14h2"/>
              <path d="M15 13v2"/>
              <path d="M9 13v2"/>
            </svg>
          </div>
          <h4>Xin chào! Mình là trợ lý AI</h4>
          <p>Mình có thể giúp bạn về ngữ pháp, từ vựng, cách phát âm, và mẹo làm bài IELTS. Hãy đặt câu hỏi nhé!</p>
          <div class="quick-questions">
            <button v-for="q in quickQuestions" :key="q" @click="sendQuickQuestion(q)" class="quick-btn">
              {{ q }}
            </button>
          </div>
        </div>

        <!-- Chat messages -->
        <div
          v-for="(msg, index) in messages"
          :key="index"
          :class="['message', msg.role === 'user' ? 'message-user' : 'message-bot']"
        >
          <div class="message-avatar" v-if="msg.role === 'bot'">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8V4H8"/>
              <rect width="16" height="12" x="4" y="8" rx="2"/>
              <path d="M2 14h2"/>
              <path d="M20 14h2"/>
              <path d="M15 13v2"/>
              <path d="M9 13v2"/>
            </svg>
          </div>
          <div class="message-content" v-html="formatMessage(msg.content)"></div>
        </div>

        <!-- Typing indicator -->
        <div v-if="isTyping" class="message message-bot">
          <div class="message-avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8V4H8"/>
              <rect width="16" height="12" x="4" y="8" rx="2"/>
              <path d="M2 14h2"/>
              <path d="M20 14h2"/>
              <path d="M15 13v2"/>
              <path d="M9 13v2"/>
            </svg>
          </div>
          <div class="message-content typing-indicator">
            <span></span><span></span><span></span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="chatbot-footer">
        <div v-if="currentLessonId" class="lesson-context">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
          </svg>
          Đang hỏi về bài học #{{ currentLessonId }}
        </div>
        <form @submit.prevent="handleSubmit" class="chatbot-form">
          <input
            v-model="inputMessage"
            @keydown.enter.exact.prevent="handleSubmit"
            type="text"
            placeholder="Nhập câu hỏi của bạn..."
            :disabled="isLoading"
            class="chatbot-input"
          />
          <button
            type="submit"
            :disabled="!inputMessage.trim() || isLoading"
            class="chatbot-send-btn"
          >
            <svg v-if="!isLoading" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m22 2-7 20-4-9-9-4Z"/>
              <path d="M22 2 11 13"/>
            </svg>
            <span v-else class="spinner"></span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { ChatbotService } from '../../../services/chatbot.js'

const props = defineProps({
  lessonId: {
    type: Number,
    default: null
  }
})

const isOpen = ref(false)
const isLoading = ref(false)
const isTyping = ref(false)
const inputMessage = ref('')
const messages = ref([])
const unreadCount = ref(0)
const messagesContainer = ref(null)
const currentLessonId = ref(props.lessonId)

const quickQuestions = [
  'Cách dùng Present Perfect?',
  'Mẹo làm bài Reading IELTS?',
  'Phân biệt since và for?',
  'Cách cải thiện phát âm?',
]

const emit = defineEmits(['open', 'close'])

const openChat = () => {
  isOpen.value = true
  unreadCount.value = 0
  emit('open')
  loadHistory()
}

const closeChat = () => {
  isOpen.value = false
  emit('close')
}

const loadHistory = async () => {
  try {
    const res = await ChatbotService.getHistory()
    if (res.data && res.data.length > 0) {
      messages.value = res.data.map(msg => ({
        role: 'user',
        content: msg.user_message,
      }))
      res.data.forEach(msg => {
        messages.value.push({
          role: 'bot',
          content: msg.bot_response,
        })
      })
      scrollToBottom()
    }
  } catch (err) {
    console.error('Load history error:', err)
  }
}

const sendMessage = async () => {
  const text = inputMessage.value.trim()
  if (!text || isLoading.value) return

  inputMessage.value = ''
  messages.value.push({ role: 'user', content: text })
  scrollToBottom()
  isTyping.value = true

  try {
    const res = await ChatbotService.chat(text, currentLessonId.value)
    isTyping.value = false

    if (res.cached) {
      const lastBotMsg = messages.value[messages.value.length - 1]
      if (lastBotMsg && lastBotMsg.role === 'bot') {
        lastBotMsg.content = '[Cache] ' + lastBotMsg.content
      }
    }

    if (res.data && res.data.response) {
      messages.value.push({ role: 'bot', content: res.data.response })
    } else {
      messages.value.push({ role: 'bot', content: 'Xin lỗi, mình chưa hiểu câu hỏi. Bạn có thể diễn đạt lại được không?' })
    }
  } catch (err) {
    isTyping.value = false
    const errorMsg = err.response?.data?.error || 'Đã có lỗi xảy ra. Vui lòng thử lại.'
    messages.value.push({ role: 'bot', content: errorMsg })
  }

  scrollToBottom()
}

const handleSubmit = () => {
  sendMessage()
}

const sendQuickQuestion = (question) => {
  inputMessage.value = question
  sendMessage()
}

const clearChat = async () => {
  if (!confirm('Xóa toàn bộ lịch sử chat?')) return

  try {
    await ChatbotService.clearHistory()
    messages.value = []
  } catch (err) {
    console.error('Clear history error:', err)
  }
}

const formatMessage = (text) => {
  if (!text) return ''
  let formatted = text
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/`(.*?)`/g, '<code>$1</code>')
    .replace(/\n/g, '<br>')
  return formatted
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

onMounted(() => {
  if (props.lessonId) {
    currentLessonId.value = props.lessonId
  }
})
</script>

<style scoped>
.chatbot-wrapper {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.chatbot-fab {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
  transition: all 0.3s ease;
  position: relative;
}

.chatbot-fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 28px rgba(99, 102, 241, 0.55);
}

.fab-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #ef4444;
  color: white;
  font-size: 11px;
  font-weight: 700;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
}

.chatbot-window {
  width: 380px;
  height: 540px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 40px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

@media (max-width: 480px) {
  .chatbot-window {
    width: calc(100vw - 32px);
    height: calc(100vh - 120px);
    bottom: 80px;
    right: 16px;
  }
}

.chatbot-header {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chatbot-header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chatbot-avatar {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chatbot-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  opacity: 0.85;
}

.status-dot {
  width: 7px;
  height: 7px;
  background: #4ade80;
  border-radius: 50%;
  animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.chatbot-header-actions {
  display: flex;
  gap: 4px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-icon:hover {
  background: rgba(255, 255, 255, 0.25);
}

.chatbot-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: #f9fafb;
  scroll-behavior: smooth;
}

.chatbot-messages::-webkit-scrollbar {
  width: 4px;
}

.chatbot-messages::-webkit-scrollbar-track {
  background: transparent;
}

.chatbot-messages::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.welcome-message {
  text-align: center;
  padding: 24px 16px;
  color: #374151;
}

.welcome-icon {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.welcome-message h4 {
  margin: 0 0 8px;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.welcome-message p {
  margin: 0 0 16px;
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
}

.quick-questions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  justify-content: center;
}

.quick-btn {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 20px;
  padding: 6px 12px;
  font-size: 12px;
  color: #6366f1;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-btn:hover {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.message {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  animation: msg-appear 0.3s ease;
}

@keyframes msg-appear {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.message-user {
  flex-direction: row-reverse;
}

.message-bot {
  flex-direction: row;
}

.message-avatar {
  width: 28px;
  height: 28px;
  min-width: 28px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 4px;
}

.message-user .message-avatar {
  background: #10b981;
}

.message-content {
  max-width: 75%;
  padding: 10px 14px;
  border-radius: 14px;
  font-size: 13.5px;
  line-height: 1.55;
  word-break: break-word;
}

.message-bot .message-content {
  background: white;
  color: #1f2937;
  border-bottom-left-radius: 4px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.message-user .message-content {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-bottom-right-radius: 4px;
}

.message-content :deep(strong) {
  font-weight: 600;
}

.message-content :deep(code) {
  background: rgba(0, 0, 0, 0.06);
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 12px;
  font-family: 'JetBrains Mono', monospace;
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 12px 16px;
}

.typing-indicator span {
  width: 7px;
  height: 7px;
  background: #9ca3af;
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
  30% { transform: translateY(-6px); opacity: 1; }
}

.chatbot-footer {
  padding: 12px 16px 16px;
  background: white;
  border-top: 1px solid #f3f4f6;
}

.lesson-context {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: #6366f1;
  background: #eef2ff;
  padding: 4px 10px;
  border-radius: 20px;
  margin-bottom: 8px;
  width: fit-content;
}

.chatbot-form {
  display: flex;
  gap: 8px;
  align-items: center;
}

.chatbot-input {
  flex: 1;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 24px;
  font-size: 13.5px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: #f9fafb;
}

.chatbot-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  background: white;
}

.chatbot-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.chatbot-send-btn {
  width: 40px;
  height: 40px;
  border: none;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.chatbot-send-btn:hover:not(:disabled) {
  transform: scale(1.08);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
}

.chatbot-send-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
