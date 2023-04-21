<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Http;

interface Responsable
{
    public function toResponse(Request $request): Response;
}