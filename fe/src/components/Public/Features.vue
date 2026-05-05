<template>
  <div class="features-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-badge">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
          Tính năng
        </div>
        <h1 class="hero-title">
          Điều gì làm nên
          <span class="highlight">sự khác biệt?</span>
        </h1>
        <p class="hero-desc">
          DTU LingoAI không chỉ là một ứng dụng học tiếng Anh thông thường.
          Chúng tôi mang đến trải nghiệm học tập được cá nhân hóa bằng AI,
          giúp bạn tiến bộ nhanh hơn và tự tin giao tiếp.
        </p>
      </div>
    </section>

    <!-- Features Grid -->
    <section class="features-section">
      <div class="container">
        <div class="features-grid">

          <!-- AI Cá nhân hóa -->
          <div class="feature-card feature-card--highlight">
            <div class="feature-number">01</div>
            <div class="feature-icon">
              <i class="fa-solid fa-brain"></i>
            </div>
            <h3>AI Cá nhân hóa lộ trình</h3>
            <p>Hệ thống AI phân tích trình độ hiện tại của bạn và xây dựng lộ trình học tập riêng biệt. Gợi ý bài học phù hợp dựa trên điểm mạnh, điểm yếu và tốc độ tiếp thu của bạn.</p>
            <div class="feature-tags">
              <span class="tag">Điều chỉnh theo bạn</span>
              <span class="tag">Gợi ý thông minh</span>
            </div>
          </div>

          <!-- Học từ vựng -->
          <div class="feature-card">
            <div class="feature-number">02</div>
            <div class="feature-icon">
              <i class="fa-solid fa-book-open"></i>
            </div>
            <h3>Học từ vựng theo chủ đề</h3>
            <p>Học từ vựng được phân loại theo từng chủ đề giao tiếp thực tế. Mỗi từ đi kèm phiên âm IPA, hình ảnh minh họa, ví dụ câu và audio phát âm chuẩn.</p>
            <div class="feature-tags">
              <span class="tag">Theo chủ đề</span>
              <span class="tag">Audio phát âm</span>
            </div>
          </div>

          <!-- Ngữ pháp -->
          <div class="feature-card">
            <div class="feature-number">03</div>
            <div class="feature-icon">
              <i class="fa-solid fa-pen-nib"></i>
            </div>
            <h3>Ngữ pháp với video bài giảng</h3>
            <p>Mỗi bài ngữ pháp đi kèm video giảng dạy chi tiết từ giảng viên. Kết hợp lý thuyết cấu trúc, các tín hiệu ngữ pháp và bài tập luyện tập ngay sau mỗi bài.</p>
            <div class="feature-tags">
              <span class="tag">Video bài giảng</span>
              <span class="tag">Tín hiệu ngữ pháp</span>
            </div>
          </div>

          <!-- Luyện nghe -->
          <div class="feature-card">
            <div class="feature-number">04</div>
            <div class="feature-icon">
              <i class="fa-solid fa-headphones"></i>
            </div>
            <h3>Luyện nghe với audio chất lượng</h3>
            <p>Bài luyện nghe sử dụng audio chất lượng cao, kết hợp câu hỏi trắc nghiệm đa dạng giúp bạn cải thiện kỹ năng nghe hiểu một cách toàn diện.</p>
            <div class="feature-tags">
              <span class="tag">Audio chất lượng cao</span>
              <span class="tag">Quiz luyện nghe</span>
            </div>
          </div>

          <!-- Luyện nói -->
          <div class="feature-card feature-card--wide">
            <div class="feature-number">05</div>
            <div class="feature-icon feature-icon--large">
              <i class="fa-solid fa-microphone"></i>
            </div>
            <h3>Luyện nói với AI chấm điểm</h3>
            <p>Sử dụng micro để ghi âm câu trả lời, công nghệ speech-to-text nhận diện giọng nói và chấm điểm phát âm ngay lập tức. Bạn có thể luyện nói 24/7 mà không cần người hướng dẫn.</p>
            <div class="feature-tags">
              <span class="tag">Micro ghi âm</span>
              <span class="tag">Speech-to-text</span>
              <span class="tag">Chấm điểm ngay</span>
            </div>
          </div>

          <!-- Luyện tập -->
          <div class="feature-card">
            <div class="feature-number">06</div>
            <div class="feature-icon">
              <i class="fa-solid fa-pencil"></i>
            </div>
            <h3>Bài tập luyện tập đa dạng</h3>
            <p>Hệ thống bài tập phong phú: trắc nghiệm, điền từ, sắp xếp câu, ghép nối... Mỗi bài tập đều có điểm số và phản hồi chi tiết giúp bạn hiểu rõ lỗi sai.</p>
            <div class="feature-tags">
              <span class="tag">Nhiều dạng bài</span>
              <span class="tag">Phản hồi chi tiết</span>
            </div>
          </div>

          <!-- Dashboard -->
          <div class="feature-card">
            <div class="feature-number">07</div>
            <div class="feature-icon">
              <i class="fa-solid fa-chart-line"></i>
            </div>
            <h3>Dashboard theo dõi tiến độ</h3>
            <p>Bảng điều khiển trực quan hiển thị số bài đã học, thời gian học, điểm trung bình và biểu đồ tiến độ theo từng ngày. Giúp bạn theo dõi và duy trì động lực học tập.</p>
            <div class="feature-tags">
              <span class="tag">Biểu đồ trực quan</span>
              <span class="tag">Thống kê chi tiết</span>
            </div>
          </div>

          <!-- Chứng chỉ -->
          <div class="feature-card">
            <div class="feature-number">08</div>
            <div class="feature-icon">
              <i class="fa-solid fa-certificate"></i>
            </div>
            <h3>Chứng chỉ hoàn thành khóa học</h3>
            <p>Sau khi hoàn thành tất cả bài học và thi cuối khóa, bạn sẽ nhận được chứng chỉ hoàn thành khóa học. Đây là minh chứng cho sự nỗ lực và tiến bộ của bạn.</p>
            <div class="feature-tags">
              <span class="tag">Thi cuối khóa</span>
              <span class="tag">Chứng chỉ</span>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- Comparison Section -->
    <section class="comparison-section">
      <div class="container">
        <div class="section-header">
          <div class="section-badge">
            <i class="fa-solid fa-scale-balanced"></i>
            So sánh
          </div>
          <h2>Tại sao chọn DTU LingoAI?</h2>
          <p>Sự khác biệt giữa học tiếng Anh truyền thống và học cùng AI.</p>
        </div>
        <div class="comparison-table">
          <div class="comp-header">
            <div class="comp-col-label"></div>
            <div class="comp-col">Học truyền thống</div>
            <div class="comp-col comp-col--highlight">DTU LingoAI</div>
          </div>
          <div class="comp-row">
            <div class="comp-col-label">Lộ trình học</div>
            <div class="comp-col">
              <i class="fa-solid fa-xmark cross"></i>
              Cố định, không cá nhân hóa
            </div>
            <div class="comp-col comp-col--highlight">
              <i class="fa-solid fa-check check"></i>
              AI cá nhân hóa theo từng người
            </div>
          </div>
          <div class="comp-row">
            <div class="comp-col-label">Phản hồi phát âm</div>
            <div class="comp-col">
              <i class="fa-solid fa-xmark cross"></i>
              Không có, cần giáo viên
            </div>
            <div class="comp-col comp-col--highlight">
              <i class="fa-solid fa-check check"></i>
              AI chấm điểm phát âm ngay
            </div>
          </div>
          <div class="comp-row">
            <div class="comp-col-label">Thời gian học</div>
            <div class="comp-col">
              <i class="fa-solid fa-xmark cross"></i>
              Theo lịch cố định
            </div>
            <div class="comp-col comp-col--highlight">
              <i class="fa-solid fa-check check"></i>
              Học mọi lúc, mọi nơi 24/7
            </div>
          </div>
          <div class="comp-row">
            <div class="comp-col-label">Theo dõi tiến độ</div>
            <div class="comp-col">
              <i class="fa-solid fa-xmark cross"></i>
              Thủ công, không rõ ràng
            </div>
            <div class="comp-col comp-col--highlight">
              <i class="fa-solid fa-check check"></i>
              Dashboard tự động cập nhật
            </div>
          </div>
          <div class="comp-row">
            <div class="comp-col-label">Chi phí</div>
            <div class="comp-col">
              <i class="fa-solid fa-xmark cross"></i>
              Cao, học phí gia sư
            </div>
            <div class="comp-col comp-col--highlight">
              <i class="fa-solid fa-check check"></i>
              Tiết kiệm, chỉ từ vài trăm nghìn
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-box">
          <div class="cta-icon">
            <i class="fa-solid fa-rocket"></i>
          </div>
          <h2>Sẵn sàng trải nghiệm?</h2>
          <p>Đăng ký ngay hôm nay và bắt đầu hành trình chinh phục tiếng Anh cùng AI.</p>
          <div class="cta-actions">
            <router-link to="/auth/register" class="btn btn-cta">
              <i class="fa-solid fa-rocket"></i>
              Đăng ký miễn phí
            </router-link>
            <router-link to="/contact" class="btn btn-outline-light">
              Liên hệ tư vấn
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
</script>

<style scoped>
.features-page {
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
  max-width: 700px;
  margin: 0 auto;
  line-height: 1.7;
}

/* ===== FEATURES GRID ===== */
.features-section {
  padding: 60px 0 80px;
  background: var(--bg);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
}

.feature-card {
  position: relative;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 32px;
  transition: all 0.3s;
  overflow: hidden;
}

.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-xl);
  border-color: var(--accent-border);
}

.feature-card--highlight {
  background: linear-gradient(135deg, var(--accent), #7c3aed);
  border-color: transparent;
  grid-column: span 2;
}

.feature-card--highlight .feature-icon {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  box-shadow: 0 4px 14px rgba(255, 255, 255, 0.2);
}

.feature-card--highlight h3,
.feature-card--highlight p,
.feature-card--highlight .feature-number {
  color: white;
}

.feature-card--highlight p {
  opacity: 0.9;
}

.feature-card--wide {
  grid-column: span 2;
}

.feature-number {
  position: absolute;
  top: 20px;
  right: 24px;
  font-size: 42px;
  font-weight: 900;
  color: var(--border);
  line-height: 1;
  user-select: none;
}

.feature-card:hover .feature-number {
  color: var(--accent-border);
}

.feature-icon {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: var(--accent-bg);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  margin-bottom: 20px;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.2);
}

.feature-icon--large {
  width: 80px;
  height: 80px;
  font-size: 32px;
  border-radius: 22px;
}

.feature-card h3 {
  font-size: 18px;
  margin-bottom: 12px;
  color: var(--text-h);
}

.feature-card p {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.75;
  margin-bottom: 16px;
}

.feature-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag {
  padding: 5px 12px;
  background: var(--accent-bg);
  color: var(--accent);
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.feature-card--highlight .tag {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

/* ===== COMPARISON ===== */
.comparison-section {
  padding: 80px 0;
  background: var(--bg-secondary);
}

.section-header {
  text-align: center;
  max-width: 600px;
  margin: 0 auto 50px;
}

.section-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  background: var(--accent-bg);
  color: var(--accent);
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 16px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.section-header h2 {
  font-size: 2.2rem;
  margin-bottom: 12px;
}

.section-header p {
  font-size: 16px;
  color: var(--text-secondary);
  line-height: 1.7;
}

.comparison-table {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  overflow: hidden;
  max-width: 900px;
  margin: 0 auto;
}

.comp-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  background: var(--bg);
  border-bottom: 2px solid var(--border);
}

.comp-col-label {
  padding: 18px 24px;
  font-size: 14px;
  font-weight: 700;
  color: var(--text-h);
}

.comp-col {
  padding: 18px 20px;
  font-size: 13px;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 8px;
  border-left: 1px solid var(--border);
  text-align: center;
  justify-content: center;
}

.comp-col-label {
  text-align: left;
  justify-content: flex-start;
  border-left: none;
}

.comp-col-label:not(:first-child) {
  font-weight: 700;
  color: var(--text-h);
  font-size: 14px;
}

.comp-col--highlight {
  background: var(--accent-bg);
  color: var(--accent);
  font-weight: 600;
}

.comp-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  border-bottom: 1px solid var(--border);
  transition: background 0.2s;
}

.comp-row:last-child {
  border-bottom: none;
}

.comp-row:hover {
  background: var(--bg-secondary);
}

.comp-row .comp-col-label {
  font-weight: 500;
  color: var(--text);
}

.check {
  color: var(--success);
  font-size: 16px;
}

.cross {
  color: var(--danger);
  font-size: 16px;
}

/* ===== CTA ===== */
.cta-section {
  padding: 80px 0;
  background: var(--bg);
}

.cta-box {
  background: linear-gradient(135deg, var(--accent), #7c3aed, #db2777);
  border-radius: 24px;
  padding: 60px;
  text-align: center;
  color: white;
  max-width: 700px;
  margin: 0 auto;
}

.cta-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin: 0 auto 24px;
}

.cta-box h2 {
  font-size: 2rem;
  color: white;
  margin-bottom: 12px;
}

.cta-box p {
  font-size: 16px;
  opacity: 0.9;
  margin-bottom: 32px;
  color: rgba(255, 255, 255, 0.9);
}

.cta-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.cta-actions .btn-cta {
  background: white;
  color: var(--accent);
  border-color: white;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
}

.cta-actions .btn-cta:hover {
  background: #f9fafb;
  transform: translateY(-3px);
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.3);
}

.btn-outline-light {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.6);
  padding: 10px 24px;
  border-radius: 999px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-outline-light:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: white;
  transform: translateY(-3px);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
  .hero-title {
    font-size: 2.2rem;
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .feature-card--highlight,
  .feature-card--wide {
    grid-column: span 2;
  }

  .comparison-table {
    overflow-x: auto;
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

  .section-header h2 {
    font-size: 1.6rem;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .feature-card--highlight,
  .feature-card--wide {
    grid-column: span 1;
  }

  .comp-header,
  .comp-row {
    grid-template-columns: 1.5fr 1fr 1fr;
    min-width: 600px;
  }

  .comparison-table {
    overflow-x: auto;
    border-radius: 16px;
  }

  .cta-box {
    padding: 40px 24px;
  }

  .cta-box h2 {
    font-size: 1.5rem;
  }

  .cta-actions {
    flex-direction: column;
    align-items: center;
  }
}

@media (max-width: 500px) {
  .feature-card {
    padding: 24px;
  }

  .comp-header,
  .comp-row {
    grid-template-columns: 1.2fr 1fr 1fr;
    min-width: 560px;
  }
}
</style>
