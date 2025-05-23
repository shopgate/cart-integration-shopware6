import '../core/component/is-shopgate';

/* global Shopware */
Shopware.Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('is_shopgate', {
        component: 'is-shopgate',
        label: 'sg.condition.is-shopgate',
        scopes: ['global'],
        group: 'misc'
    });

    return ruleConditionService;
});
