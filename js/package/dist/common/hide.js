import { Animate, Element } from '../utils';
var Hide;
(function (Hide) {
    Hide.on = function (element, options) {
        Animate.hide(element, options);
    };
})(Hide || (Hide = {}));
export var hide = function (element, options) {
    Hide.on(Element.getElement(element), options);
};
//# sourceMappingURL=hide.js.map