<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                📊 Delayed Cases Analytics Dashboard
            </h2>
            <div class="flex gap-2">
                <button id="export-csv-btn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export CSV
                </button>
                <button id="refresh-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- KPI Cards --}}
            @include('dashboard.components.kpi-cards', ['kpis' => $kpis])

            {{-- Filters & Search --}}
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-1">
                    @include('dashboard.components.filters', ['filter_options' => $filter_options])
                </div>

                <div class="lg:col-span-3">
                    @include('dashboard.components.search')
                </div>
            </div>

            {{-- Charts --}}
            @if($charts)
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                @include('dashboard.components.charts', ['charts' => $charts])
            </div>
            @endif

            {{-- Cases Table --}}
            @include('dashboard.components.table', ['delayed_cases' => $delayed_cases])

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        // Global state
        let chartInstances = {
            severity: null,
            stage: null,
            district: null
        };
        let currentPage = 1;
        let currentSort = { field: 'stage_start_date', order: 'asc' };
        let paginationData = {
            total: 0,
            per_page: 10,
            total_pages: 1,
            page: 1
        };

        // Utility function to get severity class
        function getDashboardSeverityClass(severity) {
            const classes = {
                'green': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'yellow': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'amber': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                'red': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            };
            return classes[severity?.toLowerCase()] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        }

        // Update table rows
        function updateTableRows(cases) {
            const tbody = document.getElementById('cases-tbody');
            
            if (!tbody) {
                console.error('cases-tbody element not found');
                return;
            }
            
            if (!cases || cases.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">No cases found</td></tr>';
                return;
            }

            tbody.innerHTML = cases.map(caseData => `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">${caseData.case_uuid || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">${caseData.applicant_name || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${caseData.applicant_cnic || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${caseData.district || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${caseData.partner_name || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-medium">${caseData.stage_label || '-'}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">${caseData.days_waiting || 0}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 py-1 rounded text-xs font-medium ${getDashboardSeverityClass(caseData.severity)}">${(caseData.severity || 'green').toUpperCase()}</span></td>
                </tr>
            `).join('');
        }

        // Render pagination UI
        function renderPagination(pagination) {
            const container = document.getElementById('pagination-buttons');
            if (!container) return;

            paginationData = pagination;
            let html = '';

            // Previous button
            if (pagination.page > 1) {
                html += `<button onclick="loadPage(${pagination.page - 1})" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded text-sm font-medium transition">
                    ← Previous
                </button>`;
            }

            // Page numbers
            const startPage = Math.max(1, pagination.page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                if (i === pagination.page) {
                    html += `<button class="px-3 py-1 bg-blue-600 text-white rounded text-sm font-medium">
                        ${i}
                    </button>`;
                } else {
                    html += `<button onclick="loadPage(${i})" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded text-sm font-medium transition">
                        ${i}
                    </button>`;
                }
            }

            // Next button
            if (pagination.page < pagination.total_pages) {
                html += `<button onclick="loadPage(${pagination.page + 1})" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded text-sm font-medium transition">
                    Next →
                </button>`;
            }

            container.innerHTML = html;

            // Update showing count
            const paginationContainer = document.getElementById('pagination-container');
            if (paginationContainer) {
                const showingDiv = paginationContainer.querySelector('.text-sm');
                if (showingDiv) {
                    showingDiv.textContent = `Showing ${pagination.per_page} of ${pagination.total} cases`;
                }
            }
        }
        async function loadPage(page) {
            const filters = new URLSearchParams();
            
            // Get current filters
            const district = document.getElementById('filter-district')?.value;
            const tehsil = document.getElementById('filter-tehsil')?.value;
            const partner = document.getElementById('filter-partner')?.value;
            const bank = document.getElementById('filter-bank')?.value;
            const severity = document.getElementById('filter-severity')?.value;
            const stage = document.getElementById('filter-stage')?.value;

            if (district) filters.append('district', district);
            if (tehsil) filters.append('tehsil', tehsil);
            if (partner) filters.append('partner', partner);
            if (bank) filters.append('bank', bank);
            if (severity) filters.append('severity', severity);
            if (stage) filters.append('stage', stage);

            filters.append('page', page);
            filters.append('sort_by', currentSort.field);
            filters.append('sort_order', currentSort.order);

            const url = `/api/dashboard/cases?${filters}`;

            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                
                const data = await response.json();
                
                updateTableRows(data.data || []);
                renderPagination({
                    total: data.total || 0,
                    per_page: data.per_page || 10,
                    total_pages: data.total_pages || 1,
                    page: data.page || page
                });
                currentPage = page;
            } catch (error) {
                console.error('Failed to load page:', error);
                alert('Failed to load cases. Check console for details.');
            }
        }

        // Sort table
        function sortTable(field) {
            // Map field names
            const fieldMapping = {
                'case_uuid': 'case_uuid',
                'stage': 'stage_label',
                'days_waiting': 'days_waiting',
                'severity': 'severity',
            };

            const mappedField = fieldMapping[field] || field;

            if (currentSort.field === mappedField) {
                currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.field = mappedField;
                currentSort.order = 'asc';
            }
            loadPage(1);
        }

        // Auto-refresh data on filter/search changes
        let filterTimeout;
        const refreshDashboard = async (skipCharts = false) => {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(async () => {
                const filters = new URLSearchParams();
                
                // Get filter values
                const district = document.getElementById('filter-district')?.value;
                const tehsil = document.getElementById('filter-tehsil')?.value;
                const partner = document.getElementById('filter-partner')?.value;
                const bank = document.getElementById('filter-bank')?.value;
                const severity = document.getElementById('filter-severity')?.value;
                const stage = document.getElementById('filter-stage')?.value;

                if (district) filters.append('district', district);
                if (tehsil) filters.append('tehsil', tehsil);
                if (partner) filters.append('partner', partner);
                if (bank) filters.append('bank', bank);
                if (severity) filters.append('severity', severity);
                if (stage) filters.append('stage', stage);

                const url = `/api/dashboard?${filters}`;

                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    
                    const data = await response.json();
                    
                    // Update KPIs
                    if (data.kpis) updateKPIs(data.kpis);
                    
                    // Update charts
                    if (data.charts && !skipCharts) updateCharts(data.charts);
                    
                    // Update table (reload page 1)
                    if (data.delayed_cases) {
                        updateTableRows(data.delayed_cases.data || []);
                        renderPagination({
                            total: data.delayed_cases.total || 0,
                            per_page: data.delayed_cases.per_page || 10,
                            total_pages: data.delayed_cases.total_pages || 1,
                            page: 1
                        });
                        currentPage = 1;
                    }
                } catch (error) {
                    console.error('Dashboard update failed:', error);
                }
            }, 500);
        };

        function updateKPIs(kpis) {
            document.getElementById('total-cases').textContent = kpis.total || 0;
            document.getElementById('green-cases').textContent = kpis.green || 0;
            document.getElementById('yellow-cases').textContent = kpis.yellow || 0;
            document.getElementById('amber-cases').textContent = kpis.amber || 0;
            document.getElementById('red-cases').textContent = kpis.red || 0;
        }

        function updateCharts(charts) {
            
            // Wait for Chart.js to be available
            if (typeof Chart === 'undefined') {
                console.error('Chart.js not loaded yet');
                return;
            }
            
            try {
                // Update Severity Distribution Chart
                if (charts.severity_distribution) {
                    const ctx = document.getElementById('severity-chart');
                    if (!ctx) {
                        console.error('severity-chart canvas element not found');
                    } else {
                        if (chartInstances.severity) {
                            chartInstances.severity.destroy();
                        }
                        
                        chartInstances.severity = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: charts.severity_distribution.labels || [],
                                datasets: [{
                                    data: charts.severity_distribution.data || [],
                                    backgroundColor: [
                                        '#10B981', // Green
                                        '#FBBF24', // Yellow
                                        '#F97316', // Amber
                                        '#EF4444'  // Red
                                    ],
                                    borderColor: '#fff',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { position: 'bottom' }
                                }
                            }
                        });
                    }
                }

                // Update Stage Distribution Chart
                if (charts.stage_distribution) {
                    const ctx = document.getElementById('stage-chart');
                    if (!ctx) {
                        console.error('stage-chart canvas element not found');
                    } else {
                        if (chartInstances.stage) {
                            chartInstances.stage.destroy();
                        }
                        
                        chartInstances.stage = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: charts.stage_distribution.labels || [],
                                datasets: [{
                                    label: 'Cases',
                                    data: charts.stage_distribution.data || [],
                                    backgroundColor: '#3B82F6',
                                    borderColor: '#1F2937',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                plugins: { legend: { position: 'bottom' } }
                            }
                        });
                    }
                }

                // Update District Distribution Chart
                if (charts.district_distribution) {
                    const ctx = document.getElementById('district-chart');
                    if (!ctx) {
                        console.error('district-chart canvas element not found');
                    } else {
                        if (chartInstances.district) {
                            chartInstances.district.destroy();
                        }
                        
                        chartInstances.district = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: charts.district_distribution.labels || [],
                                datasets: [{
                                    label: 'Cases',
                                    data: charts.district_distribution.data || [],
                                    backgroundColor: '#8B5CF6',
                                    borderColor: '#1F2937',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                indexAxis: 'x',
                                plugins: { legend: { position: 'bottom' } }
                            }
                        });
                    }
                }
            } catch (error) {
                console.error('Error updating charts:', error);
            }
        }

        // Initialize dashboard when DOM is ready and Chart.js is loaded
        function initDashboard() {
            
            // Initialize charts with data from page load
            if (window.initialChartData) {
                updateCharts(window.initialChartData);
            }
            
            // Attach event listeners to filters
            document.querySelectorAll('[data-filter]').forEach(el => {
                el.addEventListener('change', () => {
                    console.log(el.id, 'changed to', el.value);
                    refreshDashboard();
                });
            });

            // Attach refresh button listener
            const refreshBtn = document.getElementById('refresh-btn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    console.log('Refresh button clicked');
                    refreshDashboard(false);
                });
            } else {
                console.error('refresh-btn not found');
            }

            // Attach export CSV button listener
            const exportBtn = document.getElementById('export-csv-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    console.log('Export CSV button clicked');
                    exportToCSV();
                });
            } else {
                console.error('export-csv-btn not found');
            }
        }

        // Export filtered data to CSV
        function exportToCSV() {
            const filters = new URLSearchParams();
            
            // Get current filters
            const district = document.getElementById('filter-district')?.value;
            const tehsil = document.getElementById('filter-tehsil')?.value;
            const partner = document.getElementById('filter-partner')?.value;
            const bank = document.getElementById('filter-bank')?.value;
            const severity = document.getElementById('filter-severity')?.value;
            const stage = document.getElementById('filter-stage')?.value;

            if (district) filters.append('district', district);
            if (tehsil) filters.append('tehsil', tehsil);
            if (partner) filters.append('partner', partner);
            if (bank) filters.append('bank', bank);
            if (severity) filters.append('severity', severity);
            if (stage) filters.append('stage', stage);

            filters.append('sort_by', currentSort.field);
            filters.append('sort_order', currentSort.order);

            const url = `/api/dashboard/export-csv?${filters}`;
            console.log('Exporting CSV:', url);

            // Trigger download
            window.location.href = url;
        }

        // Wait for Chart.js to load and DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboard);
        } else {
            initDashboard();
        }
    </script>
    @endpush
</x-app-layout>
