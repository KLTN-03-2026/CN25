<template>
  <nav class="menu">

    <router-link to="/student" class="menu-item">
      <i class="fa-solid fa-house"></i>
      <span>Trang chủ</span>
    </router-link>

    <router-link to="/student/khoa-hoc" class="menu-item">
      <i class="fa-solid fa-compass"></i>
      <span>Khóa học</span>
    </router-link>

    <router-link to="/student/tai-lieu" class="menu-item">
      <i class="fa-solid fa-file-lines"></i>
      <span>Tài liệu</span>
    </router-link>

    <router-link to="/student/bai-viet" class="menu-item">
      <i class="fa-solid fa-newspaper"></i>
      <span>Bài viết</span>
    </router-link>

  </nav>
</template>

<script setup>
</script>

<style scoped>
.menu {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 16px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  color: var(--text);
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
  position: relative;
}

.menu-item i {
  font-size: 15px;
  opacity: 0.8;
  transition: opacity 0.2s, transform 0.2s;
}

.menu-item:hover {
  background: var(--accent-bg);
  color: var(--accent);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px var(--accent-bg);
}

.menu-item:hover i {
  opacity: 1;
  transform: scale(1.1);
}

.router-link-active,
.menu-item.active {
  background: var(--accent-bg);
  color: var(--accent);
  font-weight: 700;
  box-shadow: 0 0 0 2px var(--accent-border);
}

.router-link-active i,
.menu-item.active i {
  opacity: 1;
}

.menu-item:active {
  transform: scale(0.95);
}

@media (max-width: 900px) {
  .menu-item span {
    display: none;
  }
  .menu-item {
    padding: 9px 12px;
  }
}
</style>
