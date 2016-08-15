/**
 * @file
 * Javascript behaviors for YAML form help.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Autoplay help video when details element is opened.
   */
  Drupal.behaviors.yamlFormHelpDetailsVideo = {
    attach: function (context) {
      $('details.yamlform-help-details > summary', context).once('yamlform-help-details').click(function() {
        var $details = $(this).parent();

        // Track is details has 'open' attribute, which means that it is
        // being closed.
        var isClosing = ($details.attr('open') == 'open');

        $details.find('.yamlform-help-video-youtube iframe').each(function() {
          var src = $(this).attr('src');
          if (isClosing) {
            $(this).attr('src', src.replace('?autoplay=1', ''));
          }
          else {
            $(this).attr('src', src + '?autoplay=1');
          }
        })

      });
    }
  };


})(jQuery, Drupal);
