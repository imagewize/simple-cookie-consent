import 'vanilla-cookieconsent/dist/cookieconsent.css';
import * as cookieConsent from 'vanilla-cookieconsent';

// Default configuration
const defaultConfig = {
    current_lang: 'en',
    autoclear_cookies: true,
    page_scripts: true,
    languages: {
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
    },
    // Required cookie categories
    categories: {
        necessary: {
            enabled: true,
            readonly: true
        },
        analytics: {
            enabled: false,
            readonly: false
        }
    }
};

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Debug: Check if settings are available
        console.log('Cookie Consent Debug: Settings available?', typeof window.sccSettings !== 'undefined');
        
        // Get settings from WordPress if available
        let config = defaultConfig;
        
        if (typeof window.sccSettings !== 'undefined') {
            console.log('Cookie Consent Debug: WordPress settings loaded', window.sccSettings);
            const wpSettings = window.sccSettings.settings;
            
            // Update the configuration with WordPress settings
            config.current_lang = wpSettings.current_lang || config.current_lang;
            config.autoclear_cookies = typeof wpSettings.autoclear_cookies !== 'undefined' ? wpSettings.autoclear_cookies : config.autoclear_cookies;
            config.page_scripts = typeof wpSettings.page_scripts !== 'undefined' ? wpSettings.page_scripts : config.page_scripts;
            
            // Update the consent modal content
            if (config.languages[config.current_lang]) {
                config.languages[config.current_lang].consent_modal.title = wpSettings.title || config.languages[config.current_lang].consent_modal.title;
                config.languages[config.current_lang].consent_modal.description = wpSettings.description || config.languages[config.current_lang].consent_modal.description;
                config.languages[config.current_lang].consent_modal.primary_btn.text = wpSettings.primary_btn_text || config.languages[config.current_lang].consent_modal.primary_btn.text;
                config.languages[config.current_lang].consent_modal.primary_btn.role = wpSettings.primary_btn_role || config.languages[config.current_lang].consent_modal.primary_btn.role;
                config.languages[config.current_lang].consent_modal.secondary_btn.text = wpSettings.secondary_btn_text || config.languages[config.current_lang].consent_modal.secondary_btn.text;
                config.languages[config.current_lang].consent_modal.secondary_btn.role = wpSettings.secondary_btn_role || config.languages[config.current_lang].consent_modal.secondary_btn.role;
            }
        } else {
            console.warn('Cookie Consent Debug: WordPress settings not found, using defaults');
        }
        
        // FIXED: Directly call the run method from cookieConsent
        cookieConsent.run(config);
        console.log('Cookie Consent Debug: Successfully initialized');
    } catch (error) {
        console.error('Cookie Consent Error:', error);
    }
});