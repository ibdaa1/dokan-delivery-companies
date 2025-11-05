/**
 * Delivery Companies JavaScript
 *
 * @package Dokan_Delivery_Companies
 */

(function ($) {
    'use strict';

    // Delivery Companies Object
    window.DokanDeliveryCompanies = {

        /**
         * Initialize
         */
        init: function () {
            this.bindEvents();
            this.initTabs();
            this.initForms();
            this.initTables();
        },

        /**
         * Bind Events
         */
        bindEvents: function () {
            // Tab switching
            $(document).on('click', '.dashboard-tab', this.handleTabClick);

            // Form submissions
            $(document).on('submit', '#add-zone-form', this.handleAddZone);
            $(document).on('submit', '.delivery-company-form', this.handleFormSubmission);

            // Button clicks
            $(document).on('click', '.update-status-btn', this.handleStatusUpdate);
            $(document).on('click', '.delete-zone-btn', this.handleDeleteZone);
            $(document).on('click', '.btn-primary', this.handleButtonClick);

            // Country change
            $(document).on('change', '#country', this.handleCountryChange);

            // Zone type change
            $(document).on('change', '#zone_type', this.handleZoneTypeChange);
        },

        /**
         * Initialize tabs
         */
        initTabs: function () {
            if ($('.dashboard-tabs').length) {
                var currentTab = this.getCurrentTab();
                this.showTab(currentTab);
            }
        },

        /**
         * Initialize forms
         */
        initForms: function () {
            // Add form validation
            $('.delivery-company-form').on('submit', function (e) {
                if (!DokanDeliveryCompanies.validateForm($(this))) {
                    e.preventDefault();
                    return false;
                }
            });

            // Add loading states
            $('.delivery-company-form').on('submit', function () {
                DokanDeliveryCompanies.showLoading($(this));
            });
        },

        /**
         * Initialize tables
         */
        initTables: function () {
            // Add responsive table wrapper
            $('.dashboard-table').wrap('<div class="table-responsive"></div>');

            // Add hover effects
            $('.dashboard-table tbody tr').hover(
                function () { $(this).addClass('hover'); },
                function () { $(this).removeClass('hover'); }
            );
        },

        /**
         * Handle tab click
         */
        handleTabClick: function (e) {
            e.preventDefault();

            var $tab = $(this);
            // Get the last non-empty path segment to support trailing slashes
            var href = $tab.attr('href') || '';
            var parts = href.split('/');
            var tabName = 'orders';
            for (var i = parts.length - 1; i >= 0; i--) {
                if (parts[i]) {
                    tabName = parts[i];
                    break;
                }
            }

            // Update active tab
            $('.dashboard-tab').removeClass('active');
            $tab.addClass('active');

            // Load tab content
            DokanDeliveryCompanies.loadTabContent(tabName);
        },

        /**
         * Load tab content
         */
        loadTabContent: function (tabName) {
            var $content = $('.dashboard-content');

            // Show loading
            $content.addClass('loading');

            $.ajax({
                url: dokan_delivery_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dokan_delivery_load_tab',
                    tab: tabName,
                    nonce: dokan_delivery_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $content.html(response.data).removeClass('loading').addClass('fade-in');

                        // Reinitialize components
                        DokanDeliveryCompanies.initForms();
                        DokanDeliveryCompanies.initTables();
                    } else {
                        DokanDeliveryCompanies.showError(response.data.message);
                    }
                },
                error: function () {
                    DokanDeliveryCompanies.showError('An error occurred while loading the content.');
                },
                complete: function () {
                    $content.removeClass('loading');
                }
            });
        },

        /**
         * Handle add zone form
         */
        handleAddZone: function (e) {
            e.preventDefault();

            var $form = $(this);
            var formData = $form.serialize();

            if (!DokanDeliveryCompanies.validateZoneForm($form)) {
                return false;
            }

            DokanDeliveryCompanies.showLoading($form);

            $.ajax({
                url: dokan_delivery_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=dokan_delivery_add_shipping_zone&nonce=' + dokan_delivery_ajax.nonce,
                success: function (response) {
                    if (response.success) {
                        DokanDeliveryCompanies.showSuccess(response.data.message);
                        $form[0].reset();
                        DokanDeliveryCompanies.loadTabContent('shipping-zones');
                    } else {
                        DokanDeliveryCompanies.showError(response.data.message);
                    }
                },
                error: function () {
                    DokanDeliveryCompanies.showError('An error occurred while adding the shipping zone.');
                },
                complete: function () {
                    DokanDeliveryCompanies.hideLoading($form);
                }
            });
        },

        /**
         * Handle status update
         */
        handleStatusUpdate: function (e) {
            e.preventDefault();

            var $btn = $(this);
            var orderId = $btn.data('order-id');
            var status = $btn.data('status');
            var notes = prompt('Add notes (optional):');

            if (notes === null) return;

            DokanDeliveryCompanies.showLoading($btn);

            $.ajax({
                url: dokan_delivery_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dokan_delivery_update_order_status',
                    order_id: orderId,
                    status: status,
                    notes: notes,
                    nonce: dokan_delivery_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        DokanDeliveryCompanies.showSuccess(response.data.message);
                        DokanDeliveryCompanies.loadTabContent('orders');
                    } else {
                        DokanDeliveryCompanies.showError(response.data.message);
                    }
                },
                error: function () {
                    DokanDeliveryCompanies.showError('An error occurred while updating the order status.');
                },
                complete: function () {
                    DokanDeliveryCompanies.hideLoading($btn);
                }
            });
        },

        /**
         * Handle delete zone
         */
        handleDeleteZone: function (e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this shipping zone?')) {
                return;
            }

            var $btn = $(this);
            var zoneId = $btn.data('zone-id');

            DokanDeliveryCompanies.showLoading($btn);

            $.ajax({
                url: dokan_delivery_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dokan_delivery_delete_shipping_zone',
                    zone_id: zoneId,
                    nonce: dokan_delivery_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        DokanDeliveryCompanies.showSuccess(response.data.message);
                        DokanDeliveryCompanies.loadTabContent('shipping-zones');
                    } else {
                        DokanDeliveryCompanies.showError(response.data.message);
                    }
                },
                error: function () {
                    DokanDeliveryCompanies.showError('An error occurred while deleting the shipping zone.');
                },
                complete: function () {
                    DokanDeliveryCompanies.hideLoading($btn);
                }
            });
        },

        /**
         * Handle country change
         */
        handleCountryChange: function () {
            var countryCode = $(this).val();
            var $stateField = $('#state');

            if (!countryCode) {
                return;
            }

            $.ajax({
                url: dokan_delivery_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dokan_delivery_get_states',
                    country_code: countryCode,
                    nonce: dokan_delivery_ajax.nonce
                },
                success: function (response) {
                    if (response.success && response.data) {
                        var options = '<option value="">Select State/Province</option>';

                        $.each(response.data, function (code, name) {
                            options += '<option value="' + code + '">' + name + '</option>';
                        });

                        $stateField.html(options);
                    } else {
                        $stateField.html('<option value="">Select State/Province</option>');
                    }
                },
                error: function () {
                    $stateField.html('<option value="">Select State/Province</option>');
                }
            });
        },

        /**
         * Handle zone type change
         */
        handleZoneTypeChange: function () {
            var zoneType = $(this).val();
            var $zoneValue = $('#zone_value');

            // Helper to create a multi-select for countries
            function buildCountryMultiSelect() {
                var select = $('<select multiple name="zone_value[]" id="zone_value" required></select>');
                $.each(dokan_delivery_ajax.countries, function (code, name) {
                    select.append('<option value="' + code + '">' + name + '</option>');
                });
                return select;
            }

            // Replace the zone value control depending on type
            if (zoneType === 'country') {
                var countrySelect = buildCountryMultiSelect();
                $zoneValue.replaceWith(countrySelect);
            } else if (zoneType === 'state') {
                // show a pair: country select to fetch states, and a placeholder for states
                var wrapper = $('<div id="zone_value_wrapper"></div>');
                var countrySelect = $('<select id="zone_value_country"><option value="">Select Country (optional for states)</option></select>');
                countrySelect.append('<option value=""></option>');
                $.each(dokan_delivery_ajax.countries, function (code, name) {
                    countrySelect.append('<option value="' + code + '">' + name + '</option>');
                });
                var stateInput = $('<input type="text" name="zone_value" id="zone_value" placeholder="e.g., CA,NY for states" required>');
                wrapper.append(countrySelect).append(stateInput);
                $zoneValue.replaceWith(wrapper);

                // when country selected, fetch states and replace the input with multi-select
                countrySelect.on('change', function () {
                    var cc = $(this).val();
                    if (!cc) return;
                    $.ajax({
                        url: dokan_delivery_ajax.ajax_url,
                        type: 'POST',
                        data: { action: 'dokan_delivery_get_states', country_code: cc, nonce: dokan_delivery_ajax.nonce },
                        success: function (res) {
                            if (res.success && res.data) {
                                var stateSelect = $('<select multiple name="zone_value[]" id="zone_value" required></select>');
                                $.each(res.data, function (code, name) {
                                    stateSelect.append('<option value="' + code + '">' + name + '</option>');
                                });
                                wrapper.find('#zone_value').replaceWith(stateSelect);
                            }
                        }
                    });
                });
            } else {
                // city or postal -> single text input for comma-separated values
                var input = $('<input type="text" name="zone_value" id="zone_value" required placeholder="Enter comma-separated values">');
                $zoneValue.replaceWith(input);
            }
        },

        /**
         * Handle form submission
         */
        handleFormSubmission: function (e) {
            var $form = $(this);

            if (!DokanDeliveryCompanies.validateForm($form)) {
                e.preventDefault();
                return false;
            }

            DokanDeliveryCompanies.showLoading($form);
        },

        /**
         * Handle button click
         */
        handleButtonClick: function (e) {
            var $btn = $(this);

            // Add click animation
            $btn.addClass('btn-clicked');
            setTimeout(function () {
                $btn.removeClass('btn-clicked');
            }, 200);
        },

        /**
         * Validate form
         */
        validateForm: function ($form) {
            var isValid = true;
            var $requiredFields = $form.find('[required]');

            $requiredFields.each(function () {
                var $field = $(this);
                var value = $field.val().trim();

                if (!value) {
                    DokanDeliveryCompanies.showFieldError($field, 'This field is required.');
                    isValid = false;
                } else {
                    DokanDeliveryCompanies.clearFieldError($field);
                }
            });

            // Email validation
            var $emailField = $form.find('input[type="email"]');
            if ($emailField.length && $emailField.val()) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test($emailField.val())) {
                    DokanDeliveryCompanies.showFieldError($emailField, 'Please enter a valid email address.');
                    isValid = false;
                }
            }

            return isValid;
        },

        /**
         * Validate zone form
         */
        validateZoneForm: function ($form) {
            var isValid = true;

            // Check required fields
            var $requiredFields = $form.find('[required]');
            $requiredFields.each(function () {
                var $field = $(this);
                var value = $field.val().trim();

                if (!value) {
                    DokanDeliveryCompanies.showFieldError($field, 'This field is required.');
                    isValid = false;
                } else {
                    DokanDeliveryCompanies.clearFieldError($field);
                }
            });

            // Validate shipping rate
            var $shippingRate = $form.find('#shipping_rate');
            if ($shippingRate.val() && parseFloat($shippingRate.val()) < 0) {
                DokanDeliveryCompanies.showFieldError($shippingRate, 'Shipping rate must be a positive number.');
                isValid = false;
            }

            // Validate free shipping threshold
            var $freeThreshold = $form.find('#free_shipping_threshold');
            if ($freeThreshold.val() && parseFloat($freeThreshold.val()) < 0) {
                DokanDeliveryCompanies.showFieldError($freeThreshold, 'Free shipping threshold must be a positive number.');
                isValid = false;
            }

            return isValid;
        },

        /**
         * Show field error
         */
        showFieldError: function ($field, message) {
            DokanDeliveryCompanies.clearFieldError($field);

            $field.addClass('error');
            $field.after('<div class="field-error">' + message + '</div>');
        },

        /**
         * Clear field error
         */
        clearFieldError: function ($field) {
            $field.removeClass('error');
            $field.siblings('.field-error').remove();
        },

        /**
         * Show loading
         */
        showLoading: function ($element) {
            $element.addClass('loading');
            $element.find('button, input[type="submit"]').prop('disabled', true);
        },

        /**
         * Hide loading
         */
        hideLoading: function ($element) {
            $element.removeClass('loading');
            $element.find('button, input[type="submit"]').prop('disabled', false);
        },

        /**
         * Show success message
         */
        showSuccess: function (message) {
            DokanDeliveryCompanies.showMessage(message, 'success');
        },

        /**
         * Show error message
         */
        showError: function (message) {
            DokanDeliveryCompanies.showMessage(message, 'error');
        },

        /**
         * Show message
         */
        showMessage: function (message, type) {
            var $message = $('<div class="notice notice-' + type + '"><p>' + message + '</p></div>');

            $('.dashboard-content, .delivery-company-registration').prepend($message);

            // Auto-hide after 5 seconds
            setTimeout(function () {
                $message.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Get current tab
         */
        getCurrentTab: function () {
            // Return the last non-empty path segment, default to 'orders'
            var path = window.location.pathname || '';
            var parts = path.split('/');
            for (var i = parts.length - 1; i >= 0; i--) {
                if (parts[i]) {
                    if (parts[i] === 'delivery-company-dashboard') {
                        return 'orders';
                    }
                    return parts[i];
                }
            }
            return 'orders';
        },

        /**
         * Show tab
         */
        showTab: function (tabName) {
            $('.dashboard-tab').removeClass('active');
            $('.dashboard-tab[href*="' + tabName + '"]').addClass('active');
        },

        /**
         * Request payout
         */
        requestPayout: function () {
            var method = prompt('Enter payout method (bank_transfer, paypal, manual):');

            if (!method) {
                return;
            }

            var methodData = {};

            switch (method) {
                case 'bank_transfer':
                    methodData.account_number = prompt('Account Number:');
                    methodData.routing_number = prompt('Routing Number:');
                    methodData.bank_name = prompt('Bank Name:');
                    break;

                case 'paypal':
                    methodData.paypal_email = prompt('PayPal Email:');
                    break;

                case 'manual':
                    methodData.notes = prompt('Notes:');
                    break;
            }

            $.ajax({
                url: dokan_delivery_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dokan_delivery_process_payout',
                    method: method,
                    method_data: methodData,
                    nonce: dokan_delivery_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        DokanDeliveryCompanies.showSuccess(response.data.message);
                        DokanDeliveryCompanies.loadTabContent('earnings');
                    } else {
                        DokanDeliveryCompanies.showError(response.data.message);
                    }
                },
                error: function () {
                    DokanDeliveryCompanies.showError('An error occurred while processing the payout.');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        DokanDeliveryCompanies.init();
    });

    // Make requestPayout globally available
    window.requestPayout = DokanDeliveryCompanies.requestPayout;

})(jQuery);

// Additional utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add CSS for additional styles
jQuery(document).ready(function ($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .field-error {
                color: #dc3545;
                font-size: 12px;
                margin-top: 5px;
                display: block;
            }
            
            .form-group input.error,
            .form-group select.error,
            .form-group textarea.error {
                border-color: #dc3545;
                box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            }
            
            .btn-clicked {
                transform: scale(0.95);
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            @media (max-width: 768px) {
                .table-responsive {
                    border: 1px solid #e1e5e9;
                    border-radius: 8px;
                }
            }
        `)
        .appendTo('head');
});
