<?php

namespace Imhotep\Debug\Cloner;

use Imhotep\Debug\Data;

class Cloner implements ICloner
{
    protected int $maxDepth = 50;

    protected array $objectHashes = [];

    protected int $curDepth = -1;

    public function cloneVar(mixed $var): Data
    {
        $this->curDepth = -1;
        $this->objectHashes = [];

        return $this->doClone($var);
    }

    protected function doClone(mixed $var): Data
    {
        $this->curDepth++;
        if ($this->curDepth > $this->maxDepth) {
            $this->curDepth--;
            return new Data(['type' => 'meta', 'value' => '...max depth reached...']);
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
            if (isset($this->objectHashes[$data['object_id']])) {
                $this->curDepth--;
                return new Data(['type' => 'recursion', 'value' => '...recursion...']);
            }
            $this->objectHashes[$data['object_id']] = true;

            $data['class_name'] = get_class($var);
            $data['value'] = [];

            $reflector = new \ReflectionObject($var);
            foreach ($reflector->getProperties() as $property) {
                $data['value'][] = $this->doCloneObjectProperty($property, $var);
            }
        }
        elseif ($data['type'] === 'resource (closed)') {
            $data['value'] = 'closed resource';
        }
        elseif ($data['type'] === 'resource') {
            $data['value'] = get_resource_type($var).' resource';
        }
        elseif (!in_array($data['type'], ['integer', 'double', 'boolean', 'NULL'])) {
            $data['value'] = "Unhandled type: ".$data['type'];
        }

        $this->curDepth--;
        return new Data($data);
    }

    protected function doCloneObjectProperty(\ReflectionProperty $property, object $object): Data
    {
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        if (! $property->isInitialized($object)) {
            $value = new Data([
                'type' => 'uninitialized',
                'value' => 'uninitialized'
            ]);
        }
        else {
            $value = $this->doClone($property->getValue($object));
        }

        if ($property->isPublic()) {
            $visibility = 'public';
        } elseif ($property->isProtected()) {
            $visibility = 'protected';
        } else {
            $visibility = 'private';
        }

        $data = [
            'type' => 'property',
            'name' => $property->getName(),
            'value' => $value,
            'visibility' => $visibility,
        ];

        return new Data($data);
    }
}