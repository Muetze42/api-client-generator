<?php

namespace NormanHuth\ApiGenerator\Exceptions;

use RuntimeException;

class InvalidFormatException extends RuntimeException
{
    /**
     * Construct the exception.
     */
    public function __construct(string $type)
    {
        parent::__construct(sprintf('The given content is not a valid %s format.', $type));
    }
}
