<?php

declare(strict_types=1);

namespace Imhotep\Database\Calypte\Exceptions;

class ModelNotFoundException extends \Exception
{
    protected $model;

    protected $ids;

    public function setModel(string $model, string|array $ids = [])
    {
        $this->model = $model;

        $this->ids = is_array($ids) ? $ids: [$ids];

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ', ids: '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }
}