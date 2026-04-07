<?php

require __DIR__ . '/../packages/framework/vendor/autoload.php';

use Illuminate\Support\Benchmark;
use Illuminate\Support\Uri;

$results = [];

// Test URIs
$simpleUri = 'https://example.com/path';
$complexUri = 'https://user:pass@api.example.com:8080/v1/users/123?filter[status]=active&sort=-created_at&include=posts,comments#section';
$queryUri = 'https://example.com/search?q=hello+world&page=1&limit=20';

// Test 1: Parse simple URI
$results['Parse simple URI'] = Benchmark::measure(
    fn() => Uri::of($simpleUri),
    1000
);

// Test 2: Parse complex URI
$results['Parse complex URI'] = Benchmark::measure(
    fn() => Uri::of($complexUri),
    1000
);

// Test 3: Get components from parsed URI
$uri = Uri::of($complexUri);
$results['Get scheme'] = Benchmark::measure(fn() => $uri->scheme(), 1000);
$results['Get host'] = Benchmark::measure(fn() => $uri->host(), 1000);
$results['Get path'] = Benchmark::measure(fn() => $uri->path(), 1000);
$results['Get query'] = Benchmark::measure(fn() => $uri->query()->all(), 1000);

// Test 4: Modify URI (immutable operations)
$results['withScheme'] = Benchmark::measure(
    fn() => $uri->withScheme('http'),
    1000
);

$results['withHost'] = Benchmark::measure(
    fn() => $uri->withHost('new.example.com'),
    1000
);

$results['withPath'] = Benchmark::measure(
    fn() => $uri->withPath('/new/path'),
    1000
);

$results['withQuery (replace)'] = Benchmark::measure(
    fn() => $uri->withQuery(['foo' => 'bar']),
    1000
);

// Test 5: Query string operations
$results['Query all()'] = Benchmark::measure(
    fn() => $uri->query()->all(),
    1000
);

$results['Query get()'] = Benchmark::measure(
    fn() => $uri->query()->get('filter'),
    1000
);

$results['Query decode()'] = Benchmark::measure(
    fn() => $uri->query()->decode(),
    1000
);

// Test 6: Full URI reconstruction
$results['toString'] = Benchmark::measure(
    fn() => (string) $uri,
    1000
);

// Test 7: Create and modify chain
$results['Create + modify chain'] = Benchmark::measure(
    fn() => Uri::of('https://example.com')
        ->withPath('/api/v1/users')
        ->withQuery(['page' => 1, 'limit' => 50])
        ->withFragment('results'),
    500
);

// Test 8: Parse many URIs
$uris = [
    'https://example.com',
    'https://api.github.com/repos/laravel/framework',
    'https://packagist.org/packages/laravel/framework',
    'ftp://files.example.com/downloads/file.zip',
    'mailto:test@example.com',
];
$results['Parse 5 URIs'] = Benchmark::measure(
    fn() => array_map(fn($u) => Uri::of($u), $uris),
    200
);

// Output results
echo "=== URI BENCHMARK ===" . PHP_EOL;
echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('=', 50) . PHP_EOL;

foreach ($results as $name => $time) {
    printf("%-30s %.4f ms" . PHP_EOL, $name, $time);
}

// Calculate total
$total = array_sum($results);
echo str_repeat('-', 50) . PHP_EOL;
printf("%-30s %.4f ms" . PHP_EOL, "TOTAL", $total);

// Save to JSON
file_put_contents(__DIR__ . '/uri-benchmark-results.json', json_encode([
    'php_version' => PHP_VERSION,
    'date' => date('c'),
    'results' => $results,
    'total' => $total,
], JSON_PRETTY_PRINT));

echo PHP_EOL . "Results saved to uri-benchmark-results.json" . PHP_EOL;
