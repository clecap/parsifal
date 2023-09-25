import { Animate, Element } from '../utils';
var Show;
(function (Show) {
    Show.on = function (element, options) {
        Animate.show(element, options);
    };
})(Show || (Show = {}));
export var show = function (element, options) {
    Show.on(Element.getElement(element), options);
};
//# sourceMappingURL=show.js.map