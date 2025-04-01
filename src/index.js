import "vanilla-cookieconsent/dist/cookieconsent.css";
import * as CookieConsent from "vanilla-cookieconsent";

// Default configuration
const defaultConfig = {
    cookie: {
        name: 'cc_cookie',
        expiresAfterDays: 182,
    },

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

// Create configuration with WordPress settings
function createConfigFromSettings(defaultConfig, wpSettings) {
    if (!wpSettings || !wpSettings.settings || !wpSettings.settings.cookie_categories) {
        return defaultConfig;
    }
    
    const config = { ...defaultConfig };
    const settings = wpSettings.settings;
    
    // Set language default
    if (settings.current_lang) {
        config.language.default = settings.current_lang;
    }
    
    // Update modal text based on settings
    if (config.language.translations[config.language.default]) {
        const lang = config.language.translations[config.language.default];
        
        if (settings.title) {
            lang.consentModal.title = settings.title;
        }
        
        if (settings.description) {
            lang.consentModal.description = settings.description;
        }
        
        if (settings.primary_btn_text) {
            lang.consentModal.acceptAllBtn = settings.primary_btn_text;
        }
        
        if (settings.secondary_btn_text) {
            lang.consentModal.acceptNecessaryBtn = settings.secondary_btn_text;
        }
    }
    
    // Initialize or reset categories and sections
    config.categories = {};
    config.language.translations[config.language.default].preferencesModal.sections = [
        {
            title: 'Your Privacy Choices',
            description: 'This panel allows you to customize your cookie preferences.'
        }
    ];
    
    // Process each cookie category from WordPress settings
    Object.entries(settings.cookie_categories).forEach(([categoryId, category]) => {
        // Add category to config
        config.categories[categoryId] = {
            enabled: category.enabled,
            readOnly: category.readonly
        };
        
        // Add auto-clearing for cookies if any are defined
        if (category.cookies && category.cookies.length > 0) {
            config.categories[categoryId].autoClear = {
                cookies: category.cookies.map(cookie => {
                    return {
                        name: cookie.is_regex ? new RegExp(cookie.name.replace(/^\/|\/$/g, '')) : cookie.name
                    };
                })
            };
        }
        
        // Add section to preferences modal
        config.language.translations[config.language.default].preferencesModal.sections.push({
            title: category.title || categoryId.charAt(0).toUpperCase() + categoryId.slice(1),
            description: category.description || '',
            linkedCategory: categoryId
        });
    });
    
    return config;
}

// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('Initializing cookie consent...');
        
        // Get dynamic configuration from WordPress settings
        const config = typeof window.sccSettings !== 'undefined'
            ? createConfigFromSettings(defaultConfig, window.sccSettings)
            : defaultConfig;
        
        // Initialize cookie consent
        CookieConsent.run(config);
        
        console.log('Cookie consent initialized');
    } catch (error) {
        console.error('Error initializing cookie consent:', error);
        console.error('Error details:', error.message);
    }
});