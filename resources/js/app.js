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

const Yala = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#e7f8eb',
            100: '#c8f0d1',
            200: '#96e1a8',
            300: '#5dce78',
            400: '#42c264',
            500: '#36b750',
            600: '#2fa046',
            700: '#257d37',
            800: '#1e642c',
            900: '#174c22',
            950: '#0c2a13',
        },
        colorScheme: {
            light: {
                primary: {
                    color: '{primary.500}',
                    contrastColor: '#111111',
                    hoverColor: '{primary.600}',
                    activeColor: '{primary.700}',
                },
            },
            dark: {
                primary: {
                    color: '{primary.400}',
                    contrastColor: '#111111',
                    hoverColor: '{primary.300}',
                    activeColor: '{primary.200}',
                },
            },
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
        preset: Yala,
        options: {
            darkModeSelector: '.app-dark',
        },
    },
});
app.use(ToastService);
app.use(ConfirmationService);
app.directive('tooltip', Tooltip);

app.mount('#app');
