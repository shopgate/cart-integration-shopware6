<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntityChannelInstaller extends EntityInstaller
{

    /** @var EntityRepositoryInterface */
    protected $salesChannelRepo;
    /** @var EntityRepositoryInterface */
    protected $entityChannelRepo;

    /**
     * @param ContainerInterface $container
     * @noinspection MissingService
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->salesChannelRepo = $container->get('sales_channel.repository');
        $this->entityChannelRepo = $container->get('sales_channel_' . $this->entityName . '.repository');
    }

    public function install(InstallContext $context): void
    {
        parent::install($context);
        foreach ($this->getEntities() as $method) {
            $this->enableEntityForAllChannels($method, $context->getContext());
        }
    }

    /**
     * @param ClassCastInterface $method
     * @param Context $context
     */
    private function enableEntityForAllChannels(
        ClassCastInterface $method,
        Context $context
    ): void {
        $channels = $this->salesChannelRepo->searchIds(new Criteria(), $context);
        $tableKey = $this->snakeToCamel($this->entityName . 'Id');
        foreach ($channels->getIds() as $channel) {
            $data = [
                'salesChannelId' => $channel,
                $tableKey => $method->getId(),
            ];

            $this->entityChannelRepo->upsert([$data], $context);
        }
    }

    /**
     * @param string $input
     * @return string
     */
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}
