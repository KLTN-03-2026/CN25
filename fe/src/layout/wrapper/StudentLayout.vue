<template>
  <div class="student-layout">
    <HeaderStudent />
    <main class="main-content">
      <router-view />
    </main>
    <FooterStudent />
    <ChatbotWidget />
  </div>
</template>

<script setup>
import HeaderStudent from '../../layout/components/Student/HeaderStudent.vue'
import FooterStudent from '../../layout/components/Student/FooterStudent.vue'
import ChatbotWidget from '../../components/Student/Chatbot/index.vue'
</script>

<style scoped>
.student-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
  width: 100%;
}
</style>
