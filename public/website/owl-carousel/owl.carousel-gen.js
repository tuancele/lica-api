$(document).ready(function() {
	resizePanel();
	var carousel_change = $(".generic-slider-change");
	carousel_change.owlCarousel({
	loop:true,
	items : 1,
	singleItem:true,
	autoplay: true,
	autoplayTimeout: 5000,
	autoplaySpeed: 1000,
	nav:true,
	navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
});

$('#slide-new-product').owlCarousel({
	navText:['<i class="arr_nav_prev"></i>','<i class="arr_nav_next"></i>'],
	responsiveclass: true,
	responsive: {
		0: {
			items: 1,
			nav: true
		},
		768: {
			items: 2,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});


$('#about-company').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	margin: 0,
	autoplay: true,
	responsive: {
		0: {
			items: 1,
			nav: true
		},
		425: {
			items: 2,
			nav: true
		},
		768: {
			items: 3,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});


$('#relate-product').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	margin: 0,
	autoplay: true,
	responsive: {
		0: {
			items: 1,
			nav: true
		},
		425: {
			items: 2,
			nav: true
		},
		768: {
			items: 3,
			nav: true
		},
		1000: {
			items: 5,
			nav: true,
			loop: true
		}
	}
});


$('#logo-partner-1').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-2').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-3').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-4').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-5').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-6').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-7').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-8').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	autoplay: false,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 4,
			nav: true
		},
		1000: {
			items: 4,
			nav: true,
			loop: true
		}
	}
});

$('#logo-partner-20').owlCarousel({
	navText:['<i class="ti-angle-left"></i>','<i class="ti-angle-right"></i>'],
	responsiveclass: true,
	margin: 20,
	autoplay: true,
	responsive: {
		0: {
			items: 4,
			nav: true
		},
		768: {
			items: 6,
			nav: true
		},
		1000: {
			items: 10,
			nav: true,
			loop: true
		}
	}
});

/*
carousel_change.owlCarousel({
singleItem:true,
items : 1,
navigation:true,
navigationText: false,
pagination:true,
rewindNav:true,
rewindSpeed:1,
theme:"owl-ref",
autoPlay: 3000,
afterInit: function(elem){
var posindex = elem.attr('data-jumpto');
elem.trigger('owl.jumpTo', parseInt(posindex));
}
});
$(".next").click(function(){
carousel_change.trigger('owl.next');
})
$(".prev").click(function(){
carousel_change.trigger('owl.prev');
})
//$('.owl-carousel .owl-item .active').css('background', '#eee top center no-repeat fixed');
*/
});
$(window).resize(function() {
resizePanel();
});
/**
*
*/
function resizePanel() {
var w = $(window).width();
var h = $(window).height();
var dynamic_h = w*0.366;
$('.slider_panel').css('height', dynamic_h+'px');
}
