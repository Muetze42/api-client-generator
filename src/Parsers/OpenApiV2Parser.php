<?php

namespace NormanHuth\ApiGenerator\Parsers;

use Illuminate\Support\Str;
use NormanHuth\ApiGenerator\Contracts\ParserInterface;
use NormanHuth\ApiGenerator\Exceptions\InvalidFormatException;
use NormanHuth\Library\Support\Cast;

class OpenApiV2Parser extends AbstractOpenApiParser implements ParserInterface
{
    /**
     * Validate the given content.
     *
     * @throws \NormanHuth\ApiGenerator\Exceptions\InvalidFormatException
     * @throws \NormanHuth\ApiGenerator\Exceptions\ParserException
     */
    protected function validate(string $content): void
    {
        if (! Str::isJson($content)) {
            throw new InvalidFormatException('JSON');
        }

        $data = (new Cast(json_decode($content, true)))->toArray();
        $version = (new Cast(data_get($data, 'swagger')))->toString();

        if (explode('.', $version)[0] != '2') {
            throw new InvalidFormatException('Swagger Version 2');
        }

        $this->name = (new Cast(data_get($data, 'info.title')))->toString();

        $this->data = $data;
    }
}
