var __rest = (this && this.__rest) || function (s, e) {
    var t = {};
    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
        t[p] = s[p];
    if (s != null && typeof Object.getOwnPropertySymbols === "function")
        for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
            if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
                t[p[i]] = s[p[i]];
        }
    return t;
};
import { Element } from './Element';
import { Events } from './Event';
export var Animate;
(function (Animate) {
    var slideToggleAttribute = 'data-slide-toggle';
    var onRequestAnimationFrame = function (callback) {
        requestAnimationFrame(callback);
    };
    var getTransition = function (options) {
        var _a = options.miliseconds, miliseconds = _a === void 0 ? 200 : _a, _b = options.transitionFunction, transitionFunction = _b === void 0 ? 'linear' : _b;
        return "all " + miliseconds + "ms " + transitionFunction + " 0s";
    };
    var isHidden = function (element) { return Element.getAttribute(element, slideToggleAttribute) === 'false'; };
    var isShown = function (element) { return Element.getAttribute(element, slideToggleAttribute) === 'true'; };
    Animate.shouldCollapse = function (element) {
        var attribute = Element.getAttribute(element, slideToggleAttribute);
        if (!attribute) {
            var height = Element.getBoxStyles(element).height;
            return height && height > 0;
        }
        return Element.getAttribute(element, slideToggleAttribute) === 'true';
    };
    Animate.hide = function (element, options) {
        var _a;
        if (isHidden(element)) {
            return;
        }
        (_a = options.onAnimationStart) === null || _a === void 0 ? void 0 : _a.call(options);
        var _b = Element.getBoxStyles(element), height = _b.height, boxStyles = __rest(_b, ["height"]);
        Element.setStyles(element, { transition: '' });
        onRequestAnimationFrame(function () {
            Element.setStyles(element, {
                overflow: 'hidden',
                height: height + "px",
                paddingTop: boxStyles.padding.top + "px",
                paddingBottom: boxStyles.padding.bottom + "px",
                borderTopWidth: boxStyles.border.top + "px",
                borderBottomWidth: boxStyles.border.bottom + "px",
                transition: getTransition(options),
            });
            onRequestAnimationFrame(function () {
                Element.setStyles(element, {
                    height: '0',
                    paddingTop: '0',
                    paddingBottom: '0',
                    borderTopWidth: '0',
                    borderBottomWidth: '0',
                });
                var event = Events.on(element, 'transitionend', function () {
                    var _a;
                    event.destroy();
                    (_a = options.onAnimationEnd) === null || _a === void 0 ? void 0 : _a.call(options);
                });
            });
        });
        Element.setAttribute(element, slideToggleAttribute, 'false');
    };
    Animate.show = function (element, options) {
        var _a;
        if (isShown(element)) {
            return;
        }
        var _b = options.elementDisplayStyle, elementDisplayStyle = _b === void 0 ? 'block' : _b;
        (_a = options.onAnimationStart) === null || _a === void 0 ? void 0 : _a.call(options);
        Element.setStyles(element, {
            transition: '',
            display: elementDisplayStyle,
            height: 'auto',
            paddingTop: '',
            paddingBottom: '',
            borderTopWidth: '',
            borderBottomWidth: '',
        });
        var _c = Element.getBoxStyles(element), height = _c.height, boxStyles = __rest(_c, ["height"]);
        Element.setStyles(element, {
            display: 'none',
        });
        onRequestAnimationFrame(function () {
            Element.setStyles(element, {
                display: elementDisplayStyle,
                overflow: 'hidden',
                height: '0',
                paddingTop: '0',
                paddingBottom: '0',
                borderTopWidth: '0',
                borderBottomWidth: '0',
                transition: getTransition(options),
            });
            onRequestAnimationFrame(function () {
                Element.setStyles(element, {
                    height: height + "px",
                    paddingTop: boxStyles.padding.top + "px",
                    paddingBottom: boxStyles.padding.bottom + "px",
                    borderTopWidth: boxStyles.border.top + "px",
                    borderBottomWidth: boxStyles.border.bottom + "px",
                });
                var event = Events.on(element, 'transitionend', function () {
                    var _a;
                    Element.setStyles(element, {
                        height: '',
                        overflow: '',
                        paddingTop: '',
                        paddingBottom: '',
                        borderTopWidth: '',
                        borderBottomWidth: '',
                    });
                    event.destroy();
                    (_a = options.onAnimationEnd) === null || _a === void 0 ? void 0 : _a.call(options);
                });
            });
        });
        Element.setAttribute(element, slideToggleAttribute, 'true');
    };
})(Animate || (Animate = {}));
//# sourceMappingURL=Animate.js.map