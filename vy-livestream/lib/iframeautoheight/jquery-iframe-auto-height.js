(function( jQuery ) {
  var matched,
    userAgent = navigator.userAgent || "";

  // Use of jQuery.browser is frowned upon.
  // More details: http://api.jquery.com/jQuery.browser
  // jQuery.uaMatch maintained for back-compat
  jQuery.uaMatch = function( ua ) {
    ua = ua.toLowerCase();

    var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
      /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
      /(opera)(?:.*version)?[ \/]([\w.]+)/.exec( ua ) ||
      /(msie) ([\w.]+)/.exec( ua ) ||
      ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+))?/.exec( ua ) ||
      [];

    return {
      browser: match[ 1 ] || "",
      version: match[ 2 ] || "0"
    };
  };

  matched = jQuery.uaMatch( userAgent );

  jQuery.browser = {};

  if ( matched.browser ) {
    jQuery.browser[ matched.browser ] = true;
    jQuery.browser.version = matched.version;
  }

  // Deprecated, use jQuery.browser.webkit instead
  // Maintained for back-compat only
  if ( jQuery.browser.webkit ) {
    jQuery.browser.safari = true;
  }

}( jQuery ));

(function ($) {
  $.fn.iframeAutoHeight = function (spec) {

    var undef;
    if ($.browser === undef) {
      var message = [];
      message.push("WARNING: you appear to be using a newer version of jquery which does not support the $.browser variable.");
      message.push("The jQuery iframe auto height plugin relies heavly on the $.browser features.");
      message.push("Install jquery-browser: https://raw.github.com/house9/jquery-iframe-auto-height/master/release/jquery.browser.js");
      alert(message.join("\n"));
      return $;
    }

    // set default option values
    var options = $.extend({
        heightOffset: 0,
        minHeight: 0,
        maxHeight: 0,
        callback: function () {},
        animate: false,
        debug: false,
        diagnostics: false, // used for development only
        resetToMinHeight: false,
        triggerFunctions: [],
        heightCalculationOverrides: []
      }, spec);

    // logging
    function debug(message) {
      if (options.debug && options.debug === true && window.console) {
        console.log(message);
      }
    }

    // not used by production code
    function showDiagnostics(iframe, calledFrom) {
      debug("Diagnostics from '" + calledFrom + "'");
      try {
        debug("  " + $(iframe, window.parent).contents().find('body')[0].scrollHeight + " for ...find('body')[0].scrollHeight");
        debug("  " + $(iframe.contentWindow.document).height() + " for ...contentWindow.document).height()");
        debug("  " + $(iframe.contentWindow.document.body).height() + " for ...contentWindow.document.body).height()");
      } catch (ex) {
        // ie fails when called during for each, ok later on
        // probably not an issue if called in a document ready block
        debug("  unable to check in this state");
      }
      debug("End diagnostics -> results vary by browser and when diagnostics are requested");
    }

    // show all option values
    debug(options);

    // ******************************************************
    // iterate over the matched elements passed to the plugin ; return will make it chainable
    return this.each(function () {

      // ******************************************************
      // http://api.jquery.com/jQuery.browser/
      var strategyKeys = ['webkit', 'mozilla', 'msie', 'opera', 'chrome'];
      var strategies = {};
      strategies['default'] = function (iframe, $iframeBody, options) {
        // NOTE: this is how the plugin determines the iframe height, override if you need custom
        return $iframeBody[0].scrollHeight + options.heightOffset;
      };

      jQuery.each(strategyKeys, function (index, value) {
        // use the default strategy for all browsers, can be overridden if desired
        strategies[value] = strategies['default'];
      });

      // override strategies if registered in options
      jQuery.each(options.heightCalculationOverrides, function (index, value) {
        strategies[value.browser] = value.calculation;
      });

      function findStrategy(browser) {
        var strategy = null;

        jQuery.each(strategyKeys, function (index, value) {
          if (browser[value]) {
            strategy = strategies[value];
            return false;
          }
        });

        if (strategy === null) {
          strategy = strategies['default'];
        }

        return strategy;
      }
      // ******************************************************

      // for use by webkit only
      var loadCounter = 0;

      var iframeDoc = this.contentDocument || this.contentWindow.document;

      // resizeHeight
      function resizeHeight(iframe) {
        if (options.diagnostics) {
          showDiagnostics(iframe, "resizeHeight");
        }

        // set the iframe size to minHeight so it'll get smaller on resizes in FF and IE
        if (options.resetToMinHeight && options.resetToMinHeight === true) {
          iframe.style.height = options.minHeight + 'px';
        }

        // get the iframe body height and set inline style to that plus a little
        var $body = $(iframe, window.parent).contents().find('body');
        var strategy = findStrategy($.browser);
        var newHeight = strategy(iframe, $body, options, $.browser);
        debug(newHeight);

        if (newHeight < options.minHeight) {
          debug("new height is less than minHeight");
          newHeight = options.minHeight;
        }

        if (options.maxHeight > 0 && newHeight > options.maxHeight) {
          debug("new height is greater than maxHeight");
          newHeight = options.maxHeight;
        }

        newHeight += options.heightOffset;

        debug("New Height: " + newHeight);
        if (options.animate) {
          $(iframe).animate({height: newHeight + 'px'}, {duration: 500});
        } else {
          iframe.style.height = newHeight + 'px';
        }

        options.callback.apply($(iframe), [{newFrameHeight: newHeight}]);
      } // END resizeHeight

      // debug me
      debug(this);
      if (options.diagnostics) {
        showDiagnostics(this, "each iframe");
      }

      // if trigger functions are registered, invoke them
      if (options.triggerFunctions.length > 0) {
        debug(options.triggerFunctions.length + " trigger Functions");
        for (var i = 0; i < options.triggerFunctions.length; i++) {
          options.triggerFunctions[i](resizeHeight, this);
        }
      }

      // Check if browser is Webkit (Safari/Chrome) or Opera
      if ($.browser.webkit || $.browser.opera || $.browser.chrome) {
        debug("browser is webkit (Safari/Chrome) or opera");

        // Start timer when loaded.
        $(this).load(function () {
          var delay = 0;
          var iframe = this;

          var delayedResize = function () {
            resizeHeight(iframe);
          };

          if (loadCounter === 0) {
            // delay the first one
            delay = 500;
          } else {
            // Reset iframe height to 0 to force new frame size to fit window properly
            // this is only an issue when going from large to small iframe, not executed on page load
            iframe.style.height = options.minHeight + 'px';
          }

          debug("load delay: " + delay);
          setTimeout(delayedResize, delay);
          loadCounter++;
        });

        // Safari and Opera need a kick-start.
        var source = $(this).attr('src');
        $(this).attr('src', '');
        $(this).attr('src', source);
      } else {
        // For other browsers.
        if(iframeDoc.readyState  === 'complete') {
          resizeHeight(this);
        } else {
          $(this).load(function () {
            resizeHeight(this);
          });
        }
      } // if browser

    }); // $(this).each(function () {
  }; // $.fn.iframeAutoHeight = function (options) {
}(jQuery)); // (function ($) {