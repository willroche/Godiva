/**
 * @file
 * Provides GridStack admin loader.
 */

(function ($, Drupal, drupalSettings, _, window) {

  'use strict';

  Drupal.behaviors.gridstackAdmin = {
    grids: null,
    data: {},
    breakpoints: {},
    windowWidth: 0,
    opts: {},
    serializedData: [],
    form: '.form--gridstack--ui',
    storedData: {},
    options: [],
    defaultGrids: [
     {x: 0, y: 0, width: 2, height: 2},
     {x: 3, y: 1, width: 1, height: 2},
     {x: 4, y: 1, width: 1, height: 1},
     {x: 2, y: 3, width: 3, height: 1},
     {x: 1, y: 4, width: 1, height: 1},
     {x: 1, y: 3, width: 1, height: 1},
     {x: 2, y: 4, width: 1, height: 1},
     {x: 2, y: 5, width: 1, height: 1}
    ],
    attach: function (context) {
      var me = this;
      var defaults = drupalSettings.gridstack || {};
      var base = {
        itemClass: 'gridstack__box',
        handle: '.box__content',
        alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
        resizable: {
          handles: 'e, se, s, sw, w'
        }
      };
      var $form = $(me.form, context);

      $('.gridstack--admin', $form).once('gridstack-admin').each(function () {
        var that = this;
        var $that = $(that);
        var $gridstack;
        var items = null;
        var responsiveWidth = $that.data('responsiveWidth');
        var currentColumn = $that.data('currentColumn') || 12;

        me.data = $that.data('config') || {};

        if (responsiveWidth) {
          $that.css({
            maxWidth: responsiveWidth
          });

          if (currentColumn < 12) {
            me.data.width = currentColumn;
          }
        }

        me.opts = $.extend({}, defaults, base, me.data);

        me.storedData = $that.data('previewGrids') || {};
        me.serializedData = me.storedData || me.defaultGrids;

        $gridstack = $that.gridstack(me.opts);

        me.grids = $that.data('gridstack');
        me.breakpoints = $that.data('breakpoints') || null;

        me.grids.setGridWidth(currentColumn);

        // me.loadGrid($that, me.grids);
        items = window.GridStackUI.Utils.sort(me.serializeData($that));

        $('> .gridstack__box', that).each(function () {
          var $box = $(this);

          me.cleanUpBox($box);
          me.setDimensions($box);
          me.selectImage($box, items);
        });

        $that.on('added', function (e, items) {
          var $box = $('> .gridstack__box:visible', that).last();
          var index = $box.index() + 1;

          $box.addClass('box--' + index).attr('data-index', index);
          me.cleanUpBox($box);
          me.setDimensions($box);
        });

        // @todo reset index?
        // $that.on('removed', function(e, items) {});
        $gridstack.on('resizestop', function (e) {
          var $box = $(e.target);

          me.setDimensions($box);
        });

        $that.removeClass('grid-stack-12');
      });

      $('.button--main', $form).once('gridstack-button').each(function () {
        $(this).off('click.gsbutton.main').on('click.gsbutton.main', function (e) {
          if (e.target === this) {
            var $btn = $(this);

            switch ($btn.data('message')) {
              case 'save':
                me.saveGrid(e);

                break;

              case 'add':
                me.addBox(e);

                break;

              case 'clear':
                me.clearGrid(e);

                break;

              case 'load':
                var grid = me.currentGrid(e);
                var $that = $btn.closest('.gridstack-preview').find('.gridstack');

                me.loadGrid($that, grid);

                break;
            }

            return false;
          }
        });
      });

      $form.once('gridstack-form').each(function () {
        var $that = $(this);
        $that.find('.form-select--column').each(function () {
          $(this).change(function (e) {
            if (e.target === this) {
              var $elm = $(this);
              var $target = $($elm.data('target'));

              if ($target.length) {
                var grid = $target.data('gridstack');
                var c = $elm.val() ? $elm.val() : $target.data('currentColumn');

                grid.setGridWidth(c);
              }
            }
          }).change();
        });

        $that.find('.form-text--width').each(function () {
          $(this).on('keyup', _.debounce(function (e) {
            if (e.target === this) {
              var $elm = $(this);
              var $target = $($elm.data('target'));

              if ($target.length) {
                var w = $elm.val() ? $elm.val() : $target.data('responsiveWidth');

                $target.css({
                  width: w,
                  maxWidth: w
                });
              }
            }
          }, 200));
        });

        $that.off('click.gsbutton.remove').on('click.gsbutton.remove', '.button--remove', function (e) {
          if (e.target === this) {
            me.removeBox(e);

            return false;
          }
        });

        $that.on('submit', function () {
          $('.button--gridstack[data-message="save"]').each(function () {
            $(this).click();
          });
        });
      });
    },

    setDimensions: function ($box) {
      var node = $box.data('_gridstack_node');

      if (!_.isUndefined(node) && !_.isNull(node.width)) {
        $('.box__dimension', $box).html(node.width + 'x' + node.height);
      }
    },

    box: function (i) {
      return $($('#gridstack-box-template').html()).clone();
    },

    cleanUpBox: function ($box) {
      $box.find('.form-select--image-style').removeClass('form-select--original').removeAttr('id');
      if (!$box.closest('.gridstack').hasClass('gridstack--main')) {
        $('.button--remove', $box).remove();
      }
    },

    serializeData: function ($that) {
      var me = this;
      var dataStorage = $that.data('storage');
      var dataMain = $('.gridstack--main').data('previewGrids');
      var $storage = $('[data-drupal-selector="' + dataStorage + '"]');
      var dataStored = $storage.val() !== '' && $storage.val() !== '[]' ? JSON.parse($storage.val()) : '';
      var serializeData = dataStored || dataMain || me.defaultGrids;

      return serializeData;
    },

    loadGrid: function ($that, grid) {
      var me = this;

      grid.removeAll();

      var items = window.GridStackUI.Utils.sort(me.serializeData($that), -1);
      _.each(items, function (node, i) {
        $('.button--remove', me.box(i)).data('message', 'remove');

        grid.addWidget(
          $(me.box(i)),
          node.x,
          node.y,
          node.width,
          node.height,
          true
        );
      }, this);

      return false;
    },

    saveGrid: function (e) {
      var me = this;
      var $btn = $(e.currentTarget);
      var dataStorage = $btn.data('storage');
      var container = $btn.closest('.gridstack-preview').find('.gridstack');
      var grids = null;

      me.serializedData = _.map($('> .gridstack__box:visible', container), function (el, i) {
        var node = $(el).data('_gridstack_node');
        var $fake = $('.form-select--image-style', el);

        grids = {
          x: node.x,
          y: node.y,
          width: node.width,
          height: node.height,
          image_style: $fake.val()
        };

        return grids;
      }, this);

      $('[data-drupal-selector="' + dataStorage + '"]').val(_.isNull(grids) ? '' : JSON.stringify(me.serializedData));

      return false;
    },

    selectImage: function ($box, items) {
      var dataIndex = $box.data('index') - 1;
      var $fake = $('.form-select--image-style', $box);

      $fake.on('change.gschange.img', function (e) {
        if (e.target === this) {
          var $select = $(this);
          var imageStyle = typeof items[dataIndex] !== 'undefined' && typeof items[dataIndex].image_style !== 'undefined' ? items[dataIndex].image_style : '';
          var stored = $select.data('imageStyle') ? $select.data('imageStyle') : imageStyle;
          var val = $(this).val() || stored;

          $select.val(val).find('option:selected').prop('selected', true).siblings('option').prop('selected', false);
        }
      }).change();
    },

    clearGrid: function (e) {
      var me = this;
      var grid = me.currentGrid(e);
      var $target = $(e.currentTarget).closest('.gridstack-preview');

      if (grid) {
        grid.removeAll();

        $('html, body').stop().animate({
          scrollTop: $target.offset().top - 120
        }, 100);
      }

      return false;
    },

    addBox: function (e) {
      var me = this;

      $('.gridstack--admin').each(function () {
        var $that = $(this);
        var index = $('> .gridstack__box:visible', $that).last().data('index');
        var grid = $that.data('gridstack');

        // (el, x, y, width, height, autoPosition, minWidth, maxWidth, minHeight, maxHeight, id)
        grid.addWidget(
          $(me.box(index)),
          0,
          0,
          Math.floor(1 + 3 * Math.random()),
          Math.floor(1 + 3 * Math.random()),
          true
        );
      });

      return false;
    },

    removeBox: function (e) {
      var $btn = $(e.currentTarget);
      var index = $btn.closest('.gridstack__box').data('index');

      $('.gridstack--admin').each(function () {
        var $that = $(this);
        var grid = $that.data('gridstack');
        var $box = $('.gridstack__box[data-index="' + index + '"]');
        var node = $box.data('_gridstack_node');

        if (!_.isUndefined(node) && !_.isNull(node._id)) {
          grid.removeWidget(node.el);
        }
      });

      return false;
    },

    currentGrid: function (e, t) {
      var $btn = $(e.currentTarget);
      var container = $btn.closest('.gridstack-preview').find('.gridstack');
      var $that = t ? t : $(container);
      var grid = $that.data('gridstack');

      return grid;
    }

  };

})(jQuery, Drupal, drupalSettings, _, this);
