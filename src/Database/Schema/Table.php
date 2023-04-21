<?php

declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Closure;
use Imhotep\Database\Connection;

abstract class Table
{
    protected $name;

    protected $prefix;

    protected $columnClassDefault = Column::class;


    /**
     * Create column as primary auto-incrementing big integer (8-byte, 1 to 9223372036854775807).
     *
     * @param string $name
     * @return Column
     */
    abstract public function id(string $name = 'id'): Column;

    /**
     * Create string column
     *
     * @param $name
     * @param int|null $length
     * @return Column
     */
    abstract public function string($name, int $length = null): Column;

    abstract public function timestamps();


    /**
     * @var Column[]
     */
    protected array $columns = [];

    protected array $commands = [];

    public function __construct(string $name, Closure $callback = null, $prefix = '')
    {
        $this->name = $name;
        $this->prefix = $prefix;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    public function getName(){
        return $this->name;
    }

    public function build(Connection $connection, Grammar $grammar)
    {
        $statements = $this->toSql($grammar);

        foreach ($statements as $statement) {
            $connection->statement($statement);
        }
    }

    public function toSql($grammar)
    {
        $statements = [];

        foreach ($this->commands as $command) {
            $method = 'compile'.ucfirst($command['name']);

            if (method_exists($grammar, $method)) { //  || $grammar::hasMacro($method)
                if (! is_null($sql = $grammar->$method($this, $command))) {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }

        return $statements;
    }

    public function create()
    {
        $this->addCommand('create');

        return $this;
    }

    public function drop()
    {
        $this->addCommand('drop');

        return $this;
    }

    public function dropIfExists()
    {
        $this->addCommand('dropIfExists');

        return $this;
    }

    public function dropColumn(string|array $columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->addCommand('dropColumn', compact('columns'));

        return $this;
    }

    public function addColumn($type, $name, $parameters = [])
    {
        $column = new $this->columnClassDefault(
            array_merge(compact('type', 'name'), $parameters)
        );
        $this->columns[] = $column;
        return $column;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    protected function addCommand($name, $parameters = []): void
    {
        $this->commands[] = [
            'name' => $name,
            'parameters' => $parameters,
        ];
    }
}