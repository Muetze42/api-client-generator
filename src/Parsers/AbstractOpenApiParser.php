<?php

namespace NormanHuth\ApiGenerator\Parsers;

use Illuminate\Support\Str;
use NormanHuth\ApiGenerator\Exceptions\GeneratorException;
use NormanHuth\ApiGenerator\Resources\ApiResource;
use NormanHuth\ApiGenerator\Resources\ArgumentResource;
use NormanHuth\ApiGenerator\Resources\MethodResource;
use NormanHuth\Library\Support\Cast;
use RuntimeException;

abstract class AbstractOpenApiParser extends AbstractParser
{
    /**
     * The contents as an array.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Indicates whether to skip deprecated endpoints.
     */
    protected bool $skipDeprecated = true;

    /**
     * The Media type definitions.
     *
     * @var array<int|string, mixed>
     */
    protected array $definitions = [];

    /**
     * Create a new Parser instance.
     *
     * @throws \NormanHuth\ApiGenerator\Exceptions\InvalidFormatException
     */
    public function __construct(string $content, string $targetPath, ?string $name = null, ?string $configKey = null)
    {
        parent::__construct($content, $targetPath, $name, $configKey);

        if (empty($this->name)) {
            $this->name = (new Cast(data_get($this->data, 'info.title')))->toString();
        }
        if (empty($this->configKey)) {
            $this->configKey = explode('-', Str::slug($this->name))[0];
        }
    }

    /**
     * Determine whether to skip deprecated endpoints.
     *
     * @param  bool  $state
     * @return $this
     */
    public function skipDeprecated(bool $state): static
    {
        $this->skipDeprecated = $state;

        return $this;
    }

    /**
     * Generate the HTTP client from the given content.
     *
     * @throws \NormanHuth\ApiGenerator\Exceptions\InvalidFormatException
     * @throws \NormanHuth\ApiGenerator\Exceptions\ParserException
     */
    public function generate(): void
    {
        if (empty($this->name)) {
            throw new GeneratorException('Could not determine the name for the client.');
        }
        if (empty($this->configKey)) {
            throw new GeneratorException('Could not determine the config key for the client.');
        }

        $this->resource = new ApiResource($this->authentication, $this->name, $this->configKey);

        $this->setDefinitions();
        $this->resolveMethods();
        $this->executeGenerators();
    }

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

                $instance = new MethodResource(
                    name: (new Cast($data['operationId']))->toString(),
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
