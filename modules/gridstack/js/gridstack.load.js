/**
 * @file
 * Provides GridStack loader.
 */

(function ($, Drupal, drupalSettings, _, window) {

  'use strict';

  Drupal.blazy = Drupal.blazy || {};

  Drupal.behaviors.gridstack = {
    grids: null,
    config: {},
    breakpoints: null,
    windowWidth: 0,
    options: {},
    serializedData: null,
    attach: function (context) {

      var me = this;
      var base = {
        mobileFirst: false,
        itemClass: 'gridstack__box',
        handle: '.box__content'
      };
      var defaults = drupalSettings.gridstack || {};
      var $gridstack = $('.gridstack:not(.ungridstack)', context);

      $gridstack.once('gridstack').each(function () {
        var elm = $(this);
        var box = $('> .gridstack__box:visible', elm);

        me.config = elm.data('config') || {};
        me.options = $.extend({}, defaults, base, me.config);

        elm.gridstack(me.options);

        me.grids = elm.data('gridstack');
        me.breakpoints = elm.data('breakpoints') || null;
        me.serializedData = me.serialized(box);
        me.onResize(elm);
        me.cleanUp(elm, box);
      });
    },

    cleanUp: function (elm, box) {
      // @todo drop if any core fix to go without UI stuffs.
      if (elm.hasClass('grid-stack-static')) {
        box.removeClass(function (index, css) {
          return (css.match(/(^|\s)ui-\S+/g) || []).join(' ');
        }).find('.ui-resizable-handle').remove();
      }

      elm.removeClass('grid-stack-12');
      elm.removeAttr('data-config data-sm-grids data-md-grids');
    },

    serialized: function (box) {
      var me = this;
      var data = _.map(box, function (grid) {
        var node = $(grid).data('_gridstack_node');
        var grids = {
          x: node.x,
          y: node.y,
          width: node.width,
          height: node.height
        };

        return grids;
      }, me);

      return data;
    },

    resizeGrid: function (elm) {
      var me = this;
      var minWidth = me.config.minWidth;

      if (me.windowWidth <= minWidth) {
        elm.removeClass('gridstack--enabled').addClass('gridstack--disabled');
      }
      else {
        elm.removeClass('gridstack--disabled').addClass('gridstack--enabled');
      }

      if (!_.isNull(me.breakpoints)) {
        me.updateGrid(elm);
      }
    },

    updateGrid: function (elm) {
      var me = this;
      var activeGrid = null;
      var keys = _.keys(me.breakpoints);
      var max = parseInt(_.last(keys));
      var tcl = null;
      var twd = null;

      var breakpoints = keys.sort(function (a, b) {
        return (me.options.mobileFirst) ? a - b : b - a;
      });

      _.each(breakpoints, function (width, i) {
        if (me.windowWidth <= width) {
          tcl = me.breakpoints[width];
          twd = width;
        }
        else if (me.windowWidth >= max) {
          tcl = me.breakpoints[max];
          twd = max;
        }
      });

      if (!_.isNull(tcl)) {
        me.grids.setGridWidth(tcl);

        // {"480":1,"767":2,"1024":3,"1400":12}
        activeGrid = me.activeGrid(elm, twd, max);
        if (!_.isNull(activeGrid)) {
          _.each(activeGrid, function (item, i) {
            var $box = $('> .gridstack__box:visible', elm).eq(i);

            item = _.isObject(item) ? _.values(item) : item;

            // Params: el, x, y, width, height.
            me.grids.update($box, item[0], item[1], item[2], item[3]);
          });
        }
      }
    },

    activeGrid: function (elm, width, max) {
      var me = this;
      var sd = me.serializedData;
      var mg = elm.data('mdGrids');
      var mw = elm.data('mdWidth');
      var sg = elm.data('smGrids');
      var sw = elm.data('smWidth');

      // Do not do anything if no responsive grids defined.
      // @todo mobile first when Blazy supports it, and more flexible logic.
      if (sw || mw) {
        if (max <= width) {
          return sd;
        }
        else if (mw <= width) {
          return mg;
        }
        else if (sw <= width) {
          return sg;
        }
      }

      return null;
    },

    onResize: function (elm) {
      var me = this;

      me.windowWidth = Drupal.blazy.windowWidth;
      me.resizeGrid(elm);

      elm.on('resizing', function (e, windowWidth) {
        me.windowWidth = windowWidth;
        me.resizeGrid(elm);
      });
    }

  };

})(jQuery, Drupal, drupalSettings, _, this);
