(function() {
    // Run immediately, not waiting for document ready to prevent flash
    // But still use jQuery when available
    function initBanner() {
        // Safely access script params - check if it exists.
        const scriptParams = window.topbarBuddyScriptParams || {};
        const { pro_version_enabled = false, debug_mode = false, banner_params = [] } = scriptParams;
        
        // Exit early if no banner params.
        if ( ! banner_params || banner_params.length === 0 ) {
            return;
        }

        // Use jQuery if available
        const $ = window.jQuery;

    banner_params.forEach((bannerParams, i) => {
        const banner_id = i === 0 ? '' : `_${i+1}`;
        const { 
            hide_topbar_buddy,
            topbar_buddy_text,
            topbar_buddy_disabled_page_paths,
            disabled_on_current_page,
            eeab_close_button_enabled,
            eeab_close_button_expiration,
            topbar_buddy_insert_inside_element,
            topbar_buddy_prepend_element,
            keep_site_custom_css,
            keep_site_custom_js,
            wp_body_open,
            wp_body_open_enabled,
        } = bannerParams;

        // Map eeab params to close_button variables for easier reading
        const close_button_enabled = eeab_close_button_enabled;
        const close_button_expiration = eeab_close_button_expiration;

        const strings = {
            id: `topbar-buddy${banner_id}`,
            text: `topbar-buddy-text${banner_id}`,
            closeButton: `topbar-buddy-close-button${banner_id}`,
            button: `topbar-buddy-button${banner_id}`,
            scrolling: `topbar-buddy-scrolling${banner_id}`,
            siteCustomCss: `topbar-buddy-site-custom-css${banner_id}`,
            siteCustomJs: `topbar-buddy-site-custom-js${banner_id}`,
            headerMargin: `topbar-buddy-header-margin${banner_id}`,
            headerPadding: `topbar-buddy-header-padding${banner_id}`,
            closedCookie: `topbarbuddyclosed${banner_id}`,
        }

        const isTopbarBuddyTextSet = topbar_buddy_text && topbar_buddy_text !== undefined && topbar_buddy_text !== "";
        const isDisabledByPagePath = topbar_buddy_disabled_page_paths ? topbar_buddy_disabled_page_paths.split(',')
            .filter(Boolean)
            .some(path => {
                const pathname = path.trim();
                if (pathname.at(0) === '*' && pathname.at(-1) === '*') {
                    return window.location.pathname.includes(pathname.slice(1, -1));
                }
                if (pathname.at(0) === '*') {
                    return window.location.pathname.endsWith(pathname.slice(1));
                }
                if (pathname.at(-1) === '*') {
                    return window.location.pathname.startsWith(pathname.slice(0, -1));
                }
                return window.location.pathname === pathname;
            }) : false;
        
        // Check if banner is explicitly hidden
        const isBannerHidden = hide_topbar_buddy === 'yes';
        
        // Match original plugin logic: if pro_version_enabled is false (free), always show if text is set
        // If pro is enabled, check disabled conditions
        const isSimpleBannerEnabledOnPage = !pro_version_enabled || 
            (pro_version_enabled && !disabled_on_current_page && !isDisabledByPagePath);
        const isSimpleBannerVisible = isTopbarBuddyTextSet && isSimpleBannerEnabledOnPage && !isBannerHidden;
        
        // Check if banner already exists (created by PHP via wp_body_open)
        const existingBanner = document.getElementById(strings.id);
        
        // If banner should be hidden, remove or hide any existing banner
        if (isBannerHidden && existingBanner) {
            existingBanner.style.display = 'none';
            existingBanner.remove();
            return; // Exit early, don't create banner
        }
        
        if (isSimpleBannerVisible) {
            const wasBannerJustCreated = !existingBanner;
            
            if (!existingBanner) {
                // Create banner via JavaScript
                const closeButton = close_button_enabled ? `<button aria-label="Close" id="${strings.closeButton}" class="${strings.button}">&#x2715;</button>` : '';
                const prependElement = document.querySelector(topbar_buddy_insert_inside_element || topbar_buddy_prepend_element || 'body');
                const bannerHtml = `<div id="${strings.id}" class="${strings.id}"><div class="${strings.text}"><span>${topbar_buddy_text}</span></div>${closeButton}</div>`;
                
                // Use jQuery if available, otherwise vanilla JS
                if ($ && typeof $.fn.prependTo === 'function') {
                    $(bannerHtml).prependTo(prependElement || 'body');
                } else {
                    const bannerDiv = document.createElement('div');
                    bannerDiv.innerHTML = bannerHtml;
                    const target = prependElement || document.body;
                    target.insertBefore(bannerDiv.firstElementChild, target.firstChild);
                }
                
                // Verify banner was created - declare outside if block for scope
                let newBanner = document.getElementById(strings.id);
                if (newBanner) {
                    // Mark banner as loaded to prevent flash - do this AFTER banner is created
                    document.body.classList.add('topbar-buddy-loaded');
                    
                    // Force show the banner
                    newBanner.style.display = 'block';
                    newBanner.style.opacity = '1';
                    newBanner.style.visibility = 'visible';
                }
                
                // Scroll banner into view if it was just created and user is scrolled down
                // Check if this is likely a settings change (banner wasn't there before)
                if (newBanner && wasBannerJustCreated) {
                    // Check if banner is not visible in viewport
                    const rect = newBanner.getBoundingClientRect();
                    const isVisible = rect.top >= 0 && rect.top < window.innerHeight;
                    
                    // If banner is not visible and user is scrolled down, scroll to it
                    if (!isVisible && window.scrollY > 50) {
                        // Use smooth scroll if supported, otherwise instant
                        if ('scrollBehavior' in document.documentElement.style) {
                            newBanner.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            // Fallback for older browsers
                            window.scrollTo(0, 0);
                        }
                    }
                }
            }

            // could move this out of the loop but not entirely necessary
            if ($) {
                const bodyPaddingLeft = $('body').css('padding-left');
                const bodyPaddingRight = $('body').css('padding-right');

                if (bodyPaddingLeft != "0px") {
                    $('head').append(`<style type="text/css" media="screen">.${strings.id}{margin-left:-${bodyPaddingLeft};padding-left:${bodyPaddingLeft};}</style>`);
                }
                if (bodyPaddingRight != "0px") {
                    $('head').append(`<style type="text/css" media="screen">.${strings.id}{margin-right:-${bodyPaddingRight};padding-right:${bodyPaddingRight};}</style>`);
                }

                // Add scrolling class
                function scrollClass() {
                    const scroll = document.documentElement.scrollTop;
                    const banner = $(`#${strings.id}`);
                    if (banner.length && scroll > banner.height()) {
                        banner.addClass(strings.scrolling);
                    } else if (banner.length) {
                        banner.removeClass(strings.scrolling);
                    }
                }
                document.addEventListener("scroll", scrollClass);
            }
        }

        // Add close button function to close button and close if cookie found
        function closeBanner() {
            if (!keep_site_custom_css && document.getElementById(strings.siteCustomCss)) document.getElementById(strings.siteCustomCss).remove();
            if (!keep_site_custom_js && document.getElementById(strings.siteCustomJs)) document.getElementById(strings.siteCustomJs).remove();
            // Header Margin/Padding only available for Banner #1
            if (document.getElementById(strings.headerMargin)) document.getElementById(strings.headerMargin).remove();
            if (document.getElementById(strings.headerPadding)) document.getElementById(strings.headerPadding).remove();
            if (document.getElementById(strings.id)) document.getElementById(strings.id).remove();
        }
        
        if (isSimpleBannerVisible) {
            const sbCookie = strings.closedCookie;

            if (close_button_enabled){
                if (getCookie(sbCookie) === "true") {
                    closeBanner();
                    // Set cookie again here in case the expiration has changed
                    setCookie(sbCookie, "true", close_button_expiration);
                } else {
                    const closeBtn = document.getElementById(strings.closeButton);
                    if (closeBtn) {
                        closeBtn.onclick = function() {
                            closeBanner();
                            setCookie(sbCookie, "true", close_button_expiration);
                        };
                    }
                }
            } else {
                // disable cookie if it exists
                if (getCookie(sbCookie) === "true") {
                    document.cookie = `${strings.closedCookie}=true; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
                }
            }
        }
        
    })

    // Cookie Getter/Setter
    function setCookie(cname,cvalue,expiration) {
        let d;
        if (expiration === '' || expiration === '0' || parseFloat(expiration)) {
            const exdays = parseFloat(expiration) || 0;
            d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
        } else {
            d = new Date(expiration);
        }
        const expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }
    function getCookie(cname) {
        const name = cname + "=";
        const decodedCookie = decodeURIComponent(document.cookie);
        const ca = decodedCookie.split(';');
        for(let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    }

    // Run when DOM is ready
    function runWhenReady() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initBanner, 0);
            });
        } else {
            // DOM already loaded
            setTimeout(initBanner, 0);
        }
    }

    // Also use jQuery ready as primary method if available
    if (window.jQuery) {
        window.jQuery(document).ready(function() {
            initBanner();
            document.body.classList.add('topbar-buddy-loaded');
        });
    } else {
        runWhenReady();
    }
})();
