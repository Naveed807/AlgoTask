<!-- Search Component -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Search Cases</h3>

    <div class="flex gap-2">
        <div class="flex-1 relative">
            <input 
                type="text" 
                id="search-input"
                placeholder="Search by applicant name, CNIC, or case UUID..." 
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <div id="search-spinner" class="hidden absolute right-3 top-2.5">
                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <button 
            id="search-btn"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            Search
        </button>
    </div>

    <!-- Search Results -->
    <div id="search-results" class="mt-4 hidden">
        <div id="search-results-content"></div>
    </div>
</div>

@push('scripts')
<script>
    let searchTimeout;
    
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const searchResults = document.getElementById('search-results');
    const searchResultsContent = document.getElementById('search-results-content');
    const searchSpinner = document.getElementById('search-spinner');

    const performSearch = async () => {
        const query = searchInput.value.trim();
        
        if (query.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }

        searchSpinner.classList.remove('hidden');
        
        try {
            const response = await fetch(`/api/dashboard/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            displaySearchResults(data);
        } catch (error) {
            console.error('Search failed:', error);
            searchResultsContent.innerHTML = '<p class="text-red-600">Search failed. Please try again.</p>';
        } finally {
            searchSpinner.classList.add('hidden');
        }
    };

    const displaySearchResults = (data) => {
        if (data.data.length === 0) {
            searchResultsContent.innerHTML = '<p class="text-gray-600 dark:text-gray-400">No cases found matching your search.</p>';
            searchResults.classList.remove('hidden');
            return;
        }

        let html = `<div class="text-sm text-gray-600 dark:text-gray-400 mb-3">Found ${data.total} result(s)</div>`;
        html += '<div class="space-y-2 max-h-64 overflow-y-auto">';

        data.data.forEach(caseData => {
            // Use getDashboardSeverityClass from main dashboard script
            const severityClass = (severity) => {
                const classes = {
                    'green': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'yellow': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'amber': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                    'red': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                };
                return classes[severity?.toLowerCase()] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
            };
            
            html += `
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-gray-100">${caseData.case_uuid}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">${caseData.applicant_name}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                CNIC: ${caseData.applicant_cnic} | District: ${caseData.district} | Partner: ${caseData.partner_name}
                            </div>
                        </div>
                        <div class="text-right ml-2">
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded ${severityClass(caseData.severity)}">
                                ${caseData.severity.toUpperCase()}
                            </span>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">${caseData.days_waiting} days</div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        searchResultsContent.innerHTML = html;
        searchResults.classList.remove('hidden');
    };

    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') performSearch();
        else {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        }
    });
</script>
@endpush
