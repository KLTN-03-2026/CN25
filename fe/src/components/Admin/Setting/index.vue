<template>
  <div class="setting">
    <div class="header">
      <h1>Cài Đặt Hệ Thống</h1>
    </div>

    <div class="settings-container">
      <div class="settings-sidebar">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="['tab-btn', { active: activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ tab.label }}</span>
        </button>
      </div>

      <div class="settings-content">
        <div v-if="activeTab === 'general'" class="settings-section">
          <h2>Cài Đặt Chung</h2>
          <div class="form-group">
            <label>Tên Trang Web</label>
            <input v-model="settings.site_name" type="text" placeholder="Nhập tên trang web" />
          </div>
          <div class="form-group">
            <label>Logo URL</label>
            <input v-model="settings.logo_url" type="text" placeholder="https://..." />
          </div>
          <div class="form-group">
            <label>Mô Tả Trang</label>
            <textarea v-model="settings.site_description" rows="3" placeholder="Mô tả ngắn về trang web"></textarea>
          </div>
          <div class="form-group">
            <label>Email Liên Hệ</label>
            <input v-model="settings.contact_email" type="email" placeholder="contact@example.com" />
          </div>
          <div class="form-group">
            <label>Số Điện Thoại</label>
            <input v-model="settings.contact_phone" type="tel" placeholder="0xxx xxx xxx" />
          </div>
          <div class="form-group">
            <label>Địa Chỉ</label>
            <textarea v-model="settings.address" rows="2" placeholder="Địa chỉ công ty"></textarea>
          </div>
          <button class="btn-primary" @click="saveSettings">Lưu Cài Đặt</button>
        </div>

        <div v-if="activeTab === 'email'" class="settings-section">
          <h2>Cài Đặt Email</h2>
          <div class="form-group">
            <label>SMTP Host</label>
            <input v-model="settings.smtp_host" type="text" placeholder="smtp.example.com" />
          </div>
          <div class="form-group">
            <label>SMTP Port</label>
            <input v-model.number="settings.smtp_port" type="number" placeholder="587" />
          </div>
          <div class="form-group">
            <label>SMTP Username</label>
            <input v-model="settings.smtp_username" type="text" placeholder="your@email.com" />
          </div>
          <div class="form-group">
            <label>SMTP Password</label>
            <input v-model="settings.smtp_password" type="password" placeholder="********" />
          </div>
          <div class="form-group">
            <label>
              <input v-model="settings.enable_ssl" type="checkbox" :checked="settings.enable_ssl" />
              Bật SSL/TLS
            </label>
          </div>
          <button class="btn-primary" @click="saveSettings">Lưu Cài Đặt</button>
        </div>

        <div v-if="activeTab === 'payment'" class="settings-section">
          <h2>Cài Đặt Thanh Toán</h2>
          <div class="form-group">
            <label>
              <input v-model="settings.enable_vnpay" type="checkbox" :checked="settings.enable_vnpay" />
              Bật thanh toán VNPay
            </label>
          </div>
          <div class="form-group">
            <label>VNPay Merchant ID</label>
            <input v-model="settings.vnpay_merchant_id" type="text" placeholder="VNPay Merchant ID" />
          </div>
          <div class="form-group">
            <label>VNPay Secret Key</label>
            <input v-model="settings.vnpay_secret_key" type="password" placeholder="VNPay Secret Key" />
          </div>
          <div class="form-group">
            <label>
              <input v-model="settings.enable_momo" type="checkbox" :checked="settings.enable_momo" />
              Bật thanh toán MoMo
            </label>
          </div>
          <div class="form-group">
            <label>MoMo Partner Code</label>
            <input v-model="settings.momo_partner_code" type="text" placeholder="MoMo Partner Code" />
          </div>
          <button class="btn-primary" @click="saveSettings">Lưu Cài Đặt</button>
        </div>

        <div v-if="activeTab === 'notification'" class="settings-section">
          <h2>Cài Đặt Thông Báo</h2>
          <div class="form-group">
            <label>
              <input v-model="settings.emailNotification" type="checkbox" />
              Gửi thông báo qua email
            </label>
          </div>
          <div class="form-group">
            <label>
              <input v-model="settings.pushNotification" type="checkbox" />
              Bật thông báo đẩy (Push Notification)
            </label>
          </div>
          <div class="form-group">
            <label>
              <input v-model="settings.smsNotification" type="checkbox" />
              Bật thông báo SMS
            </label>
          </div>
          <div class="form-group">
            <label>Template Email Chào Mừng</label>
            <textarea v-model="settings.welcomeEmailTemplate" rows="4" placeholder="Nội dung email chào mừng"></textarea>
          </div>
          <button class="btn-primary" @click="saveSettings">Lưu Cài Đặt</button>
        </div>

        <div v-if="activeTab === 'security'" class="settings-section">
          <h2>Bảo Mật</h2>
          <div class="form-group">
            <label>
              <input v-model="settings.twoFactorAuth" type="checkbox" />
              Bật xác thực hai yếu tố (2FA)
            </label>
          </div>
          <div class="form-group">
            <label>Số lần đăng nhập sai tối đa</label>
            <input v-model.number="settings.maxLoginAttempts" type="number" min="1" />
          </div>
          <div class="form-group">
            <label>Thời gian khoá tài khoản (phút)</label>
            <input v-model.number="settings.lockoutDuration" type="number" min="1" />
          </div>
          <div class="form-group">
            <label>
              <input v-model="settings.enableCaptcha" type="checkbox" />
              Bật Captcha khi đăng nhập
            </label>
          </div>
          <div class="form-group">
            <label>Yêu cầu đổi mật khẩu sau (ngày)</label>
            <input v-model.number="settings.passwordExpiryDays" type="number" min="1" />
          </div>
          <button class="btn-primary" @click="saveSettings">Lưu Cài Đặt</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { SettingService } from '../../../services/api.js'

const activeTab = ref('general')

const tabs = [
  { id: 'general', label: 'Chung', icon: '⚙️' },
  { id: 'email', label: 'Email', icon: '✉️' },
  { id: 'payment', label: 'Thanh Toán', icon: '💳' },
  { id: 'notification', label: 'Thông Báo', icon: '🔔' },
  { id: 'security', label: 'Bảo Mật', icon: '🔒' }
]

const settings = reactive({
  site_name: 'DTU Lingo',
  logo_url: '',
  site_description: 'Nền tảng học tiếng Anh trực tuyến',
  contact_email: 'contact@example.com',
  contact_phone: '0901234567',
  address: '123 Đường ABC, Quận 1, TP.HCM',
  smtp_host: 'smtp.gmail.com',
  smtp_port: 587,
  smtp_username: '',
  smtp_password: '',
  enable_ssl: true,
  enable_vnpay: true,
  vnpay_merchant_id: '',
  vnpay_secret_key: '',
  enable_momo: false,
  momo_partner_code: '',
  email_notification: true,
  push_notification: true,
  sms_notification: false,
  two_factor_auth: false,
  max_login_attempts: 5,
  lockout_duration: 30,
  enable_captcha: true,
  password_expiry_days: 90
})

const fetchSettings = async () => {
  try {
    const data = await SettingService.getAll()
    Object.assign(settings, data)
  } catch (error) {
    console.error('Lỗi khi tải cài đặt:', error)
  }
}

const saveSettings = async () => {
  try {
    await SettingService.save(settings)
    alert('Cài đặt đã được lưu thành công!')
  } catch (error) {
    console.error('Lỗi khi lưu cài đặt:', error)
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.setting {
  padding: 20px;
}

.header {
  margin-bottom: 24px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.settings-container {
  display: flex;
  gap: 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.settings-sidebar {
  width: 220px;
  background: #f8f9fa;
  padding: 20px 0;
  flex-shrink: 0;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 12px 20px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  color: #666;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: #e5e7eb;
  color: #333;
}

.tab-btn.active {
  background: #4f46e5;
  color: white;
}

.tab-icon {
  font-size: 16px;
}

.settings-content {
  flex: 1;
  padding: 24px;
}

.settings-section h2 {
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 20px;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="password"],
.form-group input[type="number"],
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  box-sizing: border-box;
}

.form-group input[type="checkbox"] {
  margin-right: 8px;
  width: 16px;
  height: 16px;
}

.btn-primary {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.btn-primary:hover {
  background: #4338ca;
}
</style>
