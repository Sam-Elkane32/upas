import axios from 'axios';
import './csrf-interceptor';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
