<?php

declare(strict_types=1);

namespace Imhotep\Http;

class FormRequest extends Request
{

    /*protected function failedValidation(Validator $validator)
    {
        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }*/

    protected function passesAuthorization()
    {
        /*if (method_exists($this, 'authorize')) {
            $result = $this->container->call([$this, 'authorize']);

            return $result instanceof Response ? $result->authorize() : $result;
        }*/

        return true;
    }

    public function validated()
    {

    }

    public function messages()
    {

    }

    public function failedValidation()
    {

    }

    public function setValidator()
    {

    }

    public function setContainer()
    {

    }
}