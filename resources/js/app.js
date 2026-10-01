import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';

import '@/assets/tailwind.css';
import '@/assets/styles.scss';

const Kanguru = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{amber.50}',
            100: '{amber.100}',
            200: '{amber.200}',
            300: '{amber.300}',
            400: '{amber.400}',
            500: '{amber.500}',
            600: '{amber.600}',
            700: '{amber.700}',
            800: '{amber.800}',
            900: '{amber.900}',
            950: '{amber.950}',
        },
    },
});

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    locale: {
        firstDayOfWeek: 1,
        dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
        dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
        dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
        monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        today: 'Hoje',
        clear: 'Limpar',
        emptyMessage: 'Nenhum resultado',
    },
    theme: {
        preset: Kanguru,
        options: {
            darkModeSelector: '.app-dark',
        },
    },
});
app.use(ToastService);
app.use(ConfirmationService);
app.directive('tooltip', Tooltip);

app.mount('#app');
