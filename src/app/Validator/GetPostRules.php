<?php

declare(strict_types=1);

namespace PostsDMS\Validator;

use PostsDMS\Message\AbstractMessageValidator;

class GetPostRules extends AbstractMessageValidator
{
    // https://github.com/rakit/validation/tree/v1.4.0#available-rules
    protected array $rules = [
        'payload.pathParams.postId' => self::UUID_REGEX_RULE,
    ];
    protected array $messages = [
        'payload.pathParams.postId:required' => 'post id is required',
        'payload.pathParams.postId' => 'post id format is not valid',
    ];
}
