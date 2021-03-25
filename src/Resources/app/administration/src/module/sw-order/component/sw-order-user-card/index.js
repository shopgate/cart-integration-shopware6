import template from './sw-order-user-card.html.twig';
import loader from '../../../../shopgateOrderLoader'

Shopware.Component.override('sw-order-user-card', {
    template,
    ...loader
})
