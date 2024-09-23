<?php

namespace NormanHuth\ApiGenerator\Generators;

use Illuminate\Support\Traits\Conditionable;
use NormanHuth\ApiGenerator\Generators\Concerns\ResolveMethodTrait;
use NormanHuth\ApiGenerator\Resources\MethodResource;

class LaravelHttpGenerator extends AbstractGenerator
{
    use Conditionable;
    use ResolveMethodTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $traits = [];

    /**
     * @var array<int|string, mixed>
     */
    protected array $traitImports = [];

    /**
     * Generate the HTTP client from the given content.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function generate(): void
    {
        collect($this->resource->methods)->each(fn (MethodResource $method) => $this->resolveMethod($method));
        $this->writeTraits();
        $this->writeResponses();
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function writeResponses(): void
    {
        collect($this->traitImports)->flatten()->unique()->each(function (string $response) {
            $this->storage->write(
                'php/laravel-http/response',
                'Responses\\' . $response . '.php',
                [
                    'namespace' => $this->getNamespace('Responses'),
                    'name' => $response,
                ]
            );
        });
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function writeTraits(): void
    {
        collect($this->traits)->each(function (array $methods, string $trait) {
            $imports = $this->traitImports[$trait];
            sort($imports);
            $imports = array_map(fn (string $response) => 'use ' .
                $this->getNamespace(['Responses', $response]) . ';', $imports);

            $this->storage->write('php/trait', 'Concerns\\' . $trait . 'Trait.php', [
                'imports' => implode("\n", $imports),
                'methods' => implode("\n", $methods),
                'namespace' => $this->getNamespace('Concerns'),
                'name' => $trait,
            ]);
        });
    }

    /**
     * @param  string|string[]  $namespaces
     * @return string
     */
    protected function getNamespace(array|string $namespaces = []): string
    {
        return implode('\\', array_merge([
            'App',
            'Http',
            'Clients',
            explode(' ', $this->resource->clientName)[0],
        ], (array) $namespaces));
    }
}
