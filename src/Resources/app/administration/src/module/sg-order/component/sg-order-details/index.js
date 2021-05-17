import template from './sg-order-details.html.twig';

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
    }
});
