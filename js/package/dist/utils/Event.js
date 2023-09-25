export var Events;
(function (Events) {
    Events.on = function (element, event, callback) {
        element.addEventListener(event, callback);
        return {
            destroy: function () { return element && element.removeEventListener(event, callback); },
        };
    };
})(Events || (Events = {}));
//# sourceMappingURL=Event.js.map