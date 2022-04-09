<?php

declare(strict_types=1);

namespace PostsDMS\Validator;

use PostsDMS\Message\AbstractMessageValidator;

class CreatePostRules extends AbstractMessageValidator
{
    // https://github.com/rakit/validation/tree/v1.4.0#available-rules
    protected array $rules = [
        'payload.authorId' => self::UUID_REGEX_RULE,
        'payload.content' => 'required|between:16,65000',
    ];
    protected array $messages = [
        'payload.authorId:required' => 'author id is required',
        'payload.authorId' => 'author id format is not valid',
        'payload.content:required' => 'content is required',
        'payload.content:between' => 'content length is not between 16 and 65000 characters',
        'payload.content' => 'content format is not valid',
    ];
}
