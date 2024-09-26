<?php

namespace NormanHuth\ApiGenerator\Generators\Api;

use Illuminate\Support\Traits\Conditionable;
use NormanHuth\ApiGenerator\Concerns\GeneratorTrait;
use NormanHuth\ApiGenerator\Resources\MethodResource;

class LaravelHttpGenerator extends AbstractGenerator
{
    use Conditionable;
    use GeneratorTrait;

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
            if ($response == $this->defaultResponse) {
                return;
            }
            $key = data_get($this->responses[$response]['schema'], 'items.$ref', '');
            if ($key) {
                $key = '\'' . basename($key) . '\'';
            }
            $phpdoc = $this->phpDoc($this->responses[$response]['data']);

            $this->storage->write(
                'php/laravel-http/response',
                'Responses\\' . class_basename($response) . '.php',
                [
                    'namespace' => $this->getNamespace('Responses'),
                    'name' => class_basename($response),
                    'phpdoc' => $phpdoc,
                    'key' => $key,
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
            $imports = array_unique($this->traitImports[$trait]);
            sort($imports);
            $imports = array_map(fn (string $response) => 'use ' . $response . ';', $imports);

            $this->storage->write('php/trait', 'Concerns\\' . $trait . 'Trait.php', [
                'imports' => implode("\n", $imports),
                'methods' => implode("\n", $methods),
                'namespace' => $this->getNamespace('Concerns'),
                'name' => $trait,
            ]);
        });
    }
}
