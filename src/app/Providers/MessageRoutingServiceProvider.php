<?php

declare(strict_types=1);

namespace PostsDMS\Providers;

use Chassis\Framework\Providers\RoutingServiceProvider;
use PostsDMS\OutboundAdapters\DemoOperationDelete;
use PostsDMS\OutboundAdapters\DemoOperationDeletedEvents;
use PostsDMS\OutboundAdapters\DemoOperationGetAsync;
use PostsDMS\OutboundAdapters\DemoOperationGetSync;
use PostsDMS\Services\DemoDeleteEventService;
use PostsDMS\Services\DemoMoreService;
use PostsDMS\Services\DemoService;

class MessageRoutingServiceProvider extends RoutingServiceProvider
{
    /**
     * @var array|string[]
     */
    protected array $inboundRoutes = [
        'createSomething' => [DemoService::class, 'create'],
        'getSomething' => [DemoService::class, 'get'],
        'getSomethingResponse' => [DemoMoreService::class, 'complete'],
        'deleteSomething' => [DemoService::class, 'delete'],
        'somethingDeleted' => DemoDeleteEventService::class,
    ];

    /**
     * @var array|string[]
     */
    protected array $outboundRoutes = [
        'getSomethingSync' => DemoOperationGetSync::class,
        'getSomethingAsync' => DemoOperationGetAsync::class,
        'deleteSomething' => DemoOperationDelete::class,
        'deleteSomethingEvent' => DemoOperationDeletedEvents::class,
    ];
}
