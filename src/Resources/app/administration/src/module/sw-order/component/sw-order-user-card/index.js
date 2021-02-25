import template from './sw-order-user-card.html.twig';

const {Criteria} = Shopware.Data;

Shopware.Component.override('sw-order-user-card', {
    template,
    inject: [
        'repositoryFactory'
    ],
    props: {
        currentOrder: {
            type: Object,
            required: true
        },
    },
    data() {
        return {
            shopgateOrder: null
        }
    },
    computed: {
        shopgateOrderRepo() {
            return this.repositoryFactory.create('shopgate_order');
        },
    },
    methods: {
        reload() {
            this.$super('reload')
            this.shopgateOrderRepo
                .search(this.orderCriteria(), Shopware.Context.api)
                .then((response) => {
                    const result = response.first()
                    if (!result) {
                        return;
                    }
                    this.shopgateOrder = result;
                })
        },

        orderCriteria() {
            const criteria = new Criteria()
            criteria.setLimit(1)
            criteria.addFilter(
                Criteria.equals('shopwareOrderId', this.currentOrder.id)
            );
            return criteria
        },

        getPaymentName() {
            if (this.shopgateOrder) {
                return this.shopgateOrder.receivedData.payment_infos.shopgate_payment_name
            }
            return 'Shopgate Payment'
        },

        getShippingName() {
            if (this.shopgateOrder) {
                return this.shopgateOrder.receivedData.shipping_infos.display_name
            }
            return 'Shopgate Shipping'
        }
    }
})
