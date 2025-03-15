var VY_LOVERLAY = null;
var VY_WAITINGSCRIPTINTERVAL;
 
var VYLV_ASSETS = VYLV_ASSETS || (function(){
   // private
    let _args = {},
	head = document.getElementsByTagName('head')[0],
	is_module = false,
	no_module = false; 

    return {
        init : function(Args) { 
            _args = Args;
        },
		loadCSS: function(url, callback) {

         let cssnode = document.createElement('link');

         cssnode.type = 'text/css';
         cssnode.rel = 'stylesheet';
         cssnode.href = url;

         cssnode.onreadystatechange = callback;
         cssnode.onload = callback;

         head.appendChild(cssnode);
     },
		loadJS: function(url, callback, module, no_module) {
		return new Promise(async (resolve, reject) => {
         let jsnode = document.createElement('script');
	
		 if(!no_module)
         jsnode.type = module ? 'module' : 'text/javascript';
		
		 if(no_module) {
			jsnode.noModule = true;
			resolve(true);
		 }
	 
         jsnode.src = url;

         jsnode.onreadystatechange = callback;
		 if(!no_module) 
         jsnode.onload = function(){
			 
			 resolve(true);
		 };
		 
		
         head.appendChild(jsnode);
		 
		});
     },	

	 addModule: function(value){
		 
         let jsnode = document.createElement('script');
 
		 jsnode.innerHTML = 'var exports = {"__esModule": true};';
		 jsnode.id = "vy_live_esmodules";
         head.appendChild(jsnode);
 
 
	 },
	 isForeign:function(a){
		return a.toString().includes('//'); 
	 },
        load:async function(callback) {
			const that = this;
 
			// generate css assets
			for(var i = 0; i < _args.css.length;i++)
				that.loadCSS( ( that.isForeign(Object.values(_args.css[i])) ? '' : _args.path) + Object.values(_args.css[i]) + '?v=' + _args.version);
			
			// generate js assets
			for(var i = 0; i < _args.js.length;i++) {
				
				if(typeof _args.js[i][Object.keys(_args.js[i])] == 'object'){ 
 
				const obj = _args.js[i][Object.keys(_args.js[i])];
				for(var x = 0; x < obj.length; x++)
					if(obj[x] == 'module'){
 
						 that.addModule(); 
						is_module = true;
						
					} else if( obj[x] == 'nomodule') {
						no_module = 1;
					} else if(obj[x] == 'check' && Object.keys(_args.js[i]) == 'socket.io'){
						
					 if (("io" in window)) break;
				 
					} else {
					
				    await that.loadJS( (that.isForeign(obj[x]) ? '' : _args.path) + obj[x] + '?v=' + _args.version, false, is_module, no_module);
					is_module = false;
					no_module = false;
					}
				} else {
				
				await that.loadJS( (that.isForeign(Object.values(_args.js[i])) ? '' : _args.path) + Object.values(_args.js[i]) + '?v=' + _args.version);
				
				}
				
			}
 
				 callback();
				 $('#vy_live_esmodules').remove();
        
		}
    };
}());
 function vy_lv_removeLoading(){
	 
		if(VY_LOVERLAY != null) {
			VY_LOVERLAY.destroy(); 
			VY_LOVERLAY = null;
		}
 }
function vy_lv_createLoading(){
			
			if(VY_LOVERLAY != null)
				return;
			
		 
	 
			this.spinner_opts = {
			lines: 13, // The number of lines to draw
			length: 11, // The length of each line
			width: 5, // The line thickness
			radius: 17, // The radius of the inner circle
			corners: 1, // Corner roundness (0..1)
			rotate: 0, // The rotation offset
			color: '#FFF', // #rgb or #rrggbb
			speed: 1, // Rounds per second
			trail: 60, // Afterglow percentage
			shadow: false, // Whether to render a shadow
			hwaccel: false, // Whether to use hardware acceleration
			className: 'spinner', // The CSS class to assign to the spinner
			zIndex: 2e9, // The z-index (defaults to 2000000000)
			top: 'auto', // Top position relative to parent in px
			left: 'auto' // Left position relative to parent in px
		};
		this.spinner_target = document.createElement("div");
		document.body.appendChild(this.spinner_target);
		this.spinner = new Spinner(this.spinner_opts).spin(this.spinner_target);
		VY_LOVERLAY = iosOverlay({
			text: "Loading",
			duration: 99999999999,
			spinner: this.spinner
		});
		
	
}
function vy_global_openLiveStream(ev,el,id,no_loading){

    if(!("Spinner" in window))
		return setTimeout(function() { vy_global_openLiveStream(ev,el,id); },50);

    if(!no_loading)
	vy_lv_createLoading();

	if(!("VY_LIVE_STREAM" in window)) {
		
		VY_WAITINGSCRIPTINTERVAL = setTimeout(function(){ vy_global_openLiveStream(ev,el,id,1); },100);
		
	} else {
		clearTimeout(VY_WAITINGSCRIPTINTERVAL);
		vy_lv_removeLoading();	
		vy_lvst.openLiveStream(ev,el,id);
	}
	
}