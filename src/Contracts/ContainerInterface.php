<?php declare(strict_types=1);

namespace Imhotep\Contracts;

use ArrayAccess;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends ArrayAccess, PsrContainerInterface
{

}