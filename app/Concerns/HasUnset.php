<?php

namespace App\Concerns;

trait HasUnset
{
    protected function unsetAttributes(array $attributes): void
    {
        foreach ($attributes as $attribute) {
            unset($this->{$attribute});
        }
    }
}
