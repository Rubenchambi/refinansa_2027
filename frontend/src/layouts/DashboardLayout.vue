<template>
  <div class="min-h-screen bg-[#F8FAFC] flex">
    
    <!-- Sidebar / Barra Lateral -->
    <aside :class="['bg-white border-r border-slate-200 flex-col transition-all duration-300 z-20', isSidebarOpen ? 'w-64 flex' : 'hidden md:flex md:w-20']">
      
      <!-- Logo / Marca -->
      <div class="h-16 flex items-center px-5 border-b border-slate-100 gap-3 overflow-hidden">
        <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-md shadow-blue-500/25 tracking-tighter">
          CR
        </div>
        <div :class="['flex flex-col transition-opacity duration-200', !isSidebarOpen && 'md:hidden']">
          <span class="font-extrabold text-slate-800 text-sm tracking-tight leading-none">Cobranza<span class="text-blue-600">OS</span></span>
          <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Refinansa</span>
        </div>
      </div>

      <!-- Menú de Navegación -->
      <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto">
        <p :class="['px-3 text-[10px] font-bold tracking-wider text-slate-400 uppercase mb-2', !isSidebarOpen && 'md:text-center']">
          {{ isSidebarOpen ? 'Principal' : '•••' }}
        </p>
        
        <router-link 
          to="/dashboard" 
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-blue-600 bg-blue-50/80 transition-all"
          title="Dashboard"
        >
          <LayoutDashboard class="w-4 h-4 shrink-0" />
          <span :class="[!isSidebarOpen && 'md:hidden']">Dashboard</span>
        </router-link>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all" title="Cartera de Clientes">
          <Users class="w-4 h-4 shrink-0 text-slate-400" />
          <span :class="[!isSidebarOpen && 'md:hidden']">Cartera de Clientes</span>
        </a>

        <router-link 
          to="/importar" 
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all"
          active-class="text-blue-600 bg-blue-50/80"
          title="Importar Carteras"
        >
          <Upload class="w-4 h-4 shrink-0 text-slate-400" />
          <span :class="[!isSidebarOpen && 'md:hidden']">Importar Cartera</span>
        </router-link>

        <router-link 
        to="/mapeador-matriz" 
        class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-all"
        active-class="bg-blue-50 text-blue-600 border border-blue-100 shadow-sm"
      >
        <Layers class="w-4 h-4" />
        Mapeador de Matriz
      </router-link>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all" title="Gestión de Pagos">
          <CreditCard class="w-4 h-4 shrink-0 text-slate-400" />
          <span :class="[!isSidebarOpen && 'md:hidden']">Gestión de Pagos</span>
        </a>

        <p :class="['px-3 text-[10px] font-bold tracking-wider text-slate-400 uppercase mt-6 mb-2', !isSidebarOpen && 'md:text-center']">
          {{ isSidebarOpen ? 'Administración' : '•••' }}
        </p>
        
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all" title="Usuarios y Roles">
          <ShieldCheck class="w-4 h-4 shrink-0 text-slate-400" />
          <span :class="[!isSidebarOpen && 'md:hidden']">Usuarios y Roles</span>
        </a>
      </nav>

      <!-- Usuario / Cerrar Sesión -->
      <div class="p-3 border-t border-slate-100">
        <button 
          @click="logout"
          class="w-full py-2 px-3 bg-slate-100 hover:bg-red-50 hover:text-red-600 text-slate-600 text-xs font-semibold rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer"
          title="Cerrar Sesión"
        >
          <LogOut class="w-4 h-4 shrink-0" />
          <span :class="[!isSidebarOpen && 'md:hidden']">Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- Contenido Principal -->
    <div class="flex-1 flex flex-col min-w-0">
      
      <!-- Header Superior -->
      <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10">
        <div class="flex items-center gap-4">
          <!-- Botón de las 4 barritas para esconder/mostrar el panel izquierdo -->
          <button 
            @click="toggleSidebar"
            class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition-all cursor-pointer"
            title="Alternar Panel"
          >
            <Menu class="w-5 h-5" />
          </button>

          <div class="hidden sm:flex items-center gap-3 w-80 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus-within:bg-white focus-within:border-blue-600 transition-all">
            <Search class="w-4 h-4 text-slate-400 shrink-0" />
            <input 
              type="text" 
              placeholder="Buscar expedientes, clientes..." 
              class="w-full text-xs bg-transparent text-slate-800 placeholder-slate-400 focus:outline-none"
            />
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="text-right">
            <span class="block text-xs font-bold text-slate-800">{{ userName }}</span>
            <span class="block text-[10px] text-slate-400 uppercase">Gestor Activo</span>
          </div>
          <div class="w-9 h-9 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-600 text-xs shadow-sm">
            {{ userInitial }}
          </div>
        </div>
      </header>

      <!-- Área Dinámica de Vistas -->
      <main class="flex-1 p-8 overflow-y-auto">
        <router-view />
      </main>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { 
  LayoutDashboard, 
  Users, 
  CreditCard, 
  ShieldCheck, 
  LogOut, 
  Upload,
  Layers,
  Search, 
  Menu 
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

const router = useRouter();
const userName = ref('Colaborador');
const userInitial = ref('C');
const isSidebarOpen = ref(true);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

onMounted(() => {
  const storedUser = localStorage.getItem('user');
  if (storedUser) {
    try {
      const user = JSON.parse(storedUser);
      userName.value = user.name || 'Usuario';
      userInitial.value = (user.name ? user.name.charAt(0) : 'U').toUpperCase();
    } catch (e) {
      console.error(e);
    }
  }
});

const logout = () => {
  Swal.fire({
    title: '¿Cerrar sesión?',
    text: 'Saldrás del sistema de cobranzas.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, salir',
    cancelButtonText: 'Cancelar',
    background: '#ffffff',
    color: '#1e293b'
  }).then((result) => {
    if (result.isConfirmed) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      router.push('/login');
    }
  });
};
</script>