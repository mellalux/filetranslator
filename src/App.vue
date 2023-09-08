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
                <div class="row">
                    <div class="col">
                        <ul v-if="results">
                            <li v-for="(result, i) in results" :key="i" v-html="result"></li>
                        </ul>
                    </div>
                </div>
            </form>
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
            aiCommand: false
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

                    console.log(response)
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

            for (let i = 0; i < this.selectedFiles.length; i++) {
                const file = this.selectedFiles[i]

                // Looge FileReader objekt
                const reader = new FileReader()

                // Määrake, mida teha, kui fail on loetud
                reader.onload = async (event) => {
                    // Faili sisu on event.target.result
                    const Content = event.target.result
                    const fileName = file.name
                    
                    // Siin saate faili sisuga midagi teha, näiteks saata serverile

                    const cmd = this.aiCommand.replace("[lang]", this.selectedLang)
                    const command = cmd + ' ' + Content
                    const formData = new FormData()

                    // Kasutage this.OpenAI meetodit
                    try {
                        // Kasutage this.OpenAI meetodit ja oodake vastuse saamist
                        const fileContent = await this.OpenAI(command)
                        console.log(fileContent)

                        formData.append("lang", this.selectedLang)
                        formData.append("files[]", new Blob([fileContent], { type: "text/plain" }), fileName)
                    } catch (error) {
                        console.error('OpenAI error:', error)
                    }
                    

                    try {
                        const response = await this.axios.post(url, formData)
                        this.results.push(response.data.result)
                        console.log(response.data)
                    } catch (error) {
                        console.error("Üleslaadimine ebaõnnestus: " + error.message)
                    }
                }

                // Lugege faili sisu
                reader.readAsText(file)
            }

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

<style lang="less">

</style>
