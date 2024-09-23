<?php

namespace NormanHuth\ApiGenerator\Resources;

use NormanHuth\ApiGenerator\Enums\AuthenticationEnum;
use RuntimeException;

class ApiResource
{
    /**
     * Indicate the client authentication method.
     */
    public AuthenticationEnum $authentication = AuthenticationEnum::BEARER;

    /**
     * The methods of the API.
     *
     * @var list<\NormanHuth\ApiGenerator\Resources\MethodResource>
     */
    public array $methods = [];

    /**
     * @var string[]
     */
    public array $methodNames = [];

    /**
     * The name of the client.
     */
    public string $clientName;

    /**
     * The config key for the client.
     */
    public string $configKey;

    /**
     * Create a new ApiResource instance.
     *
     * @param  \NormanHuth\ApiGenerator\Enums\AuthenticationEnum  $authentication
     * @param  string  $clientName
     * @param  string  $configKey
     */
    public function __construct(AuthenticationEnum $authentication, string $clientName, string $configKey)
    {
        $this->authentication = $authentication;
        $this->clientName = $clientName;
        $this->configKey = $configKey;
    }

    /**
     * Add a method to the api resource.
     */
    public function addMethod(MethodResource $method): static
    {
        if (in_array($method->name, $this->methodNames)) {
            throw new RuntimeException(sprintf('A method with the name "%s" already exists.', $method->name));
        }

        $this->methodNames[] = $method->name;
        $this->methods[] = $method;

        return $this;
    }
}
