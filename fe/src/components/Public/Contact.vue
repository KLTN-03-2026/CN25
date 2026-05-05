<template>
  <div class="contact-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-badge">
          <i class="fa-solid fa-headset"></i>
          Liên hệ
        </div>
        <h1 class="hero-title">
          Kết nối với
          <span class="highlight">DTU LingoAI</span>
        </h1>
        <p class="hero-desc">
          Bạn có câu hỏi hoặc cần hỗ trợ? Đội ngũ của chúng tôi luôn sẵn sàng giúp đỡ bạn
          24/7 qua nhiều kênh liên lạc khác nhau.
        </p>
      </div>
    </section>

    <!-- Contact Cards -->
    <section class="contact-cards-section">
      <div class="container">
        <div class="contact-cards-grid">
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="fa-solid fa-envelope"></i>
            </div>
            <h3>Email</h3>
            <p>Gửi email cho chúng tôi</p>
            <a href="mailto:support@dtu-lingoai.edu.vn" class="contact-link">
              support@dtu-lingoai.edu.vn
            </a>
          </div>
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="fa-solid fa-phone"></i>
            </div>
            <h3>Điện thoại</h3>
            <p>Gọi trực tiếp</p>
            <a href="tel:19001234" class="contact-link">
              1900 1234
            </a>
          </div>
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="fa-solid fa-location-dot"></i>
            </div>
            <h3>Địa chỉ</h3>
            <p>Trường Đại học Duy Tân</p>
            <span class="contact-text">
              Quận Liên Chiểu, TP. Đà Nẵng
            </span>
          </div>
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="fa-solid fa-clock"></i>
            </div>
            <h3>Giờ làm việc</h3>
            <p>Hỗ trợ 24/7</p>
            <span class="contact-text">
              Thứ 2 - Thứ 6: 8:00 - 18:00
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form + Info -->
    <section class="contact-main-section">
      <div class="container">
        <div class="contact-grid">
          <!-- Form -->
          <div class="contact-form-wrap">
            <div class="form-header">
              <h2>Gửi tin nhắn cho chúng tôi</h2>
              <p>Điền thông tin vào form bên dưới, chúng tôi sẽ phản hồi trong vòng 24 giờ.</p>
            </div>
            <form class="contact-form" @submit.prevent="handleSubmit">
              <div class="form-row">
                <div class="form-group">
                  <label for="name">Họ và tên</label>
                  <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Nhập họ và tên của bạn"
                    required
                  />
                </div>
                <div class="form-group">
                  <label for="email">Email</label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="Nhập địa chỉ email"
                    required
                  />
                </div>
              </div>
              <div class="form-group">
                <label for="phone">Số điện thoại (tùy chọn)</label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  placeholder="Nhập số điện thoại"
                />
              </div>
              <div class="form-group">
                <label for="subject">Chủ đề</label>
                <select id="subject" v-model="form.subject" required>
                  <option value="" disabled selected>-- Chọn chủ đề --</option>
                  <option value="support">Hỗ trợ kỹ thuật</option>
                  <option value="course">Tư vấn khóa học</option>
                  <option value="payment">Thanh toán &amp; hóa đơn</option>
                  <option value="feedback">Góp ý &amp; phản hồi</option>
                  <option value="partnership">Hợp tác kinh doanh</option>
                  <option value="other">Khác</option>
                </select>
              </div>
              <div class="form-group">
                <label for="message">Nội dung tin nhắn</label>
                <textarea
                  id="message"
                  v-model="form.message"
                  rows="5"
                  placeholder="Mô tả chi tiết vấn đề hoặc câu hỏi của bạn..."
                  required
                ></textarea>
              </div>
              <button type="submit" class="btn btn-submit" :disabled="isSubmitting">
                <span v-if="isSubmitting">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                  Đang gửi...
                </span>
                <span v-else>
                  <i class="fa-solid fa-paper-plane"></i>
                  Gửi tin nhắn
                </span>
              </button>
            </form>
          </div>

          <!-- Sidebar Info -->
          <div class="contact-sidebar">
            <div class="sidebar-card">
              <h3>Thông tin liên hệ</h3>
              <div class="sidebar-info-list">
                <div class="sidebar-info-item">
                  <div class="sidebar-info-icon">
                    <i class="fa-solid fa-envelope"></i>
                  </div>
                  <div>
                    <strong>Email</strong>
                    <a href="mailto:support@dtu-lingoai.edu.vn">support@dtu-lingoai.edu.vn</a>
                  </div>
                </div>
                <div class="sidebar-info-item">
                  <div class="sidebar-info-icon">
                    <i class="fa-solid fa-phone"></i>
                  </div>
                  <div>
                    <strong>Hotline</strong>
                    <a href="tel:19001234">1900 1234</a>
                  </div>
                </div>
                <div class="sidebar-info-item">
                  <div class="sidebar-info-icon">
                    <i class="fa-solid fa-location-dot"></i>
                  </div>
                  <div>
                    <strong>Địa chỉ</strong>
                    <span>Đại học Duy Tân, Đà Nẵng</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="sidebar-card">
              <h3>Kết nối mạng xã hội</h3>
              <p>Theo dõi chúng tôi để cập nhật tin tức và khuyến mãi mới nhất.</p>
              <div class="social-buttons">
                <a href="#" class="social-btn facebook">
                  <i class="fa-brands fa-facebook-f"></i>
                  Facebook
                </a>
                <a href="#" class="social-btn youtube">
                  <i class="fa-brands fa-youtube"></i>
                  YouTube
                </a>
                <a href="#" class="social-btn instagram">
                  <i class="fa-brands fa-instagram"></i>
                  Instagram
                </a>
              </div>
            </div>

            <div class="sidebar-card">
              <h3>Câu hỏi thường gặp</h3>
              <p>Tìm nhanh câu trả lời cho các câu hỏi phổ biến.</p>
              <div class="faq-list">
                <div class="faq-item">
                  <i class="fa-solid fa-chevron-right"></i>
                  <span>Làm sao đăng ký khóa học?</span>
                </div>
                <div class="faq-item">
                  <i class="fa-solid fa-chevron-right"></i>
                  <span>Phương thức thanh toán?</span>
                </div>
                <div class="faq-item">
                  <i class="fa-solid fa-chevron-right"></i>
                  <span>Chính sách hoàn tiền?</span>
                </div>
                <div class="faq-item">
                  <i class="fa-solid fa-chevron-right"></i>
                  <span>Cách liên hệ hỗ trợ?</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
      <div class="container">
        <div class="map-placeholder">
          <i class="fa-solid fa-map-location-dot"></i>
          <h3>Bản đồ Đại học Duy Tân</h3>
          <p>Quận Liên Chiểu, TP. Đà Nẵng, Việt Nam</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const form = ref({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: ''
})

const isSubmitting = ref(false)

const handleSubmit = () => {
  isSubmitting.value = true
  setTimeout(() => {
    isSubmitting.value = false
    form.value = {
      name: '',
      email: '',
      phone: '',
      subject: '',
      message: ''
    }
    alert('Gửi tin nhắn thành công! Chúng tôi sẽ phản hồi trong 24 giờ.')
  }, 1500)
}
</script>

<style scoped>
.contact-page {
  overflow-x: hidden;
}

/* ===== HERO ===== */
.hero-section {
  padding: 80px 0 40px;
  text-align: center;
  background: linear-gradient(180deg, var(--bg) 0%, var(--bg-secondary) 100%);
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 20px;
  background: var(--accent-bg);
  color: var(--accent);
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 24px;
}

.hero-title {
  font-size: 3rem;
  font-weight: 900;
  margin-bottom: 20px;
  line-height: 1.2;
}

.hero-title .highlight {
  background: linear-gradient(90deg, var(--accent), #7c3aed, #db2777);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  display: block;
}

.hero-desc {
  font-size: 18px;
  color: var(--text);
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.7;
}

/* ===== CONTACT CARDS ===== */
.contact-cards-section {
  padding: 40px 0 60px;
  background: var(--bg);
}

.contact-cards-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.contact-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 28px;
  text-align: center;
  transition: all 0.3s;
}

.contact-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--accent-border);
}

.contact-card-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: var(--accent-bg);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin: 0 auto 16px;
}

.contact-card h3 {
  font-size: 16px;
  margin-bottom: 4px;
  color: var(--text-h);
}

.contact-card p {
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 10px;
}

.contact-link {
  font-size: 14px;
  font-weight: 700;
  color: var(--accent);
  text-decoration: none;
}

.contact-link:hover {
  color: var(--accent-hover);
  text-decoration: underline;
}

.contact-text {
  font-size: 14px;
  color: var(--text);
  font-weight: 500;
}

/* ===== CONTACT MAIN ===== */
.contact-main-section {
  padding: 60px 0 80px;
  background: var(--bg-secondary);
}

.contact-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 40px;
  align-items: start;
}

/* ===== FORM ===== */
.contact-form-wrap {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 40px;
}

.form-header {
  margin-bottom: 32px;
}

.form-header h2 {
  font-size: 1.6rem;
  margin-bottom: 8px;
}

.form-header p {
  font-size: 14px;
  color: var(--text-secondary);
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-h);
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
}

.form-group textarea {
  resize: vertical;
  min-height: 120px;
}

.btn-submit {
  background: linear-gradient(135deg, var(--btn-cta), var(--btn-cta-hover));
  color: white;
  border: none;
  padding: 14px 32px;
  border-radius: 999px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  align-self: flex-start;
  box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 28px rgba(220, 38, 38, 0.45);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* ===== SIDEBAR ===== */
.contact-sidebar {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.sidebar-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 28px;
}

.sidebar-card h3 {
  font-size: 16px;
  margin-bottom: 8px;
  color: var(--text-h);
}

.sidebar-card > p {
  font-size: 14px;
  color: var(--text-secondary);
  margin-bottom: 20px;
  line-height: 1.6;
}

.sidebar-info-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.sidebar-info-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.sidebar-info-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--accent-bg);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.sidebar-info-item strong {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-h);
  margin-bottom: 2px;
}

.sidebar-info-item a,
.sidebar-info-item span {
  font-size: 14px;
  color: var(--text-secondary);
  text-decoration: none;
}

.sidebar-info-item a:hover {
  color: var(--accent);
}

/* ===== SOCIAL BUTTONS ===== */
.social-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.social-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
  border: 1px solid var(--border);
  color: var(--text);
}

.social-btn:hover {
  transform: translateX(4px);
  border-color: var(--accent-border);
  background: var(--accent-bg);
  color: var(--accent);
}

.social-btn i {
  width: 20px;
  text-align: center;
}

.social-btn.facebook:hover {
  background: #1877f2;
  border-color: #1877f2;
  color: white;
}

.social-btn.youtube:hover {
  background: #ff0000;
  border-color: #ff0000;
  color: white;
}

.social-btn.instagram:hover {
  background: #e1306c;
  border-color: #e1306c;
  color: white;
}

/* ===== FAQ ===== */
.faq-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.faq-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
  font-size: 14px;
  color: var(--text-secondary);
  cursor: pointer;
  transition: color 0.2s;
}

.faq-item:last-child {
  border-bottom: none;
}

.faq-item:hover {
  color: var(--accent);
}

.faq-item i {
  font-size: 12px;
  color: var(--muted);
  transition: color 0.2s;
}

.faq-item:hover i {
  color: var(--accent);
}

/* ===== MAP ===== */
.map-section {
  padding: 60px 0 80px;
  background: var(--bg);
}

.map-placeholder {
  width: 100%;
  aspect-ratio: 21/6;
  border-radius: 20px;
  background: linear-gradient(135deg, #e0e7ff, #ede9fe, #fce7f3);
  border: 2px dashed var(--accent-border);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: var(--accent);
}

.map-placeholder i {
  font-size: 60px;
  opacity: 0.6;
}

.map-placeholder h3 {
  font-size: 20px;
  color: var(--text-h);
}

.map-placeholder p {
  font-size: 14px;
  color: var(--text-secondary);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .hero-title {
    font-size: 2.2rem;
  }

  .contact-cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .contact-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .btn-submit {
    align-self: stretch;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding: 60px 0 30px;
  }

  .hero-title {
    font-size: 1.8rem;
  }

  .hero-desc {
    font-size: 16px;
  }

  .contact-cards-grid {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .contact-card {
    padding: 20px;
  }

  .contact-form-wrap {
    padding: 24px;
  }
}

@media (max-width: 500px) {
  .contact-cards-grid {
    grid-template-columns: 1fr;
  }

  .map-placeholder {
    aspect-ratio: 4/3;
  }
}
</style>
