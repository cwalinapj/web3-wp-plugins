/**
 * Contributor Bounties - Public JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle bounty submission form
        $('#cb-bounty-submission-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('.cb-submit-button');
            var $message = $('#cb-submission-message');
            
            // Disable submit button
            $button.prop('disabled', true).text('Submitting...');
            $message.removeClass('success error').text('');
            
            // Get form data
            var formData = {
                action: 'submit_bounty_draft',
                nonce: cbAjax.nonce,
                cb_post_title: $('#cb_post_title').val(),
                cb_post_content: $('#cb_post_content').val(),
                cb_campaign_id: $('#cb_campaign_id').val() || $('input[name="cb_campaign_id"]').val()
            };
            
            // Submit via AJAX
            $.ajax({
                url: cbAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message.addClass('success').text(response.data.message);
                        $form[0].reset();
                    } else {
                        $message.addClass('error').text(response.data.message);
                    }
                    $button.prop('disabled', false).text('Submit Entry');
                },
                error: function() {
                    $message.addClass('error').text('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Submit Entry');
                }
            });
        });
    });
    
})(jQuery);
