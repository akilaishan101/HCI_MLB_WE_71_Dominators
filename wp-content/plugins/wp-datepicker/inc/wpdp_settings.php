<?php  if ( ! defined( 'ABSPATH' ) ) exit;

    global $wpdp_pro, $wpdp_url, $wpdp_premium_link, $wpdp_android_settings;
	if ( !current_user_can( 'administrator' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-datepicker' ) );
	}
// Save the field values

    if(isset($_GET['wpdp_delete_option']) && $wpdp_pro){

        delete_option($_GET['wpdp_delete_option']);

        ?>

            <script type="text/javascript" language="JavaScript">
                history.pushState({}, jQuery('title').text(), '<?php echo admin_url('/options-general.php?page=wp_dp') ?>');
            </script>

        <?php
    }


	if ( isset( $_POST['wpdp_fields_submitted'] ) && $_POST['wpdp_fields_submitted'] == 'submitted' && false) {
		
		
						
			
			if ( 
				! isset( $_POST['wpdp_nonce_action_field'] ) 
				|| ! wp_verify_nonce( $_POST['wpdp_nonce_action_field'], 'wpdp_nonce_action' ) 
			) {
			
			   print __('Sorry, your nonce did not verify.', 'wp-datepicker');
			   exit;
			
			} else {
			
			   // process form data
						
				
				update_option( 'wp_datepicker_months', 0);
				
				if($wpdp_pro){
					foreach ( $_POST as $key => $value ) {		
						if(is_array($value)){
							$value = array_map( 'esc_attr', $value );
							//pree($value);
							update_option( sanitize_text_field($key), ($value) );
						}else{
							if ( get_option( $key ) != $value ) {
								update_option( sanitize_text_field($key), sanitize_text_field($value) );
							} else {
								add_option( sanitize_text_field($key), sanitize_text_field($value), '', 'no' );
							}
						}
					}
				}else{
				
					
					update_option( 'wp_datepicker', sanitize_text_field($_POST['wp_datepicker']));
					update_option( 'wp_datepicker_weekends', sanitize_text_field($_POST['wp_datepicker_weekends']));
					update_option( 'wp_datepicker_beforeShowDay', sanitize_text_field($_POST['wp_datepicker_beforeShowDay']));
					
					update_option( 'wp_datepicker_months', sanitize_text_field($_POST['wp_datepicker_months']));
					update_option( 'wp_datepicker_wpadmin', sanitize_text_field($_POST['wp_datepicker_wpadmin']));
					
					
					update_option( 'wp_datepicker_language', sanitize_text_field($_POST['wp_datepicker_language']));
	 				update_option( 'wp_datepicker_readonly', sanitize_text_field($_POST['wp_datepicker_readonly']));
				}
				
			}
			
		
		
		
	}


    if ( isset( $_POST['wpdp_fields_submitted'] ) && $_POST['wpdp_fields_submitted'] == 'submitted' ) {

        if (
            ! isset( $_POST['wpdp_nonce_action_field'] )
            || ! wp_verify_nonce( $_POST['wpdp_nonce_action_field'], 'wpdp_nonce_action' )
        ) {

            print __('Sorry, your nonce did not verify.', 'wp-datepicker');
            exit;

        } else {

            // process form data

            if(isset($_POST['wpdp'])){
                $wpdp_data_post= sanitize_wpdp_data($_POST['wpdp']);

                $option_name = current(array_keys($wpdp_data_post));
                $options_data_array = current($wpdp_data_post);

                if(strlen($option_name)){

                    update_option( esc_attr($option_name), $options_data_array);

                }

            }


        }


    }

    global $wpdp_options_data, $current_option, $wpdp_ajax_request;

    if(isset($_POST['wpdp_get_selected_datepicker'])){

        $current_option = sanitize_wpdp_data($_POST['wpdp_get_selected_datepicker']);
        $wpdp_ajax_request = true;


    }else{

        $current_option = wpdp_get_current_option_name();
        $wpdp_ajax_request = false;

    }


    $wpdp_options_data = get_option($current_option, array());
    $wpdp_options_data_copy = $wpdp_options_data;

//    pree($wpdp_options_data);



    $wp_datepicker = false;
	$wp_datepicker_language = false;
	$wp_datepicker_weekends = false;
	$wp_datepicker_beforeShowDay = false;
	$wp_datepicker_months = false;
	$wp_datepicker_wpadmin = false;
	$wp_datepicker_readonly = true;

    if(array_key_exists('wpdp_options', $wpdp_options_data_copy)){

        unset($wpdp_options_data_copy['wpdp_options']);

    }



	extract($wpdp_options_data_copy);

    $wpdp_selectors = $wp_datepicker;
    $wp_datepicker_language = wpdp_slashes($wp_datepicker_language);



    $wpdb_string = wpdp_slashes($wpdp_selectors);
	
	$wpdb_arr = explode(',', $wpdb_string);
	
	$wpdb_arr = array_filter($wpdb_arr, 'strlen');
	
	if(empty($wpdb_arr)){
		$wpdb_arr = array('.datepicker');
	}
	
	$attrib = array('type'); //array('accept', 'align', 'right', 'top', 'middle', 'bottom', 'alt ', 'autocomplete ', 'autofocus', 'checked', 'disabled', 'max', 'maxlength', 'min', 'multiple', 'name', 'pattern', 'placeholder', 'readonly', 'required', 'size', 'src', 'step', 'type', 'value', 'width');
	$inputs = array(
		'class' => array('symbol' => '.',),
		'id' => array('symbol' => '#',),
		'input' => array(
			'symbol' => 'input',
			'type' => array(
			'button', 'checkboxcolor', 'date', 'datetime', 'datetime-local', 'email', 'file', 'hidden', 'image', 'month', 'number', 'password', 'radio', 'range', 'reset', 'search', 'submit', 'tel', 'text', 'time', 'url', 'week')
			),
		'textarea' => array('symbol' => 'textarea',),
		'select' => array('symbol' => 'select',),		
	);

	
	
?>	
<div class="wrap wpdp">

<?php if(!$wpdp_pro): ?>
<a title="<?php _e('Click here to download pro version', 'wp-datepicker'); ?>" style="background-color: #25bcf0;    color: #fff !important;    padding: 2px 30px;    cursor: pointer;    text-decoration: none;    font-weight: bold;    right: 0;    position: absolute;    top: 0;    box-shadow: 1px 1px #ddd;" href="http://shop.androidbubbles.com/download/" target="_blank"><?php _e('Already a Pro Member?', 'wp-datepicker'); ?></a>
<?php endif; ?>
	
    
  <div class="head_area">
	<h2><span class="dashicons dashicons-welcome-widgets-menus"></span><?php echo 'WP Datepicker '.'('.$wpdp_data['Version'].($wpdp_pro?') '.__('Pro', 'wp-datepicker').'':')'); ?> - <?php _e('Settings', 'wp-datepicker'); ?></h2>
      <div class="wpdp_android">
		  <?php $wpdp_android_settings->ab_io_display($wpdp_url); ?>
      </div>

      <h2 class="nav-tab-wrapper">
          <a class="nav-tab nav-tab-active"><?php _e("Datepicker","wp-datepicker"); ?></a>
          <a class="nav-tab"><?php _e("Speed Optimization","wp-datepicker"); ?></a>
      </h2>
    
    </div>

<div class="nav-tab-content">
<form method="post" action="" id="wpdp_form">
<?php wp_nonce_field( 'wpdp_nonce_action', 'wpdp_nonce_action_field' ); ?>
<input type="hidden" name="wpdp_fields_submitted" value="submitted" />
<!--<p class="submit"><input type="submit" name="Submit" class="button-primary" value="--><?php //_e( 'Save Changes', 'wp-datepicker' ); ?><!--" /></p> -->

<div class="alert alert-success fade in alert-dismissible show" style="margin-top:18px; display: none">
   <strong><?php _e( 'Success!', 'wp-datepicker' ); ?></strong> <?php _e( 'Settings are updated successfully.', 'wp-datepicker' ); ?>
</div>

<div class="wpdp_settings">

    <?php if($wpdp_pro && function_exists('wpdb_pro_settings_list')){ wpdb_pro_settings_list($current_option); } ?>


    <div class="wpdp_settings_fields">
<?php if($wpdp_pro): ?>
<a class="delete_wpdp" href="<?php echo admin_url('/options-general.php?page=wp_dp&wpdp_delete_option='.$current_option) ?>" style="text-decoration: none;"><?php _e( 'Delete', 'wp-datepicker' ); ?></a>
        <?php endif; ?>


<?php
	if(!empty($wpdb_arr)){
?>
		<a class="wpdp_cg_btn"><?php _e('How it works?', 'wp-datepicker'); ?></a>
		<div class="wpdp_cg">
		<h3><?php _e( 'Code Generator', 'wp-datepicker' ); ?>: <small>(<?php _e( 'Optional', 'wp-datepicker' ); ?>)</small></h3>
<?php
		foreach($wpdb_arr as $vals){
			$label = '';
			$type = substr($vals, 0, 1);
			$type_d = '';
			switch($type){
				case "#":
					$label = $type_d = 'id';
				break;
				case ".":
					$label = $type_d = 'class';
				break;
				case "i":
				case "s":
				case "t":
					$type_d = explode('[', $vals);
					$label = current($type_d);
				break;
			}
?>

        <?php if(!empty($inputs)): ?>
        <div class="wpdp_demo_div">
		<select name="wpdp_sel[]" class="ignore-save" style="width:200px;">
        <?php foreach($inputs as $tag_type => $input): ?>
        	<option style="background-color:#CCC; font-weight:bold;" data-tag="<?php echo $tag_type; ?>" data-type="<?php echo $inputs[$tag_type]['symbol']; ?>" value=""><?php echo $tag_type; ?></option>
            <?php if(!empty($attrib)): ?>
            <?php foreach($attrib as $attr): ?>

            <?php if(!empty($input) && isset($input[$attr])): ?>
            <option style="padding-left:20px;" data-type="<?php echo $attr; ?>" value="<?php echo $attr; ?>" <?php selected( $type, $attr ); ?>><?php echo $attr; ?></option>
            <?php foreach($input as $t => $t_array): ?>
            <?php if(!empty($t_array)): ?>
            <?php foreach($t_array as $tag_elem): ?>
            	<option style="padding-left:40px;" data-tag="<?php echo $tag_type; ?>" data-type="<?php echo $t; ?>" value="<?php echo $tag_elem; ?>" <?php selected( $type, $tag_elem ); ?>><?php echo $tag_elem; ?></option>
            <?php endforeach; ?>
        	<?php endif; ?>
            <?php endforeach; ?>
        	<?php endif; ?>

         	<?php endforeach; ?>
        	<?php endif; ?>
        <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input name="wpdp_demo_str[]" class="ignore-save" placeholder="" type="text" value="" />
		<input name="wpdp_demo_output[]" class="ignore-save" type="text" value="<?php echo $vals; ?>" style="width:350px" /><small><?php _e('Insert the output text below and glue with comma for next.', 'wp-datepicker'); ?></small>
        </div>
<?php
		}
?><br />
<?php _e('Video Tutorials', 'wp-datepicker'); ?>:<br />
<iframe width="200" height="120" src="https://www.youtube.com/embed/eILaObbYucU" frameborder="0" allowfullscreen></iframe>
<iframe width="200" height="120" src="https://www.youtube.com/embed/c2afBhUPp4w" frameborder="0" allowfullscreen></iframe>
</div>
<?php
	}

?>

<?php
global $wpdp_dir;
?>






<input type="text" width="100%" value="<?php echo wpdp_slashes($wpdp_selectors); ?>"  name="wpdp[<?php echo $current_option ?>][wp_datepicker]" class="wpdp-useable wpdp_selectors" data-name="[wp_datepicker]" placeholder="<?php _e('Enter', 'wp-datepicker'); ?> id, class, name based and/or type based CSS <?php _e('selector', 'wp-datepicker'); ?>" /><br />
<small>
<?php _e('You can enter multiple selectors as CSV', 'wp-datepicker'); ?> (<?php _e('Comma Separated Values', 'wp-datepicker'); ?>).<br />

e.g. <br />
<span class="wpdp_1">#datepicker</span><br />
or<br />
<span class="wpdp_2">#datepicker, .hasDatepicker, .date-field</span><br />
and<br />
<span class="wpdp_3"><?php _e('Sample', 'wp-datepicker'); ?> HTML: &lt;input type=&quot;text&quot; id=&quot;datepicker&quot; /&gt;</span>
</small>


<br />
<br />

<select name="wpdp[<?php echo $current_option ?>][wp_datepicker_language]" class="wpdp-useable wpdp_selectors" data-name="[wp_datepicker_language]">
<option><?php _e('Select Language', 'wp-datepicker'); ?></option>
<?php
foreach (glob($wpdp_dir."js/i18n/*.js") as $filename) {
    $content = file_get_contents($filename);
	$lines = nl2br($content);
	$lines = explode('<br>', $lines);
	$line = explode(' ', $lines[0]);
	$title = $line[1];

	$code = str_replace(array('datepicker-', '.js'), '', basename($filename));
	$val = $code.'|'.basename($filename);
?>
	<option value="<?php echo $val; ?>" <?php echo ($wp_datepicker_language==$val?'selected="selected"':''); ?>><?php echo $code.' ('.$title.')'; ?></option>
<?php
}
?>
</select>


<div class="wp_datepicker_wpadmin">
<label for=""><?php _e( 'Enable for backend (wp-admin)?', 'wp-datepicker' ); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_wpadmin]" class="wpdp-useable" data-name="[wp_datepicker_wpadmin]" id="wp_datepicker_wpadmin_yes" value="1" <?php checked($wp_datepicker_wpadmin); ?> /><label for="wp_datepicker_wpadmin_yes"><?php _e('Enable', 'wp-datepicker'); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_wpadmin]" class="wpdp-useable" data-name="[wp_datepicker_wpadmin]" id="wp_datepicker_wpadmin_no" value="0" <?php checked(!$wp_datepicker_wpadmin); ?> /><label for="wp_datepicker_wpadmin_no"><?php _e('Disable', 'wp-datepicker'); ?></label>
<small><?php _e( 'Will implement datepicker in wp-admin pages as well.', 'wp-datepicker' ); ?></small>
</div>

<div class="wp_datepicker_readyonly">
<label for=""><?php _e( 'Make datepicker field editable or readonly?', 'wp-datepicker' ); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_readonly]" class="wpdp-useable" data-name="[wp_datepicker_readonly]" id="wp_datepicker_readonly_yes" value="1" <?php checked($wp_datepicker_readonly); ?> /><label for="wp_datepicker_readonly_yes"><?php _e('Read-only', 'wp-datepicker'); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_readonly]" class="wpdp-useable" data-name="[wp_datepicker_readonly]" id="wp_datepicker_readonly_no" value="0" <?php checked(!$wp_datepicker_readonly); ?> /><label for="wp_datepicker_readonly_no"><?php _e('Editable', 'wp-datepicker'); ?></label>
</div>


<div class="wp_datepicker_months">
<label for=""><?php _e( 'Weekends?', 'wp-datepicker' ); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_weekends]" class="wpdp-useable" data-name="[wp_datepicker_weekends]" id="wp_datepicker_weekends_yes" value="0" <?php checked(!$wp_datepicker_weekends); ?> /><label for="wp_datepicker_weekends_yes"><?php _e('Enable', 'wp-datepicker'); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_weekends]" class="wpdp-useable" data-name="[wp_datepicker_weekends]" id="wp_datepicker_weekends_no" value="1" <?php checked($wp_datepicker_weekends); ?> /><label for="wp_datepicker_weekends_no"><?php _e('Disable', 'wp-datepicker'); ?></label>
<small><?php echo __( 'Will remove Saturdays & Sundays from date picker.', 'wp-datepicker').' '.__("Some service businesses don't offer weekend service.", 'wp-datepicker' ); ?></small>
</div>

<?php if($wpdp_pro){ ?>
<div class="wp_datepicker_months beforeShowDay collapsed">
<label for="" title="<?php _e( 'Click here for custom scripts', 'wp-datepicker' ); ?>"><?php _e( 'Any other requirements with weekdays?', 'wp-datepicker' ); ?></label>
<div class="textarea_div">
<textarea name="wpdp[<?php echo $current_option ?>][wp_datepicker_beforeShowDay]" class="wpdp-useable" data-name="[wp_datepicker_beforeShowDay]" id="wp_datepicker_beforeShowDay" placeholder="<?php _e('Insert your custom code here for beforeShowDay'); ?>"><?php echo $wp_datepicker_beforeShowDay; ?></textarea>
<?php
	$scripts_arr = array(
		array(
		 	'title' => 'Enable first Thursday Only?',
			'guide' => __( 'e.g.', 'wp-datepicker').' '.__('Need to enable first Thursday Only?', 'wp-datepicker').'<a style="float:right;" href="https://www.youtube.com/embed/Qb9O7TUyLek" target="_blank">'.__('Video Tutorial', 'wp-datepicker' ).'</a>'.'<br /><pre>function (date) { <br />var day = date.getDay(); <br />return [(day == 4) && date.getDate()<8];  <br />}</pre><br />'.__('It will disable every date except first thursday of each month.', 'wp-datepicker').'<br />'.__('Note: It will override weekends functionality.', 'wp-datepicker' )
		),
		array(
			'title' => 'Disable Sunday & Monday?',
			'guide' => __( 'e.g.', 'wp-datepicker').' '.__('Need to disable Sunday & Monday?', 'wp-datepicker').'<a style="float:right;" href="https://www.youtube.com/embed/57Mwqy3vWEk" target="_blank">'.__('Video Tutorial', 'wp-datepicker' ).'</a/><br /><pre><br />function (date) { var day = date.getDay(); return [(day != 0 && day != 1)]; }<br /><br /></pre><br />'.__('It will disable every Sunday & Monday each month.', 'wp-datepicker').'<br />'.__('Note: It will override weekends functionality.', 'wp-datepicker' )
		),
		array(
			'title' => 'Disable the months July and December?',
			'guide' => __( 'e.g.', 'wp-datepicker').' '.__('Need to disable the months July and December?', 'wp-datepicker').'<a style="float:right;" href="https://www.youtube.com/embed/0s7loonWbuw" target="_blank">'.__('Video Tutorial', 'wp-datepicker' ).'</a><br /><pre><br />function (date) { <br />var month = date.getMonth(); <br />return [(month != 6) && (month != 11)]; <br />}<br /><br /></pre><br />'.__('It will disable the months July and December.', 'wp-datepicker').'<br />'.__('Note: It will override weekends functionality.', 'wp-datepicker' )
		),
		array(
			'title' => 'Disable specific set of dates?',
			'guide' => __( 'e.g.', 'wp-datepicker').' '.__('Need to disable 25th December for specific years?', 'wp-datepicker').'<a style="float:right;" href="" target="_blank">'.__('Video Tutorial', 'wp-datepicker' ).'</a><br /><pre><br />function (date) { <br />var array = ["2020-12-25","2025-12-25","2030-12-25"]; <br />var string = jQuery.datepicker.formatDate("yy-mm-dd", date); <br />return [ array.indexOf(string) == -1 ]; <br />}<br /><br /></pre><br />'.__('It will disable 25th December of specific years.', 'wp-datepicker').'<br />'.__('Note: It will override weekends functionality.', 'wp-datepicker' )
		),
		array(
			'title' => 'Disable Sunday, Monday, Tuesday and also specific dates like 07/04/'.date('Y').' or 12/25/'.date('Y'),
			'guide' => __( 'e.g.', 'wp-datepicker').' '.__('Need to disable specific days and dates together?', 'wp-datepicker').'<a style="float:right;" href="https://wordpress.org/support/topic/disabling-specific-dates-pro-version" target="_blank">'.__('Support Thread', 'wp-datepicker' ).'</a><br /><pre><br />function (date) {<br>
var day = date.getDay();<br>
var array = ["'.date('Y').'-07-04","'.date('Y').'-12-25"]; <br>
var string = jQuery.datepicker.formatDate("yy-mm-dd", date); <br>
var sunday = 0;<br>
var monday = 1;<br>
var tuesday = 2;<br>
return [(day != sunday && day != monday && day != tuesday && array.indexOf(string) == -1)];<br>
}<br /><br /></pre><br />'.__('It will disable specific dates and weekdays together with one snippet of code.', 'wp-datepicker').'<br />'.__('Note: It will override weekends functionality.', 'wp-datepicker' )
		)
	);
?>
<?php if(!empty($scripts_arr)): ?>
<select name="custom-scripts">
<?php foreach($scripts_arr as $script_key => $script_val): ?>
<option value="<?php echo $script_key; ?>"><?php echo $script_val['title']; ?></option>
<?php endforeach; ?>
</select>
<?php endif; ?>

<span style='font-size: 350px;color: #6E6E6E;position: absolute;top: 70px; right:0'>&#8624;</span>

<?php if(!empty($scripts_arr)): ?>
<?php foreach($scripts_arr as $script_key => $script_val): //pree($script_val);?>

<small class="custom-scripts script-no-<?php echo $script_key; ?>"><br /><br /><br /><?php echo $script_val['guide']; ?></small>

<?php endforeach; ?>
<?php endif; ?>

</div>
</div>
<?php } ?>

<div class="wp_datepicker_months">
<label><?php _e( 'Need months in full or short?', 'wp-datepicker' ); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_months]" class="wpdp-useable" data-name="[wp_datepicker_months]" id="wp_datepicker_months_yes" value="1" <?php checked($wp_datepicker_months); ?> /><label for="wp_datepicker_months_yes"><?php _e('Short', 'wp-datepicker'); ?></label>
<input type="radio" name="wpdp[<?php echo $current_option ?>][wp_datepicker_months]" class="wpdp-useable" data-name="[wp_datepicker_months]" id="wp_datepicker_months_no" value="0" <?php checked(!$wp_datepicker_months); ?> /><label for="wp_datepicker_months_no"><?php _e('Full', 'wp-datepicker'); ?></label>
<small><?php echo __( 'e.g.', 'wp-datepicker').' Sep '.__('or September?', 'wp-datepicker' ); ?></small>
</div>



<?php if($wpdp_pro && function_exists('wpdp_pro_settings')){ wpdp_pro_settings($current_option); }else{ wpdp_free_settings($current_option);

?>
<?php
} ?>


<!--<p class="submit"><input type="submit" name="Submit" class="button-primary" value="--><?php //_e( 'Save Changes', 'wp-datepicker' ); ?><!--" /></p>-->
</div>
<?php if(!$wpdp_pro): ?>
<div class="wpdp_go_premium">
<a href="<?php echo $wpdp_premium_link; ?>" target="_blank"><img src="<?php echo $wpdp_url.'img/'; ?>go-premium.png" /></a>
</div>
<?php endif; ?>
</div>

    <div class="wpdp_modal">
        <div class="wpdp_modal_content">
           
            <img src="<?php echo $wpdp_url.'img/loader.gif';?>">
        </div>
    </div>

</form>
</div>
<div class="nav-tab-content speed_opt_content hide">
    <?php include_once ('speed_opt_template.php'); ?>
</div>
</div>


<style type="text/css">
.update-nag, #message{ display:none; }
</style>

<script type="text/javascript" language="javascript">

    jQuery(document).ready(function($) {


        <?php if(isset($_GET['t'])): ?>

        $('.nav-tab-wrapper .nav-tab:nth-child(<?php echo $_GET['t']+1; ?>)').click();

        <?php endif; ?>

    });

</script>