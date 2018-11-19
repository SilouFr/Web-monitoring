$(document).ready(function(){
	$('a[href^="#"]').on('click',function (e) {
	    e.preventDefault();

	    var target = this.hash;
	    var $target = $(target);

	    $('html, body').stop().animate({
	        'scrollTop': $target.offset().top
	    }, 500, 'swing', function () {
	        window.location.hash = target;
	    });
	});
    
    $('a[data-toggle="collapse"]').click(function(e){
        if ($(window).width() >= 768) {   
            e.stopPropagation();
        }    
    });

    $('#ad1').html("contact@");
    $('#ad2').html("ponyhub.fr");
});

