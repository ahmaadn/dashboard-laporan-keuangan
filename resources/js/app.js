import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Alpine from 'alpinejs';

import login from './components/login';
import roleSwitcher from './components/roleSwitcher';
import sidebar from './components/sidebar';

Alpine.data('login', login);
Alpine.data('roleSwitcher', roleSwitcher);
Alpine.data('sidebar', sidebar);

// Defer start until the DOM is ready so page-specific component modules
// (e.g. dashboard.js, loaded only on the dashboard view) can register
// their Alpine.data() calls before Alpine initializes the tree.
window.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});
