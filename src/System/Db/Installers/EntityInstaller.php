<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntityInstaller
{
    protected array $entityInstallList = [];
    protected string $entityName;
    protected ?EntityRepository $entityRepo;

    public function __construct(ContainerInterface $container)
    {
        $this->entityRepo = $container->get($this->entityName . '.repository');
    }

    public function install(InstallContext $context): void
    {
        foreach ($this->getEntities() as $method) {
            $this->upsertEntity($method, $context->getContext());
        }
    }

    /**
     * @return ClassCastInterface[]
     */
    protected function getEntities(): array
    {
        return array_map(static fn(string $method) => new $method(), $this->entityInstallList);
    }

    protected function upsertEntity(ClassCastInterface $entity, Context $context): void
    {
        $data = $entity->toArray();
        $existingEntity = $this->findEntity($entity->getId(), $context);
        $existingEntity ? $this->updateEntity($data, $context) : $this->installEntity($data, $context);
    }

    protected function findEntity(string $id, Context $context): ?object
    {
        $criteria = new Criteria([$id]);
        $criteria->setTitle('shopgate::' . $this->entityName . '::id');

        return $this->entityRepo->search($criteria, $context)->first();
    }

    protected function updateEntity(array $data, Context $context): void
    {
        $this->entityRepo->update([$data], $context);
    }

    protected function installEntity(array $info, Context $context): void
    {
        $this->entityRepo->create([$info], $context);
    }
}
