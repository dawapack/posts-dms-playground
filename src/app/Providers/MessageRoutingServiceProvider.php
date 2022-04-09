<?php

declare(strict_types=1);

namespace PostsDMS\Providers;

use Chassis\Framework\Providers\RoutingServiceProvider;
use PostsDMS\OutboundAdapters\GetAuthorData;
use PostsDMS\OutboundAdapters\PostCreatedEvent;
use PostsDMS\OutboundAdapters\PostDeletedEvent;
use PostsDMS\OutboundAdapters\PostUpdatedEvent;
use PostsDMS\Services\EventsService;
use PostsDMS\Services\PostsService;

class MessageRoutingServiceProvider extends RoutingServiceProvider
{
    /**
     * @var array|string[]
     */
    protected array $inboundRoutes = [
        // commands
        'getPost' => [PostsService::class, 'get'],
        'createPost' => [PostsService::class, 'create'],
        'updatePost' => [PostsService::class, 'update'],
        'deletePost' => [PostsService::class, 'delete'],

        // events
        'authorCreated' => EventsService::class,
        'authorUpdated' => EventsService::class,
        'authorDeleted' => EventsService::class,
    ];

    /**
     * @var array|string[]
     */
    protected array $outboundRoutes = [
        // commands
        'getAuthor' => GetAuthorData::class,

        // events
        'postCreated' => PostCreatedEvent::class,
        'postUpdated' => PostUpdatedEvent::class,
        'postDeleted' => PostDeletedEvent::class,
    ];
}
