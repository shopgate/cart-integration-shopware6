import template from './sw-settings-salutation-detail.html.twig';

Shopware.Component.override('sw-settings-salutation-detail', {
    template,
    computed: {
        sgValue: {
            get() {
                return this.salutation.extensions.shopgateSalutation ? this.salutation.extensions.shopgateSalutation.value : null;
            },
            set(value) {
                if (this.salutation.extensions.shopgateSalutation) {
                    this.salutation.extensions.shopgateSalutation.value = value;
                } else {
                    const entityFactory = this.repositoryFactory.create('shopgate_go_salutations');
                    const shopgateSalutation = entityFactory.create(Shopware.Context.api);
                    shopgateSalutation.shopwareSalutationId = this.salutationId;
                    shopgateSalutation.value = value;
                    this.salutation.extensions = {shopgateSalutation};
                }
            }
        }
    }
});
