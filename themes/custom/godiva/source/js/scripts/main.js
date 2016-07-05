$(function() {

	// Init masonary
	$('.grid').masonry({
		// set itemSelector so .grid-sizer is not used in layout
		itemSelector: '.grid-item',
		// use element for option
		columnWidth: '.grid-sizer',
		percentPosition: true
	})

	// Search overlay
	$(".i-search, .i-search-close").click(function() {
		$("#search").fadeToggle(300);
		$("body").toggleClass("no-scroll");
	});

	// Menu overlay
	$(".button-nav, .i-menu-close").click(function() {
		$("#menu").slideToggle(300);
		$("body").toggleClass("no-scroll");
	});

	// Init bLazy
	var bLazy = new Blazy({});

	// Masonary title grabbing last word and adding span
	$('.grid-item h2').each(function(index, element) {
		var heading = $(element), word_array, last_word, first_part;

		word_array = heading.html().split(/\s+/); // split on spaces
		last_word = word_array.pop();             // pop the last word
		first_part = word_array.join(' ');        // rejoin the first words together

		heading.html([first_part, '<br/><span>', last_word, '</span>'].join(''));
	});

	//Sticky social nav
	var sticky = new Waypoint.Sticky({
		element: $('.article-share-holder')[0]
	});

	// Share article js social
	$("#share-article").jsSocials({
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
	$(".share-article-image").jsSocials({
		showCount: false,
		showLabel: false,
		shares: [
			"twitter",
			"pinterest"
		]
	});

	// Caption slide on hover on article images
	$(".article-image").hover(function() {
		$( this ).find( ".article-img-social" ).stop().slideToggle(300);
	});

	// Slick carousel init
	$(".slick-article").slick({
    	dots: true,
    	arrows: true,
    	// autoplay: true,
    	infinite: false,
    	slidesToShow: 1,
    	slidesToScroll: 1
	});

	var bLazy = new Blazy({ 
        container: '.slick-article' // Default is window
    });


	$('.slick-article').on('afterChange', function(event, slick, direction){
	  bLazy.revalidate();
	});

	// 
	$(".recent-item:nth-child(3n+3)").after( '<div class="h-clearfix"></div>' );

});