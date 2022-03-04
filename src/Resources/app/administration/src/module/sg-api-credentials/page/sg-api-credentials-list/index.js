import template from './sg-api-credentials-list.html.twig';

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('sg-api-credentials-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            items: null,
            isLoading: false,
            showDeleteModal: false,
            repository: null,
            total: 0
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        entityRepository() {
            return this.repositoryFactory.create('shopgate_api_credentials');
        },

        columns() {
            return this.getColumns();
        }
    },

    methods: {
        getList() {
            this.isLoading = true;
            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addAssociation('language');
            criteria.addAssociation('salesChannel');

            this.entityRepository.search(criteria, Shopware.Context.api).then((items) => {
                this.total = items.total;
                this.items = items;
                this.isLoading = false;

                return items;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        getColumns() {
            return [
                {
                    property: 'active',
                    label: 'sg-api-credentials.general.active',
                    allowResize: true,
                    align: 'center'
                },
                {
                    property: 'salesChannel.name',
                    label: 'sg-api-credentials.general.salesChannel',
                    routerLink: 'sg.api.credentials.detail',
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'language.name',
                    label: 'sg-api-credentials.general.language',
                    routerLink: 'sg.api.credentials.detail',
                    allowResize: true
                },
                {
                    property: 'customerNumber',
                    label: 'sg-api-credentials.general.customerNumber',
                    routerLink: 'sg.api.credentials.detail',
                    allowResize: true
                },
                {
                    property: 'shopNumber',
                    label: 'sg-api-credentials.general.shopNumber',
                    routerLink: 'sg.api.credentials.detail',
                    allowResize: true
                },
                {
                    property: 'apiKey',
                    label: 'sg-api-credentials.general.apiKey',
                    routerLink: 'sg.api.credentials.detail',
                    allowResize: true
                }
            ];
        }
    }
});

