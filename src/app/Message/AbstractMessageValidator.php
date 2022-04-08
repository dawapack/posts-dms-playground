<?php

declare(strict_types=1);

namespace PostsDMS\Message;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Bus\Exceptions\MessageBusException;
use JsonException;
use Rakit\Validation\Validation;
use Rakit\Validation\Validator;

abstract class AbstractMessageValidator implements MessageValidatorInterface
{
    protected const UUID_REGEX_RULE = 'required|regex:/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/';

    protected Validation $validation;
    protected array $rules = [];
    protected array $messages = [];

    /**
     * @param InboundMessageInterface $message
     *
     * @throws MessageBusException
     * @throws JsonException
     */
    public function __construct(InboundMessageInterface $message)
    {
        $inputs = [
            'properties' => $message->getProperties(),
            'headers' => $message->getHeaders(),
            'payload' => $message->getBody()
        ];
        $this->validation = (new Validator($this->messages))->validate($inputs, $this->rules);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->validation->fails();
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        $errors = $this->validation
            ->errors()
            ->firstOfAll(':message', true);
        return array_map(
            function (string $key, string $value) { return [$key => $value]; },
            array_keys($errors),
            array_values($errors)
        );
    }
}
