
var imgsrc;

chrome.browserAction.onClicked.addListener( function ( tab ) {

	chrome.windows.getCurrent( function ( win ) {

		chrome.tabs.captureVisibleTab( win.id, { "format" : "png" }, function ( img ) {

			imgsrc = img;
			chrome.tabs.create( { "url" : "http://comment.dev999.com/create/comment" } );

		});

	});

});

chrome.runtime.onMessage.addListener( function ( request, sender, sendResponse ) {

	if ( request.message === "loaded" ) {

		chrome.tabs.query( { currentWindow : true, active : true }, function ( tabs ) {

			chrome.tabs.sendMessage( tabs[0].id, { "message" : "save_screenshot", "img" : imgsrc } );

		});

	}

});