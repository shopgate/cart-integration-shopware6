const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

import template from './sg-api-credentials-detail.html.twig';

Component.register('sg-api-credentials-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            item: null,
            repository: null,
            isLoading: false,
            processSuccess: false,
            isSaveSuccessful: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        languageOptions() {
            return this.item.languages;
        }
    },

    methods: {
        createdComponent() {
            this.repository = this.repositoryFactory.create('shopgate_api_credentials');
            this.getEntity();
        },

        getEntity() {
            const criteria = new Criteria();
            criteria.addAssociation('salesChannel.languages');

            this.repository
                .get(this.$route.params.id, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.item = entity;
                });
        },

        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('sg-api-credentials.detail.titleNotificationError');
            const messageSaveError = this.$tc(
                'sg-api-credentials.detail.messageSaveError'
            );
            const titleSaveSuccess = this.$tc('sg-api-credentials.detail.titleNotificationSuccess');
            const messageSaveSuccess = this.$tc(
                'sg-api-credentials.detail.messageSaveSuccess'
            );

            this.isSaveSuccessful = false;
            this.isLoading = true;

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.getEntity();
                    this.isLoading = false;
                    this.processSuccess = true;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                }).catch(() => {
                this.isLoading = false;
                this.createNotificationError({
                    title: titleSaveError,
                    message: messageSaveError
                });
            });
        },

        saveFinish() {
            this.processSuccess = false;
        }
    }
});
