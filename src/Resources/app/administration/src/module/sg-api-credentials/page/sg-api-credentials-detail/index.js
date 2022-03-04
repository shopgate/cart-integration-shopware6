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
            isSaveSuccessful: false,
            channelRepository: null,
            languageOptions: [
                {id: null, name: this.$tc('sg-api-credentials.detail.noLanguages')}
            ],
            channelLanguageMap: null
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
    watch: {
        'item.salesChannelId': function (channelId) {
            if (this.channelLanguageMap) {
                this.languageOptions = this.channelLanguageMap[channelId];
            }
        }
    },

    methods: {
        createdComponent() {
            this.repository = this.repositoryFactory.create('shopgate_api_credentials');
            this.channelResository = this.repositoryFactory.create('sales_channel');
            this.createChannelLanguageMap();
            this.getEntity();
        },

        createChannelLanguageMap() {
            const criteria = new Criteria();
            criteria.addAssociation('languages');
            this.channelResository
                .search(criteria, Shopware.Context.api)
                .then(list => {
                    this.channelLanguageMap = this.languagesToMap(list);
                });
        },
        getEntity() {
            const criteria = new Criteria();
            criteria.addAssociation('salesChannel.languages');

            this.repository
                .get(this.$route.params.id, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.item = entity;
                    this.languageOptions = entity.salesChannel.languages;
                });
        },

        languagesToMap(list) {
            let map = [];
            list.forEach(({id, languages}) => map[id] = languages);
            return map;
        },

        onClickSave() {
            this.isLoading = true;
            this.isSaveSuccessful = false;

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.getEntity();
                    this.processSuccess = true;
                    this.handleSaveSuccess();
                }).catch((e) => {
                this.handleSaveFailure(e);
            });
        },
        handleSaveSuccess() {
            this.isLoading = false;
            this.createNotificationSuccess({
                title: this.$tc('sg-api-credentials.detail.titleNotificationSuccess'),
                message: this.$tc('sg-api-credentials.detail.messageSaveSuccess')
            });
        },
        handleSaveFailure(error) {
            this.isLoading = false;
            if (error.response.status === 500) {
                this.createNotificationError({
                    title: this.$tc('sg-api-credentials.detail.titleNotificationError'),
                    message: this.$tc('sg-api-credentials.detail.messageSaveUniqueError')
                });
            } else {
                this.createNotificationError({
                    title: this.$tc('sg-api-credentials.detail.titleNotificationError'),
                    message: this.$tc('sg-api-credentials.detail.messageSaveError')
                });
            }
        },

        saveFinish() {
            this.processSuccess = false;
        }
    }
});
