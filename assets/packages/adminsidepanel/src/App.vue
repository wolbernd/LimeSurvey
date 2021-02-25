<template>
    <div class="Application-Side-Menu">
        <sidebar></sidebar>
    </div>
</template>
<script>
import SideBar from './components/sidebar.vue';

export default {
    name: 'App',
    components: {
        'sidebar': SideBar,
    },
    data() {
        return {

        }
    },
    created() {
        // TODO: Replave jQuery with plain JavaScript
        $(document).on("vue-sidebar-collapse", () => {
            this.$store.commit("changeIsCollapsed", true);
        });
    },
    mounted() {
        // TODO: Why does this be needed here?
        applySurveyId(this.$store);

        const maxHeight = $("#in_survey_common").height() - 35 || 400;
        this.$store.commit("changeMaxHeight", maxHeight);
        this.$store.commit("setAllowOrganizer", window.SideMenuData.allowOrganizer);
        this.updatePjaxLinks();

        $(document).on("vue-redraw", () => {
            this.updatePjaxLinks();
        });

        $(document).trigger("vue-reload-remote");
    }
}
</script>
<style lang="sass">

</style>