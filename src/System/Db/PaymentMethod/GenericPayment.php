<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\PaymentMethod;

use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopgate\Shopware\System\PaymentHandler\GenericHandler;

class GenericPayment extends AbstractPayment
{
    final public const UUID = '7046c79435a7410fb90a8b82f13d30a9';

    protected string $id = self::UUID;
    protected string $name = 'Shopgate Payment';
    protected string $description = 'Generic Shopgate payment method used for order imports';
    protected string $paymentHandler = GenericHandler::class;
    protected int $position = 10;
    protected bool $afterOrder = false;
    protected string $availabilityRuleId = IsShopgateRuleGroup::UUID;
    protected string $technicalName = 'sg_generic_payment';
}
