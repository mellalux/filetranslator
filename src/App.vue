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
                        {{ result }}
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref } from "vue"
import ini from "ini"

export default {

    data() {
        return {
            debug: false,
            result: null,
            fileTypes: '*',
            countries: [],
            backend_url: ''
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
        };

        const handleSelectedLang = (event) => {
            const lang = event.target.value
            selectedLang.value = lang
        };

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
            }
        })
    },

    methods: {
        uploadFiles() {
            const url = this.backend_url

            if (!url) {
                console.error("Backend URL is missing.");
                return;
            }

            if (this.selectedFiles.length === 0) {
                console.error("No files selected.");
                return;
            }

            if (!this.selectedLang) {
                console.error("Language not selected.");
                return;
            }

            const formData = new FormData()

            // for (let i = 0; i < this.selectedFiles.length; i++) {
            //     formData.append("files[]", this.selectedFiles[i])
            // }
            formData.append("lang", this.selectedLang)

            for (let i = 0; i < this.selectedFiles.length; i++) {
                const file = this.selectedFiles[i];

                // Looge FileReader objekt
                const reader = new FileReader();

                // Määrake, mida teha, kui fail on loetud
                reader.onload = async (event) => {
                    // Faili sisu on event.target.result
                    let fileContent = event.target.result;
                    const fileName = file.name;
                    console.log("File Name:", fileName);

                    // Siin saate faili sisuga midagi teha, näiteks saata serverile
                    fileContent = 'Changed';

                    // Looge uus FormData objekt igale failile ja lisage faili sisu
                    const formData = new FormData();
                    formData.append("files[]", new Blob([fileContent], { type: "text/plain" }), fileName);

                    try {
                        const response = await this.axios.post(url, formData);
                        this.result = response.data.result;
                        console.log(response.data);
                    } catch (error) {
                        console.error("Üleslaadimine ebaõnnestus: " + error.message);
                    }
                };

                // Lugege faili sisu
                reader.readAsText(file);
            }

        }

    },
}
</script>

<style lang="less">

</style>
