<?php

namespace NormanHuth\ApiGenerator\Parsers;

use Illuminate\Support\Str;
use NormanHuth\ApiGenerator\Concerns\ParserTrait;
use NormanHuth\ApiGenerator\Exceptions\GeneratorException;
use NormanHuth\ApiGenerator\Resources\ApiResource;
use NormanHuth\Library\Support\Cast;

abstract class AbstractOpenApiParser extends AbstractParser
{
    use ParserTrait;

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
        $this->resolveModels();
        $this->executeGenerators();
    }
}
