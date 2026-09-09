<?php

namespace App\Concerns;

trait HasEnumOptions
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }
    public static function options(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->name],
            self::cases()
        );
    }
}
