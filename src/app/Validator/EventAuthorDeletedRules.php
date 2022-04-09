<?php

declare(strict_types=1);

namespace PostsDMS\Validator;

use PostsDMS\Message\AbstractMessageValidator;

class EventAuthorDeletedRules extends AbstractMessageValidator
{
    // https://github.com/rakit/validation/tree/v1.4.0#available-rules
    protected array $rules = [
        'payload.authorId' => self::UUID_REGEX_RULE,
    ];
    protected array $messages = [
        'payload.authorId:required' => 'author id is required',
        'payload.authorId' => 'author id format is not valid',
    ];
}