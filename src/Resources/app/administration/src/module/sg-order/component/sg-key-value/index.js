import './sg-key-value.scss';

/* global Shopware */
const { Component, Mixin } = Shopware;

Component.register('sg-key-value', {
    mixins: [
        Mixin.getByName('sg-order-key-value')
    ],
    template: '<li v-if="!isEmpty"><span class="emphasize">{{ normalizeLabel }}</span>: {{ normalizeContent }}</li>',
    props: {
        label: {
            type: String,
            required: true
        },
        content: {
            type: [String, Number, Boolean, Array],
            required: true
        }
    },
    computed: {
        normalizeLabel: function () {
            const key = this.label;
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
