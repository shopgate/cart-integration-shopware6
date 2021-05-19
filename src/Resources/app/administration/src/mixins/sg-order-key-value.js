/* global Shopware */
const { Mixin } = Shopware;

Mixin.register('sg-order-key-value', {
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
});
