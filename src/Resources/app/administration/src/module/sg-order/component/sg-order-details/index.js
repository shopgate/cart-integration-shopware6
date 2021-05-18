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
    }
});
