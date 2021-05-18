import template from './sg-title-content.html.twig';

/* global Shopware */
const { Component } = Shopware;

Component.register('sg-title-content', {
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
    methods: {
        capitalize: (s) => {
            if (typeof s !== 'string') {
                return '';
            }
            return s.charAt(0).toUpperCase() + s.slice(1);
        },
        hasSlotData: function() {
            return (this.$slots.default && this.$slots.default[0]) &&
                ((this.$slots.default[0].text && this.$slots.default[0].text.length) ||
                    (this.$slots.default[0].children && this.$slots.default[0].children.length));
        },
        isEmptyArray: function (list) {
            return Array.isArray(list) && list.length === 0;
        },
        isDataEmpty: function (data) {
            return (data === 'undefined' ||
                data === '' ||
                data === null ||
                this.isEmptyArray(data)
            ) && !this.hasSlotData();
        },
        normalizeValue: function (value) {
            switch (value) {
                case 0:
                case '0':
                case false:
                    return this.$tc('sg-base.no');
                case 1:
                case '1':
                case true:
                    return this.$tc('sg-base.yes');
            }
            if (Array.isArray(value) && value.length > 0) {
                return value.join(', ');
            }
            return value;
        },
        normalizeKey: function (key) {
            return key.split('_').map(el => this.capitalize(el)).join(' ');
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
