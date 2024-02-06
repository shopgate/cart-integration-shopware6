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

readonly class SalutationMapping
{
    public function __construct(
        private EntityRepository $swSalutationRepository,
        private EntityRepository $sgSalutationRepository,
        private ContextManager   $contextManager)
    {
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
        $result = $this->swSalutationRepository->search(
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

        return match ($entity->getSalutationKey()) {
            'mr' => ShopgateCustomer::MALE,
            'mrs' => ShopgateCustomer::FEMALE,
            default => null,
        };
    }

    private function getMappedSalutationId(string $gender): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('value', $gender));
        $criteria->setTitle('shopgate::sgSalutation::' . $gender);
        /** @var ?ShopgateSalutationEntity $result */
        $result = $this->sgSalutationRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getShopwareSalutationId() : $this->getAnySalutationId();
    }
}
