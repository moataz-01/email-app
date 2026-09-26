<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Native\Desktop\Builder\Builder;
use Symfony\Component\Filesystem\Filesystem;

class NativeBuild extends Builder
{
    /**
     * NativePHP 2.3 hardcodes a five-minute Composer timeout. Keep its
     * artifact cleanup, but allow enough time for the PHP binary download.
     */
    public function pruneVendorDirectory(): void
    {
        $timeout = Config::integer('nativephp.build.composer_timeout');

        if ($timeout < 1) {
            throw new InvalidArgumentException('Native build Composer timeout must be a positive number of seconds.');
        }

        Process::path($this->buildPath('app'))
            ->timeout($timeout)
            ->run('composer install --no-dev --no-interaction --prefer-dist', function (string $type, string $output): void {
                echo $output;
            })
            ->throw();

        $filesystem = new Filesystem;
        $filesystem->remove([
            $this->buildPath('app/vendor/bin'),
            $this->buildPath('app/vendor/nativephp/php-bin'),
        ]);

        $binaryDirectory = $this->buildPath('app/'.$this->binaryPackageDirectory().'bin');
        $filesystem->remove(glob("{$binaryDirectory}/*/*/php-*.zip") ?: []);
    }
}
