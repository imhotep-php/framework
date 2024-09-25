<?php declare(strict_types=1);

namespace Imhotep\Database\MySQL;

use Imhotep\Database\Connector as ConnectorBase;
use PDO;

class Connector extends ConnectorBase
{
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        //PDO::ATTR_TIMEOUT => 1
    ];
}