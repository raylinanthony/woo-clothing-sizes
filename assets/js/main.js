;
(function($) {
    $(function() {

        console.log('Woo Clothes Sizes Activated');

        var inputMeter = '.my-sizes-ray .input-meter', 
            bigImg = '.my-sizes-ray .wrap-media img', 
            videoInfo = '.my-sizes-ray .wrap-video-info',
            videoGif = '.my-sizes-ray .wrap-gif img',
            videoDesc = '.my-sizes-ray .sizes-tabs .wrap-desc',  
            paneltabLi = '.my-sizes-ray .sizes-menu ul li',  
            paneltabs = '.my-sizes-ray .sizes-tabs .tab',  
            activeCls = 'active',
            current_date = new Date() ;
        
        $(document).on('focus', inputMeter, function(){

            let dataVideo = $(this).attr('data-video');
            let dataDesc = $(this).attr('data-desc');
            let dataGif = $(this).attr('data-img-gif');
            let dataImg = $(this).attr('data-img');
        
            $(videoInfo).attr('href', 'https://www.youtube.com/watch?v='+dataVideo);
            $(bigImg).attr('src', dataImg);
            $(videoGif).attr('src', dataGif);
            $(videoDesc).text( dataDesc);


        });


        //-----
        // Panel Tabs
        //-----

        $(document).on('click', paneltabLi, function(){

            if($(this).hasClass(activeCls)) return;

            let curTab = $(this).attr('data-anchor');

            $(paneltabs).removeClass(activeCls);
            $(curTab).addClass(activeCls);
             $(curTab+ ' .input-meter:first').focus();
             $(paneltabLi).removeClass(activeCls);
            $(this).addClass(activeCls);
        })
        
 
  


        //Add to cart trigger for adding total to header cart
      /*  body.on('add_to_cart added_to_cart', function(event) {
            if (headerCart.length > 0) {
                var data = {
                    'action': 'cart_count_retriever'
                };


                jQuery.post(hola.ajax_url, data, function(response) {
                    console.log('Got this from the server: ' + response);
                });
                //headerCart.text(' ass')
            }
        });*/

  
 

    })
})(jQuery);