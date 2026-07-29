<?php declare(strict_types=1);

namespace Imhotep\Database\Repository;

trait HasMultiTables
{
    protected array $tables = [];
    protected array $tableJoins = [];
    protected string $primaryTable = '';


}