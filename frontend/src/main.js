import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css'
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';

const app = createApp(App)

app.use(router)
app.mount('#app')
app.component('EasyDataTable', Vue3EasyDataTable);
app.component('v-select', vSelect);