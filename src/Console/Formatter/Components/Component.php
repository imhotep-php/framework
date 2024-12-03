<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

use Imhotep\Contracts\Console\Output as OutputContract;

abstract class Component
{
    public function __construct(
        protected OutputContract $output
    ) {}

    protected function mutate(mixed $data, array $mutators): mixed
    {
        foreach ($mutators as $mutator) {
            if (is_iterable($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = app($mutator)->__invoke($value);
                }
            } else {
                $data = app($mutator)->__invoke($data);
            }
        }

        return $data;
    }
}