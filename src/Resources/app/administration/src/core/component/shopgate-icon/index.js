import './icon.scss';

const { Component } = Shopware;

Component.register('shopgate-icon', {
    template: '<img class="shopgate-icon" :src="\'sgateshopgatepluginsw6/shopgate_logo.png\' | asset">'
});
