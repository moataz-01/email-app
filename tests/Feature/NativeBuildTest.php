<?php

use App\Services\NativeBuild;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Native\Desktop\Builder\Builder;
use Native\Desktop\Drivers\Electron\ElectronServiceProvider;

test('native commands resolve the application builder with the native build directory', function () {
    $builder = app(Builder::class);

    expect($builder)->toBeInstanceOf(NativeBuild::class);
    expect($builder->buildPath())->toBe(rtrim(ElectronServiceProvider::buildPath(), '/'));
});

test('composer gets the build timeout and only build artifacts are pruned after success', function (?int $timeout, int $expectedTimeout) {
    if ($timeout !== null) {
        config(['nativephp.build.composer_timeout' => $timeout]);
    }
    $disk = Storage::fake('native-build');
    $disk->put('app/vendor/bin/tool', 'temporary');
    $disk->put('app/vendor/nativephp/php-bin/bin/php.zip', 'temporary');
    $disk->put('app/vendor/autoload.php', 'keep');
    $disk->put('source/vendor/bin/tool', 'keep');
    Process::preventStrayProcesses();
    Process::fake(['composer install --no-dev --no-interaction --prefer-dist' => Process::result()]);
    $builder = new NativeBuild($disk->path(''));

    $builder->pruneVendorDirectory();

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === 'composer install --no-dev --no-interaction --prefer-dist'
        && $process->path === $builder->buildPath('app')
        && $process->timeout === $expectedTimeout);
    $disk->assertMissing(['app/vendor/bin/tool', 'app/vendor/nativephp/php-bin/bin/php.zip']);
    $disk->assertExists(['app/vendor/autoload.php', 'source/vendor/bin/tool']);
})->with([[null, 1800], [2400, 2400]]);

test('a failed composer install stops packaging without pruning its diagnostic files', function () {
    $disk = Storage::fake('native-build');
    $disk->put('app/vendor/bin/tool', 'keep');
    Process::preventStrayProcesses();
    Process::fake(['composer install --no-dev --no-interaction --prefer-dist' => Process::result(exitCode: 1)]);
    $builder = new NativeBuild($disk->path(''));

    expect(fn () => $builder->pruneVendorDirectory())->toThrow(ProcessFailedException::class);

    $disk->assertExists('app/vendor/bin/tool');
});

test('packaging excludes development configuration caches while retaining app source', function () {
    $disk = Storage::fake('native-build');
    $disk->put('source/bootstrap/cache/config.php', 'development secrets');
    $disk->put('source/bootstrap/cache/routes-v7.php', 'development routes');
    $disk->put('source/app/example.php', 'app source');
    $builder = new NativeBuild($disk->path('build'), $disk->path('source'));

    $builder->copyToBuildDirectory();

    $disk->assertMissing(['build/app/bootstrap/cache/config.php', 'build/app/bootstrap/cache/routes-v7.php']);
    $disk->assertExists('build/app/app/example.php');
});

test('an invalid build timeout fails before running composer', function () {
    config(['nativephp.build.composer_timeout' => 0]);
    Process::preventStrayProcesses();
    Process::fake();

    expect(fn () => app(Builder::class)->pruneVendorDirectory())->toThrow(InvalidArgumentException::class);

    Process::assertNothingRan();
});
