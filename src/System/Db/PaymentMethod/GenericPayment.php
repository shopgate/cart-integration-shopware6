<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\PaymentMethod;

use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopgate\Shopware\System\PaymentHandler\GenericHandler;

class GenericPayment extends AbstractPayment
{
    public const UUID = '7046c79435a7410fb90a8b82f13d30a9';

    protected $id = self::UUID;
    protected $name = 'Shopgate Payment';
    protected $description = 'Generic Shopgate payment method used for order imports';
    protected $paymentHandler = GenericHandler::class;
    protected $position = 10;
    protected $afterOrder = false;
    protected $availabilityRuleId = IsShopgateRuleGroup::UUID;
}
