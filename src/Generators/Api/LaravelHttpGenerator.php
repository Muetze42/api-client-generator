<?php

namespace NormanHuth\ApiGenerator\Generators\Api;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use NormanHuth\ApiGenerator\Concerns\GeneratorTrait;
use NormanHuth\ApiGenerator\Resources\MethodResource;
use NormanHuth\ApiGenerator\Resources\ModelMethodResource;
use NormanHuth\ApiGenerator\Resources\ModelResource;

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
        $this->writeModels();
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function writeModels(): void
    {
        collect($this->resource->models)->each(function (ModelResource $modelResource) {
            $casts = Arr::map(
                $modelResource->casts,
                fn (string $value, string $key) => "\t\t'{$key}' => '{$value}',"
            );
            $required = Arr::where(
                $modelResource->required,
                function (string $attribute) {
                    return substr_count(Str::kebab($attribute), '-') < 2 &&
                        ! Str::endsWith($attribute, ['Id', '_id']);
                }
            );
            $required = Arr::map(
                $required,
                fn (string $value) => "\t\t'{$value}',"
            );

            $methods = [];
            collect($modelResource->methods)
                ->reject(function (ModelMethodResource $modelMethodResource) {
                    return substr_count(Str::kebab($modelMethodResource->name), '-') > 1;
                })
                ->sortBy(fn (ModelMethodResource $modelMethodResource) => $modelMethodResource->name)
                ->sortBy(fn (ModelMethodResource $modelMethodResource) => $modelMethodResource->type)
                ->each(function (ModelMethodResource $modelMethodResource) use (&$methods, &$imports) {
                    $target = $this->getNamespace('Models\\' . $modelMethodResource->relationshipTarget);
                    $name = $modelMethodResource->type == 3 ? Str::plural($modelMethodResource->name) :
                        $modelMethodResource->name;

                    $name = Str::upper($name) === $name ? Str::lower($name) : Str::camel($name);

                    $methods[] = $this->storage->stub(
                        'php/laravel-http/model-method-' . $modelMethodResource->type,
                        [
                            'name' => $name,
                            'return-type' => $modelMethodResource->returnType,
                            'attribute' => $name,
                            'target' => $target,
                            'target-model' => $modelMethodResource->relationshipTarget,
                        ]
                    );
                });

            $this->storage->write(
                'php/laravel-http/model',
                'Models\\' . $modelResource->name . '.php',
                [
                    'namespace' => $this->getNamespace('Models'),
                    'name' => $modelResource->name,
                    'casts' => empty($casts) ? '' : "\n" . implode("\n", $casts) . "\n\t",
                    'required' => empty($required) ? '' : "\n" . implode("\n", $required) . "\n\t",
                    'methods' => empty($methods) ? '' : "\n\n" . rtrim(implode("\n", $methods)),
                ]
            );
        });
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

            $this->storage->write(
                'php/laravel-http/response',
                'Responses\\' . class_basename($response) . '.php',
                [
                    'namespace' => $this->getNamespace('Responses'),
                    'name' => class_basename($response),
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
