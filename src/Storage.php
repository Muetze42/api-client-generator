<?php

namespace NormanHuth\ApiGenerator;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Storage
{
    /**
     * The Filesystem instance.
     */
    public Filesystem $filesystem;

    /**
     * The filesystem manager instance.
     */
    protected FilesystemManager $filesystemManager;

    /**
     * The filesystem instance for the package directory.
     */
    public FilesystemAdapter|FilesystemInterface $packageDisk;

    /**
     * The filesystem instance for the target directory.
     */
    public FilesystemAdapter|FilesystemInterface $targetDisk;

    /**
     * The target path.
     */
    public string $targetPath;

    /**
     * Create a new Filesystem instance.
     *
     * @param  string  $targetPath
     */
    public function __construct(string $targetPath)
    {
        $this->registerManager();
        $this->filesystem = new Filesystem();
        $this->packageDisk = $this->filesystemManager->disk('package');
        $this->targetPath = trim($targetPath, '/\\');
    }

    /**
     * Build a new target disk for the given generator.
     *
     * @param  string  $generator
     * @return void
     */
    public function setTargetDisk(string $generator): void
    {
        $suffix = 'Generator';
        if (str_ends_with($generator, $suffix) && strlen($generator) > strlen($suffix)) {
            $generator = substr($generator, 0, -strlen($suffix));
        }

        $this->targetDisk = $this->filesystemManager->build([
            'driver' => 'local',
            'root' => $this->targetPath . '/' . Str::snake($generator, '-'),
        ]);
    }

    /**
     * Get the contents of a stub file.
     *
     * @param  string  $stubPath
     * @param  array<string, mixed>  $replaces
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function stub(string $stubPath, array $replaces = []): string
    {
        $stubPath = "stubs/{$stubPath}.stub";

        $contents = $this->packageDisk->get($stubPath);

        $replaces = Arr::mapWithKeys($replaces, fn (mixed $value, string $key) => ['{' . $key . '}' => $value]);

        if (is_null($contents)) {
            throw new FileNotFoundException(sprintf('The stub "%s" does not exist.', $stubPath));
        }

        return str_replace(array_keys($replaces), array_values($replaces), $contents);
    }

    /**
     * @param  string  $stubPath
     * @param  string  $file
     * @param  array<string, mixed>  $replaces
     * @return bool|string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function write(string $stubPath, string $file, array $replaces = []): bool|string
    {
        return $this->targetDisk->put($file, $this->stub($stubPath, $replaces));
    }

    /**
     * Register the filesystem manager.
     */
    protected function registerManager(): void
    {
        $container = new Container();
        $container->instance('app', $container);
        $container['config'] = new Repository([
            'filesystems' => [
                'default' => 'package',
                'disks' => [
                    'package' => [
                        'driver' => 'local',
                        'root' => dirname(__DIR__),
                    ],
                ],
            ],
        ]);
        /** @var \Illuminate\Contracts\Foundation\Application $container */
        $this->filesystemManager = new FilesystemManager($container);
    }
}
