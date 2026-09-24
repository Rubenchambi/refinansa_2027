<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F8FAFC] px-4">
    <div class="max-w-md w-full bg-white border border-slate-200/80 rounded-2xl p-8 shadow-xl shadow-slate-100">
      
      <!-- Cabecera / Logo -->
      <div class="flex flex-col items-center mb-8">
        <div class="w-20 h-20 flex items-center rounded-2xl justify-center text-white font-bold text-xl">
         <img :src="logoUrl" alt="Logo" class="w-50 h-50" />
        </div>
        <h1 class="text-xl font-bold text-slate-800 tracking-tight">CobranzaOS</h1>
        <p class="text-xs text-slate-500 mt-1">Refinansa Perú • Módulo de Gestión</p>
      </div>

      <!-- Formulario de Acceso -->
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Correo Electrónico</label>
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-400 flex items-center pointer-events-none">
              <Mail class="w-4 h-4" />
            </span>
            <input 
              type="email" 
              v-model="email" 
              required
              placeholder="ejemplo@refinansa.com"
              class="w-full text-xs pl-10 pr-3.5 py-2.5 rounded-lg bg-slate-50/50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Contraseña</label>
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-400 flex items-center pointer-events-none">
              <Lock class="w-4 h-4" />
            </span>
            <input 
              type="password" 
              v-model="password" 
              required
              placeholder="••••••••"
              class="w-full text-xs pl-10 pr-3.5 py-2.5 rounded-lg bg-slate-50/50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all"
            />
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full mt-2 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-md shadow-blue-600/20 transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
        >
          <span v-if="loading">Validando credenciales...</span>
          <span v-else class="flex items-center gap-2">
            Iniciar Sesión
            <LogIn class="w-4 h-4" />
          </span>
        </button>
      </form>

      <!-- Pie de página -->
      <div class="mt-8 pt-4 border-t border-slate-100 text-center">
        <p class="text-[11px] text-slate-400">Refinansa Perú © 2027 • Todos los derechos reservados</p>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Mail, Lock, LogIn, Images } from 'lucide-vue-next';
import api from '../api/axios';
import Swal from 'sweetalert2';
import logoUrl from '../../public/Images/user.png';

const email = ref('');
const password = ref('');
const loading = ref(false);
const router = useRouter();

const handleLogin = async () => {
  loading.value = true;
  try {
    const response = await api.post('/login', {
      email: email.value,
      password: password.value
   
    });

    localStorage.setItem('token', response.data.access_token);
    localStorage.setItem('user', JSON.stringify(response.data.user));

    await Swal.fire({
      icon: 'success',
      title: '¡Bienvenido!',
      text: response.data.message,
      timer: 1200,
      showConfirmButton: false,
      background: '#ffffff',
      color: '#1e293b'
    });

    router.push('/dashboard');

  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Acceso Denegado',
      text: error.response?.data?.message || 'Error al conectar con el servidor.',
      background: '#ffffff',
      color: '#1e293b'
    });
  } finally {
    loading.value = false;
  }
};
</script>