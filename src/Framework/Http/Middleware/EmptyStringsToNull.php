<?php

namespace Imhotep\Framework\Http\Middleware;

use Imhotep\Http\Request;
use Imhotep\Http\Request\ParameterBug;

class EmptyStringsToNull
{
    protected array $except = [];

    public function handle(Request $request, \Closure $next)
    {
        $this->cleanParameters($request->query);
        $this->cleanParameters($request->post);
        $this->cleanParameters($request->json);

        return $next($request);
    }

    protected function cleanParameters(ParameterBug $bag): void
    {
        $data = $bag->all();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            $data[$key] = ($val === '') ? null : $val;
        }

        $bag->replace($data);
    }
}