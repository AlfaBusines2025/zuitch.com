/**
 * Float a number of things up on a page (hearts, flowers, 👌 ...)
 * @module floating
 * @author Haroen Viaene <hello@haroen.me>
 */
(function(root, factory) {
  'use strict';
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([], function() {
      return (root.floating = factory());
    });
  } else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like environments that support module.exports,
    // like Node.
    module.exports = factory();
  } else {
    // Browser globals
    root.floating = factory();
  }
})(this, function() {
  // UMD Definition above, do not remove this line
  // To get to know more about the Universal Module Definition
  // visit: https://github.com/umdjs/umd
  'use strict';
  /**
   * Float a number of things up on a page (hearts, flowers, 👌 ...)
   * @alias module:floating
   * @param {string} [options.content='👌'] the character or string to float
   * @param {number} [options.number=1] the number of items
   * @param {number} [options.duration=10] the amount of seconds it takes to float up (default 10s)
   * @param {number|string} [options.repeat='infinite'] the number of times you want the animation to repeat (default: 'infinite')
   * @param {string} [options.direction='normal'] The animation-direction of the main animation (https://developer.mozilla.org/en-US/docs/Web/CSS/animation-direction)
   */
  function floating(options) {
    options.content = options.content || '👌';
    options.number = options.number || 1;
    options.duration = options.duration || 10;
    options.repeat = options.repeat || 'infinite';
    options.direction = options.direction || 'normal';
	options.pointerX = options.pointerX+'px' || Math.random()*100+'vw';
	options.pointerY = '-100%';//options.pointerY+'px' || '-100%';
	options.appendTo = options.appendTo || 'body';
    var style = document.createElement('style');
    style.id = 'floating-style';
    if (!document.getElementById('floating-style')) {
      document.head.appendChild(style);
    };
    document.getElementById('floating-style').innerHTML = ".float-container {z-index:1070;width: 100vw;height: 100vh;overflow: hidden;position: absolute;top: 0;left: 0;pointer-events: none;}@keyframes float {0% {bottom: -100%;opacity: 0;visibility: hidden;}10% {opacity: 1;bottom: 0;visibility: visible;}100% {bottom: 100%;}}@keyframes move {0%,100% {left: 0em;}50% {left: 2em;}}";
    var container = document.createElement('div');
    container.className = 'float-container';
    for (var i = 0; i < options.number; i++) {
      var floater = document.createElement('div');
      floater.innerHTML = options.content;
      floater.style.cssText = "position: absolute; font-size: 2em; right: 0; bottom: "+options.pointerY+"; animation: float "+options.duration+"s ease-in "+options.repeat+" "+options.direction+", move  3s ease-in-out infinite; transform: translateX("+options.pointerX+"); animation-delay: 0s;";
      floater.addEventListener('animationend', function(e){
        if (e.animationName === 'float') {
          container.removeChild(floater);
        }
      });
      container.appendChild(floater);
    }
	if(options.appendTo != 'body')
		document.getElementById(options.appendTo).appendChild(container);
	else
		document.body.appendChild(container);
  }
  return floating;
});