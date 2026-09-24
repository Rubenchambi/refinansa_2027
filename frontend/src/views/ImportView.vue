<template>
  <div class="space-y-6 max-w-full">
    
    <!-- Cabecera de la sección -->
    <div>
      <h1 class="text-xl font-bold text-slate-800 tracking-tight">Importación de Asignación de Origen</h1>
      <p class="text-xs text-slate-500 mt-1">Sube la cartera enviada por el banco o entidad. El sistema guardará los datos originales intactos.</p>
    </div>

    <!-- Formulario de Carga -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
      <form @submit.prevent="handleImport" class="space-y-5">
        
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Identificador de la Cartera (Ej: BCP_OCTUBRE_2026)</label>
          <input 
            type="text" 
            v-model="nombreCartera" 
            required
            placeholder="Ej. BCP_CAMPAÑA_01"
            class="w-full text-xs px-3.5 py-2.5 rounded-lg bg-slate-50/50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all"
          />
          <span class="text-[10px] text-slate-400 mt-1 block">Este nombre servirá para registrar el lote de la cartera en el sistema.</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Periodo de la Cartera (Ej: 2026-08)</label>
          <input 
            type="text" 
            v-model="periodo" 
            required
            placeholder="2026-08"
            maxlength="7"
            class="w-full text-xs px-3.5 py-2.5 rounded-lg bg-slate-50/50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all"
          />
          <span class="text-[10px] text-slate-400 mt-1 block">Formato obligatorio: AAAA-MM (Año y Mes).</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Archivo de Asignación (.CSV, .TXT, .XLSX, .XLS)</label>
          <div class="flex items-center justify-center w-full">
            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100/80 transition-all">
              <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                <FileSpreadsheet class="w-8 h-8 text-blue-600 mb-2" />
                <p class="text-xs text-slate-700 font-semibold mb-1">
                  <span v-if="archivo">{{ archivo.name }}</span>
                  <span v-else>Haz clic para seleccionar el archivo Excel o arrástralo aquí</span>
                </p>
                <p class="text-[10px] text-slate-400">Soporte nativo para archivos Excel y CSV</p>
              </div>
              <input type="file" @change="handleFileChange" required accept=".csv, .txt, .xlsx, .xls" class="hidden" />
            </label>
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-md shadow-blue-600/20 transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
        >
          <span v-if="loading">Procesando e importando cartera al sistema...</span>
          <span v-else class="flex items-center gap-2">
            <Upload class="w-4 h-4" />
            Migrar Cartera Original
          </span>
        </button>
      </form>
    </div>

    <!-- Panel de Resumen Instantáneo (Métricas) -->
    <div v-if="stats" class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-slate-100">
        <CheckCircle2 class="w-5 h-5 text-emerald-600" />
        <h3 class="text-sm font-bold text-slate-800">Resumen de la Importación Exitosa</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
          <span class="text-slate-400 block mb-1 font-medium">Archivo</span>
          <strong class="text-slate-800 truncate block" :title="stats.archivo">{{ stats.archivo }}</strong>
        </div>

        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
          <span class="text-slate-400 block mb-1 font-medium">Destino / Lote</span>
          <strong class="text-slate-800 block truncate" :title="stats.tabla_destino">{{ stats.tabla_destino }}</strong>
        </div>

        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
          <span class="text-slate-400 block mb-1 font-medium">Total de Cuentas</span>
          <strong class="text-blue-600 text-sm font-bold">{{ stats.total_cuentas }}</strong>
        </div>

        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
          <span class="text-slate-400 block mb-1 font-medium">Monto Total Cartera</span>
          <strong class="text-slate-800 text-sm font-bold">{{ stats.monto_total_cartera }}</strong>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Upload, FileSpreadsheet, CheckCircle2 } from 'lucide-vue-next';
import api from '../api/axios';
import Swal from 'sweetalert2';

const nombreCartera = ref('');
const archivo = ref(null);
const loading = ref(false);
const stats = ref(null);
const periodo = ref('');
const handleFileChange = (e) => {
  if (e.target.files.length > 0) {
    archivo.value = e.target.files[0];
  }
};

const handleImport = async () => {
  if (!archivo.value || !nombreCartera.value) return;

  const formData = new FormData();
  formData.append('archivo', archivo.value);
  formData.append('nombre_cartera', nombreCartera.value);
  formData.append('periodo', periodo.value);
  loading.value = true;
  stats.value = null;

  try {
    const response = await api.post('/importar-cartera', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    stats.value = response.data.stats;

    await Swal.fire({
      icon: 'success',
      title: '¡Importación Exitosa!',
      text: response.data.message,
      background: '#ffffff',
      color: '#1e293b',
      confirmButtonColor: '#2563eb'
    });

  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Acceso Denegado o Error',
      text: error.response?.data?.message || 'Hubo un error al procesar el archivo en el servidor.',
      background: '#ffffff',
      color: '#1e293b'
    });
  } finally {
    loading.value = false;
  }
};
</script>