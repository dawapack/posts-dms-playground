<?php

declare(strict_types=1);

namespace PostsDMS\Services;

use Cache\Adapter\Common\CacheItem;
use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Adapters\Outbound\Cache\CacheFactoryInterface;
use Chassis\Framework\Adapters\Outbound\Cache\RedisCache;
use Chassis\Framework\Logger\Logger;
use Chassis\Framework\Services\AbstractService;
use DateTime;
use PostsDMS\Exceptions\PostNotFoundException;
use PostsDMS\Message\ApplicationMessage;
use PostsDMS\Validator\CreatePostRules;
use PostsDMS\Validator\DeletePostRules;
use PostsDMS\Validator\GetPostRules;
use PostsDMS\Validator\UpdatePostRules;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

use function Chassis\Helpers\app;

/**
 * use /tests/phpstorm-helpers/amqp.http to trigger this sample
 */
class PostsService extends AbstractService
{
    private const INTERNAL_SERVER_ERROR_MESSAGE = 'the server encountered an unexpected condition';
    private const POST_NOT_FOUND_MESSAGE = 'post entry not found';
    private RedisCache $cache;

    /**
     * @param InboundMessageInterface $message
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(InboundMessageInterface $message)
    {
        $this->cache = app(CacheFactoryInterface::class);
        parent::__construct($message);
    }

    /**
     * @operation getPost
     */
    public function get(): OutboundMessage
    {
        $validation = new GetPostRules($this->message);
        if (!$validation->isValid()) {
            return $this->response(
                new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
            );
        }

        $body = $this->message->getBody();
        $postId = $body["pathParams"]["postId"];

        try {
            return $this->response(
                new ApplicationMessage([$this->getCache($postId)]), 200, "DONE"
            );
        } catch (PostNotFoundException $reason) {
            return $this->response(
                new ApplicationMessage([]), $reason->getCode(), "NOT_FOUND"
            );
        } catch (Throwable $reason) {
            Logger::error(
                $reason->getMessage(),
                [
                    'component' => 'post_service_get_exception',
                    'error' => $reason,
                ]
            );
        }

        return $this->response(
            new ApplicationMessage([]), 500, "INTERNAL_SERVER_ERROR"
        );
    }

    /**
     * @operation createPost
     */
    public function create(): OutboundMessage
    {
        $validation = new CreatePostRules($this->message);
        if (!$validation->isValid()) {
            return $this->response(
                new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
            );
        }

        $allowedProperties = ['authorId', 'content'];
        $payload = $this->message->getBody();
        $createdAt = (new DateTime())->format(self::DEFAULT_DATETIME_FORMAT);

        try {
            $postId = $this->setCache(
                array_merge(
                    array_intersect_key($payload, array_flip($allowedProperties)),
                    ['created_at' => $createdAt, 'updated_at' => null]
                )
            );

            // create & send post created event
            $this->send(
                'postCreated',
                new ApplicationMessage(['authorId' => $payload['authorId'], 'postId' => $postId])
            );

            // return success
            return $this->response(
                new ApplicationMessage(['postId' => $postId]), 200, "DONE"
            );
        } catch (Throwable $reason) {
            // log other exceptions
            Logger::error(
                $reason->getMessage(),
                [
                    'component' => 'post_service_create_exception',
                    'error' => $reason,
                ]
            );
        }

        // finally, return internal server error
        return $this->response(
            new ApplicationMessage(['code' => 500, 'message' => self::INTERNAL_SERVER_ERROR_MESSAGE])
            , 500
            , "INTERNAL_SERVER_ERROR"
        );
    }

    /**
     * @operation updatePost
     */
    public function update(): OutboundMessage
    {
        $validation = new UpdatePostRules($this->message);
        if (!$validation->isValid()) {
            return $this->response(
                new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
            );
        }

        $payload = $this->message->getBody();
        $postId = $this->message->getHeader("x_postId");

        try {
            $this->updateCache($postId, ['content' => $payload['content']]);

            // create & send post created event
            $this->send(
                'postUpdated',
                new ApplicationMessage(['authorId' => $payload['authorId'], 'postId' => $postId])
            );

            // return success
            return $this->response(
                new ApplicationMessage(['postId' => $postId]), 200, "DONE"
            );
        } catch (PostNotFoundException $reason) {
            // return not found
            return $this->response(
                new ApplicationMessage([]), $reason->getCode(), "NOT_FOUND"
            );
        } catch (Throwable $reason) {
            // log other exceptions
            Logger::error(
                $reason->getMessage(),
                [
                    'component' => 'post_service_update_exception',
                    'error' => $reason,
                ]
            );
        }

        // finally, return internal server error
        return $this->response(
            new ApplicationMessage(['code' => 500, 'message' => self::INTERNAL_SERVER_ERROR_MESSAGE])
            , 500
            , "INTERNAL_SERVER_ERROR"
        );
    }

    /**
     * @operation deletePost
     */
    public function delete()
    {
        $validation = new DeletePostRules($this->message);
        if (!$validation->isValid()) {
            return $this->response(
                new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
            );
        }

        $payload = $this->message->getBody();
        $postId = $payload["pathParams"]["postId"];
        $authorId = $payload["queryParams"]["authorId"];

        try {
            $this->deleteCache($postId);

            // create & send post created event
            $this->send(
                'postDeleted',
                new ApplicationMessage(['authorId' => $authorId, 'postId' => $postId])
            );

            // return success
            return $this->response(
                new ApplicationMessage(['postId' => $postId]), 200, "DONE"
            );
        } catch (PostNotFoundException $reason) {
            return $this->response(
                new ApplicationMessage([]), $reason->getCode(), "NOT_FOUND"
            );
        } catch (Throwable $reason) {
            Logger::error(
                $reason->getMessage(),
                [
                    'component' => 'post_service_delete_exception',
                    'error' => $reason,
                ]
            );
        }

        return $this->response(
            new ApplicationMessage(['code' => 500, 'message' => self::INTERNAL_SERVER_ERROR_MESSAGE])
            , 500
            , "INTERNAL_SERVER_ERROR"
        );
    }

    /**
     * @param string $postId
     *
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws PostNotFoundException
     */
    private function getCache(string $postId): array
    {
        $key = $this->cache->keyPrefix() . $postId;

        // get item from cache
        $item = $this->cache
            ->pool()
            ->getItem($key);

        // handle not found exception
        if (!$item->isHit()) {
            throw new PostNotFoundException(self::POST_NOT_FOUND_MESSAGE, 404);
        }

        return $item->get();
    }

    /**
     * @param array $value
     *
     * @return string
     */
    private function setCache(array $value): string
    {
        $postId = Uuid::uuid4()->toString();
        $key = $this->cache->keyPrefix() . $postId;

        // save post data to cache
        $this->cache
            ->pool()
            ->save(new CacheItem($key, true, $value));

        // add post id to author posts list
        $key = $this->cache->keyPrefix() . "author." . $value["authorId"];
        $this->cache
            ->client()
            ->lpush($key, $postId);

        return $postId;
    }

    /**
     * @param string $postId
     * @param array $values
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws PostNotFoundException
     */
    private function updateCache(string $postId, array $values): void
    {
        $key = $this->cache->keyPrefix() . $postId;
        $item = $this->cache
            ->pool()
            ->getItem($key);

        if (!$item->isHit()) {
            throw new PostNotFoundException(self::POST_NOT_FOUND_MESSAGE, 404);
        }

        // update post data
        $item->set(
            array_merge(
                $item->get(),
                $values,
                ['updated_at' => (new DateTime())->format(self::DEFAULT_DATETIME_FORMAT)]
            )
        );

        // save to cache
        $this->cache
            ->pool()
            ->save($item);
    }

    /**
     * @param string $postId
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws PostNotFoundException
     */
    private function deleteCache(string $postId): void
    {
        $key = $this->cache->keyPrefix() . $postId;
        $item = $this->cache
            ->pool()
            ->getItem($key);

        if (!$item->isHit()) {
            throw new PostNotFoundException(self::POST_NOT_FOUND_MESSAGE, 404);
        }
        $postData = $item->get();

        // delete post data
        $this->cache
            ->pool()
            ->deleteItem($key);

        // delete post id from author posts list
        $key = $this->cache->keyPrefix() . "author." . $postData['authorId'];
        $this->cache
            ->client()
            ->lrem($key, $postId, 0);
    }
}
