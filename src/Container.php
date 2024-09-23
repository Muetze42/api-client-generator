<?php

namespace NormanHuth\ApiGenerator;

use Illuminate\Container\Container as IlluminateContainer;

class Container extends IlluminateContainer
{
    /**
     * Determine if the application is running unit tests.
     */
    public function runningUnitTests(): bool
    {
        return false;
    }
}
