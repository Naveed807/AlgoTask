<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
    }

    /**
     * Display the dashboard page (Blade view).
     */
    public function index(Request $request)
    {
        // Get filters from request
        $filters = $request->query();

        // Get dashboard data
        $data = $this->dashboardService->getDashboard($filters);

        return view('dashboard.index', $data);
    }

    /**
     * Get dashboard data via API (JSON response).
     */
    public function api(Request $request)
    {
        // Get filters from request
        $filters = $request->query();

        // Get dashboard data
        $data = $this->dashboardService->getDashboard($filters);

        return response()->json($data);
    }

    /**
     * Get delayed cases with pagination.
     */
    public function cases(Request $request)
    {
        $filters = $request->query();
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 25);

        $cases = $this->dashboardService->getDelayedCases($filters, $page, $perPage);

        return response()->json($cases);
    }

    /**
     * Get filter options.
     */
    public function filterOptions()
    {
        $options = $this->dashboardService->getFilterOptions();

        return response()->json($options);
    }

    /**
     * Search cases.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $results = $this->dashboardService->search(
            $request->string('q'),
            $request->integer('page', 1),
            $request->integer('per_page', 25)
        );

        return response()->json($results);
    }
}
