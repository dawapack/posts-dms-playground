<?php

declare(strict_types=1);

namespace PostsDMS\Services;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Logger\Logger;
use Chassis\Framework\Services\AbstractService;
use DateTime;
use PostsDMS\Exceptions\NotFoundException;
use PostsDMS\Message\ApplicationEventMessage;
use PostsDMS\Message\ApplicationMessage;
use PostsDMS\OutboundAdapters\RedisRepository;
use PostsDMS\OutboundAdapters\RepositoryInterface;
use PostsDMS\Validator\CreatePostRules;
use PostsDMS\Validator\DeletePostRules;
use PostsDMS\Validator\GetPostRules;
use PostsDMS\Validator\UpdatePostRules;
use Psr\Cache\InvalidArgumentException;
use Throwable;

class PostsService extends AbstractService
{
    private const INTERNAL_SERVER_ERROR_MESSAGE = 'the server encountered an unexpected condition';

    private RedisRepository $repository;
    private array $fillable = ['authorId', 'content'];

    /**
     * @param InboundMessageInterface $message
     * @param RepositoryInterface|null $repository
     */
    public function __construct(
        InboundMessageInterface $message,
        RepositoryInterface $repository = null
    ) {
        $this->repository = is_null($repository) ? new RedisRepository() : $repository;
        parent::__construct($message);
    }

    /**
     * @operation getPost
     */
    public function get(): OutboundMessage
    {
        try {
            $validation = new GetPostRules($this->message);
            if (!$validation->isValid()) {
                return $this->response(
                    new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
                );
            }
            $id = ($this->message->getBody())["pathParams"]["postId"];

            return $this->response(
                new ApplicationMessage([$this->repository->getItem($id)]), 200, "DONE"
            );
        } catch (NotFoundException $reason) {
            return $this->response(
                new ApplicationMessage([]), $reason->getCode(), "NOT_FOUND"
            );
        } catch (Throwable|InvalidArgumentException $reason) {
            Logger::error(
                $reason->getMessage(),
                [
                    'component' => 'post_service_get_exception',
                    'error' => $reason,
                ]
            );
        }

        return $this->response(
            new ApplicationMessage(['code' => 500, 'message' => self::INTERNAL_SERVER_ERROR_MESSAGE]),
            500,
            "INTERNAL_SERVER_ERROR"
        );
    }

    /**
     * @operation createPost
     */
    public function create(): OutboundMessage
    {
        try {
            $validation = new CreatePostRules($this->message);
            if (!$validation->isValid()) {
                return $this->response(
                    new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
                );
            }
            $data = array_merge(
                array_intersect_key(
                    $this->message->getBody(),
                    array_flip($this->fillable)
                ),
                [
                    'createdAt' => (new DateTime())->format(self::DEFAULT_DATETIME_FORMAT),
                    'updatedAt' => null,
                ]
            );

            // create post resource
            $id = $this->repository->saveItem($data);

            // send post created event
            $this->send(
                'postCreated',
                new ApplicationEventMessage(['postId' => $id, 'authorId' => $data['authorId']])
            );

            // return success
            return $this->response(
                new ApplicationMessage(['postId' => $id]), 200, "DONE"
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
        try {
            $validation = new UpdatePostRules($this->message);
            if (!$validation->isValid()) {
                return $this->response(
                    new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
                );
            }
            $id = $this->message->getHeader('x_pathParam_postId');
            $values = array_merge(
                $this->repository->getItem($id),
                array_intersect_key(
                    $this->message->getBody(),
                    array_flip($this->fillable)
                ),
                ['updatedAt' => (new DateTime())->format(self::DEFAULT_DATETIME_FORMAT)]
            );

            // update post resource
            $this->repository->updateItem($id, $values);

            // send post updated event
            $this->send(
                'postUpdated',
                new ApplicationEventMessage(['postId' => $id, 'authorId' => $values['authorId']])
            );

            // return success
            return $this->response(
                new ApplicationMessage(['postId' => $id]), 200, "DONE"
            );
        } catch (NotFoundException $reason) {
            // return not found
            return $this->response(
                new ApplicationMessage([]), $reason->getCode(), "NOT_FOUND"
            );
        } catch (Throwable|InvalidArgumentException $reason) {
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
    public function delete(): OutboundMessage
    {
        try {
            $validation = new DeletePostRules($this->message);
            if (!$validation->isValid()) {
                return $this->response(
                    new ApplicationMessage($validation->getErrors()), 400, 'BAD_REQUEST'
                );
            }
            $id = ($this->message->getBody())["pathParams"]["postId"];
            $values = $this->repository->getItem($id);

            // delete post resource
            $this->repository->deleteItem($id);

            // send post deleted event
            $this->send(
                'postDeleted',
                new ApplicationEventMessage(['postId' => $id, 'authorId' => $values['authorId']])
            );

            // return success
            return $this->response(
                new ApplicationMessage(['postId' => $id]), 200, "DONE"
            );
        } catch (NotFoundException $reason) {
            return $this->response(
                new ApplicationMessage([]), $reason->getCode(), "NOT_FOUND"
            );
        } catch (Throwable|InvalidArgumentException $reason) {
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
}
