<?php declare(strict_types=1);

namespace Imhotep\Database\Migrations;

abstract class Migration
{
    public string $name = '';

    public ?string $connection = null;

    public bool $useTransaction = true;

    public function __construct()
    {
        $this->name = static::class;
    }

    public function up(): void
    {

    }

    public function down(): void
    {
        
    }
}