<?php

namespace NormanHuth\ApiGenerator\Resources;

class ModelMethodResource
{
    public const ATTRIBUTE_METHOD = 1;

    public const HAS_ONE_METHOD = 2;

    public const HAS_MANY_METHOD = 3;

    /**
     * The name of the name.
     */
    public string $name;

    /**
     * The type of the method.
     */
    public int $type;

    /**
     * The return type of the method.
     */
    public string $returnType;

    /**
     * The description of the name.
     */
    public ?string $description;

    /**
     * The related Model.
     */
    public ?string $relationshipTarget;

    /**
     * Create a new ModelMethodResource instance.
     *
     * @param  string  $name
     * @param  string  $returnType
     * @param  int  $type
     * @param  string|null  $description
     * @param  string|null  $relationshipTarget
     */
    public function __construct(
        string $name,
        string $returnType,
        int $type = self::ATTRIBUTE_METHOD,
        ?string $description = null,
        ?string $relationshipTarget = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->returnType = $returnType;
        $this->description = $description;
        $this->relationshipTarget = $relationshipTarget;
    }
}
