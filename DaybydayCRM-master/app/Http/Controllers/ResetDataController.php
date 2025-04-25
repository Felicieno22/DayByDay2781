<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\resetData\ResetDataService;
use Illuminate\Support\Facades\Log;

class ResetDataController extends Controller
{
    protected $resetDataService;

    public function __construct(ResetDataService $resetDataService)
    {
        $this->resetDataService = $resetDataService;
    }

    /**
     * Reset database while preserving admin users
     */
    public function resetDatabase()
    {
        try {
            $results = $this->resetDataService->resetDatabase();
            
            if ($results['success']) {
                $message = sprintf(
                    'Database reset completed successfully. Preserved %d admin users.',
                    count($results['preserved']['admins'])
                );
            } else {
                $message = 'Database reset failed: ' . implode(', ', $results['errors']);
            }

            return response()->json([
                'success' => $results['success'],
                'message' => $message,
                'data' => [
                    'preserved_admins' => $results['preserved']['admins'],
                    'errors' => $results['errors']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error resetting database: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error resetting database: ' . $e->getMessage()
            ], 500);
        }
    }
}
