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
                // Keep the settings modal configuration as is
                // ...
            }
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