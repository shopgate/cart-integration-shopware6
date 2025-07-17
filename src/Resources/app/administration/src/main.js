import './acl'
import './mixins/sg-order-key-value';
import './decorator/rule-condition-service-decoration';
import './module/sw-order/component/sw-order-general-info';
import './module/sw-order/view/sw-order-detail-details';
import './page/sw-order-detail'
import './module/sg-order/component/sg-order-details';
import './module/sg-order/component/sg-title-content';
import './module/sg-order/component/sg-key-value';
import './module';
import './core';

Shopware.Component.register('sg-icon', () => import('./module/sg-api-credentials/component/sg-icon'))
