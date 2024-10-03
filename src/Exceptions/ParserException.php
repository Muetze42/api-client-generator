<?php

namespace NormanHuth\ApiGenerator\Exceptions;

use RuntimeException;

class ParserException extends RuntimeException
{
    /**
     * Construct the exception.
     */
    public function __construct()
    {
        parent::__construct('Could not parse the given content.');
    }
}
