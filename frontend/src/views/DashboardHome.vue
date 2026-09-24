<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-slate-800 tracking-tight">Panel General</h1>
      <p class="text-xs text-slate-500 mt-1">Resumen de operaciones y métricas de recuperación</p>
    </div>

    <!-- Tarjetas de Estadísticas (KPIs estilo la imagen) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200/85 shadow-sm">
        <p class="text-xs font-semibold text-slate-400 uppercase">Cartera Total</p>
        <h3 class="text-2xl font-bold text-slate-800 mt-2">S/. 124,500</h3>
        <span class="text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md mt-3 inline-block">+12% este mes</span>
      </div>

      <div class="bg-white p-6 rounded-2xl border border-slate-200/85 shadow-sm">
        <p class="text-xs font-semibold text-slate-400 uppercase">Recuperado</p>
        <h3 class="text-2xl font-bold text-slate-800 mt-2">S/. 48,200</h3>
        <span class="text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md mt-3 inline-block">+8.4% eficiencias</span>
      </div>

      <div class="bg-white p-6 rounded-2xl border border-slate-200/85 shadow-sm">
        <p class="text-xs font-semibold text-slate-400 uppercase">Casos Pendientes</p>
        <h3 class="text-2xl font-bold text-slate-800 mt-2">342</h3>
        <span class="text-[11px] font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md mt-3 inline-block">Atención requerida</span>
      </div>

      <div class="bg-white p-6 rounded-2xl border border-slate-200/85 shadow-sm">
        <p class="text-xs font-semibold text-slate-400 uppercase">Compromisos Hoy</p>
        <h3 class="text-2xl font-bold text-slate-800 mt-2">18</h3>
        <span class="text-[11px] font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md mt-3 inline-block">En curso</span>
      </div>
    </div>
  </div>
</template>

<script setup>
// Lógica de tarjetas y estadísticas próximamente
</script>