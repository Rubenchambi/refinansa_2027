<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class AsignacionController extends Controller
{
    /**
     * 1. MÓDULO DE IMPORTACIÓN EN CRUDO (JSONB)
     * Recibe cualquier Excel o CSV, lee la estructura original y la almacena intacta.
     */
    public function importarAsignacion(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls',
            'nombre_cartera' => 'required|string',
            'periodo' => 'required|string|size:7' // Formato: AAAA-MM
        ]);

        $file = $request->file('archivo');
        $path = $file->getRealPath();
        $loteCartera = trim($request->nombre_cartera);
        $periodo = trim($request->periodo);

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['message' => 'El archivo está vacío o no contiene datos válidos.'], 422);
            }

            // Extraer y limpiar la cabecera
            $headerRow = array_shift($rows);
            $headers = array_map(function($val) {
                return trim((string)$val);
            }, $headerRow);

            $filasParaInsertar = [];
            $sumaDeudaTotal = 0;
            $columnaMontoKey = null;

            foreach ($headers as $key => $hName) {
                $lower = strtolower($hName);
                if (str_contains($lower, 'deuda') || str_contains($lower, 'saldo') || str_contains($lower, 'monto') || str_contains($lower, 'capital')) {
                    $columnaMontoKey = $key;
                    break;
                }
            }

            foreach ($rows as $row) {
                if (count(array_filter($row)) == 0) continue;

                $rowDataAssoc = [];
                foreach ($headers as $index => $headerName) {
                    if (!empty($headerName)) {
                        $rowDataAssoc[$headerName] = isset($row[$index]) ? trim((string)$row[$index]) : null;
                    }
                }

                if (!empty($rowDataAssoc)) {
                    if ($columnaMontoKey && isset($row[$columnaMontoKey])) {
                        $valMonto = str_replace([',', 'S/.', '$', ' '], '', $row[$columnaMontoKey]);
                        $sumaDeudaTotal += floatval($valMonto);
                    }

                    $filasParaInsertar[] = [
                        'lote_cartera' => $loteCartera,
                        'nombre_archivo' => $file->getClientOriginalName(),
                        'periodo' => $periodo,
                        'datos_fila' => json_encode($rowDataAssoc, JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (empty($filasParaInsertar)) {
                return response()->json(['message' => 'No se encontraron registros procesables en el archivo.'], 422);
            }

            DB::beginTransaction();
            foreach (array_chunk($filasParaInsertar, 500) as $chunk) {
                DB::table('asignaciones_cartera')->insert($chunk);
            }
            DB::commit();

            return response()->json([
                'message' => '¡Cartera importada con éxito en crudo!',
                'stats' => [
                    'tabla_destino' => 'asignaciones_cartera (Lote: ' . $loteCartera . ')',
                    'total_cuentas' => count($filasParaInsertar),
                    'total_columnas' => count(array_filter($headers)),
                    'columnas_originales' => array_values(array_filter($headers)),
                    'monto_total_cartera' => $sumaDeudaTotal > 0 ? number_format($sumaDeudaTotal, 2) : 'No detectado',
                    'archivo' => $file->getClientOriginalName()
                ]
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar el archivo Excel en crudo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. OBTENER CABECERAS DINÁMICAS DEL LOTE
     */
    public function obtenerCabecerasLote(Request $request)
    {
        $request->validate([
            'lote_cartera' => 'required|string',
            'periodo' => 'required|string|size:7'
        ]);

        $muestra = DB::table('asignaciones_cartera')
            ->where('lote_cartera', $request->lote_cartera)
            ->where('periodo', $request->periodo)
            ->first();

        if (!$muestra) {
            return response()->json(['message' => 'No se encontró el lote o periodo especificado.'], 404);
        }

        $datosFila = json_decode($muestra->datos_fila, true);
        $columnasBanco = array_keys($datosFila);

        return response()->json([
            'lote_cartera' => $request->lote_cartera,
            'periodo' => $request->periodo,
            'columnas_banco' => $columnasBanco
        ], 200);
    }

    /**
     * 3. MOTOR DE MAPEO Y TRANSFORMACIÓN DINÁMICA DE LA MATRIZ
     * Mapea hacia los 82 campos oficiales, soporta columnas del Excel o valores por defecto.
     */
    public function procesarYGenerarMatriz(Request $request)
    {
        $request->validate([
            'lote_cartera' => 'required|string',
            'periodo' => 'required|string|size:7', // Formato "YYYY-MM"
            'mapeo' => 'required|array'
        ]);

        $loteCartera = $request->lote_cartera;
        $periodo = $request->periodo;
        $mapeo = $request->mapeo;

        // Extraer Año y Mes
        list($anioStr, $mesStr) = explode('-', $periodo);
        $anioAsignacion = (int) $anioStr;
        $mesAsignacion = (int) $mesStr;

        try {
            $chunkSize = 1000;
            $procesados = 0;

            DB::table('asignaciones_cartera')
                ->where('lote_cartera', $loteCartera)
                ->where('periodo', $periodo)
                ->chunkById($chunkSize, function ($registros) use ($mapeo, $loteCartera, $mesAsignacion, $anioAsignacion, &$procesados) {
                    
                    $filasMatriz = [];

                    foreach ($registros as $reg) {
                        $json = json_decode($reg->datos_fila, true);

                        // 1. DNI y Relleno a 8 dígitos
                        $colDni = $mapeo['nro_documento'] ?? null;
                        $dniRaw = $colDni ? trim((string)($json[$colDni] ?? '')) : '';
                        $dniClean = preg_replace('/[^0-9]/', '', $dniRaw);
                        $dni = (!empty($dniClean) && strlen($dniClean) <= 8) ? str_pad($dniClean, 8, '0', STR_PAD_LEFT) : $dniRaw;

                        // 2. Cuenta / Código Operación
                        $colCuenta = $mapeo['cuenta_cod_credito'] ?? null;
                        $cuenta = $colCuenta ? trim((string)($json[$colCuenta] ?? '')) : '';

                        // 3. Operación / Código Modular
                        $colOperacion = $mapeo['operacion_cod_modular'] ?? null;
                        $operacion = $colOperacion ? trim((string)($json[$colOperacion] ?? '')) : '';

                        // 4. Nombre Cliente
                        $colNombre = $mapeo['nombre_cliente'] ?? null;
                        $nombre = $colNombre ? trim((string)($json[$colNombre] ?? '')) : 'SIN NOMBRE';

                        // 5. Capital Deuda
                        $colCapital = $mapeo['capital_deuda'] ?? null;
                        $capitalBruto = $colCapital ? ($json[$colCapital] ?? 0) : 0;
                        $capitalDeuda = floatval(str_replace([',', 'S/.', '$', ' '], '', $capitalBruto));

                        // 6. Generación del IDCLIENTE Compuesto
                        $llaveCuenta = !empty($cuenta) ? $cuenta : (!empty($operacion) ? $operacion : 'GENERICO');
                        $idcliente = (!empty($dni) && !empty($llaveCuenta)) ? ($dni . '-' . $llaveCuenta) : ((!empty($dni)) ? $dni . '-GENERICO' : 'SIN_ID_' . uniqid());

                        // 7. Resolución Flexible de CARTERA (Soporta Columna Excel O Texto Literal Libre)
                        $colCarteraMapeada = $mapeo['cartera'] ?? null;
                        $carteraFinal = $loteCartera; // Fallback por defecto

                        if (!empty($colCarteraMapeada)) {
                            if (array_key_exists($colCarteraMapeada, $json)) {
                                $carteraFinal = trim((string)$json[$colCarteraMapeada]);
                            } else {
                                $carteraFinal = trim((string)$colCarteraMapeada);
                            }
                        }

                        // 8. Cálculo Automático de Rangos e Indicador ALTOS
                        $rangoDeuda = $this->calcularRangoMonto($capitalDeuda);
                        $altos = ($capitalDeuda > 5000) ? 'ALTO' : '-';

                        $montoCampCanc = isset($mapeo['monto_campaña_cancelacion']) && !empty($mapeo['monto_campaña_cancelacion']) 
                            ? floatval(str_replace([',', 'S/.', '$', ' '], '', $json[$mapeo['monto_campaña_cancelacion']] ?? 0)) 
                            : 0.00;
                        $rangoCampCanc = ($montoCampCanc > 0) ? $this->calcularRangoMonto($montoCampCanc) : '-';

                        $montoCampSuper = isset($mapeo['monto_campaña_cancelacion_supervisor']) && !empty($mapeo['monto_campaña_cancelacion_supervisor']) 
                            ? floatval(str_replace([',', 'S/.', '$', ' '], '', $json[$mapeo['monto_campaña_cancelacion_supervisor']] ?? 0)) 
                            : 0.00;
                        $rangoCampSuper = ($montoCampSuper > 0) ? $this->calcularRangoMonto($montoCampSuper) : '-';

                        $colMonedaMapeada = $mapeo['moneda'] ?? null;
                        $monedaRaw = null;

                        if (!empty($colMonedaMapeada)) {
                            if (array_key_exists($colMonedaMapeada, $json)) {
                                $monedaRaw = $json[$colMonedaMapeada]; // Si viene de una columna del Excel
                            } else {
                                $monedaRaw = $colMonedaMapeada; // Si escribiste un texto fijo (Ej: 'USD' o 'PEN')
                            }
                        }

                        $monedaFinal = $this->normalizarMoneda($monedaRaw);

                        // 9. ESTRUCTURA BASE
                        $filaMatriz = [
                            'idcliente' => $idcliente,
                            'cuenta_cod_credito' => $cuenta,
                            'operacion_cod_modular' => $operacion,
                            'nro_documento' => $dni,
                            'unico' => 1,
                            'monto_unificado' => $capitalDeuda,
                            'nombre_cliente' => $nombre,
                            'cartera' => $carteraFinal,
                            'sub_cartera' => '-',
                            'region' => 'LIMA',
                            'linea_negocio' => '-',
                            'año_credito_castigo' => '-',
                            'mes' => str_pad($mesAsignacion, 2, '0', STR_PAD_LEFT),
                            'moneda' => $monedaFinal,
                            'capital_deuda' => $capitalDeuda,
                            'rango_deuda' => $rangoDeuda,
                            'total_saldo_vencido' => $capitalDeuda,
                            'total_saldo_diferido' => 0.00,
                            'desc_campaña_cancelacion' => '-',
                            'monto_campaña_cancelacion' => $montoCampCanc,
                            'monto_mix_cancelacion' => 0.00,
                            'rango_campaña_canc' => $rangoCampCanc,
                            'desc_campaña_cancelacion_supervisor' => '-',
                            'monto_campaña_cancelacion_supervisor' => $montoCampSuper,
                            'monto_mixto_super' => 0.00,
                            'rango_campaña_super' => $rangoCampSuper,
                            'altos' => $altos,
                            'mes_asignacion' => $mesAsignacion,
                            'año_asignacion' => $anioAsignacion,
                            'tipo_carga' => 'REAL',
                            'estado_cierre' => 'ABIERTO',
                            'estado' => 'Activo',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // 10. Mapeo Dinámico General (Evalúa si es Columna o Texto Fijo Manual)
                        foreach ($mapeo as $campoOficial => $columnaOBancoTexto) {
                            if (!empty($campoOficial) && !empty($columnaOBancoTexto) && !in_array($campoOficial, ['idcliente', 'rango_deuda', 'altos', 'rango_campaña_canc', 'rango_campaña_super', 'cartera','moneda'])) {
                                
                                if (array_key_exists($columnaOBancoTexto, $json)) {
                                    $valorRaw = trim((string)($json[$columnaOBancoTexto] ?? ''));
                                    
                                    if (str_contains($campoOficial, 'monto') || str_contains($campoOficial, 'saldo') || str_contains($campoOficial, 'pagos') || str_contains($campoOficial, 'mto_')) {
                                        $filaMatriz[$campoOficial] = floatval(str_replace([',', 'S/.', '$', ' '], '', $valorRaw));
                                    } else {
                                        $filaMatriz[$campoOficial] = $valorRaw;
                                    }
                                } else {
                                    // Si no existe como columna en el Excel, se asigna como VALOR TEXTO POR DEFECTO
                                    $filaMatriz[$campoOficial] = trim((string)$columnaOBancoTexto);
                                }
                            }
                        }

                        $filasMatriz[] = $filaMatriz;
                    }

                    if (!empty($filasMatriz)) {
                        // Deduplicación en memoria antes del upsert
                        $uniqueInChunk = [];
                        foreach ($filasMatriz as $row) {
                            $key = $row['idcliente'] . '_' . $row['mes_asignacion'] . '_' . $row['año_asignacion'];
                            $uniqueInChunk[$key] = $row;
                        }
                        $filasLimpias = array_values($uniqueInChunk);

                        $sampleRow = reset($filasLimpias);
                        $columnasActualizables = array_keys($sampleRow);
                        
                        $columnasActualizables = array_diff($columnasActualizables, ['idcliente', 'mes_asignacion', 'año_asignacion', 'created_at']);

                        DB::table('matriz_general')->upsert(
                            $filasLimpias,
                            ['idcliente', 'mes_asignacion', 'año_asignacion'],
                            array_values($columnasActualizables)
                        );
                        
                        $procesados += count($filasLimpias);
                    }
                });

            return response()->json([
                'message' => '¡Procesamiento y generación de matriz completado con éxito!',
                'registros_procesados' => $procesados,
                'lote' => $loteCartera
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al procesar la matriz: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Helper privado para rangos de deuda / campaña (Lógica idéntica a SQL Server CASE WHEN)
     */
    private function calcularRangoMonto($monto)
    {
        if ($monto < 100) return '0.[0 - 100]';
        if ($monto <= 500) return '1.[100 - 500]';
        if ($monto <= 1000) return '2.[500 - 1,000]';
        if ($monto <= 1500) return '3.[1,000 - 1,500]';
        if ($monto <= 2000) return '4.[1,500 - 2,000]';
        if ($monto <= 3000) return '5.[2,000 - 3,000]';
        if ($monto <= 4000) return '6.[3,000 - 4,000]';
        if ($monto <= 5000) return '7.[4,000 - 5,000]';
        if ($monto <= 10000) return '8.[5,000 - 10,000]';
        if ($monto <= 20000) return '9.[10,000 - 20,000]';
        return '10.[20,000 - A MAS]';
    }

    /**
     * 4. LISTAR LOTES IMPORTADOS
     */
    public function listarLotesImportados()
    {
        try {
            $lotes = DB::table('asignaciones_cartera')
                ->select('lote_cartera', 'periodo')
                ->groupBy('lote_cartera', 'periodo')
                ->orderBy(DB::raw('MAX(created_at)'), 'desc')
                ->get();

            return response()->json($lotes, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al listar los lotes importados.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 5. PREVISUALIZAR MATRIZ ANTES DE GUARDAR
     */
    public function previsualizarMatriz(Request $request)
    {
        $request->validate([
            'lote_cartera' => 'required|string',
            'periodo' => 'required|string|size:7',
            'mapeo' => 'required|array'
        ]);

        try {
            $lote = $request->lote_cartera;
            $periodo = $request->periodo;
            $mapeo = $request->mapeo;

            $muestras = DB::table('asignaciones_cartera')
                ->where('lote_cartera', $lote)
                ->where('periodo', $periodo)
                ->limit(5)
                ->get();

            $previewData = [];

            foreach ($muestras as $reg) {
                $json = json_decode($reg->datos_fila, true);
                $filaMapeada = [];

                foreach ($mapeo as $campoOficial => $columnaOBancoTexto) {
                    if (empty($columnaOBancoTexto)) {
                        $valorFinal = '';
                    } elseif (array_key_exists($columnaOBancoTexto, $json)) {
                        $valorFinal = $json[$columnaOBancoTexto] ?? '';
                    } else {
                        // Si no existe la columna en la fila, se muestra el texto literal fijado
                        $valorFinal = $columnaOBancoTexto;
                    }

                    if ($campoOficial === 'moneda') {
                        $valorFinal = $this->normalizarMoneda($valorFinal);
                    }

                    if (str_contains($campoOficial, 'capital') || str_contains($campoOficial, 'deuda') || str_contains($campoOficial, 'monto')) {
                        $valorFinal = is_numeric($valorFinal) ? number_format(floatval($valorFinal), 2) : $valorFinal;
                    }

                    $filaMapeada[$campoOficial] = $valorFinal;
                }

                $filaMapeada['periodo'] = $periodo;
                $previewData[] = $filaMapeada;
            }

            return response()->json([
                'preview' => $previewData,
                'columnas_mapeadas' => array_keys($mapeo),
                'mensaje' => 'Vista previa generada con éxito'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al generar la vista previa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 6. GUARDAR UNA NUEVA PLANTILLA DE MAPEO
     */
    public function guardarPlantillaMapeo(Request $request)
    {
        $request->validate([
            'nombre_plantilla' => 'required|string|max:100',
            'mapeo' => 'required|array'
        ]);

        try {
            DB::table('plantillas_mapeo')->updateOrInsert(
                ['nombre_plantilla' => trim($request->nombre_plantilla)],
                [
                    'mapeo_config' => json_encode($request->mapeo, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );

            return response()->json(['message' => '¡Plantilla de mapeo guardada con éxito!'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al guardar la plantilla: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 7. LISTAR PLANTILLAS GUARDADAS
     */
    public function listarPlantillasMapeo()
    {
        try {
            $plantillas = DB::table('plantillas_mapeo')
                ->select('id', 'nombre_plantilla', 'mapeo_config')
                ->orderBy('nombre_plantilla', 'asc')
                ->get();

            return response()->json($plantillas, 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al listar plantillas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 8. ELIMINAR PLANTILLA DE MAPEO
     */
    public function eliminarPlantillaMapeo($id)
    {
        try {
            DB::table('plantillas_mapeo')->where('id', $id)->delete();
            return response()->json(['message' => 'Plantilla eliminada con éxito.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar la plantilla: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 9. CARGAR PLANTILLA DESDE UN ARCHIVO EXCEL/CSV
     */
    public function cargarPlantillaExcel(Request $request)
    {
        $request->validate([
            'archivo_plantilla' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('archivo_plantilla');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['message' => 'El archivo de plantilla está vacío.'], 422);
            }

            // Omitir fila de cabecera
            array_shift($rows);

            $mapeoExtraido = [];
            foreach ($rows as $row) {
                $campoMatriz = strtolower(trim((string)($row['A'] ?? '')));
                $columnaBanco = trim((string)($row['B'] ?? ''));

                if (!empty($campoMatriz) && !empty($columnaBanco)) {
                    $mapeoExtraido[$campoMatriz] = $columnaBanco;
                }
            }

            return response()->json([
                'message' => '¡Plantilla Excel leída con éxito!',
                'mapeo' => $mapeoExtraido
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => 'Error al leer el archivo Excel: ' . $e->getMessage()], 500);
        }
    }

    private function normalizarMoneda($valorRaw)
    {
        if (is_null($valorRaw) || trim((string)$valorRaw) === '') {
            return 'PEN'; // Por defecto PEN si no viene la columna en el Excel
        }

        $val = strtoupper(trim((string)$valorRaw));

        // 1. Detección y Homologación a Soles (PEN)
        if (in_array($val, ['0', 'S/.', 'S/', 'SOLES', 'SOL', 'PEN', 'NUEVOS SOLES', 'NUEVO SOL'])) {
            return 'PEN';
        }

        // 2. Detección y Homologación a Dólares (USD)
        if (in_array($val, ['100', '101', '$', 'USD', 'DOLARES', 'DOLAR', 'US$'])) {
            return 'USD';
        }

        // 3. Detección y Homologación a Euros (EUR)
        if (in_array($val, ['EUR', 'EURO', 'EUROS', '€'])) {
            return 'EUR';
        }

        return $val; // Retorna el valor directo si ya viene en formato estándar
    }
}