import template from './sg-order-details.html.twig';
import './sg-order-details.scss';

/* global Shopware */
const {Component} = Shopware;

Component.register('sg-order-details', {
    template,

    props: {
        sgOrder: {
            type: Object,
            required: true,
            default() {
                return {};
            }
        }
    },

    computed: {
        hasShopgateCoupon: function () {
            const items = this.sgOrder?.receivedData?.items;
            return items && items.filter(item => item.type === 'sg_coupon').length;
        }
    }
});
