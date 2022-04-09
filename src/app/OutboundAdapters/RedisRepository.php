<?php

declare(strict_types=1);

namespace PostsDMS\OutboundAdapters;

use PostsDMS\Exceptions\NotFoundException;
use Cache\Adapter\Common\CacheItem;
use Chassis\Framework\Adapters\Outbound\Cache\CacheFactoryInterface;
use Chassis\Framework\Adapters\Outbound\Cache\RedisCache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Ramsey\Uuid\Uuid;

use function Chassis\Helpers\app;

class RedisRepository implements RepositoryInterface
{
    private const NOT_FOUND_MESSAGE = 'requested resource not found';
    private RedisCache $cache;

    /**
     * @param CacheFactoryInterface|null $cache
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(CacheFactoryInterface $cache = null)
    {
        $this->cache = is_null($cache) ? app(CacheFactoryInterface::class) : $cache;
    }

    /**
     * @inheritDoc
     */
    public function getItem(string $id): array
    {
        $key = $this->cache->keyPrefix() . $id;

        // get resource
        $item = $this->cache->pool()->getItem($key);

        // handle not found exception
        if (!$item->isHit()) {
            throw new NotFoundException(self::NOT_FOUND_MESSAGE, 404);
        }

        return $item->get();
    }

    /**
     * @inheritDoc
     */
    public function saveItem(array $values): string
    {
        $id = Uuid::uuid4()->toString();
        $key = $this->cache->keyPrefix() . $id;

        // create resource
        $this->cache->pool()->save(new CacheItem($key, true, $values));

        // add resource id to author list
        $key = $this->cache->keyPrefix() . "author." . $values['authorId'];
        $this->cache->client()->lpush($key, $id);

        return $id;
    }

    /**
     * @inheritDoc
     */
    public function updateItem(string $id, array $values): void
    {
        $key = $this->cache->keyPrefix() . $id;
        $item = $this->cache->pool()->getItem($key);

        if (!$item->isHit()) {
            throw new NotFoundException(self::NOT_FOUND_MESSAGE, 404);
        }

        // update resource data
        $item->set(array_merge($item->get(), $values));
        $this->cache->pool()->save($item);
    }

    /**
     * @inheritDoc
     */
    public function deleteItem(string $id): void
    {
        $key = $this->cache->keyPrefix() . $id;
        $item = $this->cache->pool()->getItem($key);

        if (!$item->isHit()) {
            throw new NotFoundException(self::NOT_FOUND_MESSAGE, 404);
        }
        $values = $item->get();

        // delete resource
        $this->cache->pool()->deleteItem($key);

        // delete resource from author list also
        $key = $this->cache->keyPrefix() . "author." . $values['authorId'];
        $this->cache->client()->lrem($key, $id, 0);
    }
}
