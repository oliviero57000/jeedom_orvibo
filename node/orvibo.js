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
