<template>
    <div class="container-fluid bg-secondary bg-gradient min-vh-100 m-0 p-5 w-100">
        <div class="container bg-white mt-3 p-5">
            <h1>Translate the Files</h1>
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
                            <option v-for="country in countries" :key="country" :value="country"> {{ country }} </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-2">
                        <button class="btn btn-secondary" type="submit">Translate</button>
                    </div>
                </div>
            </form>

            <div class="row">
                <div class="col">
                    <ul v-if="results">
                        <li v-for="(result, i) in results" :key="i" v-html="result"></li>
                    </ul>
                </div>
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

    </div>

</template>

<script>
import { ref } from "vue"
import qs from "qs"
import ini from "ini"

export default {

    data() {
        return {
            debug: false,
            results: [],
            fileTypes: '*',
            countries: [],
            backend_url: '',
            aiCommand: false,
            overlay: false
        }
    },

    methods: {
 
        async OpenAI(cmd) {
            try {
                if (cmd.length > 0) {
                    const url = this.backend_url
                    const params = {
                        func: 'openai',
                        text: cmd
                    }

                    const response = await this.axios.post(url, qs.stringify(params))
                    if (response.data.data.choices) {
                        return response.data.data.choices[0].message.content
                    } else {
                        throw new Error('No data from OpenAI')
                    }
                } else {
                    throw new Error('Invalid command')
                }
            } catch (error) {
                console.error('OpenAI error:', error)
                throw error
            }
        },
        
        async uploadFiles() {
            const url = this.backend_url

            if (!url) {
                console.error("Backend URL is missing.")
                return
            }

            if (this.selectedFiles.length === 0) {
                console.error("No files selected.")
                return
            }

            if (!this.selectedLang) {
                console.error("Language not selected.")
                return
            }

            this.overlay = true

            const uploadPromises = this.selectedFiles.map(async (file) => {
                return new Promise(async (resolve, reject) => {
                    const reader = new FileReader()
                    reader.onload = async (event) => {
                        const Content = event.target.result
                        const fileName = file.name

                        const cmd = this.aiCommand.replace("[lang]", this.selectedLang)
                        const command = cmd + ' ' + Content
                        const formData = new FormData()

                        try {
                            const fileContent = await this.OpenAI(command)
                            formData.append("lang", this.selectedLang)
                            formData.append("files[]", new Blob([fileContent], { type: "text/plain" }), fileName)
                        } catch (error) {
                            console.error('OpenAI error:', error)
                        }

                        try {
                            const response = await this.axios.post(url, formData)
                            this.results.push(response.data.result)
                            resolve()
                        } catch (error) {
                            console.error("Upload failed: " + error.message)
                            reject(error)
                        }
                    }

                    reader.readAsText(file)
                })
            })

            try {
                await Promise.all(uploadPromises)
            } catch (error) {
                console.error("One or more file uploads failed.")
            }

            this.overlay = false        }

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
                this.debug = (config.general.debug !== undefined) ? config.general.debug : false
                this.backend_url = (config.general.backend_url !== undefined) ? config.general.backend_url : false
                this.countries = (config.general.countries !== undefined) ? config.general.countries.split(',') : false
                this.fileTypes = (config.general.fileTypes !== undefined) ? config.general.fileTypes : '*'
                this.aiCommand = (config.general.aiCommand !== undefined) ? config.general.aiCommand : false
            }
        })
    }

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
