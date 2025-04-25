<?php

namespace App\Http\Controllers;

use App\Services\DataImport\DataImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataImportController extends Controller
{
    protected $importService;

    public function __construct(DataImportService $importService)
    {
        $this->middleware('auth');
        $this->middleware('user.is.admin');
        $this->importService = $importService;
    }

    public function index()
    {
        return view('dataImport.index');
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv'
        ]);

        try {
            $file = $request->file('file');
            $filepath = $file->getRealPath();

            // Obtenir les colonnes du fichier
            $fileColumns = $this->importService->getFileColumns($filepath);
            
            // Trouver la meilleure table correspondante
            $suggestedTable = $this->importService->findBestMatchingTable($fileColumns);
            
            if (!$suggestedTable) {
                return back()
                    ->with('error', 'No matching table found for this CSV structure')
                    ->with('fileColumns', $fileColumns);
            }

            // Vérifier la cohérence des colonnes
            $consistency = $this->importService->checkColumnConsistency(
                $fileColumns, 
                $this->importService->getDatabaseSchema()[$suggestedTable['table']]
            );

            // Logger les informations
            Log::info("File analyzed: " . $file->getClientOriginalName());
            Log::info("Suggested table: " . $suggestedTable['table']);
            Log::info("Match score: " . $suggestedTable['score']);
            Log::info("Column matching:", $consistency['matching_columns']);

            // Stocker temporairement le fichier pour l'importation
            $tempPath = $file->store('temp');

            return back()
                ->with('suggestedTable', $suggestedTable)
                ->with('consistency', $consistency)
                ->with('fileColumns', $fileColumns)
                ->with('tempFile', $tempPath)
                ->with('fileName', $file->getClientOriginalName());

        } catch (\Exception $e) {
            Log::error("Analysis error: " . $e->getMessage());
            return back()->with('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'table' => 'required|string',
            'tempFile' => 'required|string'
        ]);

        try {
            $table = $request->input('table');
            $filepath = storage_path('app/' . $request->input('tempFile'));

            if (!file_exists($filepath)) {
                throw new \Exception("Temporary file not found. Please upload the file again.");
            }

            // Importer les données
            $result = $this->importService->importData($filepath, $table);

            // Nettoyer le fichier temporaire
            @unlink($filepath);

            return back()
                ->with('success', "Successfully imported {$result['imported']} records")
                ->with('file', $request->input('fileName'));

        } catch (\Exception $e) {
            Log::error("Import error: " . $e->getMessage());
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
