/**
 * gridstack.js 0.2.5-dev
 * http://troolee.github.io/gridstack.js/
 * (c) 2014-2016 Pavel Reznikov
 * gridstack.js may be freely distributed under the MIT license.
 * @preserve
 *
 * A modified version to remove jQuery UI till decoupled,
 * suitable only for static grid option as normally seen at static frontend.
 */

/* global window, document, define, module, require, jQuery */
(function (factory) {
  'use strict';
  if (typeof define === 'function' && define.amd) {
    // AMD
    define(['jquery', 'underscore'], factory);
  }
  else if (typeof exports === 'object') {
    // Node, CommonJS-like
    module.exports = factory(require('jquery'), require('underscore'));
  }
  else {
    factory(jQuery, _);
  }
}(function ($, _) {

  'use strict';
  var scope = window;

  var Utils = {
    isIntercepted: function (a, b) {
      return !(a.x + a.width <= b.x || b.x + b.width <= a.x || a.y + a.height <= b.y || b.y + b.height <= a.y);
    },

    sort: function (nodes, dir, width) {
      width = width || _.chain(nodes).map(function (node) {
        return node.x + node.width;
      }).max().value();
      dir = dir !== -1 ? 1 : -1;
      return _.sortBy(nodes, function (n) {
        return dir * (n.x + n.y * width);
      });
    },

    createStylesheet: function (id) {
      var style = document.createElement('style');
      style.setAttribute('type', 'text/css');
      style.setAttribute('data-gs-style-id', id);
      if (style.styleSheet) {
        style.styleSheet.cssText = '';
      }
      else {
        style.appendChild(document.createTextNode(''));
      }
      document.getElementsByTagName('head')[0].appendChild(style);
      return style.sheet;
    },

    removeStylesheet: function (id) {
      $('STYLE[data-gs-style-id=' + id + ']').remove();
    },

    insertCSSRule: function (sheet, selector, rules, index) {
      if (typeof sheet.insertRule === 'function') {
        sheet.insertRule(selector + '{' + rules + '}', index);
      }
      else if (typeof sheet.addRule === 'function') {
        sheet.addRule(selector, rules, index);
      }
    },

    toBool: function (v) {
      if (typeof v === 'boolean') {
        return v;
      }
      if (typeof v === 'string') {
        v = v.toLowerCase();
        return !(v === '' || v === 'no' || v === 'false' || v === '0');
      }
      return Boolean(v);
    },

    _collisionNodeCheck: function (n) {
      return n !== this.node && Utils.isIntercepted(n, this.nn);
    },

    _didCollide: function (bn) {
      return Utils.isIntercepted({
        x: this.n.x,
        y: this.newY,
        width: this.n.width,
        height: this.n.height
      }, bn);
    },

    _isAddNodeIntercepted: function (n) {
      return Utils.isIntercepted({
        x: this.x,
        y: this.y,
        width: this.node.width,
        height: this.node.height
      }, n);
    },

    parseHeight: function (val) {
      var height = val;
      var heightUnit = 'px';
      if (height && _.isString(height)) {
        var match = height.match(/^([0-9]*\.[0-9]+|[0-9]+)(px|em|rem|vh|vw)?$/);
        if (!match) {
          throw new Error('Invalid height');
        }
        heightUnit = match[2] || 'px';
        height = parseFloat(match[1]);
      }
      return {
        height: height,
        unit: heightUnit
      };
    }
  };

  var idSeq = 0;

  var GridStackEngine = function (width, onchange, floatMode, height, items) {
    this.width = width;
    this.float = floatMode || false;
    this.height = height || 0;

    this.nodes = items || [];
    this.onchange = onchange || function () {};

    this._updateCounter = 0;
    this._float = this.float;

    this._addedNodes = [];
    this._removedNodes = [];
  };

  GridStackEngine.prototype.batchUpdate = function () {
    this._updateCounter = 1;
    this.float = true;
  };

  GridStackEngine.prototype.commit = function () {
    if (this._updateCounter !== 0) {
      this._updateCounter = 0;
      this.float = this._float;
      this._packNodes();
      this._notify();
    }
  };

  // For Meteor support: https://github.com/troolee/gridstack.js/pull/272
  GridStackEngine.prototype.getNodeDataByDOMEl = function (el) {
    return _.find(this.nodes, function (n) {
      return el.get(0) === n.el.get(0);
    });
  };

  GridStackEngine.prototype._fixCollisions = function (node) {
    this._sortNodes(-1);

    var nn = node;
    var hasLocked = Boolean(_.find(this.nodes, function (n) {
      return n.locked;
    }));
    if (!this.float && !hasLocked) {
      nn = {
        x: 0,
        y: node.y,
        width: this.width,
        height: node.height
      };
    }
    while (true) {
      var collisionNode = _.find(this.nodes, _.bind(Utils._collisionNodeCheck, {
        node: node,
        nn: nn
      }));
      if (typeof collisionNode === 'undefined') {
        return;
      }
      this.moveNode(collisionNode, collisionNode.x, node.y + node.height,
        collisionNode.width, collisionNode.height, true);
    }
  };

  GridStackEngine.prototype.isAreaEmpty = function (x, y, width, height) {
    var nn = {
      x: x || 0,
      y: y || 0,
      width: width || 1,
      height: height || 1
    };
    var collisionNode = _.find(this.nodes, _.bind(function (n) {
      return Utils.isIntercepted(n, nn);
    }, this));
    return collisionNode === null || typeof collisionNode === 'undefined';
  };

  GridStackEngine.prototype._sortNodes = function (dir) {
    this.nodes = Utils.sort(this.nodes, dir, this.width);
  };

  GridStackEngine.prototype._packNodes = function () {
    this._sortNodes();

    if (this.float) {
      _.each(this.nodes, _.bind(function (n, i) {
        if (n._updating || typeof n._origY === 'undefined' || n.y === n._origY) {
          return;
        }

        var newY = n.y;
        while (newY >= n._origY) {
          var collisionNode = _.chain(this.nodes)
            .find(_.bind(Utils._didCollide, {
              n: n,
              newY: newY
            }))
            .value();

          if (!collisionNode) {
            n._dirty = true;
            n.y = newY;
          }
          --newY;
        }
      }, this));
    }
    else {
      _.each(this.nodes, _.bind(function (n, i) {
        if (n.locked) {
          return;
        }
        while (n.y > 0) {
          var newY = n.y - 1;
          var canBeMoved = i === 0;

          if (i > 0) {
            var collisionNode = _.chain(this.nodes)
              .take(i)
              .find(_.bind(Utils._didCollide, {
                n: n,
                newY: newY
              }))
              .value();
            canBeMoved = typeof collisionNode === 'undefined';
          }

          if (!canBeMoved) {
            break;
          }
          n._dirty = n.y !== newY;
          n.y = newY;
        }
      }, this));
    }
  };

  GridStackEngine.prototype._prepareNode = function (node, resizing) {
    node = _.defaults(node || {}, {
      width: 1,
      height: 1,
      x: 0,
      y: 0
    });

    node.x = parseInt('' + node.x);
    node.y = parseInt('' + node.y);
    node.width = parseInt('' + node.width);
    node.height = parseInt('' + node.height);
    node.autoPosition = node.autoPosition || false;
    node.noResize = node.noResize || false;
    node.noMove = node.noMove || false;

    if (node.width > this.width) {
      node.width = this.width;
    }
    else if (node.width < 1) {
      node.width = 1;
    }

    if (node.height < 1) {
      node.height = 1;
    }

    if (node.x < 0) {
      node.x = 0;
    }

    if (node.x + node.width > this.width) {
      if (resizing) {
        node.width = this.width - node.x;
      }
      else {
        node.x = this.width - node.width;
      }
    }

    if (node.y < 0) {
      node.y = 0;
    }

    return node;
  };

  GridStackEngine.prototype._notify = function () {
    if (this._updateCounter) {
      return;
    }
    var deletedNodes = Array.prototype.slice.call(arguments, 0);
    deletedNodes = deletedNodes.concat(this.getDirtyNodes());
    this.onchange(deletedNodes);
  };

  GridStackEngine.prototype.cleanNodes = function () {
    if (this._updateCounter) {
      return;
    }
    _.each(this.nodes, function (n) {
      n._dirty = false;
    });
  };

  GridStackEngine.prototype.getDirtyNodes = function () {
    return _.filter(this.nodes, function (n) {
      return n._dirty;
    });
  };

  GridStackEngine.prototype.addNode = function (node, triggerAddEvent) {
    node = this._prepareNode(node);

    if (typeof node.maxWidth !== 'undefined') {
      node.width = Math.min(node.width, node.maxWidth);
    }
    if (typeof node.maxHeight !== 'undefined') {
      node.height = Math.min(node.height, node.maxHeight);
    }
    if (typeof node.minWidth !== 'undefined') {
      node.width = Math.max(node.width, node.minWidth);
    }
    if (typeof node.minHeight !== 'undefined') {
      node.height = Math.max(node.height, node.minHeight);
    }

    node._id = ++idSeq;
    node._dirty = true;

    if (node.autoPosition) {
      this._sortNodes();

      for (var i = 0; ; ++i) {
        var x = i % this.width;
        var y = Math.floor(i / this.width);
        if (x + node.width > this.width) {
          continue;
        }
        if (!_.find(this.nodes, _.bind(Utils._isAddNodeIntercepted, {
          x: x,
          y: y,
          node: node
        }))) {
          node.x = x;
          node.y = y;
          break;
        }
      }
    }

    this.nodes.push(node);
    if (typeof triggerAddEvent !== 'undefined' && triggerAddEvent) {
      this._addedNodes.push(_.clone(node));
    }

    this._fixCollisions(node);
    this._packNodes();
    this._notify();
    return node;
  };

  GridStackEngine.prototype.moveNode = function (node, x, y, width, height, noPack) {
    if (typeof x !== 'number') {
      x = node.x;
    }
    if (typeof y !== 'number') {
      y = node.y;
    }
    if (typeof width !== 'number') {
      width = node.width;
    }
    if (typeof height !== 'number') {
      height = node.height;
    }

    if (typeof node.maxWidth !== 'undefined') {
      width = Math.min(width, node.maxWidth);
    }
    if (typeof node.maxHeight !== 'undefined') {
      height = Math.min(height, node.maxHeight);
    }
    if (typeof node.minWidth !== 'undefined') {
      width = Math.max(width, node.minWidth);
    }
    if (typeof node.minHeight !== 'undefined') {
      height = Math.max(height, node.minHeight);
    }

    if (node.x === x && node.y === y && node.width === width && node.height === height) {
      return node;
    }

    var resizing = node.width !== width;
    node._dirty = true;

    node.x = x;
    node.y = y;
    node.width = width;
    node.height = height;

    node = this._prepareNode(node, resizing);

    this._fixCollisions(node);
    if (!noPack) {
      this._packNodes();
      this._notify();
    }
    return node;
  };

  GridStackEngine.prototype.getGridHeight = function () {
    return _.reduce(this.nodes, function (memo, n) {
      return Math.max(memo, n.y + n.height);
    }, 0);
  };

  GridStackEngine.prototype.beginUpdate = function (node) {
    _.each(this.nodes, function (n) {
      n._origY = n.y;
    });
    node._updating = true;
  };

  GridStackEngine.prototype.endUpdate = function () {
    _.each(this.nodes, function (n) {
      n._origY = n.y;
    });
    var n = _.find(this.nodes, function (n) {
      return n._updating;
    });
    if (n) {
      n._updating = false;
    }
  };

  var GridStack = function (el, opts) {
    var self = this;
    var oneColumnMode;
    var isAutoCellHeight;

    opts = opts || {};

    this.container = $(el);

    opts.itemClass = opts.itemClass || 'grid-stack-item';
    var isNested = this.container.closest('.' + opts.itemClass).size() > 0;
    var optsHandle = opts.handle ? opts.handle : '';

    this.opts = _.defaults(opts || {}, {
      width: parseInt(this.container.attr('data-gs-width')) || 12,
      height: parseInt(this.container.attr('data-gs-height')) || 0,
      itemClass: 'grid-stack-item',
      placeholderClass: 'grid-stack-placeholder',
      placeholderText: '',
      handle: '.grid-stack-item-content',
      handleClass: null,
      cellHeight: 60,
      verticalMargin: 20,
      auto: true,
      minWidth: 768,
      float: false,
      staticGrid: false,
      _class: 'grid-stack-instance-' + (Math.random() * 10000).toFixed(0),
      animate: Boolean(this.container.attr('data-gs-animate')) || false,
      alwaysShowResizeHandle: opts.alwaysShowResizeHandle || false,
      resizable: _.defaults(opts.resizable || {}, {
        autoHide: !(opts.alwaysShowResizeHandle || false),
        handles: 'se'
      }),
      draggable: _.defaults(opts.draggable || {}, {
        handle: (opts.handleClass ? '.' + opts.handleClass : optsHandle) ||
          '.grid-stack-item-content',
        scroll: false,
        appendTo: 'body'
      }),
      disableDrag: opts.disableDrag || false,
      disableResize: opts.disableResize || false,
      rtl: 'auto',
      removable: false,
      removeTimeout: 2000
    });

    if (this.opts.rtl === 'auto') {
      this.opts.rtl = this.container.css('direction') === 'rtl';
    }

    if (this.opts.rtl) {
      this.container.addClass('grid-stack-rtl');
    }

    this.opts.isNested = isNested;

    isAutoCellHeight = this.opts.cellHeight === 'auto';
    if (isAutoCellHeight) {
      self.cellHeight(self.cellWidth(), true);
    }
    else {
      this.cellHeight(this.opts.cellHeight, true);
    }
    this.verticalMargin(this.opts.verticalMargin, true);

    this.container.addClass(this.opts._class);

    this._setStaticClass();

    if (isNested) {
      this.container.addClass('grid-stack-nested');
    }

    this._initStyles();

    this.grid = new GridStackEngine(this.opts.width, function (nodes) {
      var maxHeight = 0;
      _.each(nodes, function (n) {
        if (n._id === null) {
          n.el.remove();
        }
        else {
          n.el
            .attr('data-gs-x', n.x)
            .attr('data-gs-y', n.y)
            .attr('data-gs-width', n.width)
            .attr('data-gs-height', n.height);
          maxHeight = Math.max(maxHeight, n.y + n.height);
        }
      });
      self._updateStyles(maxHeight + 10);
    }, this.opts.float, this.opts.height);

    if (this.opts.auto) {
      var elements = [];
      var me = this;
      this.container.children('.' + this.opts.itemClass + ':not(.' + this.opts.placeholderClass + ')')
        .each(function (index, el) {
          el = $(el);
          elements.push({
            el: el,
            i: parseInt(el.attr('data-gs-x')) + parseInt(el.attr('data-gs-y')) * me.opts.width
          });
        });
      _.chain(elements).sortBy(function (x) {
        return x.i;
      }).each(function (i) {
        self._prepareElement(i.el);
      }).value();
    }

    // @modified this.setAnimation(this.opts.animate);

    this.placeholder = $(
      '<div class="' + this.opts.placeholderClass + ' ' + this.opts.itemClass + '">' +
      '<div class="placeholder-content">' + this.opts.placeholderText + '</div></div>').hide();

    this._updateContainerHeight();

    this._updateHeightsOnResize = _.throttle(function () {
      self.cellHeight(self.cellWidth(), false);
    }, 100);

    this.onResizeHandler = function () {
      if (isAutoCellHeight) {
        self._updateHeightsOnResize();
      }

      if (self._isOneColumnMode()) {
        if (oneColumnMode) {
          return;
        }

        oneColumnMode = true;

        self.grid._sortNodes();
        _.each(self.grid.nodes, function (node) {
          self.container.append(node.el);
          // @modified
          if (self.opts.staticGrid) {
            return;
          }
        });
      }
      else {
        if (!oneColumnMode) {
          return;
        }

        oneColumnMode = false;

        if (self.opts.staticGrid) {
          return;
        }
      }
    };

    $(window).resize(this.onResizeHandler);
    this.onResizeHandler();
  };

  GridStack.prototype._triggerChangeEvent = function (forceTrigger) {
    var elements = this.grid.getDirtyNodes();
    var hasChanges = false;

    var eventParams = [];
    if (elements && elements.length) {
      eventParams.push(elements);
      hasChanges = true;
    }

    if (hasChanges || forceTrigger === true) {
      this.container.trigger('change', eventParams);
    }
  };

  GridStack.prototype._initStyles = function () {
    if (this._stylesId) {
      Utils.removeStylesheet(this._stylesId);
    }
    this._stylesId = 'gridstack-style-' + (Math.random() * 100000).toFixed();
    this._styles = Utils.createStylesheet(this._stylesId);
    if (this._styles !== null) {
      this._styles._max = 0;
    }
  };

  GridStack.prototype._updateStyles = function (maxHeight) {
    if (this._styles === null || typeof this._styles === 'undefined') {
      return;
    }

    var prefix = '.' + this.opts._class + ' .' + this.opts.itemClass;
    var self = this;
    var getHeight;

    if (typeof maxHeight === 'undefined') {
      maxHeight = this._styles._max;
      this._initStyles();
      this._updateContainerHeight();
    }
    if (!this.opts.cellHeight) { // The rest will be handled by CSS
      return;
    }
    if (this._styles._max !== 0 && maxHeight <= this._styles._max) {
      return;
    }

    if (!this.opts.verticalMargin || this.opts.cellHeightUnit === this.opts.verticalMarginUnit) {
      getHeight = function (nbRows, nbMargins) {
        return (self.opts.cellHeight * nbRows + self.opts.verticalMargin * nbMargins) +
          self.opts.cellHeightUnit;
      };
    }
    else {
      getHeight = function (nbRows, nbMargins) {
        if (!nbRows || !nbMargins) {
          return (self.opts.cellHeight * nbRows + self.opts.verticalMargin * nbMargins) +
            self.opts.cellHeightUnit;
        }
        return 'calc(' + ((self.opts.cellHeight * nbRows) + self.opts.cellHeightUnit) + ' + ' +
          ((self.opts.verticalMargin * nbMargins) + self.opts.verticalMarginUnit) + ')';
      };
    }

    if (this._styles._max === 0) {
      Utils.insertCSSRule(this._styles, prefix, 'min-height: ' + getHeight(1, 0) + ';', 0);
    }

    if (maxHeight > this._styles._max) {
      for (var i = this._styles._max; i < maxHeight; ++i) {
        Utils.insertCSSRule(this._styles,
          prefix + '[data-gs-height="' + (i + 1) + '"]',
          'height: ' + getHeight(i + 1, i) + ';',
          i
        );
        Utils.insertCSSRule(this._styles,
          prefix + '[data-gs-min-height="' + (i + 1) + '"]',
          'min-height: ' + getHeight(i + 1, i) + ';',
          i
        );
        Utils.insertCSSRule(this._styles,
          prefix + '[data-gs-max-height="' + (i + 1) + '"]',
          'max-height: ' + getHeight(i + 1, i) + ';',
          i
        );
        Utils.insertCSSRule(this._styles,
          prefix + '[data-gs-y="' + i + '"]',
          'top: ' + getHeight(i, i) + ';',
          i
        );
      }
      this._styles._max = maxHeight;
    }
  };

  GridStack.prototype._updateContainerHeight = function () {
    if (this.grid._updateCounter) {
      return;
    }
    var height = this.grid.getGridHeight();
    this.container.attr('data-gs-current-height', height);
    if (!this.opts.cellHeight) {
      return;
    }
    if (!this.opts.verticalMargin) {
      this.container.css('height', (height * (this.opts.cellHeight)) + this.opts.cellHeightUnit);
    }
    else if (this.opts.cellHeightUnit === this.opts.verticalMarginUnit) {
      this.container.css('height', (height * (this.opts.cellHeight + this.opts.verticalMargin) -
        this.opts.verticalMargin) + this.opts.cellHeightUnit);
    }
    else {
      this.container.css('height', 'calc(' + ((height * (this.opts.cellHeight)) + this.opts.cellHeightUnit) +
        ' + ' + ((height * (this.opts.verticalMargin - 1)) + this.opts.verticalMarginUnit) + ')');
    }
  };

  GridStack.prototype._isOneColumnMode = function () {
    return (window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth) <=
      this.opts.minWidth;
  };

  GridStack.prototype._setupRemovingTimeout = function (el) {
    var self = this;
    var node = $(el).data('_gridstack_node');

    if (node._removeTimeout || !self.opts.removable) {
      return;
    }
    node._removeTimeout = setTimeout(function () {
      el.addClass('grid-stack-item-removing');
      node._isAboutToRemove = true;
    }, self.opts.removeTimeout);
  };

  GridStack.prototype._clearRemovingTimeout = function (el) {
    var node = $(el).data('_gridstack_node');

    if (!node._removeTimeout) {
      return;
    }
    clearTimeout(node._removeTimeout);
    node._removeTimeout = null;
    el.removeClass('grid-stack-item-removing');
    node._isAboutToRemove = false;
  };

  GridStack.prototype._prepareElement = function (el, triggerAddEvent) {
    triggerAddEvent = typeof triggerAddEvent !== 'undefined' ? triggerAddEvent : false;
    var self = this;
    el = $(el);

    el.addClass(this.opts.itemClass);
    var node = self.grid.addNode({
      x: el.attr('data-gs-x'),
      y: el.attr('data-gs-y'),
      width: el.attr('data-gs-width'),
      height: el.attr('data-gs-height'),
      maxWidth: el.attr('data-gs-max-width'),
      minWidth: el.attr('data-gs-min-width'),
      maxHeight: el.attr('data-gs-max-height'),
      minHeight: el.attr('data-gs-min-height'),
      autoPosition: Utils.toBool(el.attr('data-gs-auto-position')),
      noResize: Utils.toBool(el.attr('data-gs-no-resize')),
      noMove: Utils.toBool(el.attr('data-gs-no-move')),
      locked: Utils.toBool(el.attr('data-gs-locked')),
      el: el,
      id: el.attr('data-gs-id'),
      _grid: self
    }, triggerAddEvent);
    el.data('_gridstack_node', node);
    // @modified
    el.attr('data-gs-locked', node.locked ? 'yes' : null);
  };

  GridStack.prototype.removeAll = function (detachNode) {
    _.each(this.grid.nodes, _.bind(function (node) {
      this.removeWidget(node.el, detachNode);
    }, this));
    this.grid.nodes = [];
    this._updateContainerHeight();
  };

  GridStack.prototype.destroy = function (detachGrid) {
    $(window).off('resize', this.onResizeHandler);
    this.disable();
    if (typeof detachGrid !== 'undefined' && !detachGrid) {
      this.removeAll(false);
    }
    else {
      this.container.remove();
    }
    Utils.removeStylesheet(this._stylesId);
    if (this.grid) {
      this.grid = null;
    }
  };

  GridStack.prototype.locked = function (el, val) {
    el = $(el);
    el.each(function (index, el) {
      el = $(el);
      var node = el.data('_gridstack_node');
      if (typeof node === 'undefined' || node === null) {
        return;
      }

      node.locked = (val || false);
      el.attr('data-gs-locked', node.locked ? 'yes' : null);
    });
    return this;
  };

  GridStack.prototype.maxHeight = function (el, val) {
    el = $(el);
    el.each(function (index, el) {
      el = $(el);
      var node = el.data('_gridstack_node');
      if (typeof node === 'undefined' || node === null) {
        return;
      }

      if (!isNaN(val)) {
        node.maxHeight = (val || false);
        el.attr('data-gs-max-height', val);
      }
    });
    return this;
  };

  GridStack.prototype.minHeight = function (el, val) {
    el = $(el);
    el.each(function (index, el) {
      el = $(el);
      var node = el.data('_gridstack_node');
      if (typeof node === 'undefined' || node === null) {
        return;
      }

      if (!isNaN(val)) {
        node.minHeight = (val || false);
        el.attr('data-gs-min-height', val);
      }
    });
    return this;
  };

  GridStack.prototype.maxWidth = function (el, val) {
    el = $(el);
    el.each(function (index, el) {
      el = $(el);
      var node = el.data('_gridstack_node');
      if (typeof node === 'undefined' || node === null) {
        return;
      }

      if (!isNaN(val)) {
        node.maxWidth = (val || false);
        el.attr('data-gs-max-width', val);
      }
    });
    return this;
  };

  GridStack.prototype.minWidth = function (el, val) {
    el = $(el);
    el.each(function (index, el) {
      el = $(el);
      var node = el.data('_gridstack_node');
      if (typeof node === 'undefined' || node === null) {
        return;
      }

      if (!isNaN(val)) {
        node.minWidth = (val || false);
        el.attr('data-gs-min-width', val);
      }
    });
    return this;
  };

  GridStack.prototype._updateElement = function (el, callback) {
    el = $(el).first();
    var node = el.data('_gridstack_node');
    if (typeof node === 'undefined' || node === null) {
      return;
    }

    var self = this;

    self.grid.cleanNodes();
    self.grid.beginUpdate(node);

    callback.call(this, el, node);

    self._updateContainerHeight();
    self._triggerChangeEvent();

    self.grid.endUpdate();
  };

  GridStack.prototype.resize = function (el, width, height) {
    this._updateElement(el, function (el, node) {
      width = (width !== null && typeof width !== 'undefined') ? width : node.width;
      height = (height !== null && typeof height !== 'undefined') ? height : node.height;

      this.grid.moveNode(node, node.x, node.y, width, height);
    });
  };

  GridStack.prototype.move = function (el, x, y) {
    this._updateElement(el, function (el, node) {
      x = (x !== null && typeof x !== 'undefined') ? x : node.x;
      y = (y !== null && typeof y !== 'undefined') ? y : node.y;

      this.grid.moveNode(node, x, y, node.width, node.height);
    });
  };

  GridStack.prototype.update = function (el, x, y, width, height) {
    this._updateElement(el, function (el, node) {
      x = (x !== null && typeof x !== 'undefined') ? x : node.x;
      y = (y !== null && typeof y !== 'undefined') ? y : node.y;
      width = (width !== null && typeof width !== 'undefined') ? width : node.width;
      height = (height !== null && typeof height !== 'undefined') ? height : node.height;

      this.grid.moveNode(node, x, y, width, height);
    });
  };

  GridStack.prototype.verticalMargin = function (val, noUpdate) {
    if (typeof val === 'undefined') {
      return this.opts.verticalMargin;
    }

    var heightData = Utils.parseHeight(val);

    if (this.opts.verticalMarginUnit === heightData.unit && this.opts.height === heightData.height) {
      return;
    }
    this.opts.verticalMarginUnit = heightData.unit;
    this.opts.verticalMargin = heightData.height;

    if (!noUpdate) {
      this._updateStyles();
    }
  };

  GridStack.prototype.cellHeight = function (val, noUpdate) {
    if (typeof val === 'undefined') {
      if (this.opts.cellHeight) {
        return this.opts.cellHeight;
      }
      var o = this.container.children('.' + this.opts.itemClass).first();
      return Math.ceil(o.outerHeight() / o.attr('data-gs-height'));
    }
    var heightData = Utils.parseHeight(val);

    if (this.opts.cellHeightUnit === heightData.heightUnit && this.opts.height === heightData.height) {
      return;
    }
    this.opts.cellHeightUnit = heightData.unit;
    this.opts.cellHeight = heightData.height;

    if (!noUpdate) {
      this._updateStyles();
    }
  };

  GridStack.prototype.cellWidth = function () {
    var o = this.container.children('.' + this.opts.itemClass).first();
    return Math.ceil(o.outerWidth() / parseInt(o.attr('data-gs-width'), 10));
  };

  GridStack.prototype.getCellFromPixel = function (position, useOffset) {
    var containerPos = (typeof useOffset !== 'undefined' && useOffset) ?
      this.container.offset() : this.container.position();
    var relativeLeft = position.left - containerPos.left;
    var relativeTop = position.top - containerPos.top;

    var columnWidth = Math.floor(this.container.width() / this.opts.width);
    var rowHeight = Math.floor(this.container.height() / parseInt(this.container.attr('data-gs-current-height')));

    return {
      x: Math.floor(relativeLeft / columnWidth),
      y: Math.floor(relativeTop / rowHeight)
    };
  };

  GridStack.prototype.batchUpdate = function () {
    this.grid.batchUpdate();
  };

  GridStack.prototype.commit = function () {
    this.grid.commit();
    this._updateContainerHeight();
  };

  GridStack.prototype.isAreaEmpty = function (x, y, width, height) {
    return this.grid.isAreaEmpty(x, y, width, height);
  };

  GridStack.prototype.setStatic = function (staticValue) {
    this.opts.staticGrid = (staticValue === true);
    // @modified this.enableMove(!staticValue);
    // @modified this.enableResize(!staticValue);
    this._setStaticClass();
  };

  GridStack.prototype._setStaticClass = function () {
    var staticClassName = 'grid-stack-static';

    if (this.opts.staticGrid === true) {
      this.container.addClass(staticClassName);
    }
    else {
      this.container.removeClass(staticClassName);
    }
  };

  GridStack.prototype._updateNodeWidths = function (oldWidth, newWidth) {
    this.grid._sortNodes();
    this.grid.batchUpdate();
    var node = {};
    for (var i = 0; i < this.grid.nodes.length; i++) {
      node = this.grid.nodes[i];
      // (el, x, y, width, height)
      this.update(node.el, Math.round(node.x * newWidth / oldWidth), null,
        Math.round(node.width * newWidth / oldWidth), null);
    }
    this.grid.commit();
  };

  GridStack.prototype.setGridWidth = function (gridWidth) {
    this.container.removeClass('grid-stack-' + this.opts.width);
    this._updateNodeWidths(this.opts.width, gridWidth);
    this.opts.width = gridWidth;
    this.container.addClass('grid-stack-' + gridWidth);
  };

  scope.GridStackUI = GridStack;

  scope.GridStackUI.Utils = Utils;
  scope.GridStackUI.Engine = GridStackEngine;

  $.fn.gridstack = function (opts) {
    return this.each(function () {
      var o = $(this);
      if (!o.data('gridstack')) {
        o.data('gridstack', new GridStack(this, opts));
      }
    });
  };

  return scope.GridStackUI;
}));
