<?php
/*

Plugin Name: VIPer

Plugin URI: https://surecode.me

Description: Limitation content by user level for  WordPress

Version: 1.0.0

Author: Kirill Shur(SureCode Marketing)

License: GPLv2

*/





add_action('user_new_form','viper_show_user_profile');

add_action('edit_user_profile','viper_show_user_profile');

function viper_show_user_profile($user){

  ob_start();

  global $user_levels;

  if('add-new-user' == $user){
    $current_user_level = '';
  }
  elseif(!empty($user) && isset($user->data->ID))
  {

    $user_id = $user->data->ID;
    $current_user_level = get_user_meta($user_id,'user_level',true);

  }
 ?>
 <table class="form-table">
 <tr>
 <th>Level</th>
 <td>
  <select name="user_level" id="user_level">
 <?php foreach( $user_levels as $user_level_index => $user_level )
 {
 ?>
 <option value="<?php echo esc_html($user_level_index); ?>"<?php selected( $current_user_level,$user_level_index ); ?>>
 <?php echo esc_html($user_level); ?>
</option>
 <?php
 }
 ?>
 </select>
</td>
</tr>

<?php
    $vipercallback = ob_get_clean();
      $allowed_html = array(
      'form'  => array(
      'class'=> array(),
      'method'=> array(),
      'action'=> array()
      ),
      'input' => array(
      'value' => array(),
      'type' => array(),
      'class'=> array(),
      'name'=> array(),
      'id'=> array()
      ),
      'br' => array(),
      'label' => array(
      'for'=> array()
      ),
      'div' => array(
      'class'=> array()
      ),
      'p'  => array(),
      'h2'  => array(
      'class'=> array()
      ),
      'link' => array(),
      'style' => array(),
      'select' => array(
        'value' => array(),
        'type' => array(),
        'class'=> array(),
        'name'=> array(),
        'id'=> array()
      ),
      'option' => array(
        'value' => array(),
        'selected' => array()
      ),
      'table' => array(),
      'tr' => array(),
      'th' => array(),
      'td' => array()
      );
  echo wp_kses($vipercallback, $allowed_html);
}




add_action('user_register','viper_save_user_data');

add_action('profile_update','viper_save_user_data');


function viper_save_user_data($user_id){

  global $user_levels;

  if(isset($_POST["user_level"]) &&
  !empty($_POST["user_level"])&&
  array_key_exists($_POST["user_level"],$user_levels)){
    update_user_meta($user_id,'user_level',$_POST["user_level"]);
  }else{
    update_user_meta($user_id,'user_level','regular');
  }

}


add_filter('manage_users_columns','viper_add_user_columns');

function viper_add_user_columns($columns){
 $new_columns = array_slice($columns,0,2,true) + array('level' => 'User Level') + array_slice($columns,2,NULL,true);
 return $new_columns;
}

add_filter('manage_users_custom_column','viper_display_user_columns_data',10,3);

function viper_display_user_columns_data($val,$column_name,$user_id){

  global $user_levels;

  if('level' == $column_name){

    $current_user_level = get_user_meta($user_id,'user_level',true) ;
    if(!empty($current_user_level)){
      $val = $user_levels[$current_user_level];
    }
  }
  return $val;
}



add_filter('restrict_manage_users','viper_add_user_filter');

function viper_add_user_filter(){

  global $user_levels;

  $filter_value= '';
  if(isset($_GET['user_level'])){
    $filter_value = $_GET["user_level"];
  }
?>
<select name="user_level"  class="user_level" style="float:none;">
  <option value="">No filter</option>
  <?php
    foreach ($user_levels as $user_level_index => $user_level) {
   ?>
   <option value="<?php echo $user_level_index;?>" <?php selected($filter_value,$user_level_index); ?>>
     <?php
       echo esc_html($user_level);
      ?>
   </option>
   <?php
     }
    ?>
</select>
<input type="submit" class="button"  value="Filter">
<?php
}


add_action( 'admin_footer', 'viper_user_filter' );

function viper_user_filter() {
global $current_screen;
if ( 'users' != $current_screen->id ) {
return;
}
?>
<script type="text/javascript">
jQuery( document ).ready( function() {
jQuery('.user_level' ).first().change( function() {
jQuery('.user_level' ).last().val( jQuery( this ).val() );
});
jQuery( '.user_level' ).last().change( function() {
jQuery( '.user_level' ).first().val( jQuery( this ).val() );
});
});
</script>
<?php
}

add_filter( 'pre_get_users', 'viper_filter_users' );

function viper_filter_users( $query ) {
global $pagenow;
global $user_levels;
if ( is_admin() && 'users.php' == $pagenow && isset( $_GET['user_level'] ) ) {
$filter_value = $_GET['user_level'];
if ( !empty( $filter_value ) && array_key_exists( $_GET['user_level'],$user_levels ) ) {
$query->set( 'meta_key', 'user_level' );
$query->set( 'meta_query', array(array( 'key' => 'user_level','value' => $filter_value ) ) );
}
}
}


add_shortcode( 'vip', 'vip_user_access' );

function vip_user_access($atts,$content = null){

   if(is_user_logged_in()){

     $current_user = wp_get_current_user();

     $current_user_level = get_user_meta($current_user->ID,'user_level',true);

     if('vip' == $current_user_level || current_user_can('activate_plugins')){
       return '<div class="paid">'.$content.'</div>';
     }else{
       $output = '<div class="register">';
       $output .= '<b style="color:red;font-size:min(21px,5vw);">You need to be a VIP member to access ';
       $output .= 'this content.</b></div>';
       return $output;
     }
   }
      $output = '<div class="register">';
      $output .= '<b style="color:red;font-size:min(21px,5vw);">You need to be member to access ';
      $output .= 'this content.</b></div>';
      return $output;
}


global $user_levels;


$user_levels = array('regular' => 'Regular', 'vip' => 'VIP');
