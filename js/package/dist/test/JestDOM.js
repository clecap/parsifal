export var JestDOM;
(function (JestDOM) {
    JestDOM.selectors = {
        button: 'button-with-action',
        element: 'element',
    };
    var get = function () {
        return "\n      <div>\n        <button role=\"" + JestDOM.selectors.button + "\">Button with action</button>\n        <div role=\"" + JestDOM.selectors.element + "\" style=\"height: 500px;\">\n          Lorem ipsum dolor sit amet, consectetur adipiscing elit. \n          Donec dictum ipsum non enim suscipit rutrum. \n          Praesent rutrum elit consequat ante imperdiet, ut gravida leo viverra. \n          Cras quis quam orci. Pellentesque eget quam ut quam consequat rhoncus vitae a mi.\n          Duis vulputate consequat ligula vel maximus. Donec a posuere nibh. \n          Sed euismod purus augue, sed mollis nunc molestie ac. Aenean in sem venenatis, molestie nulla sed, semper ante.\n          Integer ullamcorper non erat fringilla facilisis. Nullam id leo lacinia dolor pulvinar suscipit sed id sapien.\n  \n          Proin metus arcu, consectetur sit amet interdum sed, imperdiet vel velit. Maecenas eu tristique ipsum. \n          Morbi rhoncus, orci sed lacinia ornare, purus ex dapibus erat, sed sagittis urna leo vitae sapien. \n          Aenean nec enim eu urna feugiat sagittis. Donec et lorem in nisl cursus volutpat. \n          Pellentesque tincidunt mi nec arcu elementum, in consequat risus lobortis.\n          Donec est magna, consectetur eu eleifend quis, ornare gravida diam. Sed vel felis id justo tempus blandit.\n          Cras euismod fringilla finibus. Ut sit amet fringilla elit. Proin sit amet posuere tellus.\n          Proin at urna quam. Sed ac lorem et nunc mollis tincidunt.\n        </div>\n      </div>\n    ";
    };
    JestDOM.attach = function () {
        document.body.innerHTML = get();
    };
})(JestDOM || (JestDOM = {}));
//# sourceMappingURL=JestDOM.js.map