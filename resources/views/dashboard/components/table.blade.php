<!-- Cases Table Component -->
<div class="mt-8">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Delayed Cases</h3>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable('case_uuid')">
                            Case UUID
                            <span class="text-gray-400">⇅</span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Applicant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            CNIC
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            District
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Partner
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable('stage')">
                            Stage
                            <span class="text-gray-400">⇅</span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable('days_waiting')">
                            Days Waiting
                            <span class="text-gray-400">⇅</span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" onclick="sortTable('severity')">
                            Severity
                            <span class="text-gray-400">⇅</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="cases-tbody" class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($delayed_cases['data'] ?? [] as $case)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                            {{ $case['case_uuid'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $case['applicant_name'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $case['applicant_cnic'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $case['district'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $case['partner_name'] ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-medium">
                                {{ $case['stage_label'] ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $case['days_waiting'] ?? 0 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="{{ getSeverityBadgeClass($case['severity'] ?? 'green') }} px-2 py-1 rounded text-xs font-medium">
                                {{ strtoupper($case['severity'] ?? 'GREEN') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">
                            No delayed cases found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ count($delayed_cases['data'] ?? []) }} of {{ $delayed_cases['total'] ?? 0 }} cases
            </div>
            <div class="flex gap-2" id="pagination-buttons">
                @if($delayed_cases['page'] > 1)
                <button onclick="loadPage({{ $delayed_cases['page'] - 1 }})" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded text-sm font-medium transition">
                    ← Previous
                </button>
                @endif

                @foreach(range(max(1, ($delayed_cases['page'] - 2)), min($delayed_cases['total_pages'], ($delayed_cases['page'] + 2))) as $pageNum)
                    @if($pageNum == $delayed_cases['page'])
                    <button class="px-3 py-1 bg-blue-600 text-white rounded text-sm font-medium">
                        {{ $pageNum }}
                    </button>
                    @else
                    <button onclick="loadPage({{ $pageNum }})" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded text-sm font-medium transition">
                        {{ $pageNum }}
                    </button>
                    @endif
                @endforeach

                @if($delayed_cases['page'] < $delayed_cases['total_pages'])
                <button onclick="loadPage({{ $delayed_cases['page'] + 1 }})" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded text-sm font-medium transition">
                    Next →
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@php
function getSeverityBadgeClass($severity) {
    $classes = [
        'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'amber' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    ];
    return $classes[strtolower($severity)] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
}
@endphp
