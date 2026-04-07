<?php
require __DIR__ . '/../packages/framework/vendor/autoload.php';

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Benchmark;

$results = [];

// Test 1: Arr::first() performance
$array = range(1, 10000);
$results['Arr::first (no callback)'] = Benchmark::measure(fn() => Arr::first($array), 1000);
$results['Arr::first (with callback)'] = Benchmark::measure(fn() => Arr::first($array, fn($v) => $v > 5000), 1000);

// Test 2: Arr::last() performance
$results['Arr::last (no callback)'] = Benchmark::measure(fn() => Arr::last($array), 1000);
$results['Arr::last (with callback)'] = Benchmark::measure(fn() => Arr::last($array, fn($v) => $v < 5000), 1000);

// Test 3: Collection operations
$collection = collect($array);
$results['Collection::first()'] = Benchmark::measure(fn() => $collection->first(), 1000);
$results['Collection::last()'] = Benchmark::measure(fn() => $collection->last(), 1000);
$results['Collection::filter->map->first'] = Benchmark::measure(
    fn() => $collection->filter(fn($v) => $v > 100)->map(fn($v) => $v * 2)->first(),
    100
);

// Test 4: String operations
$results['Str pipeline'] = Benchmark::measure(
    fn() => str('  Hello World  ')->trim()->lower()->slug(),
    1000
);

// Output results
echo "=== FLEDGE BASELINE BENCHMARK ===" . PHP_EOL;
echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('=', 50) . PHP_EOL;

foreach ($results as $name => $time) {
    printf("%-40s %.4f ms" . PHP_EOL, $name, $time);
}

// Save to JSON for comparison
file_put_contents(__DIR__ . '/benchmark-results.json', json_encode([
    'php_version' => PHP_VERSION,
    'date' => date('c'),
    'results' => $results,
], JSON_PRETTY_PRINT));

echo PHP_EOL . "Results saved to benchmark-results.json" . PHP_EOL;
