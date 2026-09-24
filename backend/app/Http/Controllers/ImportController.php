<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class ImportController extends Controller
{
    public function importarAsignacion(Request $request)
    {
        // 1. Validar archivo y nombre del lote
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt,xlsx,xls',
            'nombre_cartera' => 'required|string',
            'periodo' => 'required|string|size:7'
        ]);

        $file = $request->file('archivo');
        $path = $file->getRealPath();
        $loteCartera = trim($request->nombre_cartera);
        $periodo = trim($request->periodo);

        try {
            // 2. Cargar el archivo usando PhpSpreadsheet (soporta Excel y CSV de forma nativa)
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['message' => 'El archivo está vacío o no contiene datos válidos.'], 422);
            }

            // 3. Extraer la cabecera (primera fila)
            $headerRow = array_shift($rows);
            // Limpiar valores nulos o vacíos de la cabecera
            $headers = array_map(function($val) {
                return trim((string)$val);
            }, $headerRow);

            $filasParaInsertar = [];
            $sumaDeudaTotal = 0;
            $columnaMontoKey = null;
    

            // Identificar si alguna columna es de deuda/saldo para las métricas
            foreach ($headers as $key => $hName) {
                $lower = strtolower($hName);
                if (str_contains($lower, 'deuda') || str_contains($lower, 'saldo') || str_contains($lower, 'monto')) {
                    $columnaMontoKey = $key;
                    break;
                }
            }

            // 4. Procesar cada fila manteniendo la estructura original exacta
            foreach ($rows as $row) {
                // Omitir filas totalmente vacías
                if (count(array_filter($row)) == 0) continue;

                $rowDataAssoc = [];
                foreach ($headers as $index => $headerName) {
                    if (!empty($headerName)) {
                        $rowDataAssoc[$headerName] = isset($row[$index]) ? trim((string)$row[$index]) : null;
                    }
                }

                if (!empty($rowDataAssoc)) {
                    // Sumar deuda si se detectó la columna
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

            // 5. Inserción masiva segura en la tabla única
            DB::beginTransaction();
            foreach (array_chunk($filasParaInsertar, 500) as $chunk) {
                DB::table('asignaciones_cartera')->insert($chunk);
            }
            DB::commit();

            // 6. Retornar métricas claras al frontend
            return response()->json([
                'message' => '¡Cartera importada con éxito en la tabla central!',
                'stats' => [
                    'tabla_destino' => 'asignaciones_cartera (Lote: ' . $loteCartera . ')',
                    'total_cuentas' => count($filasParaInsertar),
                    'total_columnas' => count(array_filter($headers)),
                    'columnas_originales' => array_values(array_filter($headers)),
                    'monto_total_cartera' => $sumaDeudaTotal > 0 ? number_format($sumaDeudaTotal, 2) : 'No detectado / No aplicable',
                    'archivo' => $file->getClientOriginalName()
                ]
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar el archivo Excel.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}