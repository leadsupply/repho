<?php

namespace App\Enums;

enum RepositoryAuthType: string
{
    case None = 'none';
    case Basic = 'basic';
    case Token = 'token';
}
