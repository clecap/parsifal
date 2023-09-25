



onconnect = function(e) {
  var port = e.ports[0];

  port.onmessage = function(e) {
    console.log ("!worker got: ", e.data);
    
    port.postMessage(workerResult);
  }
}



