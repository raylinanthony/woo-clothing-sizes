             function maxLengthCheck(object) {
                 if (object.value.length > object.maxLength)
                     object.value = object.value.slice(0, object.maxLength)
             }

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
                         formSizes = '.woo_sizes_form',
                         msgSize = '.msg-size',
                         sizesLoading = '.my-sizes-ray .loading',
                         formCart = 'form.cart',
                         current_date = new Date();

                     setTimeout(() => {
                         $(inputMeter + ':first').focus();
                     }, 100)


                     const getFormData = ($form) => {
                         var unindexed_array = $form.serializeArray();
                         var indexed_array = {};

                         $.map(unindexed_array, function(n, i) {
                             indexed_array[n['name']] = n['value'];
                         });

                         return indexed_array;
                     }

                     $(document).on('focus', inputMeter, function() {

                         let dataVideo = $(this).attr('data-video');
                         let dataDesc = $(this).attr('data-desc');
                         let dataGif = $(this).attr('data-img-gif');
                         let dataImg = $(this).attr('data-img');

                         $(videoInfo).attr('href', 'https://www.youtube.com/watch?v=' + dataVideo);
                         $(bigImg).attr('src', dataImg);
                         $(videoGif).attr('src', dataGif);
                         $(videoDesc).text(dataDesc);


                     });

                     $(document).on('click', '[data-lightbox]', lity);


                     //-----
                     // Saving the measurements
                     //------

                     $(formSizes).on('submit', function(e) {
                         e.preventDefault();
                         $(sizesLoading).addClass(activeCls);
                         var _this = $(this);

                         var data = {
                             'action': 'save_sizes',
                             'nonce': wooSizes.ajax_nonce,
                             'data': getFormData($(this))
                         };



                         $.ajax({
                             type: "post",
                             url: wooSizes.ajax_url,
                             data: data,
                             success: function(result) {
                                 let res = JSON.parse(result);

                                 _this.find(msgSize).addClass(res.status).addClass(activeCls);
                                 _this.find(msgSize).text(res.body);
                                 $(sizesLoading).removeClass(activeCls);
                             }
                         });


                         return false;
                     })

                     //-----
                     // Panel Tabs
                     //-----

                     $(document).on('click', paneltabLi, function() {

                         if ($(this).hasClass(activeCls)) return;

                         let curTab = $(this).attr('data-anchor');

                         $(paneltabs).removeClass(activeCls);
                         $(curTab).addClass(activeCls);
                         $(curTab + ' .input-meter:first').focus();
                         $(paneltabLi).removeClass(activeCls);
                         $(this).addClass(activeCls);
                         $(formSizes + ' ' + msgSize).attr('class', msgSize.replace('.', ''));
                         $(formSizes + ' ' + msgSize).empty();

                     })


                     // ----
                     // Change input meter then update in add to cart form
                     // ----
                     $(inputMeter).on('blur', function() {
                         let name = $(this).attr('name'),
                             val = $(this).val(),
                             formName = $(formSizes).attr('data-name');

                         $(formCart + ' input[name="' + name + '"]').val(val);

                         //Save permantely when user is not registered
                         if ($(formSizes + '.no-logged').length > 0) {
                       
                             localStorage.setItem(formName + name, val);
                         }
                     });


                     //Save permantely when user is no registered

                     if ($(formSizes + '.no-logged').length > 0) {

                         var formName = $(formSizes).attr('data-name');

                         $(inputMeter).each(function() {
                             var name = $(this).attr('name');
                             var val = $(this).val();
                             $(this).val(localStorage.getItem(formName + name));

                         });

                     }
                     //end cond

                 })
             })(jQuery);