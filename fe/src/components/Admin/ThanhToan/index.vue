<template>
  <div class="thanh-toan">
    <div class="header">
      <h1>Quản Lý Thanh Toán</h1>
    </div>

    <!-- Thong bao pending -->
    <div v-if="choXuLy > 0" class="pending-alert">
      <i class="fa-solid fa-clock"></i>
      <span>Có <strong>{{ choXuLy }}</strong> yêu cầu đang chờ xử lý</span>
    </div>

    <div class="filter-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm theo mã giao dịch, tên người dùng..."
        class="search-input"
      />
      <select v-model="filterTrangThai" class="filter-select">
        <option value="">Tất cả trạng thái</option>
        <option value="thanh-cong">Thành công</option>
        <option value="cho">Chờ xử lý</option>
        <option value="that-bai">Thất bại</option>
        <option value="hoan">Hoàn tiền</option>
      </select>
      <select v-model="filterPhuongThuc" class="filter-select">
        <option value="">Tất cả phương thức</option>
        <option value="vnpay">VNPay</option>
        <option value="momo">MoMo</option>
        <option value="banking">Chuyển khoản</option>
      </select>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-value">{{ formatCurrency(tongDoanhThu) }}</div>
        <div class="stat-label">Tổng Doanh Thu</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ thanhCong }}</div>
        <div class="stat-label">Giao Dịch Thành Công</div>
      </div>
      <div class="stat-card pending">
        <div class="stat-value">{{ choXuLy }}</div>
        <div class="stat-label">Chờ Xử Lý</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ formatCurrency(thanhCongTB) }}</div>
        <div class="stat-label">TB / Giao Dịch</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải dữ liệu...</div>

    <div v-else-if="filteredThanhToan.length === 0" class="empty-state">
      <i class="fa-solid fa-inbox"></i>
      <p>Không tìm thấy giao dịch nào.</p>
    </div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Mã Giao Dịch</th>
          <th>Người Dùng</th>
          <th>Khoá Học</th>
          <th>Số Tiền</th>
          <th>Phương Thức</th>
          <th>Trạng Thái</th>
          <th>Ngày</th>
          <th>Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="item in filteredThanhToan"
          :key="item.id"
          :class="{ 'row-pending': item.status === 'cho' }"
        >
          <td>{{ item.id }}</td>
          <td>
            <span class="ma-giao-dich">{{ item.transaction_id }}</span>
          </td>
          <td>
            <div class="user-info">
              <span class="user-name">{{ item.user?.name || '-' }}</span>
              <span class="user-email">{{ item.user?.email }}</span>
            </div>
          </td>
          <td>
            <div class="course-info">
              <span class="course-title">{{ item.course?.title || '-' }}</span>
            </div>
          </td>
          <td class="so-tien">{{ formatCurrency(item.amount) }}</td>
          <td>
            <span :class="['badge', item.payment_method]">{{ getPhuongThucLabel(item.payment_method) }}</span>
          </td>
          <td>
            <span :class="['status-badge', item.status]">{{ getTrangThaiLabel(item.status) }}</span>
          </td>
          <td>{{ item.created_at ? new Date(item.created_at).toLocaleDateString('vi-VN') : '-' }}</td>
          <td class="actions">
            <button class="btn-view" @click="viewDetail(item)">Chi tiết</button>

            <!-- Nut duyet / tu choi cho yeu cau dang cho -->
            <template v-if="item.status === 'cho'">
              <button class="btn-approve" @click="handleApprove(item)" :disabled="processingId === item.id">
                <span v-if="processingId === item.id">
                  <div class="spinner-tiny"></div>
                </span>
                <span v-else>
                  <i class="fa-solid fa-check"></i> Duyệt
                </span>
              </button>
              <button class="btn-reject" @click="openRejectModal(item)" :disabled="processingId === item.id">
                <i class="fa-solid fa-xmark"></i> Từ chối
              </button>
            </template>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal chi tiet -->
    <div v-if="showDetail" class="modal-overlay" @click.self="showDetail = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Chi Tiết Giao Dịch</h2>
          <button class="btn-close" @click="showDetail = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="detail-row">
            <span class="detail-label">Mã giao dịch:</span>
            <span class="detail-value">{{ selectedItem?.transaction_id }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Người dùng:</span>
            <span class="detail-value">{{ selectedItem?.user?.name }} ({{ selectedItem?.user?.email }})</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Khoá học:</span>
            <span class="detail-value">{{ selectedItem?.course?.title }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Số tiền:</span>
            <span class="detail-value highlight">{{ formatCurrency(selectedItem?.amount) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Phương thức:</span>
            <span class="detail-value">{{ getPhuongThucLabel(selectedItem?.payment_method) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Trạng thái:</span>
            <span :class="['status-badge', selectedItem?.status]">{{ getTrangThaiLabel(selectedItem?.status) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Ngày tạo:</span>
            <span class="detail-value">{{ selectedItem?.created_at ? new Date(selectedItem.created_at).toLocaleString('vi-VN') : '-' }}</span>
          </div>
          <div v-if="selectedItem?.note" class="detail-row">
            <span class="detail-label">Ghi chú:</span>
            <span class="detail-value">{{ selectedItem.note }}</span>
          </div>
        </div>
        <div class="modal-footer">
          <template v-if="selectedItem?.status === 'cho'">
            <button class="btn-approve-lg" @click="handleApprove(selectedItem)">
              <i class="fa-solid fa-check"></i> Duyệt
            </button>
            <button class="btn-reject-lg" @click="openRejectModal(selectedItem)">
              <i class="fa-solid fa-xmark"></i> Từ chối
            </button>
          </template>
          <button class="btn-secondary" @click="showDetail = false">Đóng</button>
        </div>
      </div>
    </div>

    <!-- Modal tu choi -->
    <div v-if="showRejectModal" class="modal-overlay" @click.self="showRejectModal = false">
      <div class="modal modal-sm">
        <div class="modal-header">
          <h2>Từ chối thanh toán</h2>
          <button class="btn-close" @click="showRejectModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <p class="reject-info">
            Từ chối yêu cầu thanh toán cho giao dịch:
            <strong>{{ rejectItem?.transaction_id }}</strong>
          </p>
          <div class="form-group">
            <label>Lý do từ chối (tùy chọn):</label>
            <textarea
              v-model="rejectNote"
              class="form-textarea"
              placeholder="VD: Không nhận được thanh toán, sai số tiền..."
              rows="3"
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="showRejectModal = false">Hủy</button>
          <button class="btn-reject-lg" @click="handleReject" :disabled="isRejecting">
            <span v-if="isRejecting">
              <div class="spinner-tiny"></div> Đang xử lý...
            </span>
            <span v-else>
              <i class="fa-solid fa-xmark"></i> Xác nhận từ chối
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PaymentService } from '../../../services/api.js'

const loading = ref(false)
const searchQuery = ref('')
const filterTrangThai = ref('')
const filterPhuongThuc = ref('')
const showDetail = ref(false)
const selectedItem = ref(null)
const showRejectModal = ref(false)
const rejectItem = ref(null)
const rejectNote = ref('')
const isRejecting = ref(false)
const processingId = ref(null)

const thanhToanList = ref([])

const fetchPayments = async () => {
  loading.value = true
  try {
    const params = {}
    if (filterTrangThai.value) params.status = filterTrangThai.value
    if (filterPhuongThuc.value) params.method = filterPhuongThuc.value
    if (searchQuery.value) params.search = searchQuery.value
    const data = await PaymentService.getAll(params)
    thanhToanList.value = Array.isArray(data) ? data : (data.data || [])
  } catch (error) {
    console.error('Lỗi khi tải thanh toán:', error)
  } finally {
    loading.value = false
  }
}

const tongDoanhThu = computed(() =>
  thanhToanList.value
    .filter(i => i.status === 'thanh-cong')
    .reduce((acc, i) => acc + (i.amount || 0), 0)
)
const thanhCong = computed(() => thanhToanList.value.filter(i => i.status === 'thanh-cong').length)
const choXuLy = computed(() => thanhToanList.value.filter(i => i.status === 'cho').length)
const thanhCongTB = computed(() => {
  if (thanhCong.value === 0) return 0
  return tongDoanhThu.value / thanhCong.value
})

const getPhuongThucLabel = (pt) => {
  const labels = { vnpay: 'VNPay', momo: 'MoMo', banking: 'Chuyển khoản' }
  return labels[pt] || pt
}

const getTrangThaiLabel = (tt) => {
  const labels = { 'thanh-cong': 'Thành công', 'cho': 'Chờ xử lý', 'that-bai': 'Thất bại', 'hoan': 'Hoàn tiền' }
  return labels[tt] || tt
}

const formatCurrency = (value) => {
  if (!value) return '0đ'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

const filteredThanhToan = computed(() => {
  let result = thanhToanList.value
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      (item.transaction_id || '').toLowerCase().includes(q) ||
      (item.user?.name || '').toLowerCase().includes(q) ||
      (item.course?.title || '').toLowerCase().includes(q)
    )
  }
  if (filterTrangThai.value) {
    result = result.filter(item => item.status === filterTrangThai.value)
  }
  if (filterPhuongThuc.value) {
    result = result.filter(item => item.payment_method === filterPhuongThuc.value)
  }
  return result
})

const viewDetail = (item) => {
  selectedItem.value = item
  showDetail.value = true
}

const handleApprove = async (item) => {
  if (processingId.value === item.id) return
  processingId.value = item.id

  try {
    await PaymentService.approvePayment(item.id)
    showDetail.value = false
    await fetchPayments()
  } catch (error) {
    console.error('Lỗi khi duyệt:', error)
    alert('Không thể duyệt yêu cầu. Vui lòng thử lại.')
  } finally {
    processingId.value = null
  }
}

const openRejectModal = (item) => {
  rejectItem.value = item
  rejectNote.value = ''
  showRejectModal.value = true
  showDetail.value = false
}

const handleReject = async () => {
  if (isRejecting.value) return
  isRejecting.value = true

  try {
    await PaymentService.rejectPayment(rejectItem.value.id, { note: rejectNote.value })
    showRejectModal.value = false
    await fetchPayments()
  } catch (error) {
    console.error('Lỗi khi từ chối:', error)
    alert('Không thể từ chối yêu cầu. Vui lòng thử lại.')
  } finally {
    isRejecting.value = false
  }
}

onMounted(() => {
  fetchPayments()
})
</script>

<style scoped>
.thanh-toan {
  padding: 20px;
}

.header {
  margin-bottom: 20px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.pending-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  background: #fffbeb;
  border: 1px solid #fcd34d;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 14px;
  color: #92400e;
}

.pending-alert i {
  color: #f59e0b;
  font-size: 18px;
}

.pending-alert strong {
  color: #d97706;
}

.filter-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.search-input {
  flex: 1;
  max-width: 400px;
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
}

.filter-select {
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  min-width: 150px;
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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
  border: 1px solid #f0f0f0;
}

.stat-card.pending {
  border-color: #fcd34d;
  background: #fffbeb;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #4f46e5;
}

.stat-card.pending .stat-value {
  color: #d97706;
}

.stat-label {
  font-size: 14px;
  color: #666;
  margin-top: 4px;
}

.loading, .empty-state {
  text-align: center;
  padding: 60px 40px;
  color: #666;
}

.empty-state i {
  font-size: 48px;
  color: #d1d5db;
  margin-bottom: 16px;
  display: block;
}

.empty-state p {
  margin: 0;
  font-size: 16px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table th,
.data-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
}

.data-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #333;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table tr:hover {
  background: #f8fafc;
}

.data-table tr.row-pending {
  background: #fffbeb;
}

.data-table tr.row-pending:hover {
  background: #fef3c7;
}

.ma-giao-dich {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  font-weight: 600;
  color: #4f46e5;
  background: #eef2ff;
  padding: 3px 8px;
  border-radius: 4px;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-weight: 600;
  font-size: 14px;
  color: #1e293b;
}

.user-email {
  font-size: 12px;
  color: #94a3b8;
}

.course-info {
  max-width: 200px;
}

.course-title {
  font-size: 13px;
  color: #475569;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.so-tien {
  font-weight: 700;
  color: #059669;
  font-size: 14px;
}

.badge {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.badge.vnpay { background: #dbeafe; color: #1e40af; }
.badge.momo { background: #fce7f3; color: #9d174d; }
.badge.banking { background: #e0e7ff; color: #3730a3; }
.badge.stripe { background: #f3e8ff; color: #6b21a8; }

.status-badge {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.thanh-cong { background: #d4edda; color: #155724; }
.status-badge.cho { background: #fff3cd; color: #856404; }
.status-badge.that-bai { background: #f8d7da; color: #721c24; }
.status-badge.hoan { background: #f3e8ff; color: #6b21a8; }

.actions {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.btn-view {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
}

.btn-view:hover {
  background: #2563eb;
}

.btn-approve {
  background: #22c55e;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-approve:hover:not(:disabled) {
  background: #16a34a;
}

.btn-approve:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-reject {
  background: #ef4444;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-reject:hover:not(:disabled) {
  background: #dc2626;
}

.btn-reject:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-tiny {
  width: 12px;
  height: 12px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Modal */
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
  overflow-y: auto;
}

.modal-sm {
  max-width: 420px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #f0f0f0;
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #94a3b8;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.modal-body {
  padding: 20px 24px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 10px 0;
  border-bottom: 1px solid #f5f5f5;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #64748b;
  font-size: 14px;
}

.detail-value {
  color: #1e293b;
  font-size: 14px;
  text-align: right;
  max-width: 60%;
}

.detail-value.highlight {
  font-weight: 700;
  color: #059669;
  font-size: 16px;
}

.reject-info {
  font-size: 14px;
  color: #475569;
  margin: 0 0 16px;
  line-height: 1.6;
}

.form-group {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
}

.form-textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  outline: none;
  transition: border-color 0.2s;
}

.form-textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid #f0f0f0;
}

.btn-approve-lg {
  background: #22c55e;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-approve-lg:hover:not(:disabled) {
  background: #16a34a;
}

.btn-approve-lg:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-reject-lg {
  background: #ef4444;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-reject-lg:hover:not(:disabled) {
  background: #dc2626;
}

.btn-reject-lg:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e5e7eb;
  color: #333;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.btn-secondary:hover {
  background: #d1d5db;
}
</style>
