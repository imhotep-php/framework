<?php

namespace Imhotep\Framework\Http\Middleware;

use Imhotep\Http\ParameterBag;
use Imhotep\Http\Request;

class TrimStrings
{
    protected array $except = [];

    public function handle(Request $request, \Closure $next)
    {
        $this->cleanParameters($request->query);
        $this->cleanParameters($request->post);
        $this->cleanParameters($request->json);

        return $next($request);
    }

    protected function cleanParameters(ParameterBag $bag): void
    {
        $data = $bag->all();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            $data[$key] = $this->cleanValue($val);
        }

        $bag->replace($data);
    }

    protected function cleanValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_replace('/^[\s\x{FEFF}\x{200B}]+|[\s\x{FEFF}\x{200B}]+$/u', '', $value) ?? trim($value);
    }
}