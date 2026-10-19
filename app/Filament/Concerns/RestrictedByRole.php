<?php

namespace App\Filament\Concerns;

/**
 * Limits a resource, page or cluster to users whose role grants `$module`.
 * Set `protected static string $module` on the class using it.
 */
trait RestrictedByRole
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canAccessModule(static::$module);
    }
}
