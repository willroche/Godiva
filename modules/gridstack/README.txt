
ABOUT
Provides integration with gridstack.js as widget layout to have multi-column
grids with drag-and-drop. Inspired by gridster.js. Built with love.

The module provides a Views style plugin to return results as a GridStack.


FEATURES
o Responsive multi-breakpoint grid displays.
o Drag and drop layout builder.
o Multi-breakpoint layout composition.
o Multi-breakpoint image styles, or multiple unique image styles per grid/box.
o Lazyloaded inline images, or CSS background images with multi-styled images.
o Easy captioning.
o A few simple box layouts.

INSTALLATION
Install the module as usual, more info can be found on:
http://drupal.org/documentation/install/modules-themes/modules-7


USAGE / CONFIGURATION
- Visit admin/structure/gridstack to build a GridStack.
- Visit admin/structure/views, and create a new page or block with GridStack
  style, and assign the designated optionset.
- Use the provided sample to begin with, be sure to read its README.txt.


REQUIREMENTS
- Blazy module.
- Views module (in core).
- Underscore (in core).
- GridStack library:
  o Download GridStack (0.2.5-dev+) from https://github.com/troolee/gridstack.js
  o Extract it as is, rename "gridstack.js-master" to "gridstack", so the assets
    are available at:

    /libraries/gridstack/dist/gridstack.min.js


SIMILAR MODULES
http://dgo.to/mason
Both try to solve one problem: empty gaps within a compact grid layout.
Mason uses auto Fillers, or manual Promoted, options to fill in empty gaps.
GridStack uses manual drag and drop layout builder to fill in empty gaps.


ROADMAP
[x] Support multi-styled images to have various sizes, only if doable. Currently
    using CSS background images to solve sizes.
    2/29/16
[x] Multi-breakpoint responsive grid layouts.
    3/11/16
[x] Multi-breakpoint image styles.
    3/14/16
o Supports lightbox boxes.

The following may likely happen by some personal project, or sponsorship.
o Field formatter and widget.
o Entity/File browser integration.
o Multimedia boxes.
o Stamps.
o D7 port.

Feel free to get in touch if you'd like to chip in or sponsor any. Thanks.


TROUBLESHOOTING
o Be sure the amount of Views results are matching the amount of the grid boxes.
o Be sure to follow the natural order keyed by index if trouble with multiple
  breakpoint image styles.
o At admin UI, some grid/box may be accidentally hidden at smaller width. If
  that happens, try giving column and width to large value first to bring
  them back into the viewport. And when they are composed, re-adjust them.
  Or hit Clear, Load Grid, Save & Continue buttons to do the reset.
  At any rate saving the form as is with the mess can have them in place till
  further refinement.
o Use the resizable handlers to snap to grid if box dragging doesn't snap.
  Be gentle with it to avoid abrupt mess.
o If trouble at frontend till gridstack library is decoupled from jQuery UI or
  at least till jQuery related issues resolved, for its static grid, check the
  option "Load jQuery UI". If you are a JS guy, you know where the problem is.
  A temporary hacky solution is also available:
  /admin/structure/gridstack/ui
o Be sure to clear cache when updating cached Gridstack otherwise no changes
  are visible immediately. You can also disable the Gridstack cache during work.


TIPS
To have re-usable different image styles at multiple breakpoints, create them
once based on grid dimensions to easily match them based on the given dimension
hintings, e.g.:
o Box 1x1
o Box 1x2
o Box 2x1
o etc.

Be sure to enable GridStack UI and visit "/admin/structure/gridstack" and edit
your working optionset to assign different image styles for each box of the
GridStack. Then you can match those image styles with the provided dimension
hints easily -- 2x2 for Box 2x2, etc. Try giving fair sizes so that they fit
well and have no artifacts at multiple breakpoints.


CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedbacks from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Alpha and Beta releases are for developers only. Be aware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues durig DEV phases. Prior to any update, be sure to open:
/admin/config/development/performance


AUTHOR/MAINTAINER/CREDITS
gausarts


READ MORE
See the project page on drupal.org: http://drupal.org/project/gridstack

See the GridStack JS docs at:
o https://github.com/troolee/gridstack.js
o http://troolee.github.io/gridstack.js/
