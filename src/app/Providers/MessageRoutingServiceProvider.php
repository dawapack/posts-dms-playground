<?php

declare(strict_types=1);

namespace PostsDMS\Providers;

use Chassis\Framework\Providers\RoutingServiceProvider;
use PostsDMS\OutboundAdapters\GetAuthorInfoData;
use PostsDMS\OutboundAdapters\PostCreatedEvent;
use PostsDMS\OutboundAdapters\PostDeletedEvent;
use PostsDMS\OutboundAdapters\PostUpdatedEvent;
use PostsDMS\Services\PostsService;

class MessageRoutingServiceProvider extends RoutingServiceProvider
{
    /**
     * @var array|string[]
     */
    protected array $inboundRoutes = [
        'getPost' => [PostsService::class, 'get'],
        'createPost' => [PostsService::class, 'create'],
        'updatePost' => [PostsService::class, 'update'],
        'deletePost' => [PostsService::class, 'delete'],
    ];

    /**
     * @var array|string[]
     */
    protected array $outboundRoutes = [
        'getAuthorInfo' => GetAuthorInfoData::class,
        'postCreated' => PostCreatedEvent::class,
        'postUpdated' => PostUpdatedEvent::class,
        'postDeleted' => PostDeletedEvent::class,
    ];
}
