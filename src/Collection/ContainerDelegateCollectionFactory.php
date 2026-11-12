<?php

declare(strict_types=1);

namespace Doctrine\Bundle\DoctrineBundle\Collection;

use Doctrine\ORM\Collection\DefaultDelegateCollectionFactory;
use Doctrine\ORM\Collection\DelegateCollection;
use Doctrine\ORM\Collection\DelegateCollectionFactory;
use Psr\Container\ContainerInterface;

/**
 * Fetches custom collections from the container or falls back to normal creation.
 *
 * @internal
 */
final class ContainerDelegateCollectionFactory implements DelegateCollectionFactory
{
    /** @param ContainerInterface $container A service locator containing the collections, indexed by class */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly DelegateCollectionFactory $default = new DefaultDelegateCollectionFactory(),
    ) {
    }

    public function create(string $collectionClass): DelegateCollection
    {
        if ($this->container->has($collectionClass)) {
            return $this->container->get($collectionClass);
        }

        return $this->default->create($collectionClass);
    }
}
