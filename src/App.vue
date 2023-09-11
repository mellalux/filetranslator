<template>
    <div class="container-fluid bg-secondary bg-gradient min-vh-100 m-0 p-5 w-100">
        <div class="container bg-white mt-3 p-5 rounded-5 shadow">
            <div class="row">
                <div class="col align-bottom">
                    <h1>Translate the Files</h1>
                </div>
                <div class="col mb-3">
                    <a href="https://www.mella.ee" target="_blank">
                        <img src="@/assets/logo.png" width="100" class="img-fluid float-end" alt="Author" />
                    </a>
                </div>
            </div>
            <form @submit.prevent="uploadFiles">
                <div class="row mb-2">
                    <div class="col">
                        <div class="input-group">
                            <input id="fileup" type="file" class="form-control" :accept="fileTypes" multiple @change="handleFileChange" />
                            <label class="input-group-text" for="fileup">Upload</label>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col">
                        <select class="form-select" aria-label="Choose Language" @change="handleSelectedLang">
                            <option selected>Choose Language</option>
                            <option v-for="language in languages" :key="language" :value="language"> {{ language }} </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-2">
                        <button class="btn btn-secondary" type="submit">Translate</button>
                    </div>
                </div>
            </form>

            <div v-if="results.length > 0" class="row my-3">
                <div class="col">
                    <h5>Results:</h5>
                    <ol>
                        <li v-for="(result, i) in results" :key="i" v-html="result"></li>
                    </ol>
                </div>
            </div>
            
            <div class="text-center">Copyright &copy; Meelis Luks</div>
            
            <Error :error="error" :visible="visible" @update-visible="updateVisible" />

        </div>
    </div>

    <div v-if="overlay" class="overlay">
        <div class="row position-absolute top-50 start-50 translate-middle">
            <div class="col">
                <div class="spinner-border text-success" style="width: 10rem; height: 10rem" role="status"></div>
            </div>
        </div>
        <div class="row position-absolute top-50 start-50 translate-middle">
            <div class="col">
                <span class="process justify-content-center"> Working... </span>
            </div>
        </div>
    </div>
</template>

<script>
import { ref } from "vue"
import qs from "qs"
import ini from "ini"
import Error from "@/components/error"

export default {

    data() {
        return {
            results: [],
            fileTypes: '*',
            languages: [],
            backend_url: '',
            aiCommand: false,
            overlay: false,
            error: '',
            visible: false
        }
    },

    components: {
        Error
    },
    
    methods: {
        
        async uploadFiles() {
            const url = this.backend_url

            if (!url) {
                this.error = "Backend URL is missing in config.ini..."
                return
            }

            if (this.selectedFiles.length === 0) {
                this.error = "No files selected..."
                return
            }

            if (!this.selectedLang) {
                this.error = "Language not selected..."
                return
            }

            this.overlay = true

            const uploadPromises = this.selectedFiles.map(async (file) => {
                return new Promise(async (resolve, reject) => {
                    const reader = new FileReader()
                    reader.onload = async (event) => {
                        const formData = new FormData()
                        formData.append("lang", this.selectedLang)
                        formData.append("aiCommand", this.aiCommand)
                        formData.append("files[]", file)

                        try {
                            const response = await this.axios.post(url, formData)
                            console.log(response)
                            if (response.data.error !== undefined) this.error = response.data.error
                            this.results.push(response.data.result)
                            resolve()
                        } catch (error) {
                            this.error = "Upload failed: " + error.message
                            reject(error)
                        }
                    }

                    reader.readAsText(file)
                })
            })

            try {
                await Promise.all(uploadPromises)
            } catch (error) {
                this.error = "One or more file uploads failed..."
            }

            this.overlay = false        
        },

        updateVisible (visible) {
            this.visible = visible
        }

    },

    setup() {

        // Looge ref, kuhu salvestatakse valitud failid
        const selectedFiles = ref([])
        const selectedLang = ref(null)

        // Käitle failide valimist
        const handleFileChange = (event) => {
            const files = event.target.files
            selectedFiles.value = Array.from(files)
        }

        const handleSelectedLang = (event) => {
            const lang = event.target.value
            selectedLang.value = lang
        }

        return {
            selectedFiles,
            selectedLang,
            handleFileChange,
            handleSelectedLang
        }
    },

    created() {
        console.log("Loading some settings from ini-file...")

        this.axios.get(process.env.BASE_URL + "config.ini?" + Date.now()).then(res => {
            let config = ini.parse(res.data)
            if (config) {
                this.backend_url = (config.general.backend_url !== undefined) ? config.general.backend_url : false
                this.languages = (config.general.languages !== undefined) ? config.general.languages.split(',') : false
                this.fileTypes = (config.general.fileTypes !== undefined) ? config.general.fileTypes : '*'
                this.aiCommand = (config.general.aiCommand !== undefined) ? config.general.aiCommand : false
            }
        })
    },

    watch: {
        error: function() {
            if (this.error.length > 0) this.visible = true
        },
        visible: function() {
            if (!this.visible) this.error = ''
        }
    },

}
</script>

<style lang="scss">

.overlay {
    position: fixed;
    display: block;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.75;
    background-color: white;
    z-index: 2000;
}

.process {
    font-size: 300%;
    font-weight: bold;
    color: white;
    -webkit-text-stroke-width: 2px;
    -webkit-text-stroke-color: black;
}

</style>
