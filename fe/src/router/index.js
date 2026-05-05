import { createRouter, createWebHistory } from 'vue-router'

import PublicLayout from '../layout/wrapper/PublicLayout.vue'
import StudentLayout from '../layout/wrapper/StudentLayout.vue'
import AdminLayout from '../layout/wrapper/AdminLayout.vue'

import Home from '../components/Student/Home.vue'
import Features from '../components/Public/Features.vue'
import Contact from '../components/Public/Contact.vue'
import AllCourses from '../components/Student/AllCourses/index.vue'
import ChuongStudent from '../components/Student/Chuong/index.vue'
import StudentDashboard from '../components/Student/Dashboard/index.vue'
import StudentProfile from '../components/Student/Profile/index.vue'
import LearningHistory from '../components/Student/LearningHistory/index.vue'
import VocabularyPlayer from '../components/Student/VocabularyPlayer/index.vue'
import GrammarPlayer from '../components/Student/GrammarPlayer/index.vue'
import ListeningPlayer from '../components/Student/ListeningPlayer/index.vue'
import SpeakingPlayer from '../components/Student/SpeakingPlayer/index.vue'
import LessonRouter from '../components/Student/LessonRouter/index.vue'
import PracticePlayer from '../components/Student/PracticePlayer/index.vue'
import FinalExamPlayer from '../components/Student/FinalExamPlayer/index.vue'
import Documents from '../components/Student/Documents/index.vue'
import DocumentDetail from '../components/Student/Documents/[id].vue'
import Articles from '../components/Student/Articles/index.vue'
import ArticleDetail from '../components/Student/Articles/[id].vue'
import Payment from '../components/Student/Payment/index.vue'

import Dashboard from '../components/Admin/Dashboard/index.vue'
import Profile from '../components/Admin/Profile/index.vue'
import KhoaHoc from '../components/Admin/KhoaHoc/index.vue'
import BaiHoc from '../components/Admin/BaiHoc/index.vue'
import BaiTap from '../components/Admin/BaiTap/index.vue'
import QuizCuoiKhoa from '../components/Admin/QuizCuoiKhoa/index.vue'
import NoiDungHoc from '../components/Admin/NoiDungHoc/index.vue'
import TuVung from '../components/Admin/TuVung/index.vue'
import NguPhap from '../components/Admin/NguPhap/index.vue'
import LuyenNghe from '../components/Admin/LuyenNghe/index.vue'
import LuyenNoi from '../components/Admin/LuyenNoi/index.vue'
import LuyenTap from '../components/Admin/LuyenTap/index.vue'
import NguoiDung from '../components/Admin/NguoiDung/index.vue'
import TienDo from '../components/Admin/TienDo/index.vue'
import TienDoHoc from '../components/Admin/TienDoHoc/index.vue'
import KetQua from '../components/Admin/KetQua/index.vue'
import ThanhToan from '../components/Admin/ThanhToan/index.vue'
import DanhGiaBinhLuan from '../components/Admin/DanhGiaBinhLuan/index.vue'
import Setting from '../components/Admin/Setting/index.vue'
import TaiLieu from '../components/Admin/TaiLieu/index.vue'
import BaiViet from '../components/Admin/BaiViet/index.vue'

import Login from '../components/Auth/Login.vue'
import Register from '../components/Auth/Register.vue'

import { useAuthStore } from '../stores/auth'

const routes = [
  // ===== PUBLIC LAYOUT (Chưa đăng nhập) =====
  {
    path: '/',
    component: PublicLayout,
    children: [
      {
        path: '',
        name: 'Home',
        component: Home
      },
      {
        path: 'features',
        name: 'Features',
        component: Features,
        meta: { title: 'Tính năng - DTU LingoAI' }
      },
      {
        path: 'contact',
        name: 'Contact',
        component: Contact,
        meta: { title: 'Liên hệ - DTU LingoAI' }
      },
      {
        path: 'auth/login',
        name: 'Login',
        component: Login,
        meta: { title: 'Đăng nhập - DTU LingoAI' }
      },
      {
        path: 'auth/register',
        name: 'Register',
        component: Register,
        meta: { title: 'Đăng ký - DTU LingoAI' }
      }
    ]
  },

  // ===== STUDENT LAYOUT (Học sinh đã đăng nhập) =====
  {
    path: '/student',
    component: StudentLayout,
    meta: { requiresAuth: true, role: 'student' },
    children: [
      {
        path: '',
        name: 'StudentDashboard',
        component: StudentDashboard,
        meta: { title: 'Trang Chủ - DTU LingoAI' }
      },
      {
        path: 'ho-so',
        name: 'StudentProfile',
        component: StudentProfile,
        meta: { title: 'Hồ Sơ Cá Nhân - DTU LingoAI' }
      },
      {
        path: 'lich-su-hoc',
        name: 'LearningHistory',
        component: LearningHistory,
        meta: { title: 'Lịch Sử Học Tập - DTU LingoAI' }
      },
      {
        path: 'khoa-hoc',
        name: 'AllCourses',
        component: AllCourses,
        meta: { title: 'Tất Cả Khóa Học - DTU LingoAI' }
      },
      {
        path: 'khoa-hoc/:id',
        name: 'ChuongStudent',
        component: ChuongStudent,
        meta: { title: 'Chương Học - DTU LingoAI' }
      },
      {
        path: 'khoa-hoc/:courseId/ch/:chapterId/bai/:id',
        name: 'LessonPlayer',
        component: LessonRouter,
        meta: { title: 'Bài Học - DTU LingoAI' }
      },
      {
        path: 'vocabulary/:id',
        name: 'VocabularyPlayer',
        component: VocabularyPlayer,
        meta: { title: 'Từ Vựng - DTU LingoAI' }
      },
      {
        path: 'grammar/:id',
        name: 'GrammarPlayer',
        component: GrammarPlayer,
        meta: { title: 'Ngữ Pháp - DTU LingoAI' }
      },
      {
        path: 'listening/:id',
        name: 'ListeningPlayer',
        component: ListeningPlayer,
        meta: { title: 'Luyện Nghe - DTU LingoAI' }
      },
      {
        path: 'speaking/:id',
        name: 'SpeakingPlayer',
        component: SpeakingPlayer,
        meta: { title: 'Luyện Nói - DTU LingoAI' }
      },
      {
        path: 'khoa-hoc/:courseId/luyen-tap',
        name: 'PracticePlayer',
        component: PracticePlayer,
        meta: { title: 'Luyện Tập - DTU LingoAI' }
      },
      {
        path: 'khoa-hoc/:courseId/thi-cuoi-khoa',
        name: 'FinalExamPlayer',
        component: FinalExamPlayer,
        meta: { title: 'Thi Cuối Khóa - DTU LingoAI' }
      },
      {
        path: 'tai-lieu',
        name: 'Documents',
        component: Documents,
        meta: { title: 'Tài Liệu - DTU LingoAI' }
      },
      {
        path: 'tai-lieu/:id',
        name: 'DocumentDetail',
        component: DocumentDetail,
        meta: { title: 'Chi Tiết Tài Liệu - DTU LingoAI' }
      },
      {
        path: 'bai-viet',
        name: 'Articles',
        component: Articles,
        meta: { title: 'Bài Viết - DTU LingoAI' }
      },
      {
        path: 'bai-viet/:id',
        name: 'ArticleDetail',
        component: ArticleDetail,
        meta: { title: 'Chi Tiết Bài Viết - DTU LingoAI' }
      },
      {
        path: 'thanh-toan/:id',
        name: 'Payment',
        component: Payment,
        meta: { title: 'Thanh Toán - DTU LingoAI', requiresAuth: true, role: 'student' }
      },
    ]
  },

  // ===== ADMIN LAYOUT (Admin đã đăng nhập) =====
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard'
      },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: Dashboard,
        meta: { title: 'Dashboard - Tổng Quan' }
      },
      {
        path: 'profile',
        name: 'AdminProfile',
        component: Profile,
        meta: { title: 'Hồ Sơ Cá Nhân - DTU LingoAI' }
      },
      {
        path: 'khoa-hoc',
        name: 'KhoaHoc',
        component: KhoaHoc,
        meta: { title: 'Quản Lý Khoá Học' }
      },
      {
        path: 'bai-hoc',
        name: 'BaiHoc',
        component: BaiHoc,
        meta: { title: 'Quản Lý Bài Học' }
      },
      {
        path: 'bai-tap',
        name: 'BaiTap',
        component: BaiTap,
        meta: { title: 'Quản Lý Bài Tập' }
      },
      {
        path: 'quiz-cuoi-khoa',
        name: 'QuizCuoiKhoa',
        component: QuizCuoiKhoa,
        meta: { title: 'Quản Lý Bài Thi Cuối Khóa' }
      },
      {
        path: 'noi-dung-hoc',
        name: 'NoiDungHoc',
        component: NoiDungHoc,
        meta: { title: 'Quản Lý Nội Dung Học' }
      },
      {
        path: 'tu-vung',
        name: 'TuVung',
        component: TuVung,
        meta: { title: 'Quản Lý Từ Vựng' }
      },
      {
        path: 'ngu-phap',
        name: 'NguPhap',
        component: NguPhap,
        meta: { title: 'Quản Lý Ngữ Pháp' }
      },
      {
        path: 'luyen-nghe',
        name: 'LuyenNghe',
        component: LuyenNghe,
        meta: { title: 'Quản Lý Luyện Nghe' }
      },
      {
        path: 'luyen-noi',
        name: 'LuyenNoi',
        component: LuyenNoi,
        meta: { title: 'Quản Lý Luyện Nói' }
      },
      {
        path: 'luyen-tap',
        name: 'LuyenTap',
        component: LuyenTap,
        meta: { title: 'Quản Lý Luyện Tập' }
      },
      {
        path: 'nguoi-dung',
        name: 'NguoiDung',
        component: NguoiDung,
        meta: { title: 'Quản Lý Người Dùng' }
      },
      {
        path: 'tien-do',
        name: 'TienDo',
        component: TienDo,
        meta: { title: 'Quản Lý Tiến Độ' }
      },
      {
        path: 'tien-do-hoc',
        name: 'TienDoHoc',
        component: TienDoHoc,
        meta: { title: 'Quản Lý Tiến Độ Học Tập' }
      },
      {
        path: 'ket-qua',
        name: 'KetQua',
        component: KetQua,
        meta: { title: 'Quản Lý Kết Quả' }
      },
      {
        path: 'thanh-toan',
        name: 'ThanhToan',
        component: ThanhToan,
        meta: { title: 'Quản Lý Thanh Toán' }
      },
      {
        path: 'danh-gia-binh-luan',
        name: 'DanhGiaBinhLuan',
        component: DanhGiaBinhLuan,
        meta: { title: 'Quản Lý Đánh Giá & Bình Luận' }
      },
      {
        path: 'setting',
        name: 'Setting',
        component: Setting,
        meta: { title: 'Cài Đặt Hệ Thống' }
      },
      {
        path: 'tai-lieu',
        name: 'TaiLieu',
        component: TaiLieu,
        meta: { title: 'Quản Lý Tài Liệu' }
      },
      {
        path: 'bai-viet',
        name: 'BaiViet',
        component: BaiViet,
        meta: { title: 'Quản Lý Bài Viết' }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Auth Guard - Kiểm tra đăng nhập và phân quyền
router.beforeEach((to, from) => {
  const token = localStorage.getItem('auth_token')
  const userStr = localStorage.getItem('auth_user')
  let user = null

  if (userStr) {
    try {
      user = JSON.parse(userStr)
    } catch (e) {
      user = null
    }
  }

  // Trang auth (login/register) - không cần đăng nhập
  if (to.path === '/auth/login' || to.path === '/auth/register') {
    if (token && user) {
      // Đã đăng nhập → chuyển đến layout phù hợp
      if (user.role === 'admin') {
        return router.push('/admin/dashboard')
      } else {
        return router.push('/student')
      }
    }
    return
  }

  // Trang cần đăng nhập
  if (to.meta.requiresAuth) {
    if (!token) {
      // Chưa đăng nhập → chuyển sang login
      return router.push('/auth/login')
    }

    // Đã đăng nhập → kiểm tra role
    const requiredRole = to.meta.role

    if (requiredRole && user?.role !== requiredRole) {
      // Role không khớp → chuyển đến layout phù hợp
      if (user?.role === 'admin') {
        return router.push('/admin/dashboard')
      } else {
        return router.push('/student')
      }
    }

    return
  }

  // Trang công khai (không cần đăng nhập)
  if (!to.meta.requiresAuth && token && user) {
    // Đã đăng nhập nhưng vào trang công khai
    // Redirect / (Home) về /student vì Home là landing page cho guest
    if (to.path === '/') {
      return router.push('/student')
    }
    // Các trang login/register cho phép ở lại
    return
  }
})

// Cập nhật title
router.beforeEach((to) => {
  if (to.meta.title) {
    document.title = to.meta.title
  }
})

export default router
