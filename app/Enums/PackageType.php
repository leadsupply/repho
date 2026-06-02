<?php

namespace App\Enums;

enum PackageType: string
{
    case GitHub = 'github';
    case GitLab = 'gitlab';
    case Git = 'git';
}
