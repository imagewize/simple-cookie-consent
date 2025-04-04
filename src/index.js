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
                    },
                    {
                        name: '_gat',
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
    // Debug the incoming data
    console.log('Creating config from settings:', wpSettings);
    
    // Start with a deep clone of the default config to avoid mutations
    const config = JSON.parse(JSON.stringify(defaultConfig));
    
    if (!wpSettings || !wpSettings.settings) {
        console.warn('WordPress settings not available, using defaults');
        return config;
    }
    
    const settings = wpSettings.settings;
    
    try {
        // Set language default
        if (settings.current_lang) {
            config.language.default = settings.current_lang;
        }
        
        // Update modal text based on settings
        if (config.language.translations[config.language.default]) {
            const lang = config.language.translations[config.language.default];
            
            // Update consent modal settings
            lang.consentModal.title = settings.title || lang.consentModal.title;
            lang.consentModal.description = settings.description || lang.consentModal.description;
            lang.consentModal.acceptAllBtn = settings.primary_btn_text || lang.consentModal.acceptAllBtn;
            lang.consentModal.acceptNecessaryBtn = settings.secondary_btn_text || lang.consentModal.acceptNecessaryBtn;
            
            // Important: Don't clear existing sections
            const existingIntroSection = lang.preferencesModal.sections[0];
            
            // Reset sections array but keep the intro
            lang.preferencesModal.sections = [existingIntroSection];
        }
        
        // Set up categories (but don't completely overwrite the defaults)
        if (settings.cookie_categories && typeof settings.cookie_categories === 'object') {
            // Log the categories coming from WordPress
            console.log('Cookie categories from WordPress:', settings.cookie_categories);
            
            // Map WordPress categories to the config
            Object.entries(settings.cookie_categories).forEach(([categoryId, category]) => {
                // Create category if it doesn't exist, or update existing
                config.categories[categoryId] = config.categories[categoryId] || {};
                config.categories[categoryId].enabled = category.enabled || false;
                config.categories[categoryId].readOnly = category.readonly || false;
                
                // Set up cookie auto-clearing
                if (category.cookies && category.cookies.length > 0) {
                    config.categories[categoryId].autoClear = {
                        cookies: category.cookies.map(cookie => {
                            // Handle regex patterns for cookie names
                            if (cookie.is_regex && cookie.name.startsWith('/') && cookie.name.includes('/')) {
                                try {
                                    // Extract pattern from /pattern/
                                    const pattern = cookie.name.slice(1, cookie.name.lastIndexOf('/'));
                                    return { name: new RegExp(pattern) };
                                } catch (e) {
                                    console.error('Invalid regex pattern:', cookie.name);
                                    return { name: cookie.name };
                                }
                            } else {
                                return { name: cookie.name };
                            }
                        })
                    };
                }
                
                // Add section to preferences modal if it doesn't exist
                if (config.language.translations[config.language.default]) {
                    const lang = config.language.translations[config.language.default];
                    
                    // Check if there's already a section for this category
                    const existingSection = lang.preferencesModal.sections.find(
                        section => section.linkedCategory === categoryId
                    );
                    
                    if (!existingSection) {
                        lang.preferencesModal.sections.push({
                            title: category.title || categoryId.charAt(0).toUpperCase() + categoryId.slice(1),
                            description: category.description || '',
                            linkedCategory: categoryId
                        });
                    } else {
                        // Update existing section
                        existingSection.title = category.title || existingSection.title;
                        existingSection.description = category.description || existingSection.description;
                    }
                }
            });
        }
        
        // Log the final config for debugging
        console.log('Final cookie consent configuration:', config);
        
        return config;
    } catch (error) {
        console.error('Error building cookie consent config:', error);
        return defaultConfig; // Fall back to defaults on error
    }
}

// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('Initializing cookie consent...');
        
        // Get dynamic configuration from WordPress settings
        const config = typeof window.sccSettings !== 'undefined'
            ? createConfigFromSettings(defaultConfig, window.sccSettings)
            : defaultConfig;
        
        // Debug the final configuration
        console.log('Running cookie consent with config:', config);
        
        // Initialize cookie consent
        CookieConsent.run(config);
        
        console.log('Cookie consent initialized successfully');
    } catch (error) {
        console.error('Error initializing cookie consent:', error);
        console.error('Error details:', error.message, error.stack);
    }
});