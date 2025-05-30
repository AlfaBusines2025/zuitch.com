setTimeout(function(){
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
		this.loverlay = iosOverlay({
			text: "Loading",
			duration: 99999999999,
			spinner: this.spinner
		});
		
},50);