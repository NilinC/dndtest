<?php

namespace App\Model;

enum Headers
{
    case Sku;
    case Status;
    case Price;
    case Description;
    case CreatedAt;
    case Slug;

    public static function serialize(): array
    {
        return [
            self::Sku->name,
            self::Status->name,
            self::Price->name,
            self::Description->name,
            self::CreatedAt->name,
            self::Slug->name
        ];
    }
}
