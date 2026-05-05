import { defineStore } from 'pinia'
import { AuthService } from '../services/api'
import apiClient from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token') || null,
    isAuthenticated: !!localStorage.getItem('auth_token'),
    loading: false,
    error: null
  }),

  getters: {
    getUser: (state) => state.user,
    isLoggedIn: (state) => state.isAuthenticated,
    getError: (state) => state.error,
    isAdmin: (state) => state.user?.role === 'admin',
    isStudent: (state) => state.user?.role === 'student',
    getRole: (state) => state.user?.role || null
  },

  actions: {
    /**
     * Đăng ký tài khoản mới
     */
    async register(data) {
      this.loading = true
      this.error = null
      try {
        const response = await AuthService.register(data)
        this.user = response.user
        this.token = response.access_token
        this.isAuthenticated = true
        localStorage.setItem('auth_token', response.access_token)
        localStorage.setItem('auth_user', JSON.stringify(response.user))
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Đăng ký thất bại'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Đăng nhập
     */
    async login(data) {
      this.loading = true
      this.error = null
      try {
        const response = await AuthService.login(data)
        this.user = response.user
        this.token = response.access_token
        this.isAuthenticated = true
        localStorage.setItem('auth_token', response.access_token)
        localStorage.setItem('auth_user', JSON.stringify(response.user))
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Đăng nhập thất bại'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Lấy thông tin user hiện tại
     */
    async fetchUser() {
      // Nếu chưa có token, không cần fetch
      if (!this.token) return null

      this.loading = true
      try {
        const response = await AuthService.me()
        this.user = response.user
        localStorage.setItem('auth_user', JSON.stringify(response.user))
        return response.user
      } catch (error) {
        // Nếu token hết hạn, đăng xuất
        if (error.response?.status === 401) {
          this.logout()
        }
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Đăng xuất
     */
    async logout() {
      try {
        if (this.token) {
          await AuthService.logout()
        }
      } catch (error) {
        // Bỏ qua lỗi khi đăng xuất
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.token = null
        this.isAuthenticated = false
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
      }
    },

    /**
     * Khởi tạo auth state từ localStorage
     */
    initAuth() {
      const token = localStorage.getItem('auth_token')
      const userStr = localStorage.getItem('auth_user')

      if (token) {
        this.token = token
        this.isAuthenticated = true
        if (userStr) {
          try {
            this.user = JSON.parse(userStr)
          } catch (e) {
            this.user = null
          }
        }
      }
    },

    /**
     * Xóa lỗi
     */
    clearError() {
      this.error = null
    },

    /**
     * Cập nhật thông tin cá nhân
     */
    async updateProfile(data) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.put('/auth/profile', data)
        this.$patch({ user: response.data.user })
        localStorage.setItem('auth_user', JSON.stringify(response.data.user))
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Cập nhật thất bại'
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Đổi mật khẩu
     */
    async changePassword(data) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.put('/auth/change-password', data)
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Đổi mật khẩu thất bại'
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
