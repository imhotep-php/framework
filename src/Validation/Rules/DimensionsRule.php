<?php declare(strict_types=1);

namespace Imhotep\Validation\Rules;

class DimensionsRule extends AbstractRule
{
    use Traits\UtilsTrait;

    public function setParameters(array $parameters): static
    {
        $listNames = ['width','min_width','max_width','height','min_height','max_height','ratio'];

        foreach ($parameters as $param) {
            $param = explode("=", $param);
            if (count($param) !== 2) continue;

            $name = str_replace("-", "_", $param[0]);
            $value = $param[1];

            if (in_array($name, $listNames) && $value >= 0) {
                $this->parameters[$name] = (int)$value;
            }

            if ($name === 'ratio') {
                $this->parameters['ratio'] = $value;
            }
        }

        return $this;
    }

    public function check(mixed $value): bool
    {
        if ( !($value = $this->makeUploadedFile($value)) ) {
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
        return ($this->width && $this->width !== $width) ||
            ($this->min_width && $this->min_width > $width) ||
            ($this->max_width && $this->max_width < $width) ||
            ($this->height && $this->height !== $height) ||
            ($this->min_height && $this->min_height > $height) ||
            ($this->max_height && $this->max_height < $height);
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