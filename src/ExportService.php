<?php declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Catalog\Category\CategoryComposer;
use Shopgate\Shopware\Catalog\Product\ProductComposer;
use Shopgate\Shopware\Catalog\Review\ReviewComposer;
use Shopgate\Shopware\Catalog\Review\ReviewMapping;
use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Order\CartComposer;
use Shopgate\Shopware\Order\OrderComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate\Shopware\System\Tax\TaxComposer;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use ShopgateCustomer;
use ShopgateExternalOrder;
use ShopgateLibraryException;
use ShopgatePluginApi;

class ExportService
{

    public function __construct(
        private readonly LoggerInterface $log,
        private readonly CategoryComposer $categoryComposer,
        private readonly ConfigBridge $configBridge,
        private readonly TaxComposer $taxComposer,
        private readonly CustomerComposer $customerComposer,
        private readonly ProductComposer $productComposer,
        private readonly OrderComposer $orderComposer,
        private readonly CartComposer $cartComposer,
        private readonly ReviewComposer $reviewComposer
    ) {
    }

    /**
     * @param string[] $ids
     * @return Shopgate_Model_Catalog_Category[]
     */
    public function getCategories(?int $limit = null, ?int $offset = null, array $ids = []): array
    {
        $this->log->debug('Start Category Export...');

        $export = $this->categoryComposer->buildCategoryTree($ids, $limit, $offset);
        $this->log->debug('End Category-Tree Build...');
        $this->log->debug('Finished Category Export...');

        return $export;
    }

    /**
     * @param string[] $ids
     * @return Shopgate_Model_Catalog_Product[]
     */
    public function getProducts(?int $limit = null, ?int $offset = null, array $ids = []): array
    {
        $this->log->debug('Start Product Export...');
        $export = $this->productComposer->loadProducts($limit, $offset, $ids);
        $this->log->debug('Finished Product Export...');

        return $export;
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function getCustomer(string $user, string $password): ShopgateCustomer
    {
        return $this->customerComposer->getCustomer($user, $password);
    }

    /**
     * @return string[]
     */
    public function getInfo(): array
    {
        return [
            'Shopware core version' => $this->configBridge->getShopwareVersion()
        ];
    }

    /**
     * @return ShopgateExternalOrder[]
     */
    public function getOrders(string $token, int $limit, int $offset, string $sortOrder, ?string $orderDateFrom): array
    {
        return $this->orderComposer->getOrders($token, $limit, $offset, $sortOrder, $orderDateFrom);
    }

    public function getSettings(): array
    {
        return [
            'customer_groups' => $this->customerComposer->getCustomerGroups(),
            'tax' => $this->taxComposer->getTaxSettings(),
            'allowed_address_countries' => [],
            'allowed_shipping_countries' => [],
            'payment_methods' => [],
        ];
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function checkCart(ExtendedCart $cart): array
    {
        return $this->cartComposer->checkCart($cart);
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function cron(string $jobname): void
    {
        $this->log->debug('Start cronjob ' . $jobname);
        switch ($jobname) {
            case ShopgatePluginApi::JOB_SET_SHIPPING_COMPLETED:
                $this->log->debug('Start setShippingCompleted');
                $this->orderComposer->setShippingCompleted();
                break;
            case ShopgatePluginApi::JOB_CANCEL_ORDERS:
                $this->log->debug('Start cancelOrders');
                $this->orderComposer->cancelOrders();
                break;
            default:
                $this->log->debug('Cronjob name could not be mapped');
                throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CRON_UNSUPPORTED_JOB);
        }
    }

    /**
     * @return ReviewMapping[]
     */
    public function getReviews(?int $limit, ?int $offset, array $uids): array
    {
        return $this->reviewComposer->getReviews($limit, $offset, $uids);
    }
}
