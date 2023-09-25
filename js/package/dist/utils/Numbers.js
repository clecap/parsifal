export var Numbers;
(function (Numbers) {
    Numbers.parseOrElse = function (str, or) {
        if (or === void 0) { or = '0'; }
        if (str) {
            return parseInt(str);
        }
        return or && typeof or === 'string' ? parseInt(or) : 0;
    };
})(Numbers || (Numbers = {}));
//# sourceMappingURL=Numbers.js.map