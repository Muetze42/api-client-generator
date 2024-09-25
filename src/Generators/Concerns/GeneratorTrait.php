<?php

namespace NormanHuth\ApiGenerator\Generators\Concerns;

use Illuminate\Support\Str;
use NormanHuth\ApiGenerator\Resources\ArgumentResource;
use NormanHuth\ApiGenerator\Resources\MethodResource;

trait GeneratorTrait
{
    /**
     * @param  \NormanHuth\ApiGenerator\Resources\MethodResource  $methodResource
     * @return void
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function resolveMethod(MethodResource $methodResource): void
    {
        $trait = Str::ucfirst(explode('/', trim($methodResource->path, '/'))[0]);

        $attributes = [
            'query' => [],
            'body' => [],
            'header' => [],
            'cookie' => [],
        ];
        $arguments = [];
        $params = [];
        $path = $methodResource->path;
        $summary = trim($methodResource->summary);
        if ($summary && ! str_ends_with($summary, '.')) {
            $summary .= '.';
        }

        collect($methodResource->arguments)
            ->sortBy(fn (ArgumentResource $argumentResource) => $argumentResource->hasDefault)
            ->each(function (ArgumentResource $argumentResource) use (
                &$attributes,
                &$arguments,
                &$params,
                &$path,
                $methodResource
            ) {
                $type = $argumentResource->type;

                if (str_contains($type, ':')) {
                    // Todo: Objects?
                    $type = 'mixed';
                }

                $argumentName = Str::camel($argumentResource->name);

                $argument = [
                    $type,
                    '$' . $argumentName,
                ];

                if ($argumentResource->hasDefault) {
                    $argument[] = $this->resolveMethodDefault(
                        $type,
                        $argumentResource->default,
                        $methodResource,
                        $argumentResource
                    );
                }

                if (! $argumentResource->required && ! $argumentResource->hasDefault) {
                    $argument[] = '= null';
                }

                $argumentString = implode(' ', $argument);
                if (! $argumentResource->required && $type != 'mixed') {
                    $argumentString = '?' . $argumentString;
                    $type = $type . '|null';
                }

                $arguments[] = $argumentString;
                $params[] = $type . '  $' . $argumentName . '  ' .
                    Str::ucfirst($argumentResource->description);

                if ($argumentResource->location === 'path') {
                    $path = str_replace(
                        '{' . $argumentResource->name . '}',
                        '\' . $' . $argumentName . ' . \'',
                        $path
                    );

                    return;
                }

                $attributes[$argumentResource->location][] = $argumentName;
            });

        $attributesString = '';
        $attributes = array_filter($attributes);

        if (! empty($attributes)) {
            $attributesString = ", \n";
            foreach ($attributes as $key => $items) {
                $attributesString .= "\t\t\t$key: [";
                foreach ($items as $item) {
                    $attributesString .= "\n\t\t\t\t'$item' => \$$item,";
                }
                $attributesString .= "\n\t\t\t]";
                if (array_key_last($attributes) != $key) {
                    $attributesString .= ",\n";
                }
            }
            $attributesString .= "\n\t\t";
        }
        $path = "'" . trim($path, '/') . "'";

        if (str_ends_with($path, " . ''")) {
            $path = substr($path, 0, -5);
        }

        $isJson = in_array('application/json', $methodResource->returnType);

        $response = $isJson ?
            $this->getNamespace(['Responses', Str::ucfirst($methodResource->name) . 'Response']) :
            'Illuminate\Http\Client\Response';

        $this->traitImports[$trait][] = $response;
        $this->traits[$trait][] = $this->storage->stub('php/trait-method', [
            'name' => $methodResource->name,
            'Name' => Str::ucfirst($methodResource->name),
            'response' => class_basename($response),
            'method' => $methodResource->method,
            'summary' => $summary,
            'path' => $path,
            'attributes' => $attributesString,
            'arguments' => implode(', ', $arguments),
            'params' => implode("\n", array_map(fn (string $param) => '     * @param  ' . $param, $params)),
        ]);
    }

    /**
     * @param  string  $type
     * @param  mixed  $default
     * @param  \NormanHuth\ApiGenerator\Resources\MethodResource  $methodResource
     * @param  \NormanHuth\ApiGenerator\Resources\ArgumentResource  $argumentResource
     * @return string
     */
    protected function resolveMethodDefault(
        string $type,
        mixed $default,
        MethodResource $methodResource,
        ArgumentResource $argumentResource
    ): string {
        if ($type == 'bool' && is_bool($default)) {
            $default = $default ? 'true' : 'false';
        }

        return in_array($type, ['int', 'bool']) ? ' = ' . $default : ' = \'' . $default . '\'';
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
