/* global Shopware */
Shopware.Component.override('sw-order-detail', {
    computed: {
        orderCriteria() {
            /** @var {Shopware.Data.Criteria} criteria */
            const criteria = this.$super('orderCriteria');
            criteria.addAssociation('shopgateOrder');
            return criteria;
        }
    }
});
