<?php

namespace NormanHuth\ApiGenerator\Resources;

class ModelResource
{
    /**
     * The name of the model.
     */
    public string $name;

    /**
     * The Models's attributes that should be cast.
     *
     * @var array<string, string>
     */
    public array $casts;

    /**
     * The Models's required attributes.
     *
     * @var string[]
     */
    public array $required;

    /**
     * The Models's relationships.
     *
     * @var string[]
     */
    public array $relationships;

    /**
     * The Models's methods.
     *
     * @var list<\NormanHuth\ApiGenerator\Resources\ModelMethodResource>
     */
    public array $methods;

    /**
     * Create a new ModelResource instance.
     *
     * @param  string  $name
     * @param  array<string, string>  $casts
     * @param  string[]  $relationships
     * @param  string[]  $required
     * @param  string[]  $methods
     */
    public function __construct(
        string $name,
        array $casts = [],
        array $relationships = [],
        array $required = [],
        array $methods = []
    ) {
        $this->name = $name;
        $this->casts = $casts;
        $this->relationships = $relationships;
        $this->required = $required;
        $this->methods = $methods;
    }
}
