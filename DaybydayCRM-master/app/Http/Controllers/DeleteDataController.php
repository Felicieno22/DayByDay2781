<?php

namespace App\Http\Controllers;

use App\Services\DeleteData\DeleteDataService; // Ensure correct namespace
use Illuminate\Http\Request;

class DeleteDataController extends Controller
{
    protected $deleteDataService;

    public function __construct(DeleteDataService $deleteDataService)
    {
        $this->deleteDataService = $deleteDataService;
    }

    public function resetDatabase(Request $request)
    {
        $tables = $request->input('tables', []);
        $this->deleteDataService->resetTables($tables);

        return redirect()->back()->with('flash_message', 'Database reset successfully!');
    }

    public function index()
    {
        $tables = $this->deleteDataService->getAllTables(); // Dynamically fetch tables
        return view('deleteData.index', compact('tables')); // Pass tables to the view
    }
}
