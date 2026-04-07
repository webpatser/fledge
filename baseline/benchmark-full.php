<?php
/**
 * Fledge Comprehensive Benchmark
 * Tests all Laravel Illuminate components for PHP 8.5 optimization baseline
 */

require __DIR__ . '/../packages/framework/vendor/autoload.php';

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Fluent;
use Illuminate\Support\Stringable;
use Illuminate\Support\Carbon;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Router;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Encryption\Encrypter;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Hashing\Argon2IdHasher;
use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

$results = [];
$iterations = 1000;
$smallIterations = 100;

echo "=== FLEDGE COMPREHENSIVE BENCHMARK ===" . PHP_EOL;
echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "Iterations: $iterations (small: $smallIterations)" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL . PHP_EOL;

// =============================================================================
// SUPPORT - Arr
// =============================================================================
echo "## Support/Arr" . PHP_EOL;
$array = range(1, 10000);
$assocArray = array_combine(range('a', 'z'), range(1, 26));

$results['Arr::first (no callback)'] = Benchmark::measure(fn() => Arr::first($array), $iterations);
$results['Arr::first (with callback)'] = Benchmark::measure(fn() => Arr::first($array, fn($v) => $v > 5000), $iterations);
$results['Arr::last (no callback)'] = Benchmark::measure(fn() => Arr::last($array), $iterations);
$results['Arr::last (with callback)'] = Benchmark::measure(fn() => Arr::last($array, fn($v) => $v < 5000), $iterations);
$results['Arr::get (dot notation)'] = Benchmark::measure(fn() => Arr::get(['a' => ['b' => ['c' => 1]]], 'a.b.c'), $iterations);
$results['Arr::set (dot notation)'] = Benchmark::measure(fn() => Arr::set($assocArray, 'x.y.z', 'value'), $iterations);
$results['Arr::has (dot notation)'] = Benchmark::measure(fn() => Arr::has(['a' => ['b' => 1]], 'a.b'), $iterations);
$results['Arr::only'] = Benchmark::measure(fn() => Arr::only($assocArray, ['a', 'b', 'c']), $iterations);
$results['Arr::except'] = Benchmark::measure(fn() => Arr::except($assocArray, ['a', 'b', 'c']), $iterations);
$results['Arr::flatten'] = Benchmark::measure(fn() => Arr::flatten([1, [2, [3, [4]]]]), $iterations);
$results['Arr::dot'] = Benchmark::measure(fn() => Arr::dot(['a' => ['b' => ['c' => 1]]]), $iterations);
$results['Arr::undot'] = Benchmark::measure(fn() => Arr::undot(['a.b.c' => 1, 'a.b.d' => 2]), $iterations);
$results['Arr::pluck'] = Benchmark::measure(fn() => Arr::pluck([['id' => 1], ['id' => 2], ['id' => 3]], 'id'), $iterations);
$results['Arr::where'] = Benchmark::measure(fn() => Arr::where($array, fn($v) => $v > 5000), $smallIterations);
$results['Arr::map'] = Benchmark::measure(fn() => Arr::map($array, fn($v) => $v * 2), $smallIterations);

printSection($results, 'Support/Arr');

// =============================================================================
// SUPPORT - Str
// =============================================================================
echo "## Support/Str" . PHP_EOL;
$strResults = [];
$testString = "  Hello World Example String  ";

$strResults['Str::of()->trim()->lower()->slug()'] = Benchmark::measure(fn() => Str::of($testString)->trim()->lower()->slug(), $iterations);
$strResults['Str::camel'] = Benchmark::measure(fn() => Str::camel('hello_world_example'), $iterations);
$strResults['Str::snake'] = Benchmark::measure(fn() => Str::snake('helloWorldExample'), $iterations);
$strResults['Str::studly'] = Benchmark::measure(fn() => Str::studly('hello_world_example'), $iterations);
$strResults['Str::kebab'] = Benchmark::measure(fn() => Str::kebab('helloWorldExample'), $iterations);
$strResults['Str::title'] = Benchmark::measure(fn() => Str::title('hello world example'), $iterations);
$strResults['Str::slug'] = Benchmark::measure(fn() => Str::slug('Hello World Example'), $iterations);
$strResults['Str::uuid'] = Benchmark::measure(fn() => Str::uuid(), $smallIterations);
$strResults['Str::ulid'] = Benchmark::measure(fn() => Str::ulid(), $smallIterations);
$strResults['Str::random(32)'] = Benchmark::measure(fn() => Str::random(32), $smallIterations);
$strResults['Str::contains'] = Benchmark::measure(fn() => Str::contains($testString, 'World'), $iterations);
$strResults['Str::startsWith'] = Benchmark::measure(fn() => Str::startsWith($testString, '  Hello'), $iterations);
$strResults['Str::endsWith'] = Benchmark::measure(fn() => Str::endsWith($testString, 'String  '), $iterations);
$strResults['Str::before'] = Benchmark::measure(fn() => Str::before($testString, 'World'), $iterations);
$strResults['Str::after'] = Benchmark::measure(fn() => Str::after($testString, 'World'), $iterations);
$strResults['Str::replace'] = Benchmark::measure(fn() => Str::replace('World', 'Universe', $testString), $iterations);
$strResults['Str::replaceArray'] = Benchmark::measure(fn() => Str::replaceArray('?', ['one', 'two'], '? and ?'), $iterations);
$strResults['Str::limit'] = Benchmark::measure(fn() => Str::limit($testString, 10), $iterations);
$strResults['Str::words'] = Benchmark::measure(fn() => Str::words($testString, 3), $iterations);
$strResults['Str::plural'] = Benchmark::measure(fn() => Str::plural('child'), $iterations);
$strResults['Str::singular'] = Benchmark::measure(fn() => Str::singular('children'), $iterations);

$results = array_merge($results, $strResults);
printSection($strResults, 'Support/Str');

// =============================================================================
// COLLECTIONS
// =============================================================================
echo "## Collections" . PHP_EOL;
$colResults = [];
$collection = collect(range(1, 1000));
$assocCollection = collect(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5]);

$colResults['Collection::first()'] = Benchmark::measure(fn() => $collection->first(), $iterations);
$colResults['Collection::last()'] = Benchmark::measure(fn() => $collection->last(), $iterations);
$colResults['Collection::filter()'] = Benchmark::measure(fn() => $collection->filter(fn($v) => $v > 500), $smallIterations);
$colResults['Collection::map()'] = Benchmark::measure(fn() => $collection->map(fn($v) => $v * 2), $smallIterations);
$colResults['Collection::reduce()'] = Benchmark::measure(fn() => $collection->reduce(fn($c, $v) => $c + $v, 0), $smallIterations);
$colResults['Collection::each()'] = Benchmark::measure(fn() => $collection->each(fn($v) => $v), $smallIterations);
$colResults['Collection::filter->map->first'] = Benchmark::measure(fn() => $collection->filter(fn($v) => $v > 100)->map(fn($v) => $v * 2)->first(), $smallIterations);
$colResults['Collection::pluck()'] = Benchmark::measure(fn() => collect([['id' => 1], ['id' => 2], ['id' => 3]])->pluck('id'), $iterations);
$colResults['Collection::where()'] = Benchmark::measure(fn() => collect([['a' => 1], ['a' => 2], ['a' => 3]])->where('a', '>', 1), $iterations);
$colResults['Collection::groupBy()'] = Benchmark::measure(fn() => collect([['type' => 'a'], ['type' => 'b'], ['type' => 'a']])->groupBy('type'), $iterations);
$colResults['Collection::sortBy()'] = Benchmark::measure(fn() => $collection->shuffle()->sortBy(fn($v) => $v), $smallIterations);
$colResults['Collection::unique()'] = Benchmark::measure(fn() => collect([1,1,2,2,3,3,4,4,5,5])->unique(), $iterations);
$colResults['Collection::merge()'] = Benchmark::measure(fn() => $assocCollection->merge(['f' => 6, 'g' => 7]), $iterations);
$colResults['Collection::combine()'] = Benchmark::measure(fn() => collect(['a','b','c'])->combine([1,2,3]), $iterations);
$colResults['Collection::flip()'] = Benchmark::measure(fn() => $assocCollection->flip(), $iterations);
$colResults['Collection::keys()'] = Benchmark::measure(fn() => $assocCollection->keys(), $iterations);
$colResults['Collection::values()'] = Benchmark::measure(fn() => $assocCollection->values(), $iterations);
$colResults['Collection::chunk(100)'] = Benchmark::measure(fn() => $collection->chunk(100), $smallIterations);
$colResults['Collection::take(10)'] = Benchmark::measure(fn() => $collection->take(10), $iterations);
$colResults['Collection::skip(10)'] = Benchmark::measure(fn() => $collection->skip(10), $iterations);

$results = array_merge($results, $colResults);
printSection($colResults, 'Collections');

// =============================================================================
// LAZY COLLECTIONS
// =============================================================================
echo "## LazyCollections" . PHP_EOL;
$lazyResults = [];
$lazyCollection = LazyCollection::make(function() { yield from range(1, 10000); });

$lazyResults['LazyCollection::first()'] = Benchmark::measure(fn() => LazyCollection::make(fn() => yield from range(1, 10000))->first(), $smallIterations);
$lazyResults['LazyCollection::take(10)->all()'] = Benchmark::measure(fn() => LazyCollection::make(fn() => yield from range(1, 10000))->take(10)->all(), $smallIterations);
$lazyResults['LazyCollection::filter->take->all'] = Benchmark::measure(fn() => LazyCollection::make(fn() => yield from range(1, 10000))->filter(fn($v) => $v > 100)->take(10)->all(), $smallIterations);

$results = array_merge($results, $lazyResults);
printSection($lazyResults, 'LazyCollections');

// =============================================================================
// CONTAINER
// =============================================================================
echo "## Container" . PHP_EOL;
$containerResults = [];
$container = new Container();
$container->bind('test', fn() => new stdClass());
$container->singleton('singleton', fn() => new stdClass());

$containerResults['Container::bind()'] = Benchmark::measure(fn() => (new Container())->bind('x', fn() => new stdClass()), $iterations);
$containerResults['Container::make() (bound)'] = Benchmark::measure(fn() => $container->make('test'), $iterations);
$containerResults['Container::make() (singleton)'] = Benchmark::measure(fn() => $container->make('singleton'), $iterations);
$containerResults['Container::make() (auto-resolve)'] = Benchmark::measure(fn() => $container->make(stdClass::class), $iterations);

$results = array_merge($results, $containerResults);
printSection($containerResults, 'Container');

// =============================================================================
// PIPELINE
// =============================================================================
echo "## Pipeline" . PHP_EOL;
$pipelineResults = [];
$pipeline = new Pipeline($container);

$pipelineResults['Pipeline (3 pipes)'] = Benchmark::measure(fn() =>
    (new Pipeline($container))
        ->send('hello')
        ->through([
            fn($v, $next) => $next(strtoupper($v)),
            fn($v, $next) => $next($v . ' WORLD'),
            fn($v, $next) => $next(trim($v)),
        ])
        ->thenReturn()
, $iterations);

$pipelineResults['Pipeline (5 pipes)'] = Benchmark::measure(fn() =>
    (new Pipeline($container))
        ->send('hello')
        ->through([
            fn($v, $next) => $next(strtoupper($v)),
            fn($v, $next) => $next($v . ' WORLD'),
            fn($v, $next) => $next(trim($v)),
            fn($v, $next) => $next(str_replace(' ', '-', $v)),
            fn($v, $next) => $next(strtolower($v)),
        ])
        ->thenReturn()
, $iterations);

$results = array_merge($results, $pipelineResults);
printSection($pipelineResults, 'Pipeline');

// =============================================================================
// EVENTS
// =============================================================================
echo "## Events" . PHP_EOL;
$eventsResults = [];
$dispatcher = new Dispatcher($container);
$dispatcher->listen('test.event', fn($data) => $data);

$eventsResults['Events::dispatch()'] = Benchmark::measure(fn() => $dispatcher->dispatch('test.event', ['data' => 'test']), $iterations);
$eventsResults['Events::listen() + dispatch()'] = Benchmark::measure(function() use ($container) {
    $d = new Dispatcher($container);
    $d->listen('x', fn($data) => $data);
    $d->dispatch('x', ['test']);
}, $smallIterations);

$results = array_merge($results, $eventsResults);
printSection($eventsResults, 'Events');

// =============================================================================
// CONFIG
// =============================================================================
echo "## Config" . PHP_EOL;
$configResults = [];
$config = new ConfigRepository([
    'app' => [
        'name' => 'Laravel',
        'env' => 'production',
        'debug' => false,
        'nested' => ['deep' => ['value' => 'test']]
    ]
]);

$configResults['Config::get()'] = Benchmark::measure(fn() => $config->get('app.name'), $iterations);
$configResults['Config::get() (nested)'] = Benchmark::measure(fn() => $config->get('app.nested.deep.value'), $iterations);
$configResults['Config::set()'] = Benchmark::measure(fn() => $config->set('app.temp', 'value'), $iterations);
$configResults['Config::has()'] = Benchmark::measure(fn() => $config->has('app.name'), $iterations);
$configResults['Config::all()'] = Benchmark::measure(fn() => $config->all(), $iterations);

$results = array_merge($results, $configResults);
printSection($configResults, 'Config');

// =============================================================================
// CACHE
// =============================================================================
echo "## Cache" . PHP_EOL;
$cacheResults = [];
$cache = new CacheRepository(new ArrayStore());

$cacheResults['Cache::put()'] = Benchmark::measure(fn() => $cache->put('key', 'value', 3600), $iterations);
$cacheResults['Cache::get()'] = Benchmark::measure(fn() => $cache->get('key'), $iterations);
$cacheResults['Cache::has()'] = Benchmark::measure(fn() => $cache->has('key'), $iterations);
$cacheResults['Cache::forget()'] = Benchmark::measure(fn() => $cache->forget('key'), $iterations);
$cacheResults['Cache::remember()'] = Benchmark::measure(fn() => $cache->remember('remember-key', 3600, fn() => 'computed'), $iterations);

$results = array_merge($results, $cacheResults);
printSection($cacheResults, 'Cache');

// =============================================================================
// VALIDATION
// =============================================================================
echo "## Validation" . PHP_EOL;
$validationResults = [];
$translator = new Translator(new ArrayLoader(), 'en');
$validation = new ValidationFactory($translator, $container);

$validationResults['Validation (simple)'] = Benchmark::measure(fn() =>
    $validation->make(['email' => 'test@example.com'], ['email' => 'required|email'])->passes()
, $smallIterations);

$validationResults['Validation (complex)'] = Benchmark::measure(fn() =>
    $validation->make(
        ['name' => 'John', 'email' => 'test@example.com', 'age' => 25],
        ['name' => 'required|string|min:2', 'email' => 'required|email', 'age' => 'required|integer|min:18']
    )->passes()
, $smallIterations);

$results = array_merge($results, $validationResults);
printSection($validationResults, 'Validation');

// =============================================================================
// ENCRYPTION
// =============================================================================
echo "## Encryption" . PHP_EOL;
$encryptionResults = [];
$encrypter = new Encrypter(str_repeat('a', 32), 'AES-256-CBC');
$testData = 'This is a test string for encryption';

$encryptionResults['Encrypt (string)'] = Benchmark::measure(fn() => $encrypter->encrypt($testData), $smallIterations);
$encrypted = $encrypter->encrypt($testData);
$encryptionResults['Decrypt (string)'] = Benchmark::measure(fn() => $encrypter->decrypt($encrypted), $smallIterations);
$encryptionResults['Encrypt (array)'] = Benchmark::measure(fn() => $encrypter->encrypt(['key' => 'value', 'nested' => ['a' => 1]]), $smallIterations);

$results = array_merge($results, $encryptionResults);
printSection($encryptionResults, 'Encryption');

// =============================================================================
// HASHING
// =============================================================================
echo "## Hashing" . PHP_EOL;
$hashingResults = [];
$bcrypt = new BcryptHasher(['rounds' => 4]); // Low rounds for benchmark
$testPassword = 'secret123';

$hashingResults['Bcrypt::make()'] = Benchmark::measure(fn() => $bcrypt->make($testPassword), 10); // Very slow, fewer iterations
$bcryptHash = $bcrypt->make($testPassword);
$hashingResults['Bcrypt::check()'] = Benchmark::measure(fn() => $bcrypt->check($testPassword, $bcryptHash), 10);

$results = array_merge($results, $hashingResults);
printSection($hashingResults, 'Hashing');

// =============================================================================
// HTTP REQUEST
// =============================================================================
echo "## Http/Request" . PHP_EOL;
$httpResults = [];
$request = Request::create('/test/path', 'POST', ['name' => 'John', 'email' => 'john@example.com']);

$httpResults['Request::create()'] = Benchmark::measure(fn() => Request::create('/test', 'GET'), $iterations);
$httpResults['Request::input()'] = Benchmark::measure(fn() => $request->input('name'), $iterations);
$httpResults['Request::all()'] = Benchmark::measure(fn() => $request->all(), $iterations);
$httpResults['Request::only()'] = Benchmark::measure(fn() => $request->only(['name']), $iterations);
$httpResults['Request::except()'] = Benchmark::measure(fn() => $request->except(['email']), $iterations);
$httpResults['Request::has()'] = Benchmark::measure(fn() => $request->has('name'), $iterations);
$httpResults['Request::merge()'] = Benchmark::measure(fn() => $request->merge(['extra' => 'data']), $iterations);

$results = array_merge($results, $httpResults);
printSection($httpResults, 'Http/Request');

// =============================================================================
// PAGINATION
// =============================================================================
echo "## Pagination" . PHP_EOL;
$paginationResults = [];
$items = range(1, 100);

$paginationResults['Paginator::create'] = Benchmark::measure(fn() => new Paginator($items, 15, 1), $iterations);
$paginationResults['LengthAwarePaginator::create'] = Benchmark::measure(fn() => new LengthAwarePaginator($items, 100, 15, 1), $iterations);

$results = array_merge($results, $paginationResults);
printSection($paginationResults, 'Pagination');

// =============================================================================
// FLUENT
// =============================================================================
echo "## Support/Fluent" . PHP_EOL;
$fluentResults = [];

$fluentResults['Fluent::create'] = Benchmark::measure(fn() => new Fluent(['name' => 'John', 'age' => 30]), $iterations);
$fluent = new Fluent(['name' => 'John', 'age' => 30]);
$fluentResults['Fluent::get()'] = Benchmark::measure(fn() => $fluent->get('name'), $iterations);
$fluentResults['Fluent::toArray()'] = Benchmark::measure(fn() => $fluent->toArray(), $iterations);

$results = array_merge($results, $fluentResults);
printSection($fluentResults, 'Support/Fluent');

// =============================================================================
// FILESYSTEM
// =============================================================================
echo "## Filesystem" . PHP_EOL;
$fsResults = [];
$fs = new Filesystem();
$tempFile = sys_get_temp_dir() . '/fledge_benchmark_test.txt';

$fsResults['Filesystem::put()'] = Benchmark::measure(function() use ($fs, $tempFile) {
    $fs->put($tempFile, 'test content');
}, $smallIterations);

$fsResults['Filesystem::get()'] = Benchmark::measure(function() use ($fs, $tempFile) {
    $fs->get($tempFile);
}, $smallIterations);

$fsResults['Filesystem::exists()'] = Benchmark::measure(fn() => $fs->exists($tempFile), $iterations);
$fsResults['Filesystem::delete()'] = Benchmark::measure(function() use ($fs, $tempFile) {
    @$fs->put($tempFile, 'x');
    $fs->delete($tempFile);
}, $smallIterations);

$results = array_merge($results, $fsResults);
printSection($fsResults, 'Filesystem');

// Cleanup
@unlink($tempFile);

// =============================================================================
// CARBON (Date/Time)
// =============================================================================
echo "## Support/Carbon" . PHP_EOL;
$carbonResults = [];

$carbonResults['Carbon::now()'] = Benchmark::measure(fn() => Carbon::now(), $iterations);
$carbonResults['Carbon::parse()'] = Benchmark::measure(fn() => Carbon::parse('2024-01-15 10:30:00'), $iterations);
$carbonResults['Carbon::create()'] = Benchmark::measure(fn() => Carbon::create(2024, 1, 15, 10, 30), $iterations);
$now = Carbon::now();
$carbonResults['Carbon::addDays()'] = Benchmark::measure(fn() => $now->copy()->addDays(5), $iterations);
$carbonResults['Carbon::diffForHumans()'] = Benchmark::measure(fn() => $now->copy()->subDays(5)->diffForHumans(), $iterations);
$carbonResults['Carbon::format()'] = Benchmark::measure(fn() => $now->format('Y-m-d H:i:s'), $iterations);
$carbonResults['Carbon::isoFormat()'] = Benchmark::measure(fn() => $now->isoFormat('LLLL'), $smallIterations);

$results = array_merge($results, $carbonResults);
printSection($carbonResults, 'Support/Carbon');

// =============================================================================
// SUMMARY
// =============================================================================
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo "SUMMARY - Total benchmarks: " . count($results) . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

// Sort by time (slowest first)
arsort($results);
echo PHP_EOL . "Top 10 Slowest Operations:" . PHP_EOL;
$i = 0;
foreach ($results as $name => $time) {
    if ($i++ >= 10) break;
    printf("  %-45s %.4f ms" . PHP_EOL, $name, $time);
}

asort($results);
echo PHP_EOL . "Top 10 Fastest Operations:" . PHP_EOL;
$i = 0;
foreach ($results as $name => $time) {
    if ($i++ >= 10) break;
    printf("  %-45s %.4f ms" . PHP_EOL, $name, $time);
}

// Save to JSON
file_put_contents(__DIR__ . '/benchmark-full-results.json', json_encode([
    'php_version' => PHP_VERSION,
    'date' => date('c'),
    'iterations' => $iterations,
    'small_iterations' => $smallIterations,
    'total_benchmarks' => count($results),
    'results' => $results,
], JSON_PRETTY_PRINT));

echo PHP_EOL . "Results saved to benchmark-full-results.json" . PHP_EOL;

// Helper function
function printSection(array $results, string $section): void {
    foreach ($results as $name => $time) {
        printf("  %-45s %.4f ms" . PHP_EOL, $name, $time);
    }
    echo PHP_EOL;
}
