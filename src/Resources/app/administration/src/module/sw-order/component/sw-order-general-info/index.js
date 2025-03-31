import template from './sw-order-general-info.twig';
import loader from '../../../../shopgateOrderLoader';
/* global Shopware */
Shopware.Component.override('sw-order-general-info', {
    template,
    computed: {
        ...loader.computed,
        dateFilter() {
            return Shopware.Filter.getByName('date');
        }
    },
    methods: {
        ...loader.methods
    }
});
