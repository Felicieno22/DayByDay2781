<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ResetDataService;
use Illuminate\Http\JsonResponse;

class ResetDataController extends Controller
{
    protected $resetDataService;

    public function __construct(ResetDataService $resetDataService)
    {
        $this->middleware('user.is.admin');
        $this->resetDataService = $resetDataService;
    }

    /**
     * Reset all data except admin users
     *
     * @return JsonResponse
     */
    public function reset(): JsonResponse
    {
        try {
            $this->resetDataService->reset();
            return response()->json(['message' => 'Toutes les données ont été réinitialisées avec succès, sauf les utilisateurs administrateurs.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue lors de la réinitialisation des données: ' . $e->getMessage()], 500);
        }
    }
} 