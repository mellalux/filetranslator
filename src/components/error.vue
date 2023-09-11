<template>
    <div>
        <!-- Modal -->
        <div v-if="OpenClose" class="modal fade show" aria-labelledby="errorModalLabel" aria-modal="true"
            role="dialog" style="display:block">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Error...</h3>
                        <button type="button" @click="OpenCloseFunction()" class="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <slot>{{ error }}</slot>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="OpenCloseFunction()" class="btn btn-danger">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Error',
    props: {
        error: String,
        visible: Boolean
    },
    data() {
        return {
            OpenClose: this.visible
        }
    },
    methods: {
        OpenCloseFunction() {
            this.OpenClose = !this.OpenClose
            this.$emit("update-visible", this.OpenClose)
        }
    },
    watch: {
        visible: function(newVal,oldVal) {
            this.OpenClose = newVal
        }
    }
        
}
</script>