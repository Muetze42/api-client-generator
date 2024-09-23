<?php

namespace NormanHuth\ApiGenerator\Parsers;

use Illuminate\Support\Arr;
use NormanHuth\ApiGenerator\Enums\AuthenticationEnum;
use NormanHuth\ApiGenerator\Generators\LaravelHttpGenerator;
use NormanHuth\ApiGenerator\Resources\ApiResource;
use NormanHuth\ApiGenerator\Storage;

abstract class AbstractParser
{
    /**
     * @var array<class-string<\NormanHuth\ApiGenerator\Contracts\GeneratorInterface>>
     */
    public array $generators;

    /**
     * The config key for the client.
     */
    public ?string $configKey = null;

    /**
     * The name for the client.
     */
    public ?string $name = null;

    /**
     * The Client request options.
     *
     * @var array<string, mixed>
     */
    public array $options = [];

    /**
     * The Client request headers options.
     *
     * @var array<string, mixed>
     */
    public array $headers = [];

    /**
     * The Guzzle request options that are mergeable via array_merge_recursive.
     *
     * @var string[]
     */
    protected array $mergeableOptions = [
        'cookies',
        'form_params',
        //'headers', # withHeaders
        'json',
        'multipart',
        'query',
    ];

    /**
     * The Storage instance.
     */
    public Storage $storage;

    /**
     * Indicate the client authentication method.
     *
     * @var \NormanHuth\ApiGenerator\Enums\AuthenticationEnum
     */
    public AuthenticationEnum $authentication = AuthenticationEnum::BEARER;

    /**
     * The ApiResource instance.
     */
    public ApiResource $resource;

    /**
     * Create a new Parser instance.
     *
     * @throws \NormanHuth\ApiGenerator\Exceptions\InvalidFormatException
     */
    public function __construct(string $content, string $targetPath, ?string $name = null, ?string $configKey = null)
    {
        $this->validate($content);

        if (! empty($name)) {
            $this->setName($name);
        }

        if (! empty($configKey)) {
            $this->setConfigKey($configKey);
        }

        $this->storage = new Storage($targetPath);

        $this->setDefaultGenerators();
    }

    /**
     * Determine the client authentication method.
     *
     * @param  \NormanHuth\ApiGenerator\Enums\AuthenticationEnum  $authentication
     * @return $this
     */
    public function authentication(AuthenticationEnum $authentication): static
    {
        $this->authentication = $authentication;

        return $this;
    }

    /**
     * Set the default generators.
     *
     * @return void
     */
    protected function setDefaultGenerators(): void
    {
        $this->setGenerators([
            LaravelHttpGenerator::class,
        ]);
    }

    /**
     * Set generators.
     *
     * @param  array<class-string<\NormanHuth\ApiGenerator\Contracts\GeneratorInterface>>  $generators
     * @return $this
     */
    public function setGenerators(array $generators): static
    {
        $this->generators = $generators;

        return $this;
    }

    /**
     * Replace the specified options on the request.
     *
     * @param  array<string, mixed>  $options
     * @return $this
     */
    public function withOptions(array $options): static
    {
        $headers = Arr::only($options, 'headers');
        if (! empty($headers)) {
            $this->withHeaders($headers);
        }

        return tap($this, function () use ($options) {
            $this->options = array_replace_recursive(
                array_merge_recursive($this->options, Arr::only($options, $this->mergeableOptions)),
                $options
            );
        });
    }

    /**
     * Add the given headers to the request.
     *
     * @param  array<string, mixed>  $headers
     * @return $this
     */
    public function withHeaders(array $headers): static
    {
        return tap($this, function () use ($headers) {
            $this->headers = array_merge_recursive($this->headers, $headers);
        });
    }

    /**
     * Set the name for the client.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the config key for the client.
     */
    public function setConfigKey(string $configKey): static
    {
        $this->configKey = $configKey;

        return $this;
    }

    /**
     * Execute the generators.
     */
    protected function executeGenerators(): void
    {
        collect($this->generators)->each(
            fn (string $generator) => (new $generator($this->storage, $this->resource, $this->headers, $this->options))
                ->generate()
        );
    }

    /**
     * Validate the given content.
     *
     * @throws \NormanHuth\ApiGenerator\Exceptions\InvalidFormatException
     */
    abstract protected function validate(string $content): void;
}
