jQuery(function() {

	// Init masonary
	jQuery('.grid').masonry({
		// set itemSelector so .grid-sizer is not used in layout
		itemSelector: '.grid-item',
		// use element for option
		columnWidth: '.grid-sizer',
		percentPosition: true
	})

	// Search overlay
	jQuery(".i-search, .i-search-close").click(function() {
		jQuery("#search").fadeToggle(300);
		jQuery("body").toggleClass("no-scroll");
	});

	// Menu overlay
	jQuery(".button-nav, .i-menu-close").click(function() {
		jQuery("#menu").slideToggle(300);
		jQuery("body").toggleClass("no-scroll");
	});

	// Init bLazy
	var bLazy = new Blazy({});

	// Masonary title grabbing last word and add span
	jQuery('.grid-item h2').each(function(index, element) {
		var heading = jQuery(element), word_array, last_word, first_part;

		word_array = heading.html().split(/\s+/); // split on spaces
		last_word = word_array.pop();             // pop the last word
		first_part = word_array.join(' ');        // rejoin the first words together

		heading.html([first_part, '<br/><span>', last_word, '</span>'].join(''));
	});

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

	// Slick carousel init
	jQuery(".slick-article").slick({
    	dots: true,
    	arrows: true,
    	infinite: false,
    	slidesToShow: 1,
    	slidesToScroll: 1
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


	jQuery('.slick-article, .home-godiva').on('afterChange', function(event, slick, direction){
	  bLazy.revalidate();
	});

	jQuery('#slick-lightbox').slickLightbox({
		caption: 'caption'
	});

	var sticky = new Waypoint.Sticky({
		element: jQuery('.article-share-holder')[0],
		offset: 49
	});

	// Add clearfix on three items category page 
	jQuery(".recent-item:nth-child(3n+3)").after( '<div class="h-clearfix"></div>' );

});