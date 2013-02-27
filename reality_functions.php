<?php

/*
Plugin Name: Reality modul
Plugin URI: http://www.gakosreal.sk
Description: Zásuvný modul pre realitnú kanceláriu GA-KOS s.r.o., rozširujúci základné vlastnosti systému Wordpress o správu nehnuteľností a realitných maklérov.
Version: 1.0
Author: Lukáš Staroň
Author URI: lukas.staron@gmail.com
*/

// Zamedzenie pokusu o neopravneny pristup
if ( ! function_exists( 'is_admin' ) ) { header( 'Status: 403 Forbidden' ); header( 'HTTP/1.1 403 Forbidden' ); exit(); }

// Definicie ciest 
if ( ! defined( 'WP_CONTENT_URL' ) ) { define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );}
if ( ! defined( 'WP_CONTENT_DIR' ) ) { define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );}
if ( ! defined( 'REALITY_URL' ) ) { define( 'REALITY_URL', WP_PLUGIN_URL . '/reality' );}
if ( ! defined( 'REALITY_DIR' ) ) { define( 'REALITY_DIR', WP_PLUGIN_DIR . '/reality' );}

if ( ! class_exists( 'reality' ) ) {

	
	class reality {
		

		// Konstruktor
		function __construct() {
			
			global $wpdb;
		      
			// Aktivuje plugin
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			
			// Deaktivuje plugin
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );	
			
			// Zaregistruje akciu pre vztvorenie menu pluginu v amdinistracii
			add_action( 'admin_menu', array( &$this, 'menu' ) );
			
			// Prepise existujuce nazvy poloziek administracneho menu na nove tvary
			add_filter('gettext', array( &$this, 'rename_admin_menu_items' ) ) ;
			add_filter('ngettext', array( &$this, 'rename_admin_menu_items' ) ) ;
			
			// Rozsirenie profilu pouzivatelov o nove polozky
			add_action( 'show_user_profile', array( &$this, 'user_profile' ) ) ;
			add_action( 'edit_user_profile', array( &$this, 'user_profile' ) ) ;		
			add_action( 'personal_options_update', array( &$this, 'save_user_profile' ) ) ;
			add_action( 'edit_user_profile_update', array( &$this, 'save_user_profile' ) ) ;

		}
		
		
		
		// Aktivacia zasuvneho modulu
		public function activate() {
			
			global $wpdb;
			
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {

	  			if ( isset( $_GET[ 'networkwide' ] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
	  				
	  				$old_blog = $wpdb->blogid;
	  				$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
	  				
	  				foreach ( $blogids as $blog_id ) {
	  					switch_to_blog( $blog_id );
	  				}
	  				
	  				switch_to_blog( $old_blog );
	  				return;
	  				
	  			}	
	  		
			} 
				
		}
		
		
		
		// Deaktivacia zasuvneho modulu
		public function deactivate() {
			
			global $wpdb;
			
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
  				
				if ( isset( $_GET[ 'networkwide'] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
  					
					$old_blog = $wpdb->blogid;
  					$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
  					
  					foreach ( $blogids as $blog_id ) {
  						switch_to_blog( $blog_id );
  					}
  			
  					switch_to_blog( $old_blog );
  					return;
  				
				}	
  			
			} 
				
		}
		
		

		// Vytvorenie navigacie v administracii
		public function menu() {
			
			// Pridaj menu na top urovni
	    	add_menu_page( 'Reality GAKOS', 'Reality GAKOS', 'manage_options', 'menu', array( &$this, 'reality_main' ), REALITY_URL.'/img/reality_icon_16x16.png' );
	    	
	      	// Pridaj submenu
	      	add_submenu_page( 'menu', 'Nastavenia', 'Nastavenia', 'manage_options', 'options.php', array( &$this, 'reality_options' ) );	
	      
		}	
	  	
		
		
	  	public function reality_main() { include_once( 'reality_main.php' ); }
	    public function reality_options() { include_once ( 'reality_options.php' );	}
	  	
	    
	    
	  	// Premenovanie poloziek administracneho menu
		public function rename_admin_menu_items( $menu ) {
			
			$menu = str_ireplace( 'Články', 'Nehnuteľnosti', $menu );
			$menu = str_ireplace( 'články', 'nehnuteľnosti', $menu );
			$menu = str_ireplace( 'Používatelia', 'Makléri', $menu );
			$menu = str_ireplace( 'používatelia', 'makléri', $menu );
			$menu = str_ireplace( 'používateľa', 'makléra', $menu );
			
			return $menu;

		}
		
		
		
		// Rozsirenie profilu pouzivatelov o nove polia
		public function user_profile( $user ) {
			?>
			
			<h3>Rozširujúce kontaktné údaje</h3>
 
			<table class='form-table'>
			    <tr>
			        <th><label for='street'>Ulica</label></th> 
			        <td> 
			            <input type='text' name='street' id='street' value='<?php echo esc_attr( get_the_author_meta( 'street', $user->ID ) ); ?>' class='regular-text' />  
			            <span class='description'>Zadajte ulicu a číslo ulice <i>(vhodný tvar: Košícka 10)</i></span> 
			        </td> 
				</tr> 
				<tr> 
					<th><label for='city'>Mesto</label></th> 
			        <td> 
			            <input type='text' name='city' id='city' value='<?php echo esc_attr( get_the_author_meta( 'city', $user->ID ) ); ?>' class='regular-text' />  
			            <span class='description'>Zadajte názov mesta</span> 
			        </td> 
			    </tr> 
				<tr> 
					<th><label for='postal_code'>PSČ</label></th> 
			        <td> 
			            <input type='text' name='postal_code' id='postal_code' value='<?php echo esc_attr( get_the_author_meta( 'postal_code', $user->ID ) ); ?>' class='regular-text' />  
			            <span class='description'>Zadajte poštové smerovacie číslo <i>(vhodný tvar: 04001)</i></span> 
			        </td> 
			    </tr> 
				<tr> 
					<th><label for='country'>Krajina</label></th> 
			        <td> 
			            <input type='text' name='country' id='country' value='<?php echo esc_attr( get_the_author_meta( 'country', $user->ID ) ); ?>' class='regular-text' />  
			            <span class='description'>Zadajte krajinu <i>(vhodný tvar: Slovenská republika)</i></span> 
			        </td> 
				</tr> 
				<tr> 
					<th><label for='phone'>Telefónne číslo</label></th> 
			        <td> 
			            <input type='text' name='phone' id='phone' value='<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>' class='regular-text' />  
			            <span class='description'>Zadajte telefónne číslo <i>(vhodný tvar: 0900 123 456, 055 1234 567)</i></span> 
			        </td> 
			    </tr> 
			</table>
		<?php 	

		}
		
		
		// Ulozenie obsahu novych poli do db
		public function save_user_profile( $user_id ) {
			
			if ( current_user_can( 'edit_user', $user_id ) ){

	   			update_user_meta( $user_id, 'street', $_POST['street'] );
	   			update_user_meta( $user_id, 'city', $_POST['city'] );
	   			update_user_meta( $user_id, 'postal_code', $_POST['postal_code'] );
	   			update_user_meta( $user_id, 'country', $_POST['country'] );
	   			update_user_meta( $user_id, 'phone', $_POST['phone'] );
   			
			}	
			
		}
  
	}
	
}

// Registracia globalnej premennej a vytvorenie objektu triedy
global $reality;
if ( class_exists( 'reality' ) && ! $reality ) { $reality = new reality(); }	

?>
