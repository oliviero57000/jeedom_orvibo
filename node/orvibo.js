var request = require('request');

var urlJeedom = '';

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

// print process.argv
process.argv.forEach(function(val, index, array) {

	switch ( index ) {
		case 2 : urlJeedom = val; break;
	}

});

function saveValue(data) {
	LogDate("info", "Send Value : " + data.toString() );
	url = urlJeedom + "&messagetype=saveValue&type=orvibo";
	request({
		url: url,
		method: 'PUT',
		json: {"data": data.toString()},
	},
function (error, response, body) {
	  if (!error && response.statusCode == 200) {
		LogDate("debug", "Got response Value: " + response.statusCode);
	  }else{
	  	LogDate("debug", "SaveValue Error : "  + error );
	  }
	});
}

var Orvibo = require("node-orvibo")
var o = new Orvibo()

// Four timers for repeating messages, as UDP doesn't guarantee delivery
// These timers are cancelled once
var timer1 // This timer is used for discovering devices
var timer2 = [] // This timer is used to subscribe to a device
var timer3 = [] // This timer is used to query a device
var timer4 = [] // This timer is used to toggle a socket or learn / emit IR

// We've listened, and now we're ready to go.
o.on("ready", function() {
  timer1 = setInterval(function() { // Set up a timer to search for sockets every second until found
    o.discover()
  }, 1000)
})

// A device has been found and added to our list of devices
o.on("deviceadded", function(device) {
  clearInterval(timer1) // Clear our first timer, as we've found at least one socket
  o.discover() // Ask around again, just in case we missed something
  timer2[device.macAddress] = setInterval(function() { // Set up a new timer for subscribing to this device. Repeat until we get confirmation of subscription
    o.subscribe(device)
  }, 1000)
})

// We've asked to subscribe (control) a device, and now we've had a response.
// Next, we will query the device for its name and such
o.on("subscribed", function(device) {
  clearInterval(timer2[device.macAddress]) // Stop the second subscribe timer for this device
  timer3[device.macAddress] = setInterval(function() { // Set up another timer, this time for querying
    o.query({
      device: device, // Query the device we just subscribed to
      table: "04" // See PROTOCOL.md for info. "04" = Device info, "03" = Timing info
    })
  }, 1000)
})

// Our device has responded to our query request
o.on("queried", function(device, table) {
  clearInterval(timer3[device.macAddress]) // Stop the query timer
  if (device.type == "Socket") { // If this is a socket
    console.log("Socket discovered ", device.macAddress)
  } else if (device.type == "AllOne") { // If we've got an AllOne instead
    console.log("Allone discovered ", device.macAddress)
  }
})

o.on("statechangeconfirmed", function(device) {
  console.log("Socket %s confirming state change to", device.macAddress, device.state)
})

o.on("ircode", function(device, ir) { // We've learned some IR
	console.log("IR learned", device.macAddress, ir)
})

// The AllOne has a button on top of it. A short press will
// generate this event. Useful for programming sequences of commands etc.
o.on("buttonpress", function(device) {
  console.log("Button on", device.macAddress)
})

o.listen()
