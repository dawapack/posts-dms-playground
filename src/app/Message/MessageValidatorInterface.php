<?php

declare(strict_types=1);

namespace PostsDMS\Message;

interface MessageValidatorInterface
{
    /**
     * @return bool
     */
    public function isValid(): bool;

    /**
     * @return array
     */
    public function getErrors(): array;
}
