import '../core/component/is-shopgate';

Shopware.Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('is_shopgate', {
        component: 'is-shopgate',
        label: 'Is Shopgate Mobile',
        scopes: ['global']
    });

    return ruleConditionService;
});
