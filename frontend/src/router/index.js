import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/LoginView.vue';
import DashboardLayout from '../layouts/DashboardLayout.vue';
import MapeadorMatriz from '../views/MapeadorMatriz.vue';

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginView
  },
  {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'DashboardHome',
        component: () => import('../views/DashboardHome.vue') 
      }
    ]
  },
  {
    path: '/importar',
    component: () => import('../layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'importar',
        component: () => import('../views/ImportView.vue')
      }
    ]
  },
  {
      path: '/mapeador-matriz',
      component: () => import('../layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'MapeadorMatriz',
          component: MapeadorMatriz
        }
      ]
    }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Guard de navegación básico para proteger rutas privadas
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  if (to.meta.requiresAuth && !token) {
    next('/login');
  } else {
    next();
  }
});

export default router;