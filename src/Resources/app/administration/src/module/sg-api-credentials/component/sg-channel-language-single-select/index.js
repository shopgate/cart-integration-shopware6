const {Component, Context, Utils} = Shopware;
const {Criteria} = Shopware.Data;

Component.extend('sg-channel-language-single-select', 'sw-entity-single-select', {
    props: {
        salesChannelId: {
            required: true,
            type: String,
            default: null
        },
        criteria: {
            type: Object,
            required: false,
            default() {
                const criteria = new Criteria(1, this.resultLimit);
                criteria.addAssociation('languages');
                return criteria;
            }
        }
    },
    watch: {
        salesChannelId() {
            /**
             * A case where we have a language loaded, e.g. Arabic
             * Switch to channel that does not have Arabic
             * So we need to unselect Arabic language to a blank
             */
            this.loadData().then(languages => {
                if (!languages.has(this.value)) {
                    this.clearSelection();
                }
            });
        }
    },
    methods: {
        loadSelected() {
            if (!this.value) {
                if (this.resetOption) {
                    this.singleSelection = {
                        id: null,
                        name: this.resetOption
                    };
                }

                return Promise.resolve();
            }

            this.isLoading = true;

            return this.repository.get(this.salesChannelId, {
                ...this.context,
                inheritance: true
            }, this.criteria).then((item) => {
                this.singleSelection = item.languages.get(this.value);
                this.isLoading = false;
                if (!this.singleSelection) {
                    /**
                     * When first loading, it's possible that the language
                     * is no longer available, so we load first available
                     */
                    this.setValue(item.languages.first());
                }
                return this.singleSelection;
            });
        },
        loadData() {
            this.isLoading = true;

            return this.repository.get(this.salesChannelId, {
                ...this.context,
                inheritance: true
            }, this.criteria).then((result) => {
                this.displaySearch(result.languages);
                this.isLoading = false;

                return result.languages;
            });
        }
    }
});
