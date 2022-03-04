Shopware.Component.extend('sg-entity-single-select', 'sw-entity-single-select', {
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

            return this.repository.search(this.criteria, this.context).then((item) => {
                this.criteria.setIds([]);

                this.singleSelection = item;
                this.isLoading = false;
                return item;
            });
        }
    }
});
