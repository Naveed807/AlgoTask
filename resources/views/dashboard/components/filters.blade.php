<!-- Filters Component -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Filters</h3>

    <div class="space-y-4">
        <!-- District Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                District
            </label>
            <select id="filter-district" data-filter="district" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Districts</option>
                @foreach($filter_options['districts'] ?? [] as $district)
                    <option value="{{ $district }}">{{ $district }}</option>
                @endforeach
            </select>
        </div>

        <!-- Tehsil Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Tehsil
            </label>
            <select id="filter-tehsil" data-filter="tehsil" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Tehsils</option>
                @foreach($filter_options['tehsils'] ?? [] as $tehsil)
                    <option value="{{ $tehsil }}">{{ $tehsil }}</option>
                @endforeach
            </select>
        </div>

        <!-- Partner Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Partner
            </label>
            <select id="filter-partner" data-filter="partner" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Partners</option>
                @foreach($filter_options['partners'] ?? [] as $partner)
                    <option value="{{ $partner }}">{{ $partner }}</option>
                @endforeach
            </select>
        </div>

        <!-- Bank Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Bank
            </label>
            <select id="filter-bank" data-filter="bank" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Banks</option>
                @foreach($filter_options['banks'] ?? [] as $bank)
                    <option value="{{ $bank }}">{{ $bank }}</option>
                @endforeach
            </select>
        </div>

        <!-- Severity Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Severity
            </label>
            <select id="filter-severity" data-filter="severity" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Severities</option>
                @foreach($filter_options['severities'] ?? [] as $severity)
                    <option value="{{ $severity }}">{{ ucfirst($severity) }}</option>
                @endforeach
            </select>
        </div>

        <!-- Stage Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Stage
            </label>
            <select id="filter-stage" data-filter="stage" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Stages</option>
                @foreach($filter_options['stages'] ?? [] as $stage)
                    <option value="{{ $stage }}">{{ $stage }}</option>
                @endforeach
            </select>
        </div>

        <!-- Clear Filters Button -->
        <button onclick="document.querySelectorAll('[data-filter]').forEach(el => el.value = '')" class="w-full mt-6 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 px-4 py-2 rounded-lg font-medium transition">
            Clear All
        </button>
    </div>
</div>
