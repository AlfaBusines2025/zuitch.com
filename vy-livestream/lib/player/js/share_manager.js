(function (window, $){
	"use strict"	
	var VPLShareManager = function(settings){

		var facebookAppId = settings.facebookAppId,
		isMobile = VPLUtils.isMobile()
			
		if(!VPLUtils.isEmpty(facebookAppId) && window.location.protocol != 'file:'){
			injectFbSdk(facebookAppId);
		}

		this.share = function(type, data, query_string){
			
			var prefix = ('https:' == window.location.protocol ? 'https:' : 'http:'),
			w = 600, h = 300, cw = (window.screen.width - w) / 2, ch = (window.screen.height - h) / 2,

			title = data.title || '',
			description = data.description || '',
			thumb = data.thumb || data.thumbDefault, url, path;
			if(data.share)url = data.share;
			else url = window.location.href + query_string;

			//console.log(url)
			
			if(!VPLUtils.relativePath(thumb))thumb = VPLUtils.qualifyURL(data.thumb);

			if(type == "facebook"){	

				//if(VPLUtils.isEmpty(settings.facebookAppId))alert('Set Facebook api key in settings!');

				if(window.FB){

					FB.ui({
					   method: 'share',
					   href: encodeURIComponent(url),
					}, function(response){});

	               /* FB.ui({
	                    method: 'share_open_graph',
	                    action_type: 'og.shares',
	                    action_properties: JSON.stringify({
	                        object: {
	                            'og:url': url,
	                            'og:title': title,
	                            'og:description': description,
	                            'og:image': thumb
	                        }
	                    })
	                },
	                // callback
				    function(response) {
					    if (response && !response.error_message) {
					        // then get post content
					        //console.log('successfully posted. Status id : '+response.post_id);
					    } else {
					        //console.log('Something went error.');
					    }
					});

	                return;*/

				}

			}else if(type == "twitter"){	

				path = prefix+'//twitter.com/intent/tweet?url='+encodeURIComponent(url)+'&text='+encodeURIComponent(title);

			}else if(type == "googleplus"){	

				path = prefix+'//plus.google.com/share?url='+encodeURIComponent(url);

			}else if(type == "tumblr"){	

				path = prefix+'//www.tumblr.com/share/link?url='+encodeURIComponent(url)+'&amp;name='+encodeURIComponent(title)+'&amp;description='+encodeURIComponent(description);

			}else if(type == "reddit"){	

				path = prefix+'//www.reddit.com/submit?url='+encodeURIComponent(url);

			}else if(type == "linkedin"){	

				path = prefix+'//www.linkedin.com/shareArticle?mini=true&url='+encodeURIComponent(url)+'&title='+encodeURIComponent(title)+'&summary='+encodeURIComponent(description)+'&source='+document.title;

			}else if(type == "digg"){	

				path = prefix+'//digg.com/submit?url='+encodeURIComponent(url)+'&title='+encodeURIComponent(title);

			}else if(type == "pinterest"){	

				path = prefix+'//www.pinterest.com/pin/create/button/?url='+encodeURIComponent(url)+'&media='+encodeURIComponent(thumb)+'&description='+encodeURIComponent(description);	

			}else if(type == "whatsapp"){	

				if(isMobile){

		            var message = encodeURIComponent(title)+" - "+encodeURIComponent(url),
		            whatsapp_url = "whatsapp://send?text="+message;
		            window.location.href = whatsapp_url;
					return;

				}else{
					alert(settings.whatsAppWarning);
				}

			}

			if(path)window.open(path, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,width='+w+',height='+h+',left='+cw+',top='+ch+'');
		}	

		function injectFbSdk(fs_api_id) {
			
			// this loads the Facebook API
		    (function (d, s, id) {
		        var js, fjs = d.getElementsByTagName(s)[0];
		        if (d.getElementById(id)) { return; }
		        js = d.createElement(s); js.id = id;
		        js.src = "https://connect.facebook.net/en_US/sdk.js";
		        fjs.parentNode.insertBefore(js, fjs);
		    }(document, 'script', 'facebook-jssdk'));

		    window.fbAsyncInit = function () {
		        FB.init({
		            appId: fs_api_id,
		            xfbml: true,
		            version: 'v3.2',
		        });
		    };
		}

	};	

	window.VPLShareManager = VPLShareManager;

}(window,jQuery));