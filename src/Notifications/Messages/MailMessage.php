<?php declare(strict_types = 1);

namespace Imhotep\Notifications\Messages;

use Imhotep\Contracts\Notifications\INotificationMessage;

class MailMessage implements INotificationMessage
{
    protected array $headers = [];

    protected ?string $view = null;

    protected array $viewData;

    protected ?string $subject = null;

    protected ?string $greeting = null;

    protected array $lines = [];

    protected ?array $action = null;

    /**
     * Уникальный идентификатор уведомления
     *
     * @var string
     */
    public string $notificationId = '';

    /**
     * Уровень приоритета сообщения
     * 0 - приоритет не задан
     *
     * @var int
     */
    public int $priority = 0;

    /**
     * Список адресов для обратного ответа на сообщения
     *
     * @var array
     */
    public array $replyTo = [];

    /**
     * Список адресов, на которые будет отправлена копия сообщения
     *
     * @var array
     */
    public array $cc = [];

    /**
     * Список адресов, на которые будет отправлена скрытая копия сообщения
     *
     * @var array
     */
    public array $bcc = [];

    public function addHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Устанавливает уровень приоритета сообщения. Значение от 1 (очень важное) до 5 (неважно)
     *
     * @param int $priority
     * @return static
     */
    public function priority(int $priority): static
    {
        if ($priority >= 1 && $priority <= 5) {
            $this->priority = $priority;
        }

        return $this;
    }

    /**
     * Добавляет один или несколько адресов для обратного ответа
     *
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function replyTo(string|array $address, ?string $name = null): static
    {
        if (is_array($address)) {
            $this->replyTo += $this->parseAddresses($address);
        }
        else {
            $this->replyTo[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Добавляет один или несколько получателей копии сообщения
     *
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function cc(string|array $address, ?string $name = null): static
    {
        if (is_array($address)) {
            $this->cc += $this->parseAddresses($address);
        }
        else {
            $this->cc[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Добавляет один или несколько скрытых получателей копии сообщения
     *
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function bcc(string|array $address, ?string $name = null): static
    {
        if (is_array($address)) {
            $this->bcc += $this->parseAddresses($address);
        }
        else {
            $this->bcc[] = [$address, $name];
        }

        return $this;
    }

    /**
     * Форматирует список адресов в необходимый формат
     *
     * @param array $addresses
     * @return array
     */
    protected function parseAddresses(array $addresses): array
    {
        $result = [];

        foreach ($addresses as $address) {
            if (is_string($address)) {
                $result[] = [$address, null];
            }
            elseif (is_array($address)) {
                if (is_string($address[0]) && is_string($address[1])) {
                    $result[] = [$address[0], $address[1]];
                }
            }
        }

        return $result;
    }

    public function getHeaders(): object
    {
        return (object)array_merge([
            'priority' => $this->priority,
            'reply-to' => $this->replyTo,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
        ], $this->headers);
    }

    public function view(string $view, array $data = []): static
    {
        $this->view = $view;

        $this->viewData = $data;

        return $this;
    }


    public function subject(?string $subject = null): static|string
    {
        if (is_null($subject)) {
            return $this->subject ?? '';
        }

        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject ?? '';
    }

    public function greeting(string $greeting): static
    {
        $this->greeting = $greeting;

        return $this;
    }

    public function line(string|array $line): static
    {
        $this->lines = array_merge($this->lines, (array)$line);

        return $this;
    }

    public function lineIf(bool $boolean, string|array $line): static
    {
        if ($boolean) {
            $this->line($line);
        }

        return $this;
    }

    public function newLine(int $lines = 1): static
    {
        for ($i = 1; $i <= $lines; $i++) {
            $this->lines[] = "&nbsp;";
        }

        return $this;
    }

    public function action(string $text, string $url): static
    {
        $this->action = [$text, $url];

        return $this;
    }

    public function actionIf(bool $boolean, string $text, string $url): static
    {
        if ($boolean) {
            return $this->action($text, $url);
        }

        return $this;
    }

    public function attach(string $filename, array $mime = []): static
    {
        return $this;
    }

    public function attachData(string $data, array $mime = []): static
    {
        return $this;
    }

    public function render(): string
    {
        return $this->toHtml();
    }

    public function toHtml(): string
    {
        if ($this->view) {
            return app('view')->make($this->view, $this->viewData)->render();
        }

        array_walk($this->lines, function (&$value) {
            $value = "<p>{$value}</p>";
        });

        return implode('', $this->lines);
    }

    public function toArray(): array
    {
        return [
            'lines' => $this->lines,
            'action' => $this->action,
        ];
    }
}