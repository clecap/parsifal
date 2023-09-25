var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import { Animate, Element } from '../utils';
var Toggle;
(function (Toggle) {
    var onHideEnd = function (options) {
        return function () {
            var _a, _b;
            (_a = options.onClose) === null || _a === void 0 ? void 0 : _a.call(options);
            (_b = options.onAnimationEnd) === null || _b === void 0 ? void 0 : _b.call(options);
        };
    };
    var onShowEnd = function (options) {
        return function () {
            var _a, _b;
            (_a = options.onOpen) === null || _a === void 0 ? void 0 : _a.call(options);
            (_b = options.onAnimationEnd) === null || _b === void 0 ? void 0 : _b.call(options);
        };
    };
    Toggle.on = function (element, options) {
        if (Animate.shouldCollapse(element)) {
            Animate.hide(element, __assign(__assign({}, options), { onAnimationEnd: onHideEnd(options) }));
        }
        else {
            Animate.show(element, __assign(__assign({}, options), { onAnimationEnd: onShowEnd(options) }));
        }
    };
})(Toggle || (Toggle = {}));
export var toggle = function (element, options) {
    Toggle.on(Element.getElement(element), options);
};
//# sourceMappingURL=toggle.js.map