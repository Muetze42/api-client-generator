<?php

namespace NormanHuth\ApiGenerator\Resources;

use InvalidArgumentException;

class ArgumentResource
{
    /**
     * The name of the argument.
     */
    public string $name;

    /**
     * The description of the argument.
     */
    public string $description;

    /**
     * Indicates whether the argument is required or not.
     */
    public bool $required;

    /**
     * The type of the argument.
     */
    public string $type;

    /**
     * The location of the argument.
     */
    public string $location;

    /**
     * Indicates whether the argument has a default value.
     */
    public bool $hasDefault = false;

    /**
     * The default value of the argument.
     */
    public mixed $default;

    /**
     * Create a new MethodArgumentResource instance.
     */
    public function __construct(
        string $name,
        string $type,
        bool $required = false,
        ?string $description = null,
        string $location = 'query'
    ) {
        if (! in_array($location, ['query', 'header', 'path', 'body', 'cookie'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid location for argument ´%s´. Allowed values: ´query´, ´header´, ´path´, ´body´, ´cookie´',
                    $name
                )
            );
        }

        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->description = trim((string) $description);
        $this->location = $location;
    }

    /**
     * Set the default value of the argument.
     */
    public function default(mixed $value): static
    {
        $this->hasDefault = true;
        $this->default = $value;

        return $this;
    }
}
