import "vanilla-cookieconsent/dist/cookieconsent.css";
import * as CookieConsent from "vanilla-cookieconsent";

// Configuration based on official documentation
const config = {
    cookie: {
        name: 'cc_cookie',
        expiresAfterDays: 182,
    },

    // Use the correct property names from documentation
    guiOptions: {
        consentModal: {
            layout: 'cloud inline',
            position: 'bottom center',
            equalWeightButtons: true,
            flipButtons: false
        },
        preferencesModal: {
            layout: 'box',
            equalWeightButtons: true,
            flipButtons: false
        }
    },

    // Required event handlers
    onFirstConsent: ({cookie}) => {
        console.log('onFirstConsent fired', cookie);
    },

    onConsent: ({cookie}) => {
        console.log('onConsent fired!', cookie);
    },

    onChange: ({changedCategories, changedServices}) => {
        console.log('onChange fired!', changedCategories, changedServices);
    },

    onModalReady: ({modalName}) => {
        console.log('ready:', modalName);
    },

    onModalShow: ({modalName}) => {
        console.log('visible:', modalName);
    },

    onModalHide: ({modalName}) => {
        console.log('hidden:', modalName);
    },

    categories: {
        necessary: {
            enabled: true,
            readOnly: true
        },
        analytics: {
            enabled: false,
            readOnly: false,
            autoClear: {
                cookies: [
                    {
                        name: /^_ga/,
                    },
                    {
                        name: '_gid',
                    }
                ]
            }
        }
    },

    language: {
        default: 'en',
        translations: {
            en: {
                consentModal: {
                    title: 'We use cookies',
                    description: 'This website uses cookies to ensure its proper operation and to understand how you interact with it.',
                    acceptAllBtn: 'Accept all',
                    acceptNecessaryBtn: 'Reject all',
                    showPreferencesBtn: 'Manage preferences',
                },
                preferencesModal: {
                    title: 'Manage cookie preferences',
                    acceptAllBtn: 'Accept all',
                    acceptNecessaryBtn: 'Reject all',
                    savePreferencesBtn: 'Accept current selection',
                    closeIconLabel: 'Close modal',
                    sections: [
                        {
                            title: 'Your Privacy Choices',
                            description: 'This panel allows you to customize your cookie preferences.'
                        },
                        {
                            title: 'Strictly Necessary',
                            description: 'These cookies are essential for the proper functioning of the website and cannot be disabled.',
                            linkedCategory: 'necessary'
                        },
                        {
                            title: 'Performance and Analytics',
                            description: 'These cookies collect information about how you use our website. All of the data is anonymized and cannot be used to identify you.',
                            linkedCategory: 'analytics'
                        }
                    ]
                }
            }
        }
    }
};

// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('Initializing cookie consent...');
        
        // Use the namespace as shown in the documentation
        CookieConsent.run(config);
        
        console.log('Cookie consent initialized');
    } catch (error) {
        console.error('Error initializing cookie consent:', error);
        console.error('Error details:', error.message);
    }
});