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
     * A optional response.
     *
     * @var mixed|null
     */
    public mixed $response = null;

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
     * Set a optional response
     *
     * @param  mixed  $response
     * @return $this
     */
    public function response(mixed $response): static
    {
        $this->response = $response;

        return $this;
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
