<?php

namespace App\Enums;

enum MessageType: string
{
    case User = 'user';
    case System = 'system';
}
