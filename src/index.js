import 'vanilla-cookieconsent/dist/cookieconsent.css';
import * as CookieConsent from 'vanilla-cookieconsent';

// Initialize directly using the provided function
CookieConsent.run({
    current_lang: 'en',
    autoclear_cookies: true, // default: false
    page_scripts: true, // default: false
    languages: {
        en: {
            consent_modal: {
                title: 'We use cookies!',
                description: 'Hello, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent.',
                primary_btn: {
                    text: 'Accept all',
                    role: 'accept_all' // 'accept_selected' or 'accept_all'
                },
                secondary_btn: {
                    text: 'Reject all',
                    role: 'accept_necessary' // 'settings' or 'accept_necessary'
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
                    }, {
                        title: 'Strictly necessary cookies',
                        description: 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
                        toggle: {
                            value: 'necessary',
                            enabled: true,
                            readonly: true // cookie categories with readonly=true are all treated as "necessary cookies"
                        }
                    }, {
                        title: 'Performance and Analytics cookies',
                        description: 'These cookies allow the website to remember the choices you have made in the past',
                        toggle: {
                            value: 'analytics', // your cookie category
                            enabled: false,
                            readonly: false
                        }
                    }
                ]
            }
        }
    }
});