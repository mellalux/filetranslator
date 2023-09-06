<template>
    <div class="container-fluid bg-secondary bg-gradient min-vh-100 m-0 p-5 w-100">
        <div class="container bg-white mt-3 p-5">
            <h1>Translate the Files</h1>
            <form @submit.prevent="uploadFiles">
                <div class="row mb-2">
                    <div class="col">
                        <div class="input-group">
                            <input id="fileup" type="file" class="form-control" multiple @change="handleFileChange" />
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
                    <div class="col">
                        <button class="btn btn-secondary" type="submit">Translate</button>
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
            countries: []
        }
    },

    setup() {

        // Looge ref, kuhu salvestatakse valitud failid
        const selectedFiles = ref([])
        const selectedLang = ref(null)
        const backend_url = ref(false)


        // Käitle failide valimist
        const handleFileChange = (event) => {
            const files = event.target.files
            selectedFiles.value = Array.from(files)
        };

        const handleSelectedLang = (event) => {
            const lang = event.target.value
            selectedLang.value = lang
        };

        const uploadFiles = async () => {
            const url = backend_url.value

            if (!url) {
                console.error("Backend URL is missing.");
                return;
            }

            if (selectedFiles.value.length === 0) {
                console.error("No files selected.");
                return;
            }

            if (!selectedLang.value) {
                console.error("Language not selected.");
                return;
            }

            const formData = new FormData()

            for (let i = 0; i < selectedFiles.value.length; i++) {
                formData.append("files[]", selectedFiles.value[i])
            }
            formData.append("lang", selectedLang.value)


            setTimeout(() => {
                // Kuvame formData objekti konsoolis
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
            }, 100);
            // try {
            //     const response = await this.axios.post(url, formData);
            //     console.log(response.data);
            // } catch (error) {
            //     console.error("Üleslaadimine ebaõnnestus: " + error.message);
            // }
        }

        return {
            handleFileChange,
            handleSelectedLang,
            uploadFiles,
            backend_url
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
            }
        })
    },
}
</script>

<style lang="less">

</style>
