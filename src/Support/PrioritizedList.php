<?php declare(strict_types=1);

namespace Imhotep\Support;

class PrioritizedList
{
    protected array $items = [];

    protected ?array $cache = null;

    public function add($item, int $priority): void
    {
        $this->items[$priority][] = $item;
        $this->cache = null;
    }

    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        krsort($this->items);

        foreach ($this->items as $group) {
            foreach ($group as $item) {
                $this->cache[] = $item;
            }
        }

        return $this->cache;
    }
}