<?php

namespace NormanHuth\ApiGenerator\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use NormanHuth\ApiGenerator\Resources\ArgumentResource;
use NormanHuth\ApiGenerator\Resources\MethodResource;
use NormanHuth\ApiGenerator\Resources\ModelMethodResource;
use NormanHuth\ApiGenerator\Resources\ModelResource;
use NormanHuth\Library\Support\Cast;
use RuntimeException;

trait ParserTrait
{
    /**
     * @return void
     */
    protected function setDefinitions(): void
    {
        foreach ($this->data['definitions'] as $definition => $data) {
            $this->definitions[$definition] = $data;
        }
    }

    protected function resolveModels(): void
    {
        foreach ($this->data['definitions'] as $model => $data) {
            if (empty($data['properties'])) {
                continue;
            }

            $casts = [];
            $relationships = [];
            $modelMethods = [];
            $required = data_get($data, 'required', []);

            foreach ($data['properties'] as $property => $schema) {
                $originalProperty = $property;
                $cast = $this->resolveSchema($schema);
                if (str_starts_with($cast, 'relationship:')) {
                    $required = Arr::where(
                        $required,
                        fn (string $attribute) => ! Str::startsWith($attribute, $property)
                    );

                    [$cast, $property] = explode(':', $cast);
                }
                if ($cast == 'relationship') {
                    $relationships[] = $property;

                    $returnType = 'array';
                    $relationshipTarget = $property;
                    $type = ModelMethodResource::HAS_MANY_METHOD;

                    if (isset($schema['type']) && $schema['type'] == 'object') {
                        $ref = array_values(array_filter(data_get($schema, '*.$ref')));
                        if (count($ref) != 1) {
                            continue;
                        }
                        $type = ModelMethodResource::HAS_ONE_METHOD;
                        $returnType = $relationshipTarget = basename($ref[0]);
                    }
                    if (isset($schema['type']) && $schema['type'] == 'array') {
                        $property = $originalProperty;
                    }

                    $modelMethods[] = new ModelMethodResource(
                        name: $property,
                        returnType: $returnType,
                        type: $type,
                        relationshipTarget: Str::singular(Str::ucfirst($relationshipTarget))
                    );

                    continue;
                }

                if ($cast != 'string') {
                    $casts[$property] = $cast;
                }

                $modelMethods[] = new ModelMethodResource(
                    name: $property,
                    returnType: $cast
                );
            }

            asort($casts);

            $this->resource->addModel(
                new ModelResource(
                    name: Str::studly($model),
                    casts: $casts,
                    relationships: $relationships,
                    required: $required,
                    methods: $modelMethods
                )
            );
        }
    }

    /**
     * Resolve the API methods.
     */
    protected function resolveMethods(): void
    {
        foreach ($this->data['paths'] as $path => $methods) {
            foreach ($methods as $method => $data) {
                if ($this->isDeprecated($data)) {
                    continue;
                }

                $methodName = (new Cast($data['operationId']))->toString();
                $instance = new MethodResource(
                    name: $methodName,
                    path: (new Cast($path))->toString(),
                    method: (new Cast($method))->toString(),
                    returnType: $data['produces'],
                    summary: (new Cast(data_get($data, 'summary')))->toString()
                );

                $this->resolveArguments($instance, (new Cast($data['parameters']))->toArray());

                $this->resource->addMethod($instance);
            }
        }
    }

    /**
     * @Todo: Enums - https://swagger.io/docs/specification/data-models/enums/
     *
     * @param  array  $schema
     * @return string
     */
    protected function resolveSchema(array $schema): string
    {
        if (isset($schema['$ref'])) {
            return 'relationship';
        }

        return match ($schema['type']) {
            'boolean' => 'bool',
            'string' => 'string',
            'object' => 'relationship',
            'array' => $this->resolveSchemaArray($schema),
            'number', 'integer' => $this->resolveSchemaNumber($schema, $schema['type']),
            default => throw new RuntimeException(
                sprintf('Could not resolve schema. "%s".', print_r($schema, true))
            ),
        };
    }

    protected function resolveSchemaArray(array $schema): string
    {
        foreach ($schema as $key => $value) {
            if (in_array($key, ['type', 'description', 'items'])) {
                continue;
            }
            if (! empty($value['name'])) {
                return 'relationship:' . $value['name'];
            }
        }

        if (! empty($schema['items']['$ref'])) {
            return 'relationship:' . basename($schema['items']['$ref']);
        }

        return 'array';
    }

    /**
     * @param  array  $schema
     * @param  string  $type
     * @return string
     */
    protected function resolveSchemaNumber(array $schema, string $type = 'number'): string
    {
        if (empty($schema['format'])) {
            return $type === 'number' ? 'float' : 'int';
        }

        return match ($schema['format']) {
            'float', 'double' => 'float',
            'int32', 'int64' => 'int',
            default => throw new RuntimeException(
                sprintf('Could not resolve number number schema. "%s".', print_r($schema, true))
            ),
        };
    }

    /**
     * Determine whether the endpoint is deprecated.
     *
     * @param  mixed  $data
     * @return bool
     */
    protected function isDeprecated(mixed $data): bool
    {
        return $this->skipDeprecated && data_get($data, 'deprecated', false);
    }

    /**
     * @phpstan-param array<int, string> $arguments
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function resolveArguments(MethodResource &$method, array $arguments): void
    {
        foreach ($arguments as $argument) {
            $argument = (new Cast($argument))->toArray();
            $instance = new ArgumentResource(
                name: $argument['name'],
                type: $this->resolveArgumentType($argument),
                required: $argument['required'],
                description: (new Cast(data_get($argument, 'description')))->toString(),
                location: $argument['in']
            );

            if (isset($argument['default'])) {
                $instance->default($argument['default']);
            }

            $method->addArgument($instance);
        }
    }

    /**
     * @param  array<int, mixed>  $argument
     */
    protected function resolveArgumentType(array $argument): string
    {
        if (isset($argument['schema'])) {
            if (! isset($argument['schema']['$ref'])) {
                return $argument['schema']['type'];
            }

            $definition = (new Cast(last(explode('/', $argument['schema']['$ref']))))->toString();
            if (! isset($this->definitions[$definition])) {
                throw new RuntimeException(sprintf('Could not find definition "%s".', $definition));
            }

            return 'definition:' . $definition;
        }
        if ($argument['type'] == 'string') {
            return 'string';
        }

        if (in_array($argument['type'], ['bool', 'boolean'])) {
            return 'bool';
        }

        if (in_array($argument['type'], ['integer', 'number'])) {
            return in_array($argument['format'], ['float', 'double']) ? 'float' : 'int';
        }

        return '';
    }

    /**
     * Determine the return type.
     *
     * @param  mixed  $produces
     * @return string
     */
    protected function returnType(mixed $produces): string
    {
        $produces = (new Cast($produces))->toArray();

        if (in_array('application/json', $produces)) {
            return 'json';
        }
        if (in_array('application/xml', $produces)) {
            return 'string';
        }

        return 'unknown';
    }
}
