import 'vanilla-cookieconsent/dist/cookieconsent.css';
import * as CookieConsent from 'vanilla-cookieconsent';

// Create a cookie consent instance
const cc = CookieConsent.initCookieConsent();

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
                title: 'Cookie settings',
                save_settings_btn: 'Save settings',
                accept_all_btn: 'Accept all',
                reject_all_btn: 'Reject all',
                close_btn_label: 'Close',
                cookie_table_headers: [
                    { col1: 'Name' },
                    { col2: 'Domain' },
                    { col3: 'Expiration' },
                    { col4: 'Description' }
                ],
                blocks: [
                    {
                        title: 'Cookie usage',
                        description: 'We use cookies to ensure the basic functionalities of the website and to enhance your online experience. You can choose for each category to opt-in/out whenever you want. For more details, please read our <a href="#privacy-policy" class="cc-link">privacy policy</a>.'
                    }, 
                    {
                        title: 'Strictly necessary cookies',
                        description: 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
                        toggle: {
                            value: 'necessary',
                            enabled: true,
                            readonly: true
                        }
                    }, 
                    {
                        title: 'Performance and Analytics cookies',
                        description: 'These cookies allow the website to remember the choices you have made in the past',
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

// Initialize with the merged configuration
cc.run(config);