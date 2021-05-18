/* global Shopware */
const { Component } = Shopware;

Component.register('sg-key-value', {
    template: '<li><strong>{{ normalizeKey }}</strong>: {{ normalizeValue }}</li>',
    props: {
        label: {
            type: String,
            required: true
        },
        value: {
            type: [String, Number, Boolean, Array],
            required: true
        }
    },
    methods: {
        capitalize: (s) => {
            if (typeof s !== 'string') {
                return '';
            }
            return s.charAt(0).toUpperCase() + s.slice(1);
        },
    },
    computed: {
        normalizeKey: function () {
            const key = this.label;
            return key.split('_').map(el => this.capitalize(el)).join(' ');
        },
        normalizeValue: function () {
            switch (this.value) {
                case 0:
                case '0':
                case false:
                    return this.$tc('sg-base.no');
                case 1:
                case '1':
                case true:
                    return this.$tc('sg-base.yes');
            }
            return this.value;
        }
    }
});
