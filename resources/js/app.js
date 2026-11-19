import './bootstrap';

import Alpine from 'alpinejs';
import { pushManager, registerServiceWorker } from './push';

window.Alpine = Alpine;
Alpine.data('pushManager', pushManager);

Alpine.start();
registerServiceWorker();
