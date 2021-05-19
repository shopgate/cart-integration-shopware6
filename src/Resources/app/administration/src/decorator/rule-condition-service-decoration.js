import '../core/component/is-shopgate';

/* global Shopware */
Shopware.Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('is_shopgate', {
        component: 'is-shopgate',
        label: 'Is Shopgate Mobile',
        scopes: ['global']
    });

    return ruleConditionService;
});
