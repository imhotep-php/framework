<?php declare(strict_types = 1);

namespace Imhotep\Validation\Data;

class InputData extends Data
{
    public function wildcard(string $key): mixed
    {
        return $this->wildcardRecursive($this->data, explode('.', $key));
    }

    protected function wildcardRecursive(mixed $data, array $segments, array $prepend = []): array
    {
        if (! is_array($data) || empty($segments)) {
            return [];
        }

        $segment = array_shift($segments);

        if ($segment === '*') {
            $result = [];

            if (empty($segments)) {
                foreach ($data as $key => $value) {
                    $result[] = implode('.', [...$prepend, $key]);
                }
            }
            else {
                foreach ($data as $key => $value) {
                    $result = [...$result, ...$this->wildcardRecursive($value, $segments, [...$prepend, $key])];
                }
            }

            return $result;
        }

        if (! empty($segments)) {
            return $this->wildcardRecursive($data[$segment] ?? null, $segments, [...$prepend, $segment]);
        }

        return [implode('.', [...$prepend, $segment])];
    }
}