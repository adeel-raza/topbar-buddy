(function() {
	const banner_id = eeabSettings.banner_id || "";
	const editorId = 'topbar_buddy_text' + banner_id;
    
	// Function to convert datetime-local to Y-m-d H:i:s format
	function convertDateTimeLocal(inputEl) {
		if (!inputEl || !inputEl.value) return;
        
		// datetime-local format is "YYYY-MM-DDTHH:mm" (e.g., "2025-11-21T05:49")
		// We need to convert it to "Y-m-d H:i:s" format (e.g., "2025-11-21 05:49:00")
		const datetimeValue = inputEl.value;
        
		// Check if it's already in the correct format (has space instead of T)
		if (datetimeValue.includes(' ')) {
			// Already converted, just ensure it has seconds
			if (!datetimeValue.match(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/)) {
				// Add seconds if missing
				inputEl.value = datetimeValue + ':00';
			}
			return;
		}
        
		// Convert from "YYYY-MM-DDTHH:mm" to "YYYY-MM-DD HH:mm:ss"
		if (datetimeValue.includes('T')) {
			const parts = datetimeValue.split('T');
			if (parts.length === 2) {
				const datePart = parts[0];
				const timePart = parts[1];
				// Ensure time has seconds
				const timeParts = timePart.split(':');
				if (timeParts.length === 2) {
					inputEl.value = datePart + ' ' + timePart + ':00';
				} else {
					inputEl.value = datePart + ' ' + timePart;
				}
			}
		} else {
			// Fallback: try to parse as Date object
			const date = new Date(datetimeValue);
			if (!isNaN(date.getTime())) {
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, '0');
				const day = String(date.getDate()).padStart(2, '0');
				const hours = String(date.getHours()).padStart(2, '0');
				const minutes = String(date.getMinutes()).padStart(2, '0');
				const seconds = String(date.getSeconds()).padStart(2, '0');
				inputEl.value = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
			}
		}
	}
	
	// Top save button - now it's a submit button inside the form, so we just need to handle preprocessing
	// Wait for DOM to be ready
	function initTopSaveButton() {
		const topSaveButton = document.getElementById('sb-top-save-button');
		const settingsForm = document.querySelector('.sb-settings-form');
		
		if (!topSaveButton || !settingsForm) {
			return; // Button or form not found, skip
		}
		
		// Handle form submission to run preprocessing
		settingsForm.addEventListener('submit', function(e) {
			// Save TinyMCE content to textarea before submit
			if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
				tinymce.get(editorId).save();
			} else {
				// Fallback: remove newlines from textarea if TinyMCE not available
				const textareaEl = document.getElementById(editorId);
				if (textareaEl) {
					textareaEl.value = textareaEl.value.replace(/\n/g, '');
				}
			}
			
			// Convert datetime-local to proper format before submit
			const startDateEl = document.getElementById('topbar_buddy_start_after_date' + banner_id);
			const endDateEl = document.getElementById('topbar_buddy_remove_after_date' + banner_id);
			
			if (startDateEl) {
				convertDateTimeLocal(startDateEl);
			}
			if (endDateEl) {
				convertDateTimeLocal(endDateEl);
			}
		});
	}
	
	// Initialize save button when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initTopSaveButton);
	} else {
		// DOM is already ready
		initTopSaveButton();
	}
	
	// Helper function to sanitize banner text
	const hrefRegex = /href\=[\'\"](?!http|https)([^\/].*?)[\'\"]/gsi;
	const scriptStyleRegex = /<(script|style)[^>]*?>.*?<\/(script|style)>/gsi;
	function stripBannerText(string) {
		let strippedString = string;
		while (strippedString.match(scriptStyleRegex)) { 
			strippedString = strippedString.replace(scriptStyleRegex, '');
		}
		const secureString = strippedString.replace(hrefRegex, "href=\"https://$1\"");
		// Use DOMPurify if available, otherwise return as-is (WordPress will sanitize on save).
		if (typeof DOMPurify !== 'undefined') {
			return DOMPurify.sanitize(secureString);
		}
		return secureString;
	}

	// Initialize preview styles
	const style_font_size = document.createElement('style');
	const style_background_color = document.createElement('style');
	const style_link_color = document.createElement('style');
	const style_text_color = document.createElement('style');
	const style_close_color = document.createElement('style');
	const style_custom_css = document.createElement('style');
	const style_custom_text_css = document.createElement('style');
	const style_custom_button_css = document.createElement('style');

	// Banner Text Preview - Works with WYSIWYG editor
	const previewTextEl = document.getElementById('preview_banner_text' + banner_id);
	
	function updateBannerPreview() {
		if (!previewTextEl) return;
		
		// Get close button elements (may not be defined yet, so access via DOM)
		const closeButtonCheckbox = document.getElementById('close_button_enabled' + banner_id);
		const previewBannerContainer = document.getElementById('preview_banner' + banner_id);
		
		// Check if close button should be preserved
		const closeButtonEnabled = closeButtonCheckbox && closeButtonCheckbox.checked;
		const existingCloseButton = closeButtonEnabled && previewBannerContainer ? previewBannerContainer.querySelector('.topbar-buddy-button' + banner_id) : null;
		const closeButtonHTML = existingCloseButton ? existingCloseButton.outerHTML : '';
		
		let content = '';
		// Try to get content from TinyMCE editor first
		if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
			content = tinymce.get(editorId).getContent();
		} else {
			// Fallback to textarea if TinyMCE not loaded yet
			const textareaEl = document.getElementById(editorId);
			if (textareaEl) {
				content = textareaEl.value;
			}
		}
		
		// Only update if there's actual content (user has typed something)
		// Don't overwrite PHP-rendered content on initial load
		if (content && content.trim() !== '') {
			previewTextEl.innerHTML = '<span>' + stripBannerText(content) + '</span>';
			
			// Restore close button if it was enabled
			if (closeButtonEnabled && closeButtonHTML && previewBannerContainer) {
				const currentCloseButton = previewBannerContainer.querySelector('.topbar-buddy-button' + banner_id);
				if (!currentCloseButton) {
					previewBannerContainer.insertAdjacentHTML('beforeend', closeButtonHTML);
				}
			}
		}
		// If content is empty, don't overwrite - let PHP content stay
	}
	
	// Function to initialize all preview styles on page load
	function initializePreviewStyles() {
		// Trigger input events on all color/style inputs to apply initial values
		const styleInputs = [
			'topbar_buddy_font_size',
			'topbar_buddy_color',
			'topbar_buddy_text_color',
			'topbar_buddy_link_color',
			'topbar_buddy_close_color',
			'topbar_buddy_custom_css',
			'topbar_buddy_text_custom_css',
			'topbar_buddy_button_css'
		];
		
		styleInputs.forEach(function(inputId) {
			const inputEl = document.getElementById(inputId + banner_id);
			if (inputEl && inputEl.value) {
				// Trigger input event to apply the style
				inputEl.dispatchEvent(new Event('input', { bubbles: true }));
			}
		});
	}
	
	// Initialize preview
	if (previewTextEl) {
		// Don't update immediately - preserve PHP-rendered content
		// Only update when user actually types in the editor
		
		// Initialize all styles on page load (once)
		setTimeout(function() {
			initializePreviewStyles();
		}, 100);
		
		// Wait for TinyMCE to be ready
		if (typeof tinymce !== 'undefined') {
			// Listen for editor initialization
			tinymce.on('AddEditor', function(e) {
				if (e.editor.id === editorId) {
					// Only update on user input, not on initial load
					e.editor.on('keyup change input NodeChange', updateBannerPreview);
					// Don't call updateBannerPreview() here - preserve PHP content
					initializePreviewStyles(); // Re-apply styles after editor loads
				}
			});
			
			// Also check if editor is already initialized
			if (tinymce.get(editorId)) {
				tinymce.get(editorId).on('keyup change input NodeChange', updateBannerPreview);
				// Don't call updateBannerPreview() here - preserve PHP content
				initializePreviewStyles();
			}
		}
		
		// Fallback: listen to textarea changes (for when TinyMCE is not available)
		const textareaEl = document.getElementById(editorId);
		if (textareaEl) {
			textareaEl.addEventListener('input', updateBannerPreview);
			textareaEl.addEventListener('change', updateBannerPreview);
		}
	}

	// Close Button Preview
	const closeButtonEl = document.getElementById('close_button_enabled' + banner_id);
	const previewBannerEl = document.getElementById('preview_banner' + banner_id);
	if (closeButtonEl && previewBannerEl) {
		const closeButton = '<button id="topbar-buddy-close-button' + banner_id + '" class="topbar-buddy-button' + banner_id + '">✕</button>';
		
		// Function to update close button
		function updateCloseButton() {
			// Get current text content (without close button)
			const textEl = document.getElementById('preview_banner_text' + banner_id);
			if (!textEl) return;
			
			const textContent = textEl.innerHTML;
			
			// Remove existing close button if any
			const existingCloseButton = previewBannerEl.querySelector('.topbar-buddy-button' + banner_id);
			if (existingCloseButton) {
				existingCloseButton.remove();
			}
			
			// Add close button if enabled
			if (closeButtonEl.checked) {
				previewBannerEl.insertAdjacentHTML('beforeend', closeButton);
			}
		}
		
		// Initialize close button on page load (after a short delay to ensure text is loaded)
		setTimeout(updateCloseButton, 100);
		
		// Update when checkbox changes
		closeButtonEl.addEventListener('change', function(e) {
			updateCloseButton();
		});
	}

	// Font Size Preview
	if (document.getElementById('topbar_buddy_font_size' + banner_id)) {
		style_font_size.type = 'text/css';
		style_font_size.id = 'preview_banner_font_size' + banner_id;
		const fontSize = document.getElementById('topbar_buddy_font_size' + banner_id).value || '1em';
		// Use more specific selector for preview to ensure it applies
		style_font_size.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + '{font-size:' + fontSize + ' !important;line-height:1.55 !important;}'));
		document.getElementsByTagName('head')[0].appendChild(style_font_size);

		document.getElementById('topbar_buddy_font_size' + banner_id).addEventListener('input', function(e) {
			const child = document.getElementById('preview_banner_font_size' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_font_size' + banner_id;
			// Use more specific selector for preview to ensure it applies
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + '{font-size:' + (e.target.value || '1em') + ' !important;line-height:1.55 !important;}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});
	}

	// Background Color Preview
	if (document.getElementById('topbar_buddy_color' + banner_id)) {
		style_background_color.type = 'text/css';
		style_background_color.id = 'preview_banner_background_color' + banner_id;
		const bgColor = document.getElementById('topbar_buddy_color' + banner_id).value || '#000000';
		// Use more specific selector for preview to ensure it applies, use background-color to match frontend
		style_background_color.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ', .topbar-buddy' + banner_id + '{background-color:' + bgColor + ' !important;}'));
		document.getElementsByTagName('head')[0].appendChild(style_background_color);

		document.getElementById('topbar_buddy_color' + banner_id).addEventListener('input', function(e) {
			if (document.getElementById('topbar_buddy_color_show' + banner_id)) {
				document.getElementById('topbar_buddy_color_show' + banner_id).value = e.target.value || '#000000';
			}
			const child = document.getElementById('preview_banner_background_color' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_background_color' + banner_id;
			// Use more specific selector for preview to ensure it applies, use background-color to match frontend
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ', .topbar-buddy' + banner_id + '{background-color:' + (e.target.value || '#000000') + ' !important;}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});

		if (document.getElementById('topbar_buddy_color_show' + banner_id)) {
			document.getElementById('topbar_buddy_color_show' + banner_id).addEventListener('change', function(e) {
				document.getElementById('topbar_buddy_color' + banner_id).value = e.target.value;
				document.getElementById('topbar_buddy_color' + banner_id).dispatchEvent(new Event('input'));
			});
		}
	}

	// Text Color Preview
	if (document.getElementById('topbar_buddy_text_color' + banner_id)) {
		style_text_color.type = 'text/css';
		style_text_color.id = 'preview_banner_text_color' + banner_id;
		const textColor = document.getElementById('topbar_buddy_text_color' + banner_id).value || '#ffffff';
		// Use more specific selector for preview to ensure it applies
		style_text_color.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + '{color:' + textColor + ' !important;}'));
		document.getElementsByTagName('head')[0].appendChild(style_text_color);

		document.getElementById('topbar_buddy_text_color' + banner_id).addEventListener('input', function(e) {
			if (document.getElementById('topbar_buddy_text_color_show' + banner_id)) {
				document.getElementById('topbar_buddy_text_color_show' + banner_id).value = e.target.value || '#ffffff';
			}
			const child = document.getElementById('preview_banner_text_color' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_text_color' + banner_id;
			// Use more specific selector for preview to ensure it applies
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + '{color:' + (e.target.value || '#ffffff') + ' !important;}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});

		if (document.getElementById('topbar_buddy_text_color_show' + banner_id)) {
			document.getElementById('topbar_buddy_text_color_show' + banner_id).addEventListener('change', function(e) {
				document.getElementById('topbar_buddy_text_color' + banner_id).value = e.target.value;
				document.getElementById('topbar_buddy_text_color' + banner_id).dispatchEvent(new Event('input'));
			});
		}
	}

	// Link Color Preview
	if (document.getElementById('topbar_buddy_link_color' + banner_id)) {
		style_link_color.type = 'text/css';
		style_link_color.id = 'preview_banner_link_color' + banner_id;
		const linkColor = document.getElementById('topbar_buddy_link_color' + banner_id).value || '#f16521';
		// Use more specific selector for preview to ensure it applies
		style_link_color.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ' a, .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ' a{color:' + linkColor + ' !important;}'));
		document.getElementsByTagName('head')[0].appendChild(style_link_color);

		document.getElementById('topbar_buddy_link_color' + banner_id).addEventListener('input', function(e) {
			if (document.getElementById('topbar_buddy_link_color_show' + banner_id)) {
				document.getElementById('topbar_buddy_link_color_show' + banner_id).value = e.target.value || '#f16521';
			}
			const child = document.getElementById('preview_banner_link_color' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_link_color' + banner_id;
			// Use more specific selector for preview to ensure it applies
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ' a, .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ' a{color:' + (e.target.value || '#f16521') + ' !important;}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});

		if (document.getElementById('topbar_buddy_link_color_show' + banner_id)) {
			document.getElementById('topbar_buddy_link_color_show' + banner_id).addEventListener('change', function(e) {
				document.getElementById('topbar_buddy_link_color' + banner_id).value = e.target.value;
				document.getElementById('topbar_buddy_link_color' + banner_id).dispatchEvent(new Event('input'));
			});
		}
	}

	// Close Color Preview
	if (document.getElementById('topbar_buddy_close_color' + banner_id)) {
		style_close_color.type = 'text/css';
		style_close_color.id = 'preview_banner_close_color' + banner_id;
		const closeColor = document.getElementById('topbar_buddy_close_color' + banner_id).value || '#ffffff';
		// Use more specific selector for preview to ensure it applies
		style_close_color.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + '{color:' + closeColor + ' !important;}'));
		document.getElementsByTagName('head')[0].appendChild(style_close_color);

		document.getElementById('topbar_buddy_close_color' + banner_id).addEventListener('input', function(e) {
			if (document.getElementById('topbar_buddy_close_color_show' + banner_id)) {
				document.getElementById('topbar_buddy_close_color_show' + banner_id).value = e.target.value || '#ffffff';
			}
			const child = document.getElementById('preview_banner_close_color' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_close_color' + banner_id;
			// Use more specific selector for preview to ensure it applies
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + '{color:' + (e.target.value || '#ffffff') + ' !important;}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});

		if (document.getElementById('topbar_buddy_close_color_show' + banner_id)) {
			document.getElementById('topbar_buddy_close_color_show' + banner_id).addEventListener('change', function(e) {
				document.getElementById('topbar_buddy_close_color' + banner_id).value = e.target.value;
				document.getElementById('topbar_buddy_close_color' + banner_id).dispatchEvent(new Event('input'));
			});
		}
	}

	// Custom CSS Preview
	if (document.getElementById('topbar_buddy_custom_css' + banner_id)) {
		style_custom_css.type = 'text/css';
		style_custom_css.id = 'preview_banner_custom_stylesheet' + banner_id;
		// Use more specific selector for preview to ensure it applies
		style_custom_css.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ', .topbar-buddy' + banner_id + '{' + document.getElementById('topbar_buddy_custom_css' + banner_id).value + '}'));
		document.getElementsByTagName('head')[0].appendChild(style_custom_css);

		document.getElementById('topbar_buddy_custom_css' + banner_id).addEventListener('input', function() {
			const child = document.getElementById('preview_banner_custom_stylesheet' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_custom_stylesheet' + banner_id;
			// Use more specific selector for preview to ensure it applies
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ', .topbar-buddy' + banner_id + '{' + document.getElementById('topbar_buddy_custom_css' + banner_id).value + '}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});
	}

	// Custom Text CSS Preview
	if (document.getElementById('topbar_buddy_text_custom_css' + banner_id)) {
		style_custom_text_css.type = 'text/css';
		style_custom_text_css.id = 'preview_banner_custom_text_stylesheet' + banner_id;
		// Use more specific selector for preview to ensure it applies
		style_custom_text_css.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + '{' + document.getElementById('topbar_buddy_text_custom_css' + banner_id).value + '}'));
		document.getElementsByTagName('head')[0].appendChild(style_custom_text_css);

		document.getElementById('topbar_buddy_text_custom_css' + banner_id).addEventListener('input', function() {
			const child = document.getElementById('preview_banner_custom_text_stylesheet' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_custom_text_stylesheet' + banner_id;
			// Use more specific selector for preview to ensure it applies
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-text' + banner_id + '{' + document.getElementById('topbar_buddy_text_custom_css' + banner_id).value + '}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});
	}

	// Custom Button CSS Preview
	if (document.getElementById('topbar_buddy_button_css' + banner_id)) {
		style_custom_button_css.type = 'text/css';
		style_custom_button_css.id = 'preview_banner_custom_button_stylesheet' + banner_id;
		// Use more specific selector for preview to ensure it applies
		style_custom_button_css.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + '{' + document.getElementById('topbar_buddy_button_css' + banner_id).value + '}'));
		document.getElementsByTagName('head')[0].appendChild(style_custom_button_css);

		document.getElementById('topbar_buddy_button_css' + banner_id).addEventListener('input', function() {
			const child = document.getElementById('preview_banner_custom_button_stylesheet' + banner_id);
			if (child) {
				child.innerText = '';
				child.id = '';
			}
			const style_dynamic = document.createElement('style');
			style_dynamic.type = 'text/css';
			style_dynamic.id = 'preview_banner_custom_button_stylesheet' + banner_id;
			// Use more specific selector for preview to ensure it applies
			style_dynamic.appendChild(document.createTextNode('.sb-preview-wrapper .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + ', .topbar-buddy' + banner_id + ' .topbar-buddy-button' + banner_id + '{' + document.getElementById('topbar_buddy_button_css' + banner_id).value + '}'));
			document.getElementsByTagName('head')[0].appendChild(style_dynamic);
		});
	}

	// Disabled Pages Checkboxes
	const disabledPagesEl = document.getElementById('topbar_buddy_pro_disabled_pages' + banner_id);
	if (disabledPagesEl) {
		disabledPagesEl.addEventListener('click', function(e) {
			if (e.target.type === 'checkbox') {
				let disabledPagesArray = [];
				Array.from(disabledPagesEl.getElementsByTagName('input')).forEach(function(input) {
					if (input.checked) {
						disabledPagesArray.push(input.value);
					}
				});
				const hiddenInput = document.getElementById('eeab_disabled_pages_array' + banner_id);
				if (hiddenInput) {
					hiddenInput.value = disabledPagesArray.join(',');
				}
			}
		});
	}

	// Handle banner text on submit - get content from TinyMCE if available
	const submitBtn = document.getElementById('submit');
	if (submitBtn) {
		submitBtn.addEventListener('click', function(e) {
			// Get content from TinyMCE editor if available
			if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
				// TinyMCE handles content automatically, but we can sync to textarea if needed
				tinymce.get(editorId).save();
			} else {
				// Fallback: remove newlines from textarea if TinyMCE not available
				const textareaEl = document.getElementById(editorId);
				if (textareaEl) {
					textareaEl.value = textareaEl.value.replace(/\n/g, '');
				}
			}
		});
	}

	// Handle datetime-local inputs - convert to proper format for saving
	const startDateEl = document.getElementById('topbar_buddy_start_after_date' + banner_id);
	const endDateEl = document.getElementById('topbar_buddy_remove_after_date' + banner_id);
	
	if (submitBtn && (startDateEl || endDateEl)) {
		submitBtn.addEventListener('click', function(e) {
			// Convert datetime-local to proper format before submit
			if (startDateEl) {
				convertDateTimeLocal(startDateEl);
			}
			if (endDateEl) {
				convertDateTimeLocal(endDateEl);
			}
		});
	}
})();
