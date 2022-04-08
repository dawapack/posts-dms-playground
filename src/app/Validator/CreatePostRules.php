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
        'headers.jobId' => self::UUID_REGEX_RULE,
    ];
    protected array $messages = [
        'payload.authorId:required' => 'payload authorId property is required',
        'payload.authorId' => 'payload authorId property format is not valid',
        'payload.content:required' => 'payload content property is required',
        'payload.content:between' => 'payload content length must be between 16 and 65000 characters',
        'payload.content' => 'payload content format is not valid',
        'headers.jobId:required' => 'jobId header property is required',
        'headers.jobId' => 'jobId header property format is not valid',
    ];
}