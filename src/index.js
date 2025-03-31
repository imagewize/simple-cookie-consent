import 'vanilla-cookieconsent/dist/cookieconsent.css';

// Import cookie consent library differently - this is crucial
// Import individual functions instead of the whole module
import { 
    run,
    acceptCategory, 
    acceptService,
    hide,
    show,
    showPreferences
} from 'vanilla-cookieconsent';

// Super minimal configuration 
const config = {
    current_lang: 'en',
    autoclear_cookies: true,
    page_scripts: true,
    
    language: {
        default: 'en',
        translations: {
            en: {
                consent_modal: {
                    title: 'We use cookies!',
                    description: 'This website uses cookies to ensure basic functionality.',
                    primary_btn: {
                        text: 'Accept all',
                        role: 'accept_all'
                    },
                    secondary_btn: {
                        text: 'Reject all',
                        role: 'accept_necessary'
                    }
                },
                settings_modal: {
                    title: 'Cookie preferences',
                    save_settings_btn: 'Save settings',
                    accept_all_btn: 'Accept all',
                    reject_all_btn: 'Reject all',
                    close_btn_label: 'Close',
                    cookie_table_headers: [
                        {col1: 'Name'},
                        {col2: 'Domain'},
                        {col3: 'Expiration'},
                        {col4: 'Description'}
                    ],
                    blocks: [
                        {
                            title: 'Cookie usage',
                            description: 'We use cookies to ensure the basic functionalities of the website.'
                        }, 
                        {
                            title: 'Strictly necessary cookies',
                            description: 'These cookies are essential for the proper functioning of the website.',
                            toggle: {
                                value: 'necessary',
                                enabled: true,
                                readonly: true
                            }
                        }
                    ]
                }
            }
        }
    },
    
    categories: {
        necessary: {
            enabled: true,
            readonly: true
        }
    }
};

// Simplified initialization with direct function call 
window.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('Initializing cookie consent with minimal config');
        
        // Fix by calling directly without setTimeout
        run(config);
        
        console.log('Cookie consent initialized');
    } catch (error) {
        console.error('Error initializing cookie consent:', error);
    }
});