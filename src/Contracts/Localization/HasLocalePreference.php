<?php declare(strict_types=1);

namespace Imhotep\Contracts\Localization;

interface HasLocalePreference
{
    /**
     * Get the user's preferred locale.
     *
     * @return string
     */
    public function preferredLocale(): string;
}