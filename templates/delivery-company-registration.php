<?php

/**
 * Delivery Company Registration Template
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="delivery-company-registration">
    <h1><?php _e('Become a Delivery Company', 'dokan-delivery-companies'); ?></h1>

    <?php if (isset($_GET['message'])) : ?>
        <?php if ($_GET['message'] === 'registration_success') : ?>
            <div class="notice notice-success">
                <p><?php _e('Registration submitted successfully! We will review your application and contact you soon.', 'dokan-delivery-companies'); ?></p>
            </div>
        <?php elseif ($_GET['message'] === 'registration_error') : ?>
            <div class="notice notice-error">
                <p><?php _e('Registration failed. Please try again.', 'dokan-delivery-companies'); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="post" class="delivery-company-form">
        <?php wp_nonce_field('delivery_company_registration', 'delivery_company_nonce'); ?>
        <input type="hidden" name="delivery_company_registration" value="1">

        <div class="form-group">
            <label for="company_name"><?php _e('Company Name', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
            <input type="text" name="company_name" id="company_name" required>
        </div>

        <div class="form-group">
            <label for="contact_person"><?php _e('Contact Person', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
            <input type="text" name="contact_person" id="contact_person" required>
        </div>

        <div class="form-group">
            <label for="email"><?php _e('Email Address', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
            <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
            <label for="phone"><?php _e('Phone Number', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
            <input type="tel" name="phone" id="phone" required>
        </div>

        <div class="form-group">
            <label for="address"><?php _e('Business Address', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
            <textarea name="address" id="address" rows="3" required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="city"><?php _e('City', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
                <input type="text" name="city" id="city" required>
            </div>

            <div class="form-group">
                <label for="state"><?php _e('State/Province', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
                <input type="text" name="state" id="state" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="postal_code"><?php _e('Postal Code', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
                <input type="text" name="postal_code" id="postal_code" required>
            </div>

            <div class="form-group">
                <label for="country"><?php _e('Country', 'dokan-delivery-companies'); ?> <span class="required">*</span></label>
                <select name="country" id="country" required>
                    <option value=""><?php _e('Select Country', 'dokan-delivery-companies'); ?></option>
                    <?php
                    $countries = Dokan_Delivery_Shipping_Zone::get_countries();
                    foreach ($countries as $code => $name) {
                        echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="terms_agreed" required>
                <?php _e('I agree to the terms and conditions', 'dokan-delivery-companies'); ?> <span class="required">*</span>
            </label>
        </div>

        <div class="form-group">
            <button type="submit" class="submit-btn"><?php _e('Submit Application', 'dokan-delivery-companies'); ?></button>
        </div>
    </form>
</div>

<style>
    .delivery-company-registration {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .delivery-company-registration h1 {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
    }

    .delivery-company-form {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-row {
        display: flex;
        gap: 20px;
    }

    .form-row .form-group {
        flex: 1;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }

    .required {
        color: #e74c3c;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #0073aa;
    }

    .submit-btn {
        background: #0073aa;
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: background-color 0.3s;
    }

    .submit-btn:hover {
        background: #005a87;
    }

    .notice {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .notice-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .notice-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }

        .delivery-company-registration {
            padding: 10px;
        }

        .delivery-company-form {
            padding: 20px;
        }
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Country change handler
        $('#country').on('change', function() {
            var countryCode = $(this).val();

            if (countryCode) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dokan_delivery_get_states',
                        country_code: countryCode,
                        nonce: '<?php echo wp_create_nonce('dokan_delivery_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var stateSelect = '<select name="state" id="state" required>';
                            stateSelect += '<option value="">Select State/Province</option>';

                            $.each(response.data, function(code, name) {
                                stateSelect += '<option value="' + code + '">' + name + '</option>';
                            });

                            stateSelect += '</select>';

                            $('#state').replaceWith(stateSelect);
                        }
                    }
                });
            }
        });
    });
</script>

<?php get_footer(); ?>