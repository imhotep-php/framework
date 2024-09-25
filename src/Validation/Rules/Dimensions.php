<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

use Imhotep\Validation\Rule;

class Dimensions extends Rule
{
    use Traits\UtilsTrait;

    protected mixed $default = null;

    public function setParameters(array $parameters): static
    {
        foreach ($parameters as $param) {
            $param = explode("=", $param);
            if (count($param) !== 2) continue;

            $this->parameters[$param[0]] = $param[1];
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        if ( !($value = $this->makeUploadedFile($value)) ) {
            return false;
        }

        if (! $value->isValidImage()) {
            return false;
        }

        if ( !($size = @getimagesize($value->getRealPath())) ) {
            return false;
        }

        if ($this->failsDimensionChecks((int)$size[0], (int)$size[1])) {
            return false;
        }

        if ($this->failsRatioCheck((int)$size[0], (int)$size[1])) {
            return false;
        }

        return true;
    }

    protected function failsDimensionChecks(int $width, int $height): bool
    {
        return ($this->width && (int)$this->width !== $width) ||
            ($this->min_width && (int)$this->min_width > $width) ||
            ($this->max_width && (int)$this->max_width < $width) ||
            ($this->height && (int)$this->height !== $height) ||
            ($this->min_height && (int)$this->min_height > $height) ||
            ($this->max_height && (int)$this->max_height < $height);
    }

    protected function failsRatioCheck(int $width, int $height): bool
    {
        if (! $this->ratio) {
            return false;
        }

        [$numerator, $denominator] = array_replace(
            [1, 1], array_filter(sscanf($this->ratio, '%f/%d'))
        );

        $precision = 1 / (max($width, $height) + 1);

        return abs($numerator / $denominator - $width / $height) > $precision;
    }
}