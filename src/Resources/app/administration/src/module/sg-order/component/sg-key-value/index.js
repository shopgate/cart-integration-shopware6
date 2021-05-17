import template from './sg-key-calue.html.twig';

/* global Shopware */
const { Component } = Shopware;

Component.register('sg-key-value', {
    inheritAttrs: false,
    template,
    props: {
        title: {
            type: String,
            required: true
        },
        content: {
            type: [String, Number, Boolean],
            required: false
        }
    },
    methods: {
        capitalize: (s) => {
            if (typeof s !== 'string') {
                return '';
            }
            return s.charAt(0).toUpperCase() + s.slice(1);
        }
    },
    computed: {
        normalizeTitle: function () {
            const key = this.title;
            return key.split('_').map(el => this.capitalize(el)).join(' ');
        },
        normalizeContent: function () {
            switch (this.content) {
                case 0:
                case '0':
                case false:
                    return this.$tc('sg-base.no');
                case 1:
                case '1':
                case true:
                    return this.$tc('sg-base.yes');
            }
            return this.content;
        },
        isEmpty: function () {
            return this.content === 'undefined' || this.content === '' || this.content === null;
        }
    }
});
