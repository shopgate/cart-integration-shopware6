import template from './sg-config-api-notice.html.twig';

const {Component, Context, Data: {Criteria}} = Shopware;

Component.register('sg-config-api-notice', {
    template,
    inject: [
        'repositoryFactory'
    ],
    mixins: [],
    props: {},
    data() {
        return {
            isLoading: false,
            repository: null,
            total: 0
        };
    },
    computed: {
        entityRepository() {
            return this.repositoryFactory.create('shopgate_api_credentials');
        },
        hasConfigs() {
            return this.total > 0;
        }
    },
    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadEntityData();
        },

        loadEntityData() {
            this.isLoading = true;
            const criteria = new Criteria(1, 1);
            this.entityRepository.search(criteria, Context.api)
                .then(items => {
                    this.total = items.total;
                    this.isLoading = false;
                })
                .catch(() => {
                    this.isLoading = false;
                });
        }
    }
});
