import template from './sw-order-detail-base.html.twig';
import './sw-order-detail-base.scss';

const {Criteria} = Shopware.Data;
/* global Shopware */
Shopware.Component.override('sw-order-detail-base', {
    template,
    inject: [
        'repositoryFactory'
    ],
    computed: {
        shopgateOrderRepo() {
            return this.repositoryFactory.create('shopgate_order');
        },
    },
    created() {
        this.$on('loading-change', this.reload);
    },

    destroyed() {
        this.$off('loading-change');
    },

    methods: {

        reload() {
            this.shopgateOrderRepo
                .search(this.getOrderCriteria(), Shopware.Context.api)
                .then((response) => {
                    const result = response.first();
                    if (!result) {
                        return;
                    }
                    const shopgateOrder = result;
                    if (this.order.hasOwnProperty('extensions')) {
                        this.order.extensions = {
                            ...this.order.extensions,
                            shopgateOrder
                        };
                    }
                    this.order.extensions = {shopgateOrder};
                });
        },

        getOrderCriteria() {
            const criteria = new Criteria();
            criteria.setLimit(1);
            criteria.addFilter(
                Criteria.equals('shopwareOrderId', this.orderId)
            );
            return criteria;
        },

    }
});
