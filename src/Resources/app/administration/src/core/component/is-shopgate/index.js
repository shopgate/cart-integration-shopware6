import template from './is-shopgate.html.twig';
/* global Shopware */
Shopware.Component.extend('is-shopgate', 'sw-condition-base', {
    template,
    computed: {
        selectValues() {
            return [
                {
                    label: this.$tc('global.sw-condition.condition.yes'),
                    value: true
                },
                {
                    label: this.$tc('global.sw-condition.condition.no'),
                    value: false
                }
            ];
        },
        isShopgate: {
            get() {
                this.ensureValueExist();

                if (this.condition.value.isShopgate === null) {
                    this.condition.value.isShopgate = false;
                }

                return this.condition.value.isShopgate;
            },
            set(isShopgate) {
                this.ensureValueExist();
                this.condition.value = {...this.condition.value, isShopgate: isShopgate};
            }
        }
    }
});
