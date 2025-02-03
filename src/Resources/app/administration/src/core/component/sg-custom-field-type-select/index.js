import template from './sg-custom-field-type-select.html.twig'
import './sg-custom-field-type-select.scss'

Shopware.Component.register('sg-custom-field-type-select', {
    template,

    compatConfig: Shopware.compatConfig,

    inject: ['customFieldDataProviderService'],

    props: {
        value: {
            type: Array,
            required: true,
            default () {
                return []
            },
        }
    },

    data () {
        return {
            currentValue: this.value.filter(relation => relation !== null),
        }
    },

    computed: {

        currentValue: {
            get () {
                return this.value
            },

            set (val) {
                this.$emit('update:value', val)
            },
        },

        selectedRelationEntityNames () {
            if (!this.currentValue) {
                return []
            }

            return this.currentValue
                .map(relation => relation.value)
                .filter(relation => relation !== null)
        },

        relationEntityNames () {
            if (!this.value) {
                return []
            }

            const entityNames = this.customFieldDataProviderService.getEntityNames()
            return entityNames.map(entityName => {
                const relation = {}
                relation.value = entityName
                if (this.$te(`global.entities.${entityName}`)) {
                    // noinspection Shopware6AdministrationSnippetMissing
                    relation.label = this.$tc(`global.entities.${entityName}`, 2, this.$i18n.locale)
                } else {
                    relation.label = entityName
                }

                return relation
            })
        },
    },

    watch: {
        currentValue: {
            deep: true,
            handler (val) {
                this.$emit('update:value', val)
            },
        },
    },

    methods: {
        onAddRelation (relation) {
            this.currentValue.push(relation)
        },

        onRemoveRelation (relationToRemove) {
            const index = this.currentValue.findIndex(val => val.value === relationToRemove.value)

            if (index === -1) {
                return
            }

            this.currentValue.splice(index, 1)
        },
    },
})
