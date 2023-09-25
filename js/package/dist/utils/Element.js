import { Numbers } from './Numbers';
export var Element;
(function (Element) {
    var isElement = function (element) { return element instanceof HTMLElement; };
    Element.setStyles = function (element, styles) {
        Object.keys(styles).map(function (key) {
            element.style[key] = styles[key];
        });
    };
    Element.getBoxStyles = function (element) {
        var computedValue = window.getComputedStyle(element);
        return {
            height: Numbers.parseOrElse(computedValue.height),
            padding: {
                top: Numbers.parseOrElse(computedValue.paddingTop),
                bottom: Numbers.parseOrElse(computedValue.paddingBottom),
            },
            border: {
                top: Numbers.parseOrElse(computedValue.borderTopWidth),
                bottom: Numbers.parseOrElse(computedValue.borderBottomWidth),
            },
        };
    };
    Element.getElement = function (element) {
        if (isElement(element)) {
            return element;
        }
        var elementFromSelector = document.querySelector(element);
        if (isElement(elementFromSelector)) {
            return elementFromSelector;
        }
        throw new Error('Your element does not exist in the DOM.');
    };
    Element.setAttribute = function (element, attribute, value) {
        element.setAttribute(attribute, value);
    };
    Element.getAttribute = function (element, attribute) {
        return element.getAttribute(attribute);
    };
})(Element || (Element = {}));
//# sourceMappingURL=Element.js.map