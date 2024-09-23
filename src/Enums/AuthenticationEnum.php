<?php

namespace NormanHuth\ApiGenerator\Enums;

enum AuthenticationEnum
{
    case BEARER;
    case BASIC;
    case DIGEST;
}
