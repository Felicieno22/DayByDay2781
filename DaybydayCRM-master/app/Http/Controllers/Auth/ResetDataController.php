<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ResetData\ResetDataService;
use Illuminate\Support\Facades\Session;

class ResetDataController extends Controller
{
    protected $resetDataService;

    public function __construct(ResetDataService $resetDataService)
    {
        $this->middleware('user.is.admin');
        $this->resetDataService = $resetDataService;
    }

    /**
     * Affiche la page de réinitialisation des données
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('resetData.index');
    }

    /**
     * Réinitialise la base de données en préservant les admins
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset()
    {
        try {
            $this->resetDataService->reset();
            Session::flash('flash_message', 'Base de données réinitialisée avec succès. Les utilisateurs administrateurs ont été préservés.');
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            Session::flash('flash_message_warning', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
            return redirect()->back();
        }
    }
} 