<?php

declare(strict_types=1);

namespace Imhotep\Database\Migrations;

abstract class Migration
{
    public string|null $connection = null;

    public bool $useTransaction = true;

    public function up(): void
    {

    }

    public function down(): void
    {
        
    }
}