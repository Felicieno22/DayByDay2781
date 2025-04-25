<?php

namespace App\Http\Controllers;

use App\Services\csvImport\CsvImportService;
use Illuminate\Http\Request;

class CsvImportController extends Controller
{
    protected $csvImportService;

    public function __construct(CsvImportService $csvImportService)
    {
        $this->csvImportService = $csvImportService;
    }

    public function showImportForm()
    {
        return view('csv.import');
    }

    public function analyzeFile(Request $request)
    {
        try {
            if (!$request->hasFile('csv_file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun fichier sélectionné'
                ]);
            }

            $file = $request->file('csv_file');
            if ($file->getClientOriginalExtension() !== 'csv') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier doit être au format CSV'
                ]);
            }

            $analysis = $this->csvImportService->analyzeFile($file->getRealPath());

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => $analysis['table_matches']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage()
            ]);
        }
    }

    public function import(Request $request)
    {
        try {
            if (!$request->hasFile('csv_file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun fichier sélectionné'
                ]);
            }

            $file = $request->file('csv_file');
            if ($file->getClientOriginalExtension() !== 'csv') {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier doit être au format CSV'
                ]);
            }

            $result = $this->csvImportService->importData($file->getRealPath());

            return response()->json([
                'success' => true,
                'message' => 'Import réussi',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ]);
        }
    }
}
