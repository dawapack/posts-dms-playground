<?php

declare(strict_types=1);

namespace PostsDMS\OutboundAdapters;

use PostsDMS\Exceptions\NotFoundException;
use Psr\Cache\InvalidArgumentException;

interface RepositoryInterface
{
    /**
     * @param string $id
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function getItem(string $id): array;

    /**
     * @param array $values
     *
     * @return string
     */
    public function saveItem(array $values): string;

    /**
     * @param string $id
     * @param array $values
     *
     * @return void
     *
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function updateItem(string $id, array $values): void;

    /**
     * @param string $id
     *
     * @return void
     *
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function deleteItem(string $id): void;
}
