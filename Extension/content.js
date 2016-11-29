// content.js

chrome.runtime.onMessage.addListener( function( request, sender, sendResponse ) {

    	if( request.message === "save_screenshot" ) {

			var data = { 
				image : request.img,
			};

			document.dispatchEvent( new CustomEvent( 'csEvent', { detail : data } ) );

    	}
  	}
);

chrome.runtime.sendMessage( { message : "loaded" } );
