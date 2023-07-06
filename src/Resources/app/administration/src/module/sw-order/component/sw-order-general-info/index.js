import template from './sw-order-general-info.twig';
import loader from '../../../../shopgateOrderLoader';
/* global Shopware */
Shopware.Component.override('sw-order-general-info', {
    template,
    ...loader
});
