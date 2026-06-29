<!-- Charts Component -->
@if($charts)
<div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Severity Distribution</h3>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <canvas id="severity-chart" height="80"></canvas>
    </div>
</div>

<div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Stage Distribution</h3>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <canvas id="stage-chart" height="80"></canvas>
    </div>
</div>

<div>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top 10 Districts</h3>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <canvas id="district-chart" height="80"></canvas>
    </div>
</div>

@push('scripts')
<script>
    // Charts are initialized and updated by the main dashboard script
    // This pushes the initial chart data to window for the dashboard to use
    window.initialChartData = {!! json_encode($charts) !!};
</script>
@endpush
@endif
