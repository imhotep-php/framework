<?php declare(strict_types=1);

namespace Imhotep\Notifications\Messages;

use Imhotep\Contracts\Notifications\INotificationMessage;

/**
 * Message to send by Firebase Cloud Messaging Service.
 *
 * @see https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages
 */
class FcmMessage implements INotificationMessage
{
    protected array $message = [];

    public function __construct(?string $title = null, ?string $body = null, ?string $image = null)
    {
        if ($title) $this->message['notification']['title'] = $title;
        if ($body) $this->message['notification']['body'] = $body;
        if ($image) $this->message['notification']['image'] = $image;
    }

    public function name(string $name): static
    {
        $this->message['name'] = $name;

        return $this;
    }

    public function topic(string $topic): static
    {
        $this->message['topic'] = $topic;

        return $this;
    }

    public function condition(string $condition): static
    {
        $this->message['condition'] = $condition;

        return $this;
    }

    public function title(string $title): static
    {
        $this->message['notification']['title'] = $title;

        return $this;
    }

    public function body(string $body): static
    {
        $this->message['notification']['body'] = $body;

        return $this;
    }

    public function image(string $image): static
    {
        $this->message['notification']['image'] = $image;

        return $this;
    }

    public function data(array $data): static
    {
        $this->message['data'] = $data;

        return $this;
    }

    /**
     * @param array $config
     * @return $this
     *
     * @see https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages
     * @see https://developer.mozilla.org/en-US/docs/Web/API/Notification
     */
    public function webpush(array $config): static
    {
        $this->message['webpush'] = $config;

        return $this;
    }

    /**
     * @param array $config
     * @return $this
     *
     * @see https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages
     */
    public function android(array $config): static
    {
        $this->message['android'] = $config;

        return $this;
    }

    /**
     * @param array $config
     * @return $this
     *
     * @see https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages
     * @see https://developer.apple.com/documentation/usernotifications/generating-a-remote-notification
     */
    public function apns(array $config): static
    {
        $this->message['apns'] = $config;

        return $this;
    }

    public function fcmOptions(string $options): static
    {
        $this->message['fcm_options'] = $options;

        return $this;
    }

    public function analyticsLabel(string $label): static
    {
        $this->message['fcm_options']['analytics_label'] = $label;

        return $this;
    }

    public function toArray(): array
    {
        return $this->message;
    }
}