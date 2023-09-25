export var JestPromise;
(function (JestPromise) {
    JestPromise.delay = function (miliseconds) {
        return new Promise(function (res) {
            setTimeout(function () { return res({}); }, miliseconds);
        });
    };
})(JestPromise || (JestPromise = {}));
//# sourceMappingURL=JestPromise.js.map