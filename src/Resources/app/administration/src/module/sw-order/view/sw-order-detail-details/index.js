import template from './sw-order-detail-details.twig';
import loader from '../../../../shopgateOrderLoader';
/* global Shopware */
Shopware.Component.override('sw-order-detail-details', {
    template,
    computed: {
        ...loader.computed
    },
    methods: {
        ...loader.methods
    }
});
