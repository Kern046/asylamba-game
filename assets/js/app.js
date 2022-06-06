import {createApp} from 'vue';
import { Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';


const app = createApp({});
app.component('toolbar-picto', require('../components/atoms/header/ToolbarPicto').default);
app.component('toolbar-toggler', require('../components/atoms/header/ToolbarToggler').default);
app.component('disclosure', Disclosure);
app.component('disclosure-button', DisclosureButton);
app.component('disclosure-panel', DisclosurePanel);
app.component('menu-button', MenuButton);
app.component('menu-items', MenuItems);
app.component('menu-item', MenuItem);
app.component('menuu', Menu);

app.mount('#app');

