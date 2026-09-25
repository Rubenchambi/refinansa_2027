<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportController; 
use App\Http\Controllers\AsignacionController;

// 5 intentos por minuto máximo por IP para el login
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Rutas protegidas por Sanctum estándar
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Rutas protegidas por Sanctum Y por el Middleware de Roles
Route::middleware(['auth:sanctum', 'role:admin,supervisor'])->group(function () {
    Route::post('/importar-cartera', [ImportController::class, 'importarAsignacion']);
});

Route::post('/importar-cartera', [AsignacionController::class, 'importarAsignacion']);
Route::post('/obtener-cabeceras', [AsignacionController::class, 'obtenerCabecerasLote']);
Route::post('/procesar-matriz', [AsignacionController::class, 'procesarYGenerarMatriz']);
Route::get('/lotes-importados', [AsignacionController::class, 'listarLotesImportados']);
Route::post('/previsualizar-matriz', [AsignacionController::class, 'previsualizarMatriz']);
Route::get('/plantillas-mapeo', [AsignacionController::class, 'listarPlantillasMapeo']);
Route::post('/guardar-plantilla-mapeo', [AsignacionController::class, 'guardarPlantillaMapeo']);
Route::post('/cargar-plantilla-excel', [AsignacionController::class, 'cargarPlantillaExcel']);
Route::delete('/plantillas-mapeo/{id}', [AsignacionController::class, 'eliminarPlantillaMapeo']);