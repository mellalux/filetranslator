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
                            <input id="fileup" type="file" :class="validFiles" class="form-control" :accept="fileTypes" multiple @change="handleFileChange" />
                            <label class="input-group-text" for="fileup">Upload</label>
                        </div>
                        <div class="invalid-feedback">
                            Please select file(s).
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col">
                        <select :class="validLang" class="form-select" aria-label="Choose Language" @change="handleSelectedLang">
                            <option selected>Choose Language</option>
                            <option v-for="language in languages" :key="language" :value="language"> {{ language }} </option>
                        </select>
                        <div class="invalid-feedback">
                            Language has not been selected.
                        </div>
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
        
        uploadFiles() {
            const url = this.backend_url

            if (!url) {
                this.error = "Backend URL is missing in config.ini..."
                return
            }

            if (this.selectedFiles.length === 0) {
                this.error = "No files selected..."
                this.validFiles = 'is-invalid'
                return
            }

            if (!this.selectedLang) {
                this.error = "Language not selected..."
                this.validLang = 'is-invalid'
                return
            }

            this.overlay = true;

            const promises = this.selectedFiles.map(async (file) => {
                const formData = new FormData();
                formData.append("lang", this.selectedLang);
                formData.append("aiCommand", this.aiCommand);
                formData.append("files[]", file);

                try {
                    const response = await this.axios.post(url, formData);
                    if (response.data.result) {
                        this.results.push(response.data.result);
                    }
                    if (response.data.error !== undefined) {
                        this.error = response.data.error;
                    }
                } catch (error) {
                    console.log(error);
                }
            });

            // Oodake kõikide päringute lõppemist Promise.all abil
            Promise.all(promises).then(() => {
                this.overlay = false; // Kui kõik päringud on lõpetatud, muutke overlay väärtust
            });

        },

        updateVisible (visible) {
            this.visible = visible
        }

    },

    setup() {

        // Looge ref, kuhu salvestatakse valitud failid
        const selectedFiles = ref([])
        const selectedLang = ref(null)
        const validFiles = ref('')
        const validLang = ref('')

        // Käitle failide valimist
        const handleFileChange = (event) => {
            const files = event.target.files
            selectedFiles.value = Array.from(files)
            validFiles.value = ''
        }

        const handleSelectedLang = (event) => {
            const lang = event.target.value
            selectedLang.value = lang
            validLang.value = ''
        }

        return {
            selectedFiles,
            selectedLang,
            validFiles,
            validLang,
            handleFileChange,
            handleSelectedLang
        }
    },

    created() {
        this.axios.get(process.env.BASE_URL + "config.ini?" + Date.now()).then(res => {
            let config = ini.parse(res.data)
            if (config) {
                if (config.general.backend_url !== undefined) {
                    this.backend_url = config.general.backend_url
                } else {
                    this.error = 'Please specify the exact address of "backend_url" in the config.ini file.'
                }
                if (config.general.languages !== undefined) {
                    this.languages = config.general.languages.split(',')
                } else {
                    this.error = 'The configuration file config.ini is missing the variable "languages", which contains a list of languages.'
                }
                if (config.general.fileTypes !== undefined) {
                    this.fileTypes = config.general.fileTypes
                } else {
                    this.error = 'The configuration file config.ini is missing the variable "fileTypes" and the list of file types.'
                }
                if (config.general.aiCommand !== undefined) {
                    this.aiCommand = config.general.aiCommand
                } else {
                    this.error = 'The configuration file config.ini is missing the description for "aiCommand" for artificial intelligence.'
                }
            }
        }).catch((error) => {
            this.error = 'Configuration file config.ini was not found.'
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
