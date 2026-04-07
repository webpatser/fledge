<?php

require __DIR__ . '/../packages/framework/vendor/autoload.php';

use Illuminate\Support\Benchmark;
use League\Uri\Uri as LeagueUri;
use Uri\Rfc3986\Uri as NativeUri;

$iterations = 1000;

// Test URIs
$simpleUri = 'https://example.com/path';
$complexUri = 'https://user:pass@api.example.com:8080/v1/users/123?status=active&sort=created_at#section';

echo "=== URI BENCHMARK: League vs Native PHP 8.5 ===" . PHP_EOL;
echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "Iterations per test: $iterations" . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;
echo sprintf("%-35s %12s %12s %10s", "Test", "League", "Native", "Diff") . PHP_EOL;
echo str_repeat('-', 70) . PHP_EOL;

$results = [];

// Test 1: Parse simple URI
$league = Benchmark::measure(fn() => LeagueUri::new($simpleUri), $iterations);
$native = Benchmark::measure(fn() => new NativeUri($simpleUri), $iterations);
$results['Parse simple URI'] = ['league' => $league, 'native' => $native];
printResult('Parse simple URI', $league, $native);

// Test 2: Parse complex URI
$league = Benchmark::measure(fn() => LeagueUri::new($complexUri), $iterations);
$native = Benchmark::measure(fn() => new NativeUri($complexUri), $iterations);
$results['Parse complex URI'] = ['league' => $league, 'native' => $native];
printResult('Parse complex URI', $league, $native);

// Test 3: Get scheme
$leagueObj = LeagueUri::new($complexUri);
$nativeObj = new NativeUri($complexUri);
$league = Benchmark::measure(fn() => $leagueObj->getScheme(), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->getScheme(), $iterations);
$results['Get scheme'] = ['league' => $league, 'native' => $native];
printResult('Get scheme', $league, $native);

// Test 4: Get host
$league = Benchmark::measure(fn() => $leagueObj->getHost(), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->getHost(), $iterations);
$results['Get host'] = ['league' => $league, 'native' => $native];
printResult('Get host', $league, $native);

// Test 5: Get path
$league = Benchmark::measure(fn() => $leagueObj->getPath(), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->getPath(), $iterations);
$results['Get path'] = ['league' => $league, 'native' => $native];
printResult('Get path', $league, $native);

// Test 6: Get query
$league = Benchmark::measure(fn() => $leagueObj->getQuery(), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->getQuery(), $iterations);
$results['Get query'] = ['league' => $league, 'native' => $native];
printResult('Get query', $league, $native);

// Test 7: Get port
$league = Benchmark::measure(fn() => $leagueObj->getPort(), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->getPort(), $iterations);
$results['Get port'] = ['league' => $league, 'native' => $native];
printResult('Get port', $league, $native);

// Test 8: withScheme (immutable)
$league = Benchmark::measure(fn() => $leagueObj->withScheme('http'), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->withScheme('http'), $iterations);
$results['withScheme'] = ['league' => $league, 'native' => $native];
printResult('withScheme', $league, $native);

// Test 9: withHost (immutable)
$league = Benchmark::measure(fn() => $leagueObj->withHost('new.example.com'), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->withHost('new.example.com'), $iterations);
$results['withHost'] = ['league' => $league, 'native' => $native];
printResult('withHost', $league, $native);

// Test 10: withPath (immutable)
$league = Benchmark::measure(fn() => $leagueObj->withPath('/new/path'), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->withPath('/new/path'), $iterations);
$results['withPath'] = ['league' => $league, 'native' => $native];
printResult('withPath', $league, $native);

// Test 11: withQuery (immutable)
$league = Benchmark::measure(fn() => $leagueObj->withQuery('foo=bar'), $iterations);
$native = Benchmark::measure(fn() => $nativeObj->withQuery('foo=bar'), $iterations);
$results['withQuery'] = ['league' => $league, 'native' => $native];
printResult('withQuery', $league, $native);

// Test 12: toString
$league = Benchmark::measure(fn() => (string) $leagueObj, $iterations);
$native = Benchmark::measure(fn() => $nativeObj->toString(), $iterations);
$results['toString'] = ['league' => $league, 'native' => $native];
printResult('toString', $league, $native);

// Test 13: Full chain - parse, modify, stringify
$league = Benchmark::measure(fn() => (string) LeagueUri::new($simpleUri)->withPath('/api')->withQuery('v=1'), 500);
$native = Benchmark::measure(fn() => (new NativeUri($simpleUri))->withPath('/api')->withQuery('v=1')->toString(), 500);
$results['Parse+modify+string'] = ['league' => $league, 'native' => $native];
printResult('Parse+modify+string', $league, $native);

echo str_repeat('=', 70) . PHP_EOL;

// Calculate totals
$leagueTotal = array_sum(array_column($results, 'league'));
$nativeTotal = array_sum(array_column($results, 'native'));
printResult('TOTAL', $leagueTotal, $nativeTotal);

// Save results
file_put_contents(__DIR__ . '/uri-compare-results.json', json_encode([
    'php_version' => PHP_VERSION,
    'date' => date('c'),
    'iterations' => $iterations,
    'results' => $results,
    'totals' => ['league' => $leagueTotal, 'native' => $nativeTotal],
], JSON_PRETTY_PRINT));

echo PHP_EOL . "Results saved to uri-compare-results.json" . PHP_EOL;

function printResult(string $name, float $league, float $native): void {
    $diff = (($native - $league) / $league) * 100;
    $diffStr = $diff < 0 ? sprintf("%.1f%% faster", abs($diff)) : sprintf("+%.1f%%", $diff);
    $winner = $diff < 0 ? '✓' : '';
    printf("%-35s %10.4f ms %10.4f ms %10s %s" . PHP_EOL, $name, $league, $native, $diffStr, $winner);
}
