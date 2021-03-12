<script>
import ajaxMethods from '../../mixins/runAjax.js';
import Submenu from './_submenu.vue';

export default {
    name: 'sidemenu',
    components : {
        'submenu': Submenu
    },
    mixins: [ajaxMethods],
    props: {
        'openSubpanelId' : {type: Number},
        loading: {type: Boolean, default: false}
    },
    data(){
        return {
            menues : {},
        };
    },
    computed: {
        sortedMenues(){
            return LS.ld.orderBy(this.$store.state.sidemenus,(a)=>{return parseInt((a.ordering || 999999)) }, ['asc']);
        },
        loadingState: {
            get() { return this.loading; },
            set(newState) { this.$emit('changeLoadingState', newState); }
        }
    },
    methods:{
        sortedMenuEntries(entries) {
            let orderedArray = LS.ld.orderBy(entries,(a)=>{return parseInt((a.ordering || 999999)) }, ['asc']);            
            return orderedArray;
        },
        setActiveMenuIndex(menuItem){
            this.$store.commit('lastMenuOpen', menuItem)
            
        },
        setActiveMenuItemIndex(menuItem){
            this.$store.commit('lastMenuItemOpen', menuItem)
            
        },
        setOpenSubpanel(sId){
            this.openSubpanelId = sId;
            this.$emit('menuselected', sId);
        },
        debugOut(obj){
            return JSON.stringify(obj);
        }
    },
    created(){
        this.$store.dispatch('getSidemenus')
        .then(
            () => {},
            this.$log.error
        )
        .finally(
            () => { this.loadingState = false }
        );
    },
    mounted(){
        this.updatePjaxLinks();

        $(document).on('vue-reload-remote', ()=>{
            //this.$forceUpdate();
        });
    }
}
</script>
<template>
    <div class="ls-flex-column fill menu-pane overflow-enabled ls-space padding all-0 margin top-5" >
        <div v-show="!loadingState"  v-for="menu in sortedMenues" :title="menu.title" :id="menu.id" class="ls-flex-row wrap ls-space padding all-0" v-bind:key="menu.id">
            <label class="menu-label">{{menu.title}}</label>
            <submenu :menu="menu"></submenu>
        </div>
        <loader-widget v-if="loadingState" id="sidemenuLoaderWidget" />
    </div>
</template>
<style lang="scss">

</style>
