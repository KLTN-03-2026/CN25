<template>
  <div class="nguoi-dung">
    <div class="header">
      <h1>Quản Lý Người Dùng</h1>
      <button class="btn-primary" @click="openAddModal">
        <span>+</span> Thêm Người Dùng
      </button>
    </div>

    <div class="filter-bar">
      <div class="search-wrapper">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.35-4.35"/>
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm theo tên, email, số điện thoại..."
          class="search-input"
          @input="debounceSearch"
        />
      </div>
      <select v-model="filterVaiTro" class="filter-select" @change="fetchUsers">
        <option value="">Tất cả vai trò</option>
        <option value="admin">Quản trị viên</option>
        <option value="student">Học viên</option>
      </select>
      <select v-model="filterTrangThai" class="filter-select" @change="fetchUsers">
        <option value="">Tất cả trạng thái</option>
        <option value="active">Hoạt động</option>
        <option value="inactive">Bị khoá</option>
      </select>
      <select v-model="filterGioiTinh" class="filter-select" @change="fetchUsers">
        <option value="">Tất cả giới tính</option>
        <option value="male">Nam</option>
        <option value="female">Nữ</option>
        <option value="other">Khác</option>
      </select>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-icon total">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ pagination.total || nguoiDungList.length }}</div>
          <div class="stat-label">Tổng Người Dùng</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon student">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ soHocVien }}</div>
          <div class="stat-label">Học Viên</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon admin">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ soQuanTri }}</div>
          <div class="stat-label">Quản Trị Viên</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ nguoiDungActive }}</div>
          <div class="stat-label">Đang Hoạt Động</div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
      <span>Đang tải dữ liệu...</span>
    </div>

    <div v-else-if="nguoiDungList.length === 0" class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <p>Không tìm thấy người dùng nào.</p>
    </div>

    <template v-else>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width:50px">ID</th>
              <th>Người Dùng</th>
              <th>Email</th>
              <th>SĐT</th>
              <th>Giới Tính</th>
              <th>Vai Trò</th>
              <th>Đăng Nhập Cuối</th>
              <th>Trạng Thái</th>
              <th style="width:200px">Hành Động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in nguoiDungList" :key="item.id">
              <td class="id-cell">#{{ item.id }}</td>
              <td>
                <div class="user-cell">
                  <img :src="item.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=4f46e5&color=fff&size=64`" :alt="item.name" class="avatar" />
                  <span class="user-name">{{ item.name }}</span>
                </div>
              </td>
              <td class="email-cell">{{ item.email }}</td>
              <td>{{ item.phone || '—' }}</td>
              <td>{{ getGioiTinhLabel(item.gender) }}</td>
              <td>
                <span :class="['badge', item.role]">{{ getVaiTroLabel(item.role) }}</span>
              </td>
              <td class="date-cell">{{ formatDate(item.last_login_at) }}</td>
              <td>
                <button
                  :class="['status-toggle', item.is_active ? 'active' : 'inactive']"
                  @click="toggleStatus(item)"
                  :title="item.is_active ? 'Click để khoá' : 'Click để kích hoạt'"
                >
                  <span class="status-dot"></span>
                  {{ item.is_active ? 'Hoạt động' : 'Bị khoá' }}
                </button>
              </td>
              <td class="actions">
                <button class="btn-icon btn-view" @click="viewDetail(item)" title="Xem chi tiết">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
                <button class="btn-icon btn-edit" @click="editItem(item)" title="Sửa">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
                <button class="btn-icon btn-delete" @click="deleteItem(item)" title="Xoá">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="pagination">
        <button class="page-btn" :disabled="pagination.current_page === 1" @click="changePage(pagination.current_page - 1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </button>
        <div class="page-numbers">
          <span v-for="page in visiblePages" :key="page">
            <button v-if="page !== '...'" class="page-btn" :class="{ active: page === pagination.current_page }" @click="changePage(page)">{{ page }}</button>
            <span v-else class="page-ellipsis">...</span>
          </span>
        </div>
        <button class="page-btn" :disabled="pagination.current_page === pagination.last_page" @click="changePage(pagination.current_page + 1)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </button>
        <span class="page-info">Trang {{ pagination.current_page }} / {{ pagination.last_page }} — {{ pagination.total }} người dùng</span>
      </div>
    </template>

    <!-- Modal Xem Chi Tiết -->
    <div v-if="showViewModal" class="modal-overlay" @click.self="showViewModal = false">
      <div class="modal modal-view">
        <div class="modal-header">
          <h2>Chi Tiết Người Dùng</h2>
          <button class="btn-close" @click="showViewModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="profile-header">
            <img :src="viewData.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(viewData.name)}&background=4f46e5&color=fff&size=128`" :alt="viewData.name" class="profile-avatar" />
            <div class="profile-info">
              <h3>{{ viewData.name }}</h3>
              <span :class="['badge', viewData.role]">{{ getVaiTroLabel(viewData.role) }}</span>
              <span :class="['status-badge', viewData.is_active ? 'active' : 'inactive']">
                {{ viewData.is_active ? 'Hoạt động' : 'Bị khoá' }}
              </span>
            </div>
          </div>
          <div class="detail-grid">
            <div class="detail-item">
              <label>Email</label>
              <span>{{ viewData.email }}</span>
            </div>
            <div class="detail-item">
              <label>Số Điện Thoại</label>
              <span>{{ viewData.phone || '—' }}</span>
            </div>
            <div class="detail-item">
              <label>Giới Tính</label>
              <span>{{ getGioiTinhLabel(viewData.gender) }}</span>
            </div>
            <div class="detail-item">
              <label>Ngày Sinh</label>
              <span>{{ viewData.birthday ? formatDate(viewData.birthday) : '—' }}</span>
            </div>
            <div class="detail-item">
              <label>Quốc Gia</label>
              <span>{{ viewData.country || '—' }}</span>
            </div>
            <div class="detail-item">
              <label>Thành Phố</label>
              <span>{{ viewData.city || '—' }}</span>
            </div>
            <div class="detail-item">
              <label>Ngày Đăng Ký</label>
              <span>{{ formatDate(viewData.created_at) }}</span>
            </div>
            <div class="detail-item">
              <label>Đăng Nhập Cuối</label>
              <span>{{ viewData.last_login_at ? formatDate(viewData.last_login_at) : '—' }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showViewModal = false">Đóng</button>
          <button class="btn-primary" @click="goToEdit">Sửa Người Dùng</button>
        </div>
      </div>
    </div>

    <!-- Modal Thêm / Sửa -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ editingItem ? 'Sửa Người Dùng' : 'Thêm Người Dùng' }}</h2>
          <button class="btn-close" @click="closeModal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Họ Tên <span class="required">*</span></label>
            <input v-model="formData.name" type="text" placeholder="Nhập họ tên đầy đủ" :class="{ error: errors.name }" />
            <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Email <span class="required">*</span></label>
              <input v-model="formData.email" type="email" placeholder="email@example.com" :class="{ error: errors.email }" />
              <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
            </div>
            <div class="form-group">
              <label>Số Điện Thoại</label>
              <input v-model="formData.phone" type="tel" placeholder="0xxx xxx xxx" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Vai Trò <span class="required">*</span></label>
              <select v-model="formData.role" :class="{ error: errors.role }">
                <option value="student">Học viên</option>
                <option value="admin">Quản trị viên</option>
              </select>
              <span v-if="errors.role" class="error-text">{{ errors.role }}</span>
            </div>
            <div class="form-group">
              <label>Link Avatar</label>
              <input v-model="formData.avatar" type="text" placeholder="https://..." />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Giới Tính</label>
              <select v-model="formData.gender">
                <option value="">— Chọn —</option>
                <option value="male">Nam</option>
                <option value="female">Nữ</option>
                <option value="other">Khác</option>
              </select>
            </div>
            <div class="form-group">
              <label>Ngày Sinh</label>
              <input v-model="formData.birthday" type="date" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Quốc Gia</label>
              <input v-model="formData.country" type="text" placeholder="Việt Nam" />
            </div>
            <div class="form-group">
              <label>Thành Phố</label>
              <input v-model="formData.city" type="text" placeholder="TP. Hồ Chí Minh" />
            </div>
          </div>
          <div v-if="!editingItem" class="form-row">
            <div class="form-group">
              <label>Mật Khẩu <span class="required">*</span></label>
              <div class="password-wrapper">
                <input v-model="formData.password" :type="showPassword ? 'text' : 'password'" placeholder="Ít nhất 6 ký tự" :class="{ error: errors.password }" />
                <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                  <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
              <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
            </div>
            <div class="form-group">
              <label>Xác Nhận Mật Khẩu <span class="required">*</span></label>
              <div class="password-wrapper">
                <input v-model="formData.password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Nhập lại mật khẩu" :class="{ error: errors.password_confirmation }" />
              </div>
              <span v-if="errors.password_confirmation" class="error-text">{{ errors.password_confirmation }}</span>
            </div>
          </div>
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="formData.is_active" type="checkbox" />
              <span class="checkbox-custom"></span>
              Hoạt động
            </label>
            <span class="checkbox-hint">Nếu bỏ chọn, người dùng sẽ không thể đăng nhập.</span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="closeModal">Huỷ</button>
          <button class="btn-primary" :disabled="saving" @click="saveItem">
            <span v-if="saving" class="btn-spinner"></span>
            {{ saving ? 'Đang lưu...' : (editingItem ? 'Cập nhật' : 'Thêm mới') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span>{{ toast.message }}</span>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { UserService } from '../../../services/api.js'

const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const editingItem = ref(null)
const searchQuery = ref('')
const filterVaiTro = ref('')
const filterTrangThai = ref('')
const filterGioiTinh = ref('')
const showPassword = ref(false)
const searchTimeout = ref(null)

const viewData = ref({})
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const formData = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  role: 'student',
  avatar: '',
  is_active: true,
  gender: '',
  birthday: '',
  country: '',
  city: ''
})

const errors = ref({})

const toast = ref({ show: false, type: 'success', message: '' })

const nguoiDungList = ref([])

const showToast = (message, type = 'success') => {
  toast.value = { show: true, type, message }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const fetchUsers = async (page = 1) => {
  loading.value = true
  try {
    const params = { page, per_page: 15 }
    if (filterVaiTro.value) params.role = filterVaiTro.value
    if (filterTrangThai.value) params.status = filterTrangThai.value
    if (filterGioiTinh.value) params.gender = filterGioiTinh.value
    if (searchQuery.value) params.search = searchQuery.value
    const data = await UserService.getAll(params)
    if (Array.isArray(data)) {
      nguoiDungList.value = data
      pagination.value = { current_page: 1, last_page: 1, total: data.length }
    } else {
      nguoiDungList.value = data.data || []
      pagination.value = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        total: data.total || 0
      }
    }
  } catch (error) {
    console.error('Lỗi khi tải người dùng:', error)
    showToast('Không thể tải danh sách người dùng!', 'error')
  } finally {
    loading.value = false
  }
}

const debounceSearch = () => {
  clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => fetchUsers(1), 400)
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  fetchUsers(page)
}

const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages = []
  if (current <= 4) {
    for (let i = 1; i <= 5; i++) pages.push(i)
    pages.push('...')
    pages.push(last)
  } else if (current >= last - 3) {
    pages.push(1)
    pages.push('...')
    for (let i = last - 4; i <= last; i++) pages.push(i)
  } else {
    pages.push(1, '...', current - 1, current, current + 1, '...', last)
  }
  return pages
})

const soHocVien = computed(() => nguoiDungList.value.filter(i => i.role === 'student').length)
const soQuanTri = computed(() => nguoiDungList.value.filter(i => i.role === 'admin').length)
const nguoiDungActive = computed(() => nguoiDungList.value.filter(i => i.is_active).length)

const getVaiTroLabel = (vaiTro) => {
  const labels = { admin: 'Quản trị viên', student: 'Học viên' }
  return labels[vaiTro] || vaiTro
}

const getGioiTinhLabel = (gender) => {
  const labels = { male: 'Nam', female: 'Nữ', other: 'Khác' }
  return labels[gender] || '—'
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const viewDetail = (item) => {
  viewData.value = { ...item }
  showViewModal.value = true
}

const goToEdit = () => {
  showViewModal.value = false
  editItem(viewData.value)
}

const openAddModal = () => {
  editingItem.value = null
  resetForm()
  errors.value = {}
  showModal.value = true
}

const editItem = (item) => {
  editingItem.value = item.id
  formData.value = {
    name: item.name || '',
    email: item.email || '',
    password: '',
    password_confirmation: '',
    phone: item.phone || '',
    role: item.role || 'student',
    avatar: item.avatar || '',
    is_active: item.is_active !== false && item.is_active !== 0,
    gender: item.gender || '',
    birthday: item.birthday || '',
    country: item.country || '',
    city: item.city || '',
    bio: item.bio || ''
  }
  errors.value = {}
  showModal.value = true
}

const validateForm = () => {
  const errs = {}
  if (!formData.value.name.trim()) errs.name = 'Họ tên không được để trống.'
  if (!formData.value.email.trim()) errs.email = 'Email không được để trống.'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) errs.email = 'Email không hợp lệ.'
  if (!editingItem.value) {
    if (!formData.value.password) errs.password = 'Mật khẩu không được để trống.'
    else if (formData.value.password.length < 6) errs.password = 'Mật khẩu phải ít nhất 6 ký tự.'
    if (!formData.value.password_confirmation) errs.password_confirmation = 'Vui lòng xác nhận mật khẩu.'
    else if (formData.value.password !== formData.value.password_confirmation) errs.password_confirmation = 'Mật khẩu xác nhận không khớp.'
  }
  if (!formData.value.role) errs.role = 'Vui lòng chọn vai trò.'
  errors.value = errs
  return Object.keys(errs).length === 0
}

const saveItem = async () => {
  if (!validateForm()) return
  saving.value = true
  try {
    const data = { ...formData.value }
    if (editingItem.value) {
      delete data.password
      delete data.password_confirmation
      await UserService.update(editingItem.value, data)
      showToast('Cập nhật người dùng thành công!')
    } else {
      await UserService.create(data)
      showToast('Thêm người dùng mới thành công!')
    }
    closeModal()
    await fetchUsers(pagination.value.current_page)
  } catch (error) {
    console.error('Lỗi khi lưu:', error)
    const msg = error.response?.data?.message || error.response?.data?.error || 'Có lỗi xảy ra!'
    if (error.response?.data?.errors) {
      const fieldErrors = error.response.data.errors
      errors.value = {}
      for (const key in fieldErrors) {
        errors.value[key] = Array.isArray(fieldErrors[key]) ? fieldErrors[key][0] : fieldErrors[key]
      }
    }
    showToast(msg, 'error')
  } finally {
    saving.value = false
  }
}

const deleteItem = async (item) => {
  if (!confirm(`Bạn có chắc chắn muốn xoá người dùng "${item.name}"?`)) return
  if (item.id === authUserId()) {
    showToast('Bạn không thể xoá chính mình!', 'error')
    return
  }
  try {
    loading.value = true
    await UserService.delete(item.id)
    showToast('Xoá người dùng thành công!')
    await fetchUsers(pagination.value.current_page)
  } catch (error) {
    console.error('Lỗi khi xoá:', error)
    showToast(error.response?.data?.message || 'Không thể xoá người dùng!', 'error')
  } finally {
    loading.value = false
  }
}

const toggleStatus = async (item) => {
  const newStatus = !item.is_active
  const action = newStatus ? 'kích hoạt' : 'khoá'
  if (!confirm(`Bạn có chắc muốn ${action} tài khoản "${item.name}"?`)) return
  try {
    await UserService.update(item.id, { is_active: newStatus })
    item.is_active = newStatus
    showToast(`Đã ${action === 'kích hoạt' ? 'kích hoạt' : 'khoá'} tài khoản thành công!`)
  } catch (error) {
    console.error('Lỗi khi đổi trạng thái:', error)
    showToast('Không thể thay đổi trạng thái!', 'error')
  }
}

const closeModal = () => {
  showModal.value = false
  editingItem.value = null
  resetForm()
  errors.value = {}
}

const resetForm = () => {
  formData.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    role: 'student',
    avatar: '',
    is_active: true,
    gender: '',
    birthday: '',
    country: '',
    city: '',
    bio: ''
  }
  errors.value = {}
  showPassword.value = false
}

const authUserId = () => {
  try {
    const userData = localStorage.getItem('user')
    if (userData) return JSON.parse(userData).id
  } catch (e) {}
  return null
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.nguoi-dung {
  padding: 24px;
  background: #f0f2f5;
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  background: white;
  padding: 20px 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.header h1 {
  font-size: 22px;
  font-weight: 700;
  margin: 0;
  color: #1a1a2e;
}

.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 250px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  color: #9ca3af;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 10px 15px 10px 40px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
}

.search-input:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.filter-select {
  padding: 10px 15px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  cursor: pointer;
  min-width: 160px;
  transition: border-color 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #4f46e5;
}

.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon svg {
  width: 24px;
  height: 24px;
}

.stat-icon.total { background: #ede9fe; color: #7c3aed; }
.stat-icon.student { background: #dbeafe; color: #2563eb; }
.stat-icon.admin { background: #fee2e2; color: #dc2626; }
.stat-icon.active { background: #d1fae5; color: #059669; }

.stat-content {
  min-width: 0;
}

.stat-value {
  font-size: 26px;
  font-weight: 700;
  color: #1a1a2e;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #6b7280;
  margin-top: 2px;
}

.loading-overlay {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px;
  background: white;
  border-radius: 12px;
  color: #6b7280;
  font-size: 15px;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 40px;
  background: white;
  border-radius: 12px;
  color: #9ca3af;
  gap: 12px;
}

.empty-state svg {
  width: 64px;
  height: 64px;
  opacity: 0.4;
}

.empty-state p {
  font-size: 16px;
  margin: 0;
}

.table-wrapper {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f9fafb;
  padding: 14px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #f3f4f6;
}

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #f3f4f6;
  font-size: 14px;
  color: #374151;
  vertical-align: middle;
}

.data-table tr:last-child td {
  border-bottom: none;
}

.data-table tr:hover td {
  background: #f9fafb;
}

.id-cell {
  font-weight: 600;
  color: #9ca3af;
  font-size: 13px;
}

.email-cell {
  color: #6b7280;
  font-size: 13px;
}

.date-cell {
  font-size: 13px;
  color: #6b7280;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #f3f4f6;
  flex-shrink: 0;
}

.user-name {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.badge.admin { background: #fee2e2; color: #b91c1c; }
.badge.student { background: #dbeafe; color: #1d4ed8; }

.status-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.status-toggle.active { background: #d1fae5; color: #065f46; }
.status-toggle.inactive { background: #f3f4f6; color: #6b7280; }

.status-toggle:hover {
  transform: scale(1.05);
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-toggle.active .status-dot { background: #059669; }
.status-toggle.inactive .status-dot { background: #9ca3af; }

.actions {
  display: flex;
  gap: 6px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0;
}

.btn-icon svg {
  width: 16px;
  height: 16px;
}

.btn-view { background: #ede9fe; color: #7c3aed; }
.btn-view:hover { background: #7c3aed; color: white; }

.btn-edit { background: #dbeafe; color: #2563eb; }
.btn-edit:hover { background: #2563eb; color: white; }

.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-delete:hover { background: #dc2626; color: white; }

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.page-btn {
  min-width: 36px;
  height: 36px;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #374151;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0 8px;
}

.page-btn svg {
  width: 16px;
  height: 16px;
}

.page-btn:hover:not(:disabled) {
  border-color: #4f46e5;
  color: #4f46e5;
}

.page-btn.active {
  background: #4f46e5;
  border-color: #4f46e5;
  color: white;
  font-weight: 600;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-ellipsis {
  color: #9ca3af;
  padding: 0 4px;
}

.page-info {
  margin-left: 16px;
  font-size: 13px;
  color: #9ca3af;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 520px;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  animation: modalIn 0.2s ease;
}

.modal-view {
  max-width: 480px;
}

@keyframes modalIn {
  from { opacity: 0; transform: translateY(-20px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #f3f4f6;
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #9ca3af;
  padding: 0;
  line-height: 1;
  transition: color 0.2s;
}

.btn-close:hover { color: #374151; }

.modal-body {
  padding: 24px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid #f3f4f6;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f3f4f6;
  margin-bottom: 20px;
}

.profile-avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #f3f4f6;
}

.profile-info h3 {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 700;
  color: #1a1a2e;
}

.profile-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.status-badge {
  display: inline-flex;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  width: fit-content;
}

.status-badge.active { background: #d1fae5; color: #065f46; }
.status-badge.inactive { background: #f3f4f6; color: #6b7280; }

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item label {
  font-size: 12px;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item span {
  font-size: 14px;
  color: #374151;
}

.avatar-link {
  word-break: break-all;
  font-size: 12px;
  color: #6b7280 !important;
}

.full-width {
  grid-column: 1 / -1;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.required {
  color: #ef4444;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="password"],
.form-group input[type="date"],
.form-group select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
  background: white;
}

.form-group textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  resize: vertical;
  min-height: 60px;
  box-sizing: border-box;
  background: white;
  font-family: inherit;
}

.form-group textarea:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-group input.error,
.form-group select.error {
  border-color: #ef4444;
}

.form-group input.error:focus,
.form-group select.error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-text {
  display: block;
  color: #ef4444;
  font-size: 12px;
  margin-top: 4px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.password-wrapper {
  position: relative;
}

.toggle-password {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  padding: 4px;
  display: flex;
}

.toggle-password svg {
  width: 18px;
  height: 18px;
}

.password-wrapper input {
  padding-right: 40px;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
  margin-bottom: 0;
}

.checkbox-label input[type="checkbox"] {
  display: none;
}

.checkbox-custom {
  width: 18px;
  height: 18px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  flex-shrink: 0;
  transition: all 0.2s;
  position: relative;
  background: white;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
  background: #4f46e5;
  border-color: #4f46e5;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
  content: '';
  position: absolute;
  left: 5px;
  top: 2px;
  width: 5px;
  height: 9px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-left: 28px;
}

.btn-primary {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: background 0.2s;
  min-width: 100px;
  justify-content: center;
}

.btn-primary:hover:not(:disabled) {
  background: #4338ca;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}

.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  z-index: 2000;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.toast svg {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.toast.success {
  background: #059669;
  color: white;
}

.toast.error {
  background: #dc2626;
  color: white;
}

.toast-enter-active { animation: toastIn 0.3s ease; }
.toast-leave-active { animation: toastOut 0.3s ease; }

@keyframes toastIn {
  from { opacity: 0; transform: translateY(20px) scale(0.9); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes toastOut {
  from { opacity: 1; transform: translateY(0); }
  to { opacity: 0; transform: translateY(10px); }
}

@media (max-width: 768px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .filter-bar {
    flex-direction: column;
  }

  .filter-select {
    width: 100%;
  }

  .pagination {
    gap: 2px;
  }

  .page-info {
    width: 100%;
    text-align: center;
    margin-left: 0;
    margin-top: 8px;
  }

  .data-table {
    font-size: 12px;
  }

  .data-table th,
  .data-table td {
    padding: 10px 8px;
  }
}
</style>
