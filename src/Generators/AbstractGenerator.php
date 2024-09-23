<?php

namespace NormanHuth\ApiGenerator\Generators;

use NormanHuth\ApiGenerator\Contracts\GeneratorInterface;
use NormanHuth\ApiGenerator\Resources\ApiResource;
use NormanHuth\ApiGenerator\Storage;

abstract class AbstractGenerator implements GeneratorInterface
{
    /**
     * The Client request options.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * The Client request headers options.
     *
     * @var array<string, mixed>
     */
    protected array $headers = [];

    /**
     * The Storage instance.
     */
    public Storage $storage;

    /**
     * The ApiResource instance.
     */
    public ApiResource $resource;

    /**
     * The name of the client.
     */
    public string $clientName;

    /**
     * Create a new Generator instance.
     *
     * @param  \NormanHuth\ApiGenerator\Storage  $storage
     * @param  \NormanHuth\ApiGenerator\Resources\ApiResource  $resource
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $options
     */
    public function __construct(Storage $storage, ApiResource $resource, array $headers = [], array $options = [])
    {
        $this->storage = $storage;
        $this->resource = $resource;
        $this->options = $options;
        $this->headers = $headers;

        $this->storage->setTargetDisk(class_basename($this));
    }
}
