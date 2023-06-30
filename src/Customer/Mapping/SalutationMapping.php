<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Shopgate\SalutationExtension;
use Shopgate\Shopware\Shopgate\Salutations\ShopgateSalutationEntity;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCustomer;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Salutation\SalutationEntity;

class SalutationMapping
{
    private EntityRepository $swSalutationRepo;
    private EntityRepository $sgSalutationRepo;
    private ContextManager $contextManager;

    public function __construct(EntityRepository $swSalutationRepository, EntityRepository $sgSalutationRepository, ContextManager $contextManager)
    {
        $this->swSalutationRepo = $swSalutationRepository;
        $this->sgSalutationRepo = $sgSalutationRepository;
        $this->contextManager = $contextManager;
    }

    public function getSalutationIdByGender(string $gender): string
    {
        return $this->getMappedSalutationId($gender) ?: $this->getAnySalutationId();
    }

    /**
     * This is the last fallback and should not be needed
     * Return any SalutationId as it is required to register customers
     */
    public function getAnySalutationId(): string
    {
        $criteria = new Criteria();
        $criteria->setTitle('shopgate::swSalutation::any');
        $result = $this->swSalutationRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getId();
    }

    public function toShopgateGender(SalutationEntity $entity): ?string
    {
        $extension = $entity->getExtension(SalutationExtension::PROPERTY);
        if ($extension && $value = $extension->getVars()['value']) {
            return $value;
        }

        switch ($entity->getSalutationKey()) {
            case 'mr':
                return ShopgateCustomer::MALE;
            case 'mrs':
                return ShopgateCustomer::FEMALE;
            default:
                return null;
        }
    }

    private function getMappedSalutationId(string $gender): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('value', $gender));
        $criteria->setTitle('shopgate::sgSalutation::' . $gender);
        /** @var ?ShopgateSalutationEntity $result */
        $result = $this->sgSalutationRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getShopwareSalutationId() : $this->getAnySalutationId();
    }
}
