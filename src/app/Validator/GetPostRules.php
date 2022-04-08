<?php

declare(strict_types=1);

namespace PostsDMS\Validator;

use PostsDMS\Message\AbstractMessageValidator;

class GetPostRules extends AbstractMessageValidator
{
    // https://github.com/rakit/validation/tree/v1.4.0#available-rules
    protected array $rules = [
        'payload.pathParams.postId' => self::UUID_REGEX_RULE,
        'headers.jobId' => self::UUID_REGEX_RULE,
    ];
    protected array $messages = [
        'payload.pathParams.postId:required' => 'postId path property is required',
        'payload.pathParams.postId' => 'postId path property format is not valid',
        'headers.jobId:required' => 'jobId header property is required',
        'headers.jobId' => 'jobId header property format is not valid',
    ];
}