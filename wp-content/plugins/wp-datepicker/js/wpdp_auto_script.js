	
	

	jQuery(document).ready(function($){


		
		if($('.wpcf7-form-control.wpcf7-repeater-add').length>0){
			$('.wpcf7-form-control.wpcf7-repeater-add').on('click', function(){
				wpdp_refresh_4961(jQuery, true);
			});
		}
		
	
});
var wpdp_refresh_first_4961 = 'yes';
var wpdp_counter_4961 = 0;
var wpdp_month_array_4961 = [];
var wpdp_dateFormat = "mm/dd/yy";
var wpdp_defaultDate = "";
function wpdp_refresh_4961($, force){
				if(typeof $.datepicker!='undefined' && typeof $.datepicker.regional["en-GB"]!='undefined'){
					
				wpdp_month_array_4961 = $.datepicker.regional["en-GB"].monthNames;
									
				}
				
		
		
				


				if($("#datepicker").length>0){
					
				$("#datepicker").attr("autocomplete", "off");
					
				//document.title = wpdp_refresh_first=='yes';
				force = true;
				if(wpdp_refresh_first_4961 == 'yes' || force){
					
					if(typeof $.datepicker!='undefined')
					$("#datepicker").datepicker( "destroy" );
					
					$("#datepicker").removeClass("hasDatepicker");
					wpdp_refresh_first_4961 = 'done';
					
				}
				$('body').on('mouseover, mousemove', function(){//#datepicker									
				if ($(this).val()!= "") {
					$(this).attr('data-default-val', $(this).val());
				}		
							
				if(wpdp_counter_4961 > 2)
				clearInterval(wpdp_intv_4961);
				
				if(!$("#datepicker").hasClass('hasDatepicker')){

				
					
				$("#datepicker").datepicker($.extend(  
					{},  // empty object  
					$.datepicker.regional[ "en-GB" ],       // Dynamically  
					{  
 					dateFormat: wpdp_dateFormat
  } // your custom options 
				)); 
				
				
				
				
				
				$("#datepicker").datepicker( "option", "dateFormat", "mm/dd/yy" );


setTimeout(function(){ 

	 $.each($("#datepicker"), function(){
		
		var expected_default = $(this).data('default');		 
	 
		if(expected_default!=''){ $(this).datepicker().datepicker('setDate', expected_default); } 
		
	});
	
}, 100);
	




                

					$.each($("#datepicker"), function(){
						if($(this).data('default-val')!= ""){
							$(this).val($(this).data('default-val'));
						}
						
					});
						
				
				}
				});
				}
		


		
		$('.ui-datepicker').addClass('notranslate');
}
	var wpdp_intv_4961 = setInterval(function(){
		wpdp_counter_4961++;
		wpdp_refresh_4961(jQuery, false);
	}, 500);

	                jQuery(document).ready(function($){

                        $("#datepicker").on('click', function(){

                            $('.ui-datepicker-div-wrapper').prop('class', 'ui-datepicker-div-wrapper wp_datepicker_option-1 ');

                        });

                        setTimeout(function () {
                                $("#datepicker").click();
                                //$("//").focusout();
                        }, 1000);



                });

            
    //wpdp_refresh_//(jQuery, false);
	
	    
