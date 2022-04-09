<?php

declare(strict_types=1);

namespace PostsDMS\Services;

use Chassis\Framework\Adapters\Message\InboundMessageInterface;
use Chassis\Framework\Bus\Exceptions\MessageBusException;
use Chassis\Framework\Logger\Logger;
use JsonException;
use PostsDMS\Exceptions\BadRequestException;
use PostsDMS\OutboundAdapters\RedisRepository;
use PostsDMS\OutboundAdapters\RepositoryInterface;
use PostsDMS\Validator\EventAuthorDeletedRules;
use Throwable;

class EventsService
{
    private InboundMessageInterface $message;
    private RedisRepository $repository;

    public function __construct(RepositoryInterface $repository = null)
    {
        $this->repository = is_null($repository) ? new RedisRepository() : $repository;
    }

    /**
     * Nobody cares about implementation
     *
     * @param InboundMessageInterface $message
     *
     * @return void
     *
     * @throws MessageBusException
     * @throws JsonException
     */
    public function __invoke(InboundMessageInterface $message): void
    {
        $this->message = $message;
        switch ($this->message->getProperty('type')) {
            case "authorCreated":
            case "authorUpdated":
                // nothing to do - just skip
                break;
            case "authorDeleted":
                $this->deleteRelatedPosts();
                break;
            default:
                Logger::info(
                    'got unhandled event type',
                    [
                        'component' => 'post_events_service_info',
                        'message' => [
                            'properties' => $message->getProperties(),
                            'headers' => $message->getHeaders(),
                            'payload' => $message->getBody(),
                        ],
                    ]
                );
        }
    }

    /**
     * @return void
     *
     * @throws MessageBusException
     * @throws JsonException
     */
    private function deleteRelatedPosts(): void
    {
        try {
            $validation = new EventAuthorDeletedRules($this->message);
            if (!$validation->isValid()) {
                throw new BadRequestException("event message validation error");
            }

            // delete all posts associated to author
            $this->repository
                ->deleteAuthorPosts(
                    ($this->message->getBody())["authorId"]
                );
        } catch (BadRequestException $reason) {
            Logger::emergency(
                'event message validation error',
                [
                    'component' => 'post_events_service_emergency',
                    'message' => [
                        'properties' => $this->message->getProperties(),
                        'headers' => $this->message->getHeaders(),
                        'payload' => $this->message->getBody(),
                    ],
                ]
            );
        } catch (Throwable $reason) {
            Logger::error(
                '',
                [
                    'component' => 'post_events_service_error',
                    'message' => [
                        'properties' => $this->message->getProperties(),
                        'headers' => $this->message->getHeaders(),
                        'payload' => $this->message->getBody(),
                    ],
                    'error' => $reason,
                ]
            );
        }
    }
}
