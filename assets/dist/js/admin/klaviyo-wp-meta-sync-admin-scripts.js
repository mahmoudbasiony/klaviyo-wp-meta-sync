"use strict";

/* jshint esversion: 6 */

/**
 * Back-end scripts.
 *
 * Scripts to run on the WordPress dashboard.
 */

($ => {
  $(document).ready(function () {
    // Disable the email addresses` field if enable email notifications is unchecked.
    $('#email_notifications').change(function () {
      $('#email_addresses').prop('disabled', !$(this).is(':checked'));
    });
    const totalUsers = klaviyo_wp_meta_sync_params.totalUsers;
    console.log(totalUsers);
    if (totalUsers > 0) {
      const interval = setInterval(() => {
        $.ajax({
          url: klaviyo_wp_meta_sync_params.ajaxUrl,
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'bulk_sync_progress',
            bulk_sync_nonce: klaviyo_wp_meta_sync_params.syncNonce
          },
          success(response) {
            console.log(response);
            if (response.success) {
              const {
                total_users,
                completed_users,
                failed_users,
                remaining_users
              } = response.data;

              // Update the progress notice dynamically
              let progressMessage = `
								<div style="font-size: 14px; line-height: 1.6; margin-bottom: 10px;">
									<strong style="color: green;">${completed_users.length}</strong> out of <strong>${total_users}</strong> users synced successfully.<br>
									<strong style="color: red;">${failed_users.length}</strong> users failed.<br>
									<strong style="color: orange;">${remaining_users}</strong> users are still in progress.
								</div>
							`;
              $('#bulk-sync-progress').html(progressMessage);

              // Stop polling when all syncs are completed
              if (remaining_users <= 0) {
                clearInterval(interval);
                let finalMessage = `
									<div style="margin-top: 10px; font-size: 14px; line-height: 1.6;">
										<strong style="color: green;">All syncs completed successfully!</strong>
										${failed_users.length > 0 ? `<br><strong style="color: red;">${failed_users.length} users failed. Check logs for details.</strong>` : ''}
									</div>
								`;
                $('#bulk-sync-progress').html(progressMessage + finalMessage);
              }
            } else {
              console.log('All syncs completed or no users left to process.');
              // Stop polling and update notice for no users in progress
              clearInterval(interval);
              $('#bulk-sync-progress').html('<strong>All syncs completed or no users left to process.</strong>');
            }
          },
          error() {
            // Stop polling and show an error message
            clearInterval(interval);
            $('#bulk-sync-progress').html('<strong>An error occurred while updating the sync progress. Please refresh the page to check the latest status.</strong>');
            console.error('Failed to fetch bulk sync progress.');
          }
        });
      }, 5000); // Poll every 5 seconds
    } else {
      // No users to scan
      $('#bulk-sync-progress').html('<strong>No users to scan. Please initiate a new bulk sync.</strong>');
    }
  });

  /**
   * Hide/show the loader.
   *
   * @param {string} action 
   */
  const loader = action => {
    let loader = $('.wpcbl-is-scanning');
    if (action === 'show') {
      loader.show();
    } else if (action === 'hide') {
      loader.hide();
    }
  };
})(jQuery);