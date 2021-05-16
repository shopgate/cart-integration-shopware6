/* global Shopware */
const {Criteria} = Shopware.Data;

export default {
    inject: [
        'repositoryFactory'
    ],
    data() {
        return {
            shopgateOrder: null
        };
    },
    props: {
        currentOrder: {
            type: Object,
            required: true
        }
    },
    computed: {
        shopgateOrderRepo() {
            return this.repositoryFactory.create('shopgate_order');
        },
        getPaymentName() {
            if (this.shopgateOrder) {
                return this.shopgateOrder.receivedData.payment_infos.shopgate_payment_name;
            }
            return null;
        },
        getShippingName() {
            if (this.shopgateOrder) {
                return this.shopgateOrder.receivedData.shipping_infos.display_name;
            }
            return null;
        }
    },
    created() {
        this.reload();
    },
    methods: {

        reload() {
            this.shopgateOrderRepo
                .search(this.orderCriteria(), Shopware.Context.api)
                .then((response) => {
                    const result = response.first();
                    if (!result) {
                        return;
                    }
                    this.shopgateOrder = result;
                });
        },

        orderCriteria() {
            const criteria = new Criteria();
            criteria.setLimit(1);
            criteria.addFilter(
                Criteria.equals('shopwareOrderId', this.getOrderId())
            );
            return criteria;
        },

        getOrderId() {
            return this.currentOrder ? this.currentOrder.id : this.order ? this.order.id : '';
        }
    }
};
