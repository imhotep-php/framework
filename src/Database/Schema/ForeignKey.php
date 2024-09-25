<?php declare(strict_types=1);

namespace Imhotep\Database\Schema;

use Imhotep\Support\Fluent;

/**
 * @method ForeignKey deferrable(bool $value = true) Set the foreign key as deferrable (PostgreSQL)
 * @method ForeignKey initiallyImmediate(bool $value = true) Set the default time to check the constraint (PostgreSQL)
 * @method ForeignKey on(string $table) Specify the referenced table
 * @method ForeignKey onDelete(string $action) Add an ON DELETE action
 * @method ForeignKey onUpdate(string $action) Add an ON UPDATE action
 * @method ForeignKey references(string|array $columns) Specify the referenced column(s)
 */
class ForeignKey extends Fluent
{
    /**
     * Set ON UPDATE action to RESTRICT;
     *
     * @return $this
     */
    public function onUpdateRestrict(): static
    {
        return $this->onUpdate('RESTRICT');
    }

    /**
     * Set ON UPDATE action to SET NULL;
     *
     * @return $this
     */
    public function onUpdateSetNull(): static
    {
        return $this->onUpdate('SET NULL');
    }

    /**
     * Set ON UPDATE action to SET DEFAULT;
     *
     * @return $this
     */
    public function onUpdateSetDefault(): static
    {
        return $this->onUpdate('SET DEFAULT');
    }

    /**
     * Set ON UPDATE action to CASCADE;
     *
     * @return $this
     */
    public function onUpdateCascade(): static
    {
        return $this->onUpdate('CASCADE');
    }

    /**
     * Set ON DELETE action to RESTRICT;
     *
     * @return $this
     */
    public function onDeleteRestrict(): static
    {
        return $this->onDelete('RESTRICT');
    }

    /**
     * Set ON DELETE action to SET NULL;
     *
     * @return $this
     */
    public function onDeleteSetNull(): static
    {
        return $this->onDelete('SET NULL');
    }

    /**
     * Set ON DELETE action to SET DEFAULT;
     *
     * @return $this
     */
    public function onDeleteSetDefault(): static
    {
        return $this->onDelete('SET DEFAULT');
    }

    /**
     * Set ON DELETE action to CASCADE;
     *
     * @return $this
     */
    public function onDeleteCascade(): static
    {
        return $this->onDelete('CASCADE');
    }
}