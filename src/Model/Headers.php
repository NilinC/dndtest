<?php

namespace App\Model;

enum Headers: String
{
    case Sku = "Sku";
    case Status = "Status";
    case Price = "Price";
    case Description = "Description";
    case CreatedAt = "Created At";
    case Slug = "Slug";

    public static function serialize(): array
    {
        return [
            self::Sku->value,
            self::Status->value,
            self::Price->value,
            self::Description->value,
            self::CreatedAt->value,
            self::Slug->value
        ];
    }
}
