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

    // Disable the number field if number of links to scan is set to all.
    $('#all_links, #set_number').change(function () {
      $('#number_of_links').prop('disabled', $('#all_links').is(':checked'));
    });
  });

  /**
   * On click of the manual scan button, start the manual scan.
   */
  $(document).on('click', '#wpcbl-manual-scan', function (event) {
    event.preventDefault();
    console.log('Manual scan started...');
    let nonce = klaviyo_wp_meta_sync_params.nonce;
    let data = {
      action: 'wpcbl_broken_links_manual_scan',
      scan_page_url: klaviyo_wp_meta_sync_params.scanPageUrl,
      nonce: nonce
    };

    // Call the manual scan function.
    manualScan(data).then(result => {
      console.log(result);

      // Validate the AJAX response
      if (typeof result === 'object' && result.hasOwnProperty('success') && result.success) {
        // Check if result.data is a string before using it as HTML
        if (typeof result.data === 'string') {
          // Replace the table's HTML with the new HTML
          $('.wpcbl-klaviyo-wp-meta-sync-links-table').html(result.data);
          $('.wpcbl_export_csv_wrap').show();

          // Modify the pagination URLs
          $('.wpcbl-klaviyo-wp-meta-sync-links-table .tablenav-pages a').each(function () {
            var oldUrl = new URL($(this).attr('href'));
            var paged = oldUrl.searchParams.get('paged');
            var newUrl = new URL(klaviyo_wp_meta_sync_params.scanPageUrl);
            newUrl.searchParams.set('paged', paged);
            $(this).attr('href', newUrl.toString());
          });
        } else {
          console.error('Error: Unexpected AJAX response', result);
        }
      } else {
        console.error('Error: AJAX request failed', result);
      }
    });
  });

  /**
   * On click of the mark as fixed button, mark the link as fixed.
   */
  $(document).on('click', '#wpcbl-mark-as-fixed.not-fixed', function (event) {
    event.preventDefault();
    console.log('Mark as fixed started...');
    let nonce = klaviyo_wp_meta_sync_params.nonce;
    let currentEl = $(this);
    let link = currentEl.data('link');
    let postId = currentEl.data('post-id');
    let data = {
      action: 'wpcbl_broken_links_mark_as_fixed',
      nonce: nonce,
      link: link,
      postId: postId
    };

    // Call the mark as fixed function.
    markAsFixed(data).then(result => {
      console.log(result);
      if (typeof result === 'object' && result.hasOwnProperty('success') && result.success) {
        if (typeof result.data === 'boolean' && result.data) {
          if (currentEl.hasClass('fixed')) {
            currentEl.removeClass('fixed').addClass('not-fixed').html('Mark as Fixed');
            currentEl.closest('tr').find('.column-type .status-type').removeClass('fixed').addClass('not-fixed').html('Broken');
          } else {
            currentEl.removeClass('not-fixed').addClass('fixed').html('Mark as Broken');
            currentEl.closest('tr').find('.column-type .status-type').html('Fixed').removeClass('not-fixed').addClass('fixed');
          }
        }
      }
    });
  });

  /**
   * On click of the mark as broken button, mark the link as broken.
   */
  $(document).on('click', '#wpcbl-mark-as-fixed.fixed', function (event) {
    event.preventDefault();
    console.log('Mark as broken started...');
    let nonce = klaviyo_wp_meta_sync_params.nonce;
    let currentEl = $(this);
    let link = currentEl.data('link');
    let postId = currentEl.data('post-id');
    let data = {
      action: 'wpcbl_broken_links_mark_as_broken',
      nonce: nonce,
      link: link,
      postId: postId
    };

    // Call the mark as broken function.
    markAsBroken(data).then(result => {
      console.log(result);
      if (typeof result === 'object' && result.hasOwnProperty('success') && result.success) {
        if (typeof result.data === 'boolean' && result.data) {
          // Correct typo in 'boolean'

          if (currentEl.hasClass('fixed')) {
            currentEl.removeClass('fixed').addClass('not-fixed').html('Mark as Fixed');
            currentEl.closest('tr').find('.column-type .status-type').removeClass('fixed').addClass('not-fixed').html('Broken');
          } else {
            currentEl.removeClass('not-fixed').addClass('fixed').html('Mark as Broken');
            currentEl.closest('tr').find('.column-type .status-type').removeClass('not-fixed').addClass('fixed').html('Fixed');
          }
        }
      }
    });
  });
  $(document).on('click', '.wpcbl-klaviyo-wp-meta-sync-faq-item input', function (event) {
    if ($(this).is(':checked')) {
      $(this).siblings('label').find('.sign').text('-');
    } else {
      $(this).siblings('label').find('.sign').text('+');
    }
  });

  /**
   * Mark a link as broken.
   *
   * @param {object} data 
   * @returns 
   */
  const markAsBroken = async data => {
    let result;
    try {
      result = await $.ajax({
        url: klaviyo_wp_meta_sync_params.ajaxUrl,
        type: 'POST',
        data: data,
        beforeSend: function () {
          console.log('Before mark links as broken');
        },
        complete: function () {
          console.log('Complete');
        }
      });
      return result;
    } catch (error) {
      console.error('Error:', error.statusText);
    }
  };

  /**
   * Manually scan for broken links.
   *
   * @param {object} data 
   * @returns 
   */
  const manualScan = async data => {
    let result;
    try {
      result = await $.ajax({
        url: klaviyo_wp_meta_sync_params.ajaxUrl,
        type: 'POST',
        data: data,
        beforeSend: function () {
          console.log('Sending data...');
          loader('show');
          $('.wpcbl-klaviyo-wp-meta-sync-links-table').html('');
          $('.wpcbl_export_csv_wrap').hide();
        },
        complete: function () {
          console.log('Data sent.');
          loader('hide');
        }
      });
      return result;
    } catch (error) {
      console.error('Error:', error.statusText);
    }
  };

  /**
   * Mark a link as fixed.
   *
   * @param {object} data 
   * @returns 
   */
  const markAsFixed = async data => {
    let result;
    try {
      result = await $.ajax({
        url: klaviyo_wp_meta_sync_params.ajaxUrl,
        type: 'POST',
        data: data,
        beforeSend: function () {
          console.log('Before mark links as broken');
        },
        complete: function () {
          console.log('Complete.');
        }
      });
      return result;
    } catch (error) {
      console.error('Error:', error.statusText);
    }
  };

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