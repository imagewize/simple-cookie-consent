import 'vanilla-cookieconsent/dist/cookieconsent.css';
// Import the vanilla-cookieconsent library
import * as cookieConsentApi from 'vanilla-cookieconsent';

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
            }
        }
    }
};

// Get settings from WordPress if available
let config = defaultConfig;

if (typeof window.sccSettings !== 'undefined') {
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
        
        // Update privacy policy URL
        if (wpSettings.privacy_policy_url) {
            const policyBlock = config.languages[config.current_lang].settings_modal.blocks[0];
            policyBlock.description = policyBlock.description.replace(/#privacy-policy/, wpSettings.privacy_policy_url);
        }
    }
}

// Add the initCookieConsent function to window object for global access
window.initCookieConsent = function() {
    // Create a new object that contains all the methods from vanilla-cookieconsent
    const cc = {};
    
    // Copy all the methods from the vanilla-cookieconsent API
    for (const key in cookieConsentApi) {
        if (typeof cookieConsentApi[key] === 'function') {
            cc[key] = cookieConsentApi[key];
        }
    }
    
    return cc;
};

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Use the now-global initCookieConsent function
        const cc = window.initCookieConsent();
        
        // Run cookie consent with our configuration
        cc.run(config);
        console.log('Cookie consent initialized successfully');
    } catch (error) {
        console.error('Error initializing cookie consent:', error);
    }
});