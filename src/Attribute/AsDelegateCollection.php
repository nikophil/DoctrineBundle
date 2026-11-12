<?php

declare(strict_types=1);

namespace Doctrine\Bundle\DoctrineBundle\Attribute;

use Attribute;

/**
 * Registers the class as a custom collection created by the dependency
 * injection container, so its constructor can receive services.
 *
 * Each entity gets its own instance. Requires doctrine/orm >= 3.8.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsDelegateCollection
{
}
