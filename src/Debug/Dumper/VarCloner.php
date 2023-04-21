<?php

namespace Imhotep\Debug\Dumper;

class VarCloner
{
    protected int $maxDepth = 50;

    protected int $curDepth = -1;

    public function cloneVar(mixed $var): Data
    {
        return $this->doClone($var);
    }

    protected function doClone(mixed $var): Data
    {
        $this->curDepth++;
        if ($this->curDepth > $this->maxDepth) {
            return new Data(['type' => '', 'value' => '']);
        }

        $data = [
            'type' => gettype($var),
            'value' => $var,
        ];

        if ($data['type'] === 'string') {
            $data['length'] = mb_strlen($var, 'UTF-8');
        }
        elseif ($data['type'] === 'array') {
            $data['count'] = count($var);

            foreach ($var as $key => $val) {
                $data['value'][$key] = $this->doClone($val);
            }
        }
        elseif ($data['type'] === 'object') {
            $data['object_id'] = spl_object_id($var);
            $data['class_name'] = get_class($var);
            $data['value'] = [];

            $reflector = new \ReflectionObject($var);
            foreach ($reflector->getProperties() as $property) {
                $data['value'][] = $this->doCloneObjectProperty($property, $var);
            }
        }
        elseif (!in_array($data['type'], ['integer', 'double', 'boolean', 'NULL'])) {
            echo "VarDumper: type [{$data['type']}] not configured.";
            exit();
        }

        return new Data($data);
    }

    protected function doCloneObjectProperty(\ReflectionProperty $property, object $object): Data
    {
        $data = [
            'type' => 'property',
            'name' => $property->getName(),
            'value' => $this->doClone($property->getValue($object)),
            'is_public' => $property->isPublic(),
        ];

        return new Data($data);
    }
}