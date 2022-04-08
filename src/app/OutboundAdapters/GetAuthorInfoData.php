<?php

declare(strict_types=1);

namespace PostsDMS\OutboundAdapters;

use Chassis\Framework\Adapters\Operations\AbstractOperationsAdapter;

class GetAuthorInfoData extends AbstractOperationsAdapter
{
    /**
     * Enable sync over async mode
     *
     * @var bool
     */
    protected bool $isSyncOverAsync = true;

    /**
     * In sync over async mode we provide the destination queue as routing key
     *
     * @var string
     */
    protected string $routingKey = "Authors.Q.Commands";

    /**
     * Message discriminator type
     *
     * @var string
     */
    protected string $operation = "getAuthorInfo";
}
