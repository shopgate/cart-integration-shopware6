/* global Shopware */

export default {
    computed: {
        getPaymentName() {
            if (this.getOrder().extensions.hasOwnProperty('shopgateOrder')) {
                return this.getOrder().extensions.shopgateOrder.receivedData.payment_infos.shopgate_payment_name;
            }
            return null;
        },
        getShippingName() {
            if (this.getOrder().extensions.hasOwnProperty('shopgateOrder')) {
                return this.getOrder().extensions.shopgateOrder.receivedData.shipping_infos.display_name;
            }
            return null;
        }
    },
    methods: {
        getOrder() {
            return this.currentOrder ? this.currentOrder : this.order ? this.order : '';
        },
    }
};
