import template from './sg-title-content.html.twig';

/* global Shopware */
const { Component, Mixin  } = Shopware;

Component.register('sg-title-content', {
    mixins: [
        Mixin.getByName('sg-order-key-value')
    ],
    template,
    props: {
        title: {
            type: String,
            required: true
        },
        content: {
            type: [String, Number, Boolean, Array],
            required: false,
            default: null
        }
    },

    computed: {
        normalizeTitle: function () {
            const key = this.title;
            return this.normalizeKey(key);
        },
        normalizeContent: function () {
            const content = this.content;
            return this.normalizeValue(content);
        },
        isEmpty: function () {
            return this.isDataEmpty(this.content);
        }
    }
});
