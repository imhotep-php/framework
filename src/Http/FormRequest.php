<?php declare(strict_types=1);

namespace Imhotep\Http;

use Imhotep\Contracts\Auth\AuthorizationException;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Validation\IFactory;
use Imhotep\Contracts\Validation\IValidator;
use Imhotep\Validation\ValidationException;

abstract class FormRequest
{
    protected IValidator $validator;

    protected ?string $redirect = null;

    protected ?string $redirectRoute = null;

    protected ?string $redirectAction = null;

    public function __construct(
        protected IFactory $validatorFactory,
        protected Request $request
    )
    {
        if ($this->autoValidate()) {
            $this->validate();
        }
    }

    protected function autoValidate(): bool
    {
        return true;
    }

    protected function stopOnFirstFailure(): bool
    {
        return true;
    }

    public function validate(): void
    {
        if (! $this->authorize()) {
            $this->failedAuthorization();
        }

        $data = $this->data();
        $data = array_merge($data, $this->before($data));

        $this->validator = $this->validatorFactory->make($data, $this->rules(), $this->messages(), $this->attributes());

        foreach ($this->after() as $validation) {
            $this->validator->after($validation);
        }

        if ($this->validator->fails()) {
            $this->failedValidation();
        }

        foreach ($this->validator->validated()->toArray() as $property => $value) {
            $this->{$property} = $value;
        }

        $this->passedValidation();
    }

    public function data(): array
    {
        return $this->request->all();
    }

    public function validated(): array
    {
        return $this->validator->validated()->toArray();
    }

    public function defaults(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function before(array $rawData): array
    {
        return [];
    }

    public function after(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    public function passesAuthorization(): bool
    {
        return true;
    }

    public function passedValidation(): void
    {

    }

    public function failedValidation(): void
    {
        throw new ValidationException($this->validator);
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function __call(string $name, array $arguments)
    {
        return $this->request->{$name}(...$arguments);
    }
}