Custom Collections
==================

Doctrine ORM can expose a to-many association through a `custom collection
class`_, which implements ``Doctrine\ORM\Collection\DelegateCollection``. When
the ORM loads an entity, it creates the custom collection without calling its
constructor, so the collection cannot receive dependencies. The
``#[AsDelegateCollection]`` attribute makes the ORM get the collection from the
service container instead.

.. note::

    Custom collections require Doctrine ORM >= 3.8 and DoctrineBundle >= 3.4.

Injecting Services into a Collection
------------------------------------

Add the ``#[AsDelegateCollection]`` attribute to the collection class and
declare its dependencies in the constructor:

.. code-block:: php

    // src/Collection/DocumentList.php
    namespace App\Collection;

    use App\Entity\Document;
    use App\Tenant\TenantContext;
    use Doctrine\Bundle\DoctrineBundle\Attribute\AsDelegateCollection;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Collection\DelegateCollection;
    use Traversable;

    #[AsDelegateCollection]
    final class DocumentList implements DelegateCollection
    {
        public function __construct(
            private readonly TenantContext $tenant,
            private Collection $collection = new ArrayCollection(),
        ) {
        }

        public function getIterator(): Traversable
        {
            return $this->collection
                ->filter(fn (Document $document) => $document->getTenant() === $this->tenant->getCurrent())
                ->getIterator();
        }
    }

The association is mapped like any other custom collection:

.. code-block:: php

    // src/Entity/Folder.php
    namespace App\Entity;

    use App\Collection\DocumentList;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity]
    class Folder
    {
        #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'folder')]
        private DocumentList $documents;

        // ...
    }

Each time the ORM creates the collection of an entity, for instance when it
loads the entity, it asks the container for a ``DocumentList``. The container
autowires ``$tenant``, then the ORM puts the managed collection in the
``$collection`` property. The service is not shared, so each entity gets its
own instance.

A custom collection without the attribute is still created by the ORM, without
calling its constructor.

.. warning::

    The attribute only applies to a class that is registered as a service with
    autoconfiguration. The ``config/services.yaml`` file of applications created
    before Symfony 7.3 excludes ``src/Entity/``: a collection stored next to
    the entities is not a service there, so the ORM creates it without calling
    its constructor and its dependencies are never set. Store the collection in
    another directory, ``src/Collection/`` for instance, or register it
    explicitly:

    .. code-block:: yaml

        # config/services.yaml
        services:
            App\Entity\DocumentList: ~

.. note::

    A collection that comes from the container is built by calling its
    constructor, unlike the collections that the ORM creates itself. The
    container only builds the collections of the entities that the ORM loads or
    refreshes: a new entity creates its collection itself, and must give it its
    dependencies.

Registering a Collection as a Service
-------------------------------------

The attribute is autoconfigured to the ``doctrine.orm.delegate_collection`` tag,
so tagging a service is equivalent to using the attribute. This is useful when
autoconfiguration is disabled. The bundle makes every tagged service non-shared,
and finds the service of a collection by its class:

.. configuration-block::

    .. code-block:: yaml

        # config/services.yaml
        services:
            App\Collection\DocumentList:
                tags:
                    - { name: doctrine.orm.delegate_collection }

    .. code-block:: php

        // config/services.php
        namespace Symfony\Component\DependencyInjection\Loader\Configurator;

        use App\Collection\DocumentList;

        return static function (ContainerConfigurator $container): void {
            $container->services()
                ->set(DocumentList::class)
                ->tag('doctrine.orm.delegate_collection');
        };

Replacing the Collection Factory
--------------------------------

The collections are created by the ``Doctrine\ORM\Collection\DelegateCollectionFactory``
of the entity manager. By default, the bundle configures the
``doctrine.orm.container_delegate_collection_factory`` service on every entity
manager. Use the ``delegate_collection_factory`` option to configure the id of
another service:

.. configuration-block::

    .. code-block:: yaml

        # config/packages/doctrine.yaml
        doctrine:
            orm:
                entity_managers:
                    default:
                        delegate_collection_factory: App\Collection\CollectionFactory

    .. code-block:: php

        // config/packages/doctrine.php
        namespace Symfony\Component\DependencyInjection\Loader\Configurator;

        use App\Collection\CollectionFactory;

        return App::config([
            'doctrine' => [
                'orm' => [
                    'entity_managers' => [
                        'default' => [
                            'delegate_collection_factory' => CollectionFactory::class,
                        ],
                    ],
                ],
            ],
        ]);

The entity manager then ignores the tagged collections, unless your factory
calls the ``doctrine.orm.container_delegate_collection_factory`` service.

.. _`custom collection class`: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#custom-collection-classes
