<?php declare(strict_types = 1);

namespace Imhotep\Validation;

use Exception;
use Imhotep\Contracts\Validation\IValidator as ValidatorContract;
use Imhotep\Facades\Validator as ValidatorFacade;
use Imhotep\Support\MessageBag;

class ValidationException extends Exception
{
    public ValidatorContract $validator;

    public mixed $response;

    public int $status = 422;

    public ?string $redirectTo = null;

    public function __construct(ValidatorContract $validator, mixed $response = null)
    {
        parent::__construct(static::summarize($validator));

        $this->validator = $validator;
        $this->response = $response;
    }

    public static function withMessages(array $messages): static
    {
        return new static(tap(ValidatorFacade::make([], []), function ($validator) use ($messages) {
            $validator->errors()->add($messages);
        }));
    }

    protected static function summarize(ValidatorContract $validator)
    {
        $messages = $validator->errors()->all();

        if (count($messages) === 0 || ! is_string($messages[0])) {
            return $validator->getLocalizator()->get('The given data was invalid');
        }

        $message = array_shift($messages);

        if ($count = count($messages)) {
            $message .= ' '.$validator->getLocalizator()->get("(and :count more {:count | error | errors})", compact('count'));
        }

        return $message;
    }

    public function errors(): MessageBag
    {
        return $this->validator->errors();
    }

    public function status(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function redirectTo(string $url): static
    {
        $this->redirectTo = $url;

        return $this;
    }
}