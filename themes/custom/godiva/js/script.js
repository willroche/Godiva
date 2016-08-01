jQuery(function() {

	// Init masonary
	// jQuery('.grid').masonry({
	// 	// set itemSelector so .grid-sizer is not used in layout
	// 	itemSelector: '.grid-item',
	// 	// use element for option
	// 	columnWidth: '.grid-sizer',
	// 	percentPosition: true
	// })

	// Search overlay
	jQuery(".i-search").click(function() {
		jQuery(".header-search").toggleClass("search-expand");
	});

	// Close search box
	jQuery(".close-search").click(function() {
		jQuery(".header-search").toggleClass("search-expand");
	});

	// Init bLazy
	var bLazy = new Blazy({});

	// Masonary title grabbing last word and add span
	// jQuery('.grid-item h2').each(function(index, element) {
	// 	var heading = jQuery(element), word_array, last_word, first_part;

	// 	word_array = heading.html().split(/\s+/); // split on spaces
	// 	last_word = word_array.pop();             // pop the last word
	// 	first_part = word_array.join(' ');        // rejoin the first words together

	// 	heading.html([first_part, '<br/><span>', last_word, '</span>'].join(''));
	// });

	// Share article js social
	jQuery("#share-article").jsSocials({
		showCount: true,
		showLabel: false,
		shares: [
			"twitter",
			"facebook",
			"googleplus",
			"pinterest"
		]
	});

	// Share article image js social
	jQuery(".share-article-image").jsSocials({
		showCount: false,
		showLabel: false,
		shares: [
			"twitter",
			"pinterest"
		]
	});

	// Caption slide on hover on article images
	jQuery(".article-image").hover(function() {
		jQuery( this ).find( ".article-img-social" ).stop().slideToggle(300);
	});

	// Focus search field on click
	jQuery('.i-search').click(function(){
		jQuery('.header-search form input').focus();
	});

	// Slick carousel init
	jQuery(".slick-article").slick({
    	dots: true,
    	arrows: true,
    	infinite: false,
    	slidesToShow: 1,
    	slidesToScroll: 1
	});

	jQuery(".slick-home-carousel").slick({
    	dots: true,
    	arrows: true,
    	infinite: true,
    	slidesToShow: 1,
    	slidesToScroll: 1,
    	pauseOnHover: false,
    	autoplay: true,
    	autoplaySpeed: 4000
	});

	// Slick carousel init
	jQuery(".home-godiva").slick({
		dots: false,
		arrows: true,
		draggable: true,
		infinite: false,
		slidesToShow: 4,
		slidesToScroll: 1,
		responsive: [
		    {
		      breakpoint: 767,
		      settings: {
		        slidesToShow: 1,
		        slidesToScroll: 1
		      }
		    }
		]
	});

	var bLazy = new Blazy({ 
        container: '.slick-article' // Default is window
    });


	jQuery('.slick-article, .home-godiva, .slick-home-carousel').on('afterChange', function(event, slick, direction){
	  bLazy.revalidate();
	});

	// jQuery('#slick-lightbox').slickLightbox({
	// 	caption: 'caption'
	// });

	jQuery(window).scroll(function() {
		var scroll = jQuery(window).scrollTop();

		if (scroll >= 200) {
			jQuery(".slice-nav").addClass("nav-small");
		} else {
			jQuery(".slice-nav").removeClass("nav-small");
		}
	});

	// Add clearfix on three items category page 
	jQuery(".recent-item:nth-child(4n+3)").after( '<div class="h-clearfix"></div>' );

	if ( jQuery( ".article-share-holder" ).length ) {
 		var sticky = new Waypoint.Sticky({
		element: jQuery('.article-share-holder')[0],
		offset: 49
	});
 
}

	

});

/**
* Slide right instantiation and action.
*/
var slideRight = new Menu({
	wrapper: '#o-wrapper',
	type: 'slide-right',
	menuOpenerClass: '.c-button',
	maskId: '#c-mask'
});

var slideRightBtn = document.querySelector('#c-button--slide-right');

slideRightBtn.addEventListener('click', function(e) {
	e.preventDefault;
	slideRight.open();
});