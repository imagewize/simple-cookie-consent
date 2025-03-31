import 'vanilla-cookieconsent/dist/cookieconsent.css';
import * as cookieConsent from 'vanilla-cookieconsent';

// Default configuration
const defaultConfig = {
    current_lang: 'en',
    autoclear_cookies: true,
    page_scripts: true,
    
    language: {
        default: 'en',
        translations: {
            en: {
                consent_modal: {
                    title: 'We use cookies!',
                    description: 'Hello, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent.',
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
                            description: 'We use cookies to ensure the basic functionalities of the website and to enhance your online experience.'
                        }, 
                        {
                            title: 'Strictly necessary cookies',
                            description: 'These cookies are essential for the proper functioning of the website. Without these cookies, the website would not work properly.',
                            toggle: {
                                value: 'necessary',
                                enabled: true,
                                readonly: true
                            }
                        },
                        {
                            title: 'Analytics cookies',
                            description: 'These cookies collect information about how you use the website, which pages you visited and which links you clicked on. All of the data is anonymized and cannot be used to identify you.',
                            toggle: {
                                value: 'analytics',
                                enabled: false,
                                readonly: false
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
        },
        analytics: {
            enabled: false,
            readonly: false
        }
    },
    
    gui_options: {
        consent_modal: {
            layout: 'cloud',
            position: 'bottom center',
            transition: 'slide'
        },
        settings_modal: {
            layout: 'box',
            transition: 'slide'
        }
    },
    
    // Event handlers
    onAccept: function() {
        console.log('Cookies accepted');
    },
    onChange: function() {
        console.log('Cookie preferences changed');
    },
    onFirstAction: function() {
        console.log('First user action recorded');
    }
};

// Create a container for the cookie consent
function createConsentContainer() {
    // Check if container already exists
    if (document.getElementById('cc-container')) {
        return;
    }

    const container = document.createElement('div');
    container.id = 'cc-container';
    document.body.appendChild(container);
}

// Modified initialization function
function initCookieConsent() {
    try {
        console.log('Cookie Consent Debug: Starting initialization');
        
        // Create container for cookie consent
        createConsentContainer();
        
        let config = {...defaultConfig};
        
        if (typeof window.sccSettings !== 'undefined') {
            console.log('Cookie Consent Debug: WordPress settings loaded', window.sccSettings);
            const wpSettings = window.sccSettings.settings;
            
            // Apply WordPress settings
            config.current_lang = wpSettings.current_lang || config.current_lang;
            config.autoclear_cookies = wpSettings.autoclear_cookies === true;
            config.page_scripts = wpSettings.page_scripts === true;
            
            if (config.language.translations[config.current_lang]) {
                config.language.translations[config.current_lang].consent_modal.title = wpSettings.title || config.language.translations[config.current_lang].consent_modal.title;
                config.language.translations[config.current_lang].consent_modal.description = wpSettings.description || config.language.translations[config.current_lang].consent_modal.description;
                config.language.translations[config.current_lang].consent_modal.primary_btn.text = wpSettings.primary_btn_text || config.language.translations[config.current_lang].consent_modal.primary_btn.text;
                config.language.translations[config.current_lang].consent_modal.primary_btn.role = wpSettings.primary_btn_role || config.language.translations[config.current_lang].consent_modal.primary_btn.role;
                config.language.translations[config.current_lang].consent_modal.secondary_btn.text = wpSettings.secondary_btn_text || config.language.translations[config.current_lang].consent_modal.secondary_btn.text;
                config.language.translations[config.current_lang].consent_modal.secondary_btn.role = wpSettings.secondary_btn_role || config.language.translations[config.current_lang].consent_modal.secondary_btn.role;
            }
        }
        
        console.log('Cookie Consent Debug: Final config', config);
        
        // Add container selector
        config.container = '#cc-container';
        
        // Initialize with delay
        setTimeout(() => {
            try {
                cookieConsent.run(config);
                console.log('Cookie Consent Debug: Successfully initialized');
            } catch (runError) {
                console.error('Cookie Consent Run Error:', runError);
            }
        }, 1000);
    } catch (error) {
        console.error('Cookie Consent Error:', error.message);
    }
}

// Ensure initialization after complete page load
window.addEventListener('load', function() {
    // Give extra time for all DOM manipulations to complete
    setTimeout(initCookieConsent, 500);
});