<?php

namespace NormanHuth\ApiGenerator\Contracts;

interface ParserInterface
{
    /**
     * Generate the HTTP client from the given content.
     *
     */
    public function generate(): void;
}
