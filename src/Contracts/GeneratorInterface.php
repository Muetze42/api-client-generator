<?php

namespace NormanHuth\ApiGenerator\Contracts;

interface GeneratorInterface
{
    /**
     * Generate the HTTP client from the given content.
     */
    public function generate(): void;
}
