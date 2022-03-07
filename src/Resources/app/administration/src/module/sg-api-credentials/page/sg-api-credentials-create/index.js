const {Component, Utils} = Shopware;

Component.extend('sg-api-credentials-create', 'sg-api-credentials-detail', {
    template: '',

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('sg.api.credentials.create') && !to.params.id) {
            to.params.id = Utils.createId();
            to.params.newItem = true;
        }

        next();
    },

    methods: {
        getEntity() {
            this.item = this.repository.create(Shopware.Context.api);
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({name: 'sg.api.credentials.detail', params: {id: this.item.id}});
        },

        onClickSave() {
            this.isLoading = true;
            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.handleSaveSuccess();
                    this.$router.push({name: 'sg.api.credentials.detail', params: {id: this.item.id}});
                }).catch((e) => {
                this.handleSaveFailure(e);
            });
        }
    }
});
