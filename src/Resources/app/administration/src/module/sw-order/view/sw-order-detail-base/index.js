import template from './sw-order-detail-base.html.twig';
import './sw-order-detail-base.scss';

const {Criteria} = Shopware.Data;
/* global Shopware */
Shopware.Component.override('sw-order-detail-base', {
    template,
    computed: {
        orderCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria
                .addAssociation('lineItems')
                .addAssociation('currency')
                .addAssociation('orderCustomer')
                .addAssociation('language');

            criteria
                .getAssociation('deliveries')
                .addSorting(Criteria.sort('shippingCosts.unitPrice', 'DESC'));

            criteria
                .getAssociation('salesChannel')
                .getAssociation('mailTemplates')
                .addAssociation('mailTemplateType');

            criteria
                .addAssociation('addresses.country')
                .addAssociation('addresses.countryState')
                .addAssociation('deliveries.shippingMethod')
                .addAssociation('deliveries.shippingOrderAddress')
                .addAssociation('transactions.paymentMethod')
                .addAssociation('documents.documentType')
                .addAssociation('tags');
            criteria.addAssociation('shopgateOrder');

            criteria.getAssociation('transactions').addSorting(Criteria.sort('createdAt'));
            return criteria;
        }
    }
});
