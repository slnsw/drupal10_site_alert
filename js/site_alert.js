/**
 * @file
 * Contains javascript to refresh alert div contents.
 */

(function ($, Drupal, drupalSettings) {

  let callback;

  // Function to update alert text.
  let loadAlert = function (siteAlert) {
    // Object contains information about the currently loaded theme for
    // processing by our theme callback. Without it the default theme is always
    // assumed.
    let options = {
      ajax_page_state: drupalSettings.ajaxPageState
    };
    siteAlert.load(callback, options);

    setTimeout(function () {
      loadAlert(siteAlert);
    }, drupalSettings.siteAlert.timeout * 1000);
  };

  Drupal.behaviors.siteAlert = {
    attach: function (context, settings) {
      // Update content at configured interval.
      if (drupalSettings.siteAlert.timeout > 0) {
        callback = settings.path.baseUrl + 'ajax/site_alert';
        setTimeout(function () {
          loadAlert($('.site-alert', context));
        }, drupalSettings.siteAlert.timeout * 1000);
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
