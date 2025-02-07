Shopware.Service('privileges').addPrivilegeMappingEntry({
  category: 'permissions',
  parent: 'shopgate',
  key: 'shopgate_go',
  roles: {
    viewer: {
      privileges: [
        'sales_channel:read',
        'language:read',
        'locale:read',
        'shopgate_api_credentials:read',
        'shopgate_go_salutations:read',
        'shopgate_go_category_product_mapping:read',
        'shopgate_order:read'
      ],
      dependencies: []
    },
    editor: {
      privileges: [
        'shopgate_api_credentials:update',
        'shopgate_go_salutations:update',
        'shopgate_go_category_product_mapping:update',
        'shopgate_order:update'
      ],
      dependencies: [
        'shopgate_go.viewer'
      ]
    },
    creator: {
      privileges: [
        'shopgate_api_credentials:create',
        'shopgate_go_salutations:create',
        'shopgate_go_category_product_mapping:create',
        'shopgate_order:create'
      ],
      dependencies: [
        'shopgate_go.editor',
        'shopgate_go.viewer'
      ]
    },
    deleter: {
      privileges: [
        'shopgate_api_credentials:delete',
        'shopgate_go_salutations:delete',
        'shopgate_go_category_product_mapping:delete',
        'shopgate_order:delete'
      ],
      dependencies: [
        'shopgate_go.editor',
        'shopgate_go.viewer'
      ]
    }
  }
})
