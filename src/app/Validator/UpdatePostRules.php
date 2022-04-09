<?php

declare(strict_types=1);

namespace PostsDMS\Validator;

use PostsDMS\Message\AbstractMessageValidator;

class UpdatePostRules extends AbstractMessageValidator
{
    // https://github.com/rakit/validation/tree/v1.4.0#available-rules
    protected array $rules = [
        'payload.authorId' => self::UUID_REGEX_RULE,
        'payload.content' => 'required|between:16,65000',
        'headers.x_pathParam_postId' => self::UUID_REGEX_RULE,
    ];
    protected array $messages = [
        'payload.authorId:required' => 'author id is required',
        'payload.authorId' => 'author id format is not valid',
        'payload.content:required' => 'content is required',
        'payload.content:between' => 'content length is not between 16 and 65000 characters',
        'payload.content' => 'content is not valid',
        'headers.x_pathParam_postId:required' => 'post id is required',
        'headers.x_pathParam_postId' => 'post id format is not valid',
    ];
}
