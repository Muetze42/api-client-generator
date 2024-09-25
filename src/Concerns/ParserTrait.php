<?php

namespace NormanHuth\ApiGenerator\Concerns;

use NormanHuth\ApiGenerator\Resources\ArgumentResource;
use NormanHuth\ApiGenerator\Resources\MethodResource;
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

                if ($response = $this->resolveResponse(data_get($data, 'responses.200.schema'), $methodName)) {
                    $instance->response($response);
                }

                $this->resource->addMethod($instance);
            }
        }
    }

    /**
     * @param  array|null  $schema
     * @param  string  $methodName
     * @return mixed
     */
    protected function resolveResponse(?array $schema, string $methodName): mixed
    {
        if (empty($schema)) {
            return null;
        }

        return $this->resolveResponseSchema($schema, $methodName);
    }

    /**
     * @param  array  $schema
     * @param  string  $methodName
     * @return mixed
     */
    protected function resolveResponseSchema(array $schema, string $methodName): mixed
    {
        if (isset($schema['$ref'])) {
            return $this->resolveResponse($this->definitions[basename($schema['$ref'])], $methodName);
        }

        /**
         * @Todo: Mixed types - https://swagger.io/docs/specification/data-models/data-types/#mixed-type
         *
         * @Todo: Null - https://swagger.io/docs/specification/data-models/data-types/#null
         */

        return match ($schema['type']) {
            'object' => ['type' => 'object', 'properties' => $this->resolveResponseObject($schema, $methodName)],
            'array' => ['type' => 'array', 'properties' => $this->resolveResponseArray($schema, $methodName)],
            'boolean' => 'bool',
            'string' => 'string', // @Todo: Enums - https://swagger.io/docs/specification/data-models/enums/
            'number' => $this->resolveResponseNumber($schema, $methodName),
            'integer' => $this->resolveResponseNumber($schema, $methodName, 'integer'),
            default => throw new RuntimeException(
                sprintf('Could not resolve response for method "%s".', $methodName)
            ),
        };
    }

    /**
     * @param  array  $schema
     * @param  string  $methodName
     * @param  string  $type
     * @return string
     */
    protected function resolveResponseNumber(array $schema, string $methodName, string $type = 'number'): string
    {
        if (empty($schema['format'])) {
            return $type === 'number' ? 'float' : 'int';
        }

        return match ($schema['format']) {
            'float', 'double' => 'float',
            'int32', 'int64' => 'int',
            default => throw new RuntimeException(
                sprintf('Could not resolve response number for method "%s".', $methodName)
            ),
        };
    }

    /**
     * @param  array  $schema
     * @param  string  $methodName
     * @return mixed
     */
    protected function resolveResponseArray(array $schema, string $methodName): mixed
    {
        return $this->resolveResponseSchema($schema['items'], $methodName);
    }

    /**
     * @param  array  $schema
     * @param  string  $methodName
     * @return array<string, mixed>
     */
    protected function resolveResponseObject(array $schema, string $methodName): array
    {
        $result = [];
        foreach ($schema['properties'] as $key => $property) {
            $result[$key] = $this->resolveResponseSchema($property, $methodName);
        }

        return $result;
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
