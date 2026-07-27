<?php

namespace App\Enums;

enum JenisTransaksi: string
{
    case Online = 'online';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
        };
    }
}
