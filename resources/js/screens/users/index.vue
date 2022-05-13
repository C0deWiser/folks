<script type="text/ecmascript-6">

export default {
    /**
     * The component's data.
     */
    data() {
        return {
            ready: false,
            error: undefined,
            info: {}
        };
    },

    /**
     * Components
     */
    components: {

    },

    /**
     * Prepare the component.
     */
    mounted() {
        document.title = "Folks - Users";

        this.loadData();
    },

    watch: {
        '$route'() {
            this.loadData();
        },
    },


    methods: {
        loadData() {
            this.$http.get(Folks.basePath + '/api/users')
                .then(response => {
                    this.info = response.data;

                    this.error = undefined;
                    this.ready = true;
                })
                .catch(error => {
                    this.error = error;
                });
        },
    }
}
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Users</h5>
            </div>

            <div v-if="!ready"
                 class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg v-if="!error" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span v-if="!error">Loading...</span>

                <span v-if="error">{{ error }}</span>
            </div>

            <div v-if="ready" class="card-body pb-0 pt-0">


            </div>
        </div>

    </div>
</template>
