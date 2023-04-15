<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Shopgate\SalutationExtension;
use Shopgate\Shopware\Shopgate\Salutations\ShopgateSalutationEntity;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCustomer;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Salutation\SalutationEntity;

class SalutationMapping
{
    private EntityRepositoryInterface $swSalutationRepo;
    private EntityRepositoryInterface $sgSalutationRepo;
    private ContextManager $contextManager;

    public function __construct(EntityRepositoryInterface $swSalutationRepository, EntityRepositoryInterface $sgSalutationRepository, ContextManager $contextManager)
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
     * @deprecated 3.x will be removed
     */
    public function getMaleSalutationId(): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'mr'));
        $criteria->setTitle('shopgate::swSalutation::male');
        $result = $this->swSalutationRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : $this->getUnspecifiedSalutationId();
    }

    /**
     * @deprecated 3.x will be removed
     */
    public function getUnspecifiedSalutationId(): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'not_specified'));
        $criteria->setTitle('shopgate::swSalutation::unspecified');
        $result = $this->swSalutationRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : $this->getAnySalutationId();
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

    /**
     * @deprecated 3.x will be removed
     */
    public function getFemaleSalutationId(): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'mrs'));
        $criteria->setTitle('shopgate::swSalutation::female');
        $result = $this->swSalutationRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : $this->getUnspecifiedSalutationId();
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
