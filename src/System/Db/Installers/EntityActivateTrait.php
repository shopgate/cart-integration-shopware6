<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;

trait EntityActivateTrait
{
    /** @retrun ClassCastInterface */
    abstract public function getEntities(): array;

    public function activate(ActivateContext $context): void
    {
        foreach ($this->getEntities() as $entity) {
            $this->setIsActive(true, $entity, $context->getContext());
        }
    }

    protected function setIsActive(bool $active, ClassCastInterface $entity, Context $context): void
    {
        if ($this->entityExists($entity->getId(), $context) === false) {
            return;
        }

        $data = ['id' => $entity->getId(), 'active' => $active];
        $this->entityRepo->update([$data], $context);
    }

    protected function entityExists(string $id, Context $context): bool
    {
        if (empty($id)) {
            return false;
        }

        $criteria = new Criteria([$id]);
        $criteria->setTitle('shopgate::' . $this->entityName . '::id');
        $result = $this->entityRepo->search($criteria, $context);

        return $result->getTotal() !== 0;
    }

    /**
     * Only deactivates entities, deleting can cause data issues.
     * e.g. orders should not be referencing a non-existing shipping method, payment, etc
     */
    public function deactivate(Context $context): void
    {
        foreach ($this->getEntities() as $entity) {
            $this->setIsActive(false, $entity, $context);
        }
    }
}
