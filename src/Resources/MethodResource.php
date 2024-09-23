<?php

namespace NormanHuth\ApiGenerator\Resources;

use Illuminate\Support\Str;

class MethodResource
{
    /**
     * The name of the method.
     */
    public string $name;

    /**
     * The path for the method.
     */
    public string $path;

    /**
     * The method for the method.
     */
    public string $method;

    /**
     * The summary for the method.
     */
    public string $summary;

    /**
     * The return type for the method.
     */
    public array $returnType;

    /**
     * The arguments for the method.
     *
     * @var list<\NormanHuth\ApiGenerator\Resources\ArgumentResource>
     */
    public array $arguments;

    /**
     * Create a new ApiMethod instance.
     *
     * @param  list<\NormanHuth\ApiGenerator\Resources\ArgumentResource>  $arguments
     */
    public function __construct(
        string $name,
        string $path,
        string $method,
        array $returnType,
        string $summary = '',
        array $arguments = []
    ) {
        $this->name = Str::camel($name);
        $this->path = trim($path, '/');
        $this->method = $method;
        $this->returnType = $returnType;
        $this->summary = $summary;
        $this->arguments = $arguments;
    }

    /**
     * Add a argument to the method.
     */
    public function addArgument(ArgumentResource $argument): static
    {
        $this->arguments[] = $argument;

        return $this;
    }
}
