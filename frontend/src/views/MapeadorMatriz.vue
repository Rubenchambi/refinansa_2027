<template>
  <div class="space-y-6 max-w-full">
    
    <!-- Cabecera del Módulo -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-800 tracking-tight">Mapeo Dinámico y Generación de Matriz General</h1>
        <p class="text-xs text-slate-500 mt-1">Selecciona el lote importado, usa plantillas predefinidas o configura equivalencias al instante.</p>
      </div>
    </div>

    <!-- Paso 1: Selección de Lote e Ingreso de Periodo -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs">
      <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
        <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-black">1</span>
        Identificación del Lote y Periodo Operativo
      </h2>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Lote Importado (Base Banco)</label>
          <select 
            v-model="form.lote_cartera" 
            class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all cursor-pointer font-medium"
          >
            <option value="">-- Selecciona el lote importado --</option>
            <option v-for="lote in listaLotes" :key="lote.lote_cartera" :value="lote.lote_cartera">
              {{ lote.lote_cartera }} (Periodo: {{ lote.periodo }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Periodo Operativo (AAAA-MM)</label>
          <input 
            type="text" 
            v-model="form.periodo" 
            placeholder="2026-08"
            maxlength="7"
            class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:outline-none focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 transition-all font-mono font-medium"
          />
        </div>

        <button 
          @click="cargarCabeceras" 
          :disabled="loadingCabeceras || !form.lote_cartera || !form.periodo"
          class="py-2.5 px-4 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <Layers class="w-4 h-4" />
          {{ loadingCabeceras ? 'Cargando Estructura...' : 'Cargar Columnas del Lote' }}
        </button>
      </div>
    </div>

    <!-- Paso 2: Interfaz de Equivalencias con GESTIÓN DE PLANTILLAS -->
    <div v-if="columnasBanco.length > 0" class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs space-y-6">
      
      <!-- BARRA DE PLANTILLAS -->
      <div class="bg-slate-100 border border-slate-200 p-4 rounded-xl flex flex-wrap items-center justify-between gap-4 shadow-xs">
        
        <div class="flex items-center gap-3 shrink-0">
          <BookmarkCheck class="w-5 h-5 text-blue-600 shrink-0" />
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Plantillas de Mapeo por Banco</h3>
            <p class="text-[11px] text-slate-500">Carga un perfil guardado o sube un Excel para auto-completar los selectores.</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 ml-auto w-full md:w-auto justify-end">
          
          <input 
            type="file" 
            ref="fileInputPlantilla" 
            @change="procesarExcelPlantilla" 
            accept=".xlsx, .xls, .csv" 
            class="hidden" 
          />

          <!-- Selector de Plantillas BD con Opción de Eliminar Integrada -->
          <div class="flex items-center gap-1">
            <select 
              v-model="plantillaSeleccionada" 
              @change="aplicarPlantilla"
              class="text-xs px-3 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-800 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 cursor-pointer font-medium shadow-2xs min-w-[210px]"
            >
              <option value="">-- Cargar Plantilla BD --</option>
              <option v-for="p in listaPlantillas" :key="p.id" :value="p.id">
                {{ p.nombre_plantilla }}
              </option>
            </select>

            <button 
              v-if="plantillaSeleccionada"
              @click="eliminarPlantillaSeleccionada"
              type="button"
              class="p-2.5 bg-white hover:bg-red-50 text-red-500 border border-red-200 rounded-xl transition-all shadow-2xs cursor-pointer"
              title="Eliminar esta plantilla de la BD"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>

          <!-- Botón Subir Excel -->
          <button 
            @click="$refs.fileInputPlantilla.click()" 
            type="button" 
            class="px-3.5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold rounded-xl shadow-2xs flex items-center gap-1.5 cursor-pointer transition-all shrink-0"
          >
            <FileSpreadsheet class="w-3.5 h-3.5 text-slate-500" /> Subir Excel
          </button>

          <!-- Botón Guardar Plantilla -->
          <button 
            @click="guardarNuevaPlantilla" 
            type="button" 
            class="px-3.5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold rounded-xl shadow-2xs flex items-center gap-1.5 cursor-pointer transition-all shrink-0"
          >
            <Save class="w-3.5 h-3.5 text-slate-500" /> Guardar Plantilla
          </button>

        </div>
      </div>

      <div class="flex items-center justify-between border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-black">2</span>
            Configuración de Equivalencias
          </h2>
          <p class="text-xs text-slate-400 mt-1">Escribe dentro del selector para buscar rápidamente las columnas de la entidad bancaria o escribe un texto fijo.</p>
        </div>
        <div>
          <span class="text-xs bg-blue-50 text-blue-700 font-bold px-3 py-1.5 rounded-full border border-blue-200">
            {{ columnasBanco.length }} columnas origen detectadas
          </span>
        </div>
      </div>

      <!-- Grid de 3 Columnas con Fondos Continuos Originales (Sky, Emerald, Amber) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        
        <!-- BLOQUE 1: Identificación y Contactabilidad (Azul Sky) -->
        <div class="border-2 border-sky-200 bg-sky-50/70 rounded-2xl p-4 space-y-4 shadow-xs flex flex-col justify-between">
          <div class="space-y-4">
            <div class="text-xs font-bold text-sky-800 uppercase tracking-wider text-center pb-2 border-b border-sky-200">
              Identificación y Contacto
            </div>
            
            <div v-for="(campo, idx) in columnasCol1" :key="'col1-'+idx" class="bg-white border border-sky-200 p-3 rounded-xl space-y-2 shadow-2xs">
              <div class="flex items-center justify-between gap-2">
                <input type="text" v-model="campo.nombreOficial" placeholder="Nombre Campo Matriz" class="text-xs font-bold text-sky-900 bg-sky-50/50 px-2 py-1 rounded-lg border border-sky-200 outline-none w-full" />
                
                <!-- Íconos de Verificación / Advertencia -->
                <div class="flex items-center gap-1 shrink-0">
                  <CheckCircle2 
                    v-if="esColumnaValida(campo.columnaBanco)" 
                    class="w-4 h-4 text-emerald-500" 
                    title="Coincidencia exacta con el lote importado" 
                  />
                  <AlertCircle 
                    v-else-if="campo.columnaBanco" 
                    class="w-4 h-4 text-amber-500 cursor-help" 
                    title="Esta columna no existe en el lote actual (se tomará como valor fijo o debes corregirla)" 
                  />
                  <button @click="removerSelector(1, idx)" class="text-slate-400 hover:text-red-500 cursor-pointer p-1" title="Eliminar fila">
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              
              <v-select 
                v-model="campo.columnaBanco" 
                :options="columnasBanco" 
                :taggable="true"
                placeholder="Buscar columna o escribir valor fijo..."
                class="custom-v-select text-xs"
              />
            </div>
          </div>

          <button 
            @click="agregarSelectorAColumna(1)" 
            type="button" 
            class="w-full py-2 bg-white hover:bg-sky-100 text-sky-700 border border-dashed border-sky-300 text-xs font-bold rounded-xl shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer transition-all mt-3"
          >
            <Plus class="w-3.5 h-3.5" /> Añadir Selector
          </button>
        </div>

        <!-- BLOQUE 2: Contratos, Cuentas y Operaciones (Verde Emerald) -->
        <div class="border-2 border-emerald-200 bg-emerald-50/70 rounded-2xl p-4 space-y-4 shadow-xs flex flex-col justify-between">
          <div class="space-y-4">
            <div class="text-xs font-bold text-emerald-800 uppercase tracking-wider text-center pb-2 border-b border-emerald-200">
              Cuentas, Contratos y Operaciones
            </div>
            
            <div v-for="(campo, idx) in columnasCol2" :key="'col2-'+idx" class="bg-white border border-emerald-200 p-3 rounded-xl space-y-2 shadow-2xs">
              <div class="flex items-center justify-between gap-2">
                <input type="text" v-model="campo.nombreOficial" placeholder="Nombre Campo Matriz" class="text-xs font-bold text-emerald-900 bg-emerald-50/50 px-2 py-1 rounded-lg border border-emerald-200 outline-none w-full" />
                
                <!-- Íconos de Verificación / Advertencia -->
                <div class="flex items-center gap-1 shrink-0">
                  <CheckCircle2 
                    v-if="esColumnaValida(campo.columnaBanco)" 
                    class="w-4 h-4 text-emerald-500" 
                    title="Coincidencia exacta con el lote importado" 
                  />
                  <AlertCircle 
                    v-else-if="campo.columnaBanco" 
                    class="w-4 h-4 text-amber-500 cursor-help" 
                    title="Esta columna no existe en el lote actual (se tomará como valor fijo o debes corregirla)" 
                  />
                  <button @click="removerSelector(2, idx)" class="text-slate-400 hover:text-red-500 cursor-pointer p-1" title="Eliminar fila">
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              
              <v-select 
                v-model="campo.columnaBanco" 
                :options="columnasBanco" 
                :taggable="true"
                placeholder="Buscar columna o escribir valor fijo..."
                class="custom-v-select text-xs"
              />
            </div>
          </div>

          <button 
            @click="agregarSelectorAColumna(2)" 
            type="button" 
            class="w-full py-2 bg-white hover:bg-emerald-100 text-emerald-700 border border-dashed border-emerald-300 text-xs font-bold rounded-xl shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer transition-all mt-3"
          >
            <Plus class="w-3.5 h-3.5" /> Añadir Selector
          </button>
        </div>

        <!-- BLOQUE 3: Financiero, Deudas y Comodines (Ámbar) -->
        <div class="border-2 border-amber-200 bg-amber-50/70 rounded-2xl p-4 space-y-4 shadow-xs flex flex-col justify-between">
          <div class="space-y-4">
            <div class="text-xs font-bold text-amber-800 uppercase tracking-wider text-center pb-2 border-b border-amber-200">
              Saldos, Montos y Comodines (P/Var)
            </div>
            
            <div v-for="(campo, idx) in columnasCol3" :key="'col3-'+idx" class="bg-white border border-amber-200 p-3 rounded-xl space-y-2 shadow-2xs">
              <div class="flex items-center justify-between gap-2">
                <input type="text" v-model="campo.nombreOficial" placeholder="Nombre Campo Matriz" class="text-xs font-bold text-amber-900 bg-amber-50/50 px-2 py-1 rounded-lg border border-amber-200 outline-none w-full" />
                
                <!-- Íconos de Verificación / Advertencia -->
                <div class="flex items-center gap-1 shrink-0">
                  <CheckCircle2 
                    v-if="esColumnaValida(campo.columnaBanco)" 
                    class="w-4 h-4 text-emerald-500" 
                    title="Coincidencia exacta con el lote importado" 
                  />
                  <AlertCircle 
                    v-else-if="campo.columnaBanco" 
                    class="w-4 h-4 text-amber-500 cursor-help" 
                    title="Esta columna no existe en el lote actual (se tomará como valor fijo o debes corregirla)" 
                  />
                  <button @click="removerSelector(3, idx)" class="text-slate-400 hover:text-red-500 cursor-pointer p-1" title="Eliminar fila">
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              
              <v-select 
                v-model="campo.columnaBanco" 
                :options="columnasBanco" 
                :taggable="true"
                placeholder="Buscar columna o escribir valor fijo..."
                class="custom-v-select text-xs"
              />
            </div>
          </div>

          <button 
            @click="agregarSelectorAColumna(3)" 
            type="button" 
            class="w-full py-2 bg-white hover:bg-amber-100 text-amber-700 border border-dashed border-amber-300 text-xs font-bold rounded-xl shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer transition-all mt-3"
          >
            <Plus class="w-3.5 h-3.5" /> Añadir Selector
          </button>
        </div>

      </div>

      <!-- Botón de Previsualización -->
      <div class="pt-4 border-t border-slate-100 flex justify-end">
        <button 
          @click="solicitarVistaPrevia" 
          :disabled="loadingProceso"
          class="py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <ArrowRightLeft class="w-4 h-4" />
          Previsualizar Mapeo de Matriz
        </button>
      </div>

    </div>

    <!-- Modal de Vista Previa Ampliada -->
    <div v-if="showModalPreview" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-7xl w-full p-6 space-y-4 shadow-2xl border border-slate-200 max-h-[92vh] flex flex-col">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-sm font-bold text-slate-800">Vista Previa de Transformación Homologada</h3>
            <p class="text-xs text-slate-400 mt-0.5">Inspecciona los valores formateados mediante búsqueda, paginación y ordenamiento.</p>
          </div>
          <button @click="showModalPreview = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl cursor-pointer hover:bg-slate-100">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="flex justify-between items-center bg-slate-50 p-2.5 rounded-xl border border-slate-200">
          <input 
            v-model="searchValue" 
            type="text" 
            placeholder="Buscar valor en cualquier columna..." 
            class="text-xs px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-800 outline-none focus:border-blue-600 w-80 font-medium"
          />
          <span class="text-xs text-slate-500 font-semibold">
            Mostrando muestra de {{ dataPreview.length }} registros
          </span>
        </div>

        <div class="overflow-hidden border border-slate-200 rounded-xl">
          <EasyDataTable
            :headers="headersTable"
            :items="dataPreview"
            :search-value="searchValue"
            :rows-per-page="5"
            buttons-pagination
            theme-color="#2563eb"
            table-class-name="matriz-preview-table"
          />
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-xs font-semibold text-slate-500">
            Total de columnas asignadas: {{ columnasPreview.length }}
          </span>
          <div class="flex items-center gap-3">
            <button 
              @click="showModalPreview = false" 
              class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer"
            >
              Regresar a Editar
            </button>
            <button 
              @click="ejecutarTransformacionFinal" 
              class="py-2.5 px-6 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer"
            >
              Confirmar y Generar Matriz General
            </button>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Layers, ArrowRightLeft, Plus, FileSpreadsheet, Trash2, X, BookmarkCheck, Save, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import api from '../api/axios';
import Swal from 'sweetalert2';

const listaLotes = ref([]);
const listaPlantillas = ref([]);
const plantillaSeleccionada = ref('');
const fileInputPlantilla = ref(null);
const form = ref({
  lote_cartera: '',
  periodo: ''
});

const columnasBanco = ref([]);
const loadingCabeceras = ref(false);
const loadingProceso = ref(false);

const showModalPreview = ref(false);
const dataPreview = ref([]);
const columnasPreview = ref([]);
const searchValue = ref('');

const headersTable = computed(() => {
  return columnasPreview.value.map(col => ({
    text: col.toUpperCase(),
    value: col,
    sortable: true
  }));
});

// Helper para determinar si el valor seleccionado existe como columna real en el lote
const esColumnaValida = (valorSeleccionado) => {
  if (!valorSeleccionado) return false;
  return columnasBanco.value.some(
    col => col.toLowerCase().trim() === valorSeleccionado.toLowerCase().trim()
  );
};

// Estructura Base Inicial de 3 Columnas
const columnasCol1 = ref([
  { nombreOficial: 'nro_documento', columnaBanco: '' },
  { nombreOficial: 'nombre_cliente', columnaBanco: '' },
  { nombreOficial: 'cel1', columnaBanco: '' },
  { nombreOficial: 'cel2', columnaBanco: '' },
  { nombreOficial: 'departamento_origen', columnaBanco: '' }
]);

const columnasCol2 = ref([
  { nombreOficial: 'cuenta_cod_credito', columnaBanco: '' },
  { nombreOficial: 'operacion_cod_modular', columnaBanco: '' },
  { nombreOficial: 'envio_correo', columnaBanco: '' },
  { nombreOficial: 'linea_negocio', columnaBanco: '' },
  { nombreOficial: 'provincia_origen', columnaBanco: '' }
]);

const columnasCol3 = ref([
  { nombreOficial: 'capital_deuda', columnaBanco: '' },
  { nombreOficial: 'monto_campaña_cancelacion', columnaBanco: '' },
  { nombreOficial: 'p1', columnaBanco: '' },
  { nombreOficial: 'p2', columnaBanco: '' },
  { nombreOficial: 'var1', columnaBanco: '' },
  { nombreOficial: 'var2', columnaBanco: '' }
]);

// Agregar un selector individual en la columna específica
const agregarSelectorAColumna = (colNum) => {
  const nuevoCard = { nombreOficial: `nuevo_campo_col${colNum}`, columnaBanco: '' };
  if (colNum === 1) columnasCol1.value.push(nuevoCard);
  if (colNum === 2) columnasCol2.value.push(nuevoCard);
  if (colNum === 3) columnasCol3.value.push(nuevoCard);
};

const removerSelector = (colIndex, idx) => {
  if (colIndex === 1) columnasCol1.value.splice(idx, 1);
  if (colIndex === 2) columnasCol2.value.splice(idx, 1);
  if (colIndex === 3) columnasCol3.value.splice(idx, 1);
};

// Cargar Plantillas desde el Backend
const cargarPlantillas = async () => {
  try {
    const res = await api.get('/plantillas-mapeo');
    listaPlantillas.value = res.data;
  } catch (e) {
    console.error('Error al obtener plantillas de mapeo', e);
  }
};

onMounted(async () => {
  try {
    const res = await api.get('/lotes-importados');
    listaLotes.value = res.data;
    await cargarPlantillas();
  } catch (e) {
    console.error('Error al inicializar datos', e);
  }
});

/**
 * REPARTIDOR RECURSIVO: Distribuye cualquier diccionario {campo: valor} 
 * equitativamente entre las 3 columnas sin saturar ninguna.
 */
const reconstruirYDistribuirColumnas = (mapeoObj) => {
  columnasCol1.value = [];
  columnasCol2.value = [];
  columnasCol3.value = [];

  const entradas = Object.entries(mapeoObj);
  
  entradas.forEach(([campo, colBanco], index) => {
    const cardData = {
      nombreOficial: campo,
      columnaBanco: colBanco || ''
    };

    if (index % 3 === 0) {
      columnasCol1.value.push(cardData);
    } else if (index % 3 === 1) {
      columnasCol2.value.push(cardData);
    } else {
      columnasCol3.value.push(cardData);
    }
  });
};

/**
 * Procesar Plantilla subida desde Excel
 */
const procesarExcelPlantilla = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('archivo_plantilla', file);

  Swal.fire({
    title: 'Leyendo Plantilla Excel...',
    text: 'Procesando equivalencias y distribuyendo campos',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  try {
    const res = await api.post('/cargar-plantilla-excel', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    const config = res.data.mapeo;
    
    // Homologar con las columnas reales del banco o dejar el texto literal
    const mapeoHomologado = {};
    Object.keys(config).forEach(campoKey => {
      const valorExcel = config[campoKey];
      const coincidencia = columnasBanco.value.find(
        col => col.toLowerCase().trim() === valorExcel.toLowerCase().trim()
      );
      mapeoHomologado[campoKey] = coincidencia || valorExcel;
    });

    reconstruirYDistribuirColumnas(mapeoHomologado);

    Swal.fire({
      icon: 'success',
      title: '¡Plantilla Excel Aplicada!',
      text: `Se cargaron ${Object.keys(config).length} equivalencias repartidas balanceadamente.`,
      timer: 2000,
      showConfirmButton: false
    });

  } catch (e) {
    Swal.fire('Error', e.response?.data?.message || 'No se pudo leer la plantilla Excel.', 'error');
  } finally {
    event.target.value = '';
  }
};

/**
 * Cargar estructura de cabeceras desde el Lote
 */
const cargarCabeceras = async () => {
  if (!form.value.lote_cartera || !form.value.periodo) {
    Swal.fire('Atención', 'Selecciona un lote y escribe el periodo correspondiente.', 'warning');
    return;
  }

  loadingCabeceras.value = true;
  columnasBanco.value = [];

  try {
    const response = await api.post('/obtener-cabeceras', form.value);
    columnasBanco.value = response.data.columnas_banco;
    
    autoMapearColumnas();

    Swal.fire({
      icon: 'success',
      title: '¡Estructura Cargada!',
      text: `Se detectaron ${columnasBanco.value.length} columnas en este lote.`,
      timer: 1500,
      showConfirmButton: false
    });
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'No se pudieron cargar las columnas del lote.', 'error');
  } finally {
    loadingCabeceras.value = false;
  }
};

const autoMapearColumnas = () => {
  const buscarCoincidencia = (nombreOficial) => {
    return columnasBanco.value.find(col => {
      const colLow = col.toLowerCase().trim();
      if (nombreOficial === 'nro_documento') return ['dni', 'documento', 'nro_documento', 'doc identidad', 'nro_doc'].includes(colLow);
      if (nombreOficial === 'cuenta_cod_credito') return ['cuenta', 'operacion', 'codigo_credito', 'credito', 'nro_cuenta'].includes(colLow);
      if (nombreOficial === 'capital_deuda') return ['capital', 'capital_deuda', 'saldo_capital', 'capital_soles', 'deuda'].includes(colLow);
      if (nombreOficial === 'nombre_cliente') return ['nombre', 'cliente', 'nombre_cliente', 'docentes', 'titular'].includes(colLow);
      return colLow === nombreOficial.toLowerCase();
    }) || '';
  };

  [columnasCol1.value, columnasCol2.value, columnasCol3.value].forEach(lista => {
    lista.forEach(item => {
      if (!item.columnaBanco) {
        item.columnaBanco = buscarCoincidencia(item.nombreOficial);
      }
    });
  });
};

/**
 * Aplicar Plantilla seleccionada desde la BD
 */
const aplicarPlantilla = () => {
  if (!plantillaSeleccionada.value) return;

  const plantilla = listaPlantillas.value.find(p => p.id === plantillaSeleccionada.value);
  if (!plantilla) return;

  const config = typeof plantilla.mapeo_config === 'string' 
    ? JSON.parse(plantilla.mapeo_config) 
    : plantilla.mapeo_config;

  reconstruirYDistribuirColumnas(config);

  Swal.fire({
    icon: 'success',
    title: '¡Plantilla Aplicada!',
    text: `Se cargó el perfil "${plantilla.nombre_plantilla}".`,
    timer: 1500,
    showConfirmButton: false
  });
};

/**
 * Eliminar la plantilla seleccionada de la Base de Datos
 */
const eliminarPlantillaSeleccionada = async () => {
  if (!plantillaSeleccionada.value) return;

  const plantilla = listaPlantillas.value.find(p => p.id === plantillaSeleccionada.value);
  if (!plantilla) return;

  const confirmacion = await Swal.fire({
    title: '¿Eliminar Plantilla?',
    text: `¿Estás seguro de que deseas eliminar la plantilla "${plantilla.nombre_plantilla}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (confirmacion.isConfirmed) {
    try {
      await api.delete(`/plantillas-mapeo/${plantilla.id}`);
      plantillaSeleccionada.value = '';
      await cargarPlantillas();

      Swal.fire('¡Eliminada!', 'La plantilla se eliminó de la base de datos.', 'success');
    } catch (e) {
      Swal.fire('Error', 'No se pudo eliminar la plantilla.', 'error');
    }
  }
};

/**
 * Guardar el mapeo actual en la BD
 */
const guardarNuevaPlantilla = async () => {
  const mapeoActual = construirMapeoFinal();

  const { value: nombrePlantilla } = await Swal.fire({
    title: 'Guardar Plantilla de Mapeo',
    input: 'text',
    inputLabel: 'Nombre para la plantilla (Ej: Caja Arequipa, Pichincha Castigo)',
    inputPlaceholder: 'Ingresa el nombre aquí...',
    showCancelButton: true,
    confirmButtonText: 'Guardar',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (!value) return '¡Debes escribir un nombre para la plantilla!';
    }
  });

  if (nombrePlantilla) {
    try {
      await api.post('/guardar-plantilla-mapeo', {
        nombre_plantilla: nombrePlantilla,
        mapeo: mapeoActual
      });

      await cargarPlantillas();

      Swal.fire('¡Guardado!', `La plantilla "${nombrePlantilla}" se guardó correctamente.`, 'success');
    } catch (e) {
      Swal.fire('Error', 'No se pudo guardar la plantilla.', 'error');
    }
  }
};

const construirMapeoFinal = () => {
  const mapeoFinal = {};
  const procesarColumna = (lista) => {
    lista.forEach(item => {
      if (item.nombreOficial && item.columnaBanco) {
        mapeoFinal[item.nombreOficial.trim().toLowerCase()] = item.columnaBanco;
      }
    });
  };
  procesarColumna(columnasCol1.value);
  procesarColumna(columnasCol2.value);
  procesarColumna(columnasCol3.value);
  return mapeoFinal;
};

// Previsualización
const solicitarVistaPrevia = async () => {
  const mapeoFinal = construirMapeoFinal();

  if (!mapeoFinal.nro_documento || !mapeoFinal.capital_deuda) {
    Swal.fire('Campos Requeridos', 'Asegúrate de asignar al menos nro_documento y capital_deuda para continuar.', 'warning');
    return;
  }

  Swal.fire({
    title: 'Generando Vista Previa...',
    text: 'Procesando los primeros registros de la cartera',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  try {
    const response = await api.post('/previsualizar-matriz', {
      lote_cartera: form.value.lote_cartera,
      periodo: form.value.periodo,
      mapeo: mapeoFinal
    });

    Swal.close();
    dataPreview.value = response.data.preview;
    columnasPreview.value = response.data.columnas_mapeadas;
    showModalPreview.value = true;

  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Error al generar la vista previa.', 'error');
  }
};

// Procesamiento Final
const ejecutarTransformacionFinal = async () => {
  const mapeoFinal = construirMapeoFinal();
  showModalPreview.value = false;

  Swal.fire({
    title: 'Procesando Matriz General...',
    html: 'Construyendo registros, calculando tramos y guardando en la base de datos.<br><b>Por favor no cierres la ventana.</b>',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => { Swal.showLoading(); }
  });

  try {
    const response = await api.post('/procesar-matriz', {
      lote_cartera: form.value.lote_cartera,
      periodo: form.value.periodo,
      mapeo: mapeoFinal
    });

    Swal.fire({
      icon: 'success',
      title: '¡Matriz Procesada con Éxito!',
      text: response.data.message,
      confirmButtonColor: '#2563eb'
    });

  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Error al procesar la transformación de la matriz.', 'error');
  }
};
</script>

<style scoped>
:deep(.custom-v-select .vs__dropdown-toggle) {
  border-radius: 0.5rem;
  border-color: #cbd5e1;
  background-color: #ffffff;
  padding-top: 2px;
  padding-bottom: 2px;
}

:deep(.custom-v-select .vs__selected) {
  font-size: 11px;
  font-weight: 600;
  color: #1e293b;
}

:deep(.matriz-preview-table) {
  --easy-table-header-font-size: 11px;
  --easy-table-header-height: 40px;
  --easy-table-header-font-color: #1e293b;
  --easy-table-header-bg-color: #f1f5f9;
  --easy-table-body-row-font-size: 11px;
  --easy-table-body-row-height: 36px;
  --easy-table-body-row-font-color: #334155;
  --easy-table-body-row-hover-bg-color: #eff6ff;
}
</style>