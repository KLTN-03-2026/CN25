<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="brand-row">
            <div class="brand-icon">
              <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="brand-text">
              <span class="brand-name">DTU LingoAI</span>
              <span class="brand-tagline">Học tiếng Anh thông minh</span>
            </div>
          </div>
          <p class="footer-desc">
            Nền tảng học tiếng Anh sử dụng AI, giúp bạn luyện nói, luyện nghe và giao tiếp tự tin mỗi ngày.
          </p>
          <div class="social-links">
            <a href="#" class="social-btn" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" class="social-btn" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
            <a href="#" class="social-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" class="social-btn" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
          </div>
        </div>

        <div class="footer-cols">
          <div class="footer-col">
            <div class="footer-title">Học tập</div>
            <router-link to="/student">Trang chủ</router-link>
            <router-link to="/student/khoa-hoc">Khóa học</router-link>
            <router-link to="/student/tai-lieu">Tài liệu</router-link>
            <router-link to="/student/bai-viet">Bài viết</router-link>
          </div>

          <div class="footer-col">
            <div class="footer-title">Hỗ trợ</div>
            <a href="#">Hướng dẫn sử dụng</a>
            <a href="#">Câu hỏi thường gặp</a>
            <a href="#">Liên hệ hỗ trợ</a>
            <a href="#">Báo lỗi</a>
          </div>

          <div class="footer-col">
            <div class="footer-title">Về chúng tôi</div>
            <a href="#">Giới thiệu</a>
            <a href="#">Chính sách bảo mật</a>
            <a href="#">Điều khoản sử dụng</a>
          </div>

          <div class="footer-col">
            <div class="footer-title">Liên hệ</div>
            <div class="contact-item">
              <i class="fa-solid fa-envelope"></i>
              <a href="mailto:support@dtulingoai.vn">support@dtulingoai.vn</a>
            </div>
            <div class="contact-item">
              <i class="fa-solid fa-phone"></i>
              <a href="tel:0909000000">0909 000 000</a>
            </div>
            <div class="contact-item">
              <i class="fa-solid fa-location-dot"></i>
              <span>Đại học Duy Tân, Đà Nẵng</span>
            </div>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <span>&copy; {{ year }} DTU LingoAI. Tất cả quyền được bảo vệ.</span>
        <span class="muted">Đồ án tốt nghiệp &ndash; Trường Đại học Duy Tân</span>
      </div>
    </div>
  </footer>
</template>

<script setup>
const year = new Date().getFullYear()
</script>

<style scoped>
.footer {
  background: var(--bg-secondary);
  color: var(--text);
  border-top: 1px solid var(--border);
  margin-top: auto;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.footer-inner {
  display: flex;
  gap: 48px;
  padding: 48px 0 36px;
  flex-wrap: wrap;
}

.footer-brand {
  flex: 0 0 260px;
}

.brand-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.brand-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--accent), #7c3aed);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.brand-name {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-h);
}

.brand-tagline {
  font-size: 12px;
  color: var(--muted);
  font-weight: 500;
}

.footer-desc {
  color: var(--text);
  line-height: 1.7;
  margin-bottom: 16px;
  font-size: 14px;
}

.social-links {
  display: flex;
  gap: 8px;
}

.social-btn {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--card-bg);
  border: 1px solid var(--border);
  color: var(--text);
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 14px;
  transition: all 0.25s;
}

.social-btn:hover {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
  transform: translateY(-2px);
}

.footer-cols {
  display: grid;
  grid-template-columns: repeat(3, 1fr) 1.2fr;
  gap: 32px;
  flex: 1;
}

.footer-col {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.footer-title {
  font-weight: 700;
  font-size: 14px;
  color: var(--text-h);
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.footer-col a,
.footer-col span {
  text-decoration: none;
  color: var(--text);
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.footer-col a:hover {
  color: var(--accent);
  transform: translateX(4px);
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: var(--text);
}

.contact-item i {
  width: 16px;
  color: var(--accent);
  font-size: 13px;
}

.contact-item a {
  text-decoration: none;
  color: inherit;
  font-size: inherit;
  font-weight: inherit;
  padding: 0;
  transform: none !important;
}

.contact-item a:hover {
  color: var(--accent);
  transform: none !important;
}

.footer-bottom {
  border-top: 1px solid var(--border);
  padding: 18px 0;
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
  color: var(--muted);
}

.muted {
  font-style: italic;
}

@media (max-width: 1024px) {
  .footer-inner {
    flex-direction: column;
  }

  .footer-brand {
    flex: none;
  }

  .footer-cols {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .footer-inner {
    padding: 36px 0 24px;
  }

  .footer-cols {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }
}
</style>
