<?php

/***************************************************************************************************************** 
	Plugin Name: Reality modul
	Plugin URI: http://www.gakosreal.sk
	Description: Zásuvný modul pre realitnú kanceláriu GA-KOS s.r.o., rozširujúci základné vlastnosti systému Wordpress o správu nehnuteľností a realitných maklérov.
	Version: 1.0
	Author: Lukáš Staroň
	Author URI: lukas.staron@gmail.com
*****************************************************************************************************************/


/***************************************************************************************************************** 
	Zakladne opatrenia a definicie
*****************************************************************************************************************/
 
 
// Zamedzenie pokusu o neopravneny pristup
if ( ! function_exists( 'is_admin' ) ) { header( 'Status: 403 Forbidden' ); header( 'HTTP/1.1 403 Forbidden' ); exit(); }

// Definicie ciest 
if ( ! defined( 'WP_CONTENT_URL' ) ) 	{ define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );}
if ( ! defined( 'WP_CONTENT_DIR' ) ) 	{ define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );}
if ( ! defined( 'REALITY_URL' ) ) 		{ define( 'REALITY_URL', WP_PLUGIN_URL . '/reality' );}
if ( ! defined( 'REALITY_DIR' ) ) 		{ define( 'REALITY_DIR', WP_PLUGIN_DIR . '/reality' );}
if ( ! defined( 'RWMB_URL' ) ) 			{ define( 'RWMB_URL', trailingslashit( REALITY_URL . '/meta-box' ) );}
if ( ! defined( 'RWMB_DIR' ) ) 			{ define( 'RWMB_DIR', trailingslashit( REALITY_DIR . '/meta-box' ) );}


/***************************************************************************************************************** 
  Zadefinovanie tried, jej argumentov a metod:
  --------------------------------------------
  _construct - registruje vsetky akcie pluginu, vytvara defaultne nastavenia a pod.
  activate - aktivuje plugin
  deactivate - deaktivuje plugin
  menu - registruje navigaciu pluginu v administracii
  reality_main - inkluduje subor so zakladnymi popismi pluginu, ...
  reality_options - inkluduje subor, ktory sprostredkuva zakladne nastavenia pluginu
  rename_admin_menu_items - upravuje nazvy poloziek menu na mieru
  user_profile - rozsiruje a upravuje profily pouzivatelov
  save_user_profile - stara sa o ukladanie hodnot doplnenych poli do databazy - wp_user_meta 
  reality_metabox_add - zaregistrovanie rozsirujuceho metaboxu vo WordPresse
  reality_metabox_def - definovanie struktury metaboxu, kt. pridava nove polia do administracie clankov
  reality_metabox_show - styly a forma rozsirujucich poli
  reality_metabox_save - uklada udaje z poli do wp_post_meta pre kazdy clanok
  
*****************************************************************************************************************/
 

if ( ! class_exists( 'reality' ) ) {
	
	class reality {
  

/*****************************************************************************************************************
  Konstruktor
*****************************************************************************************************************/
	
	  
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
			
			// Rozsirenie administracie clankov o extra polia
			$metabox_base_script = RWMB_DIR . 'meta-box.php';
	    	if ( ! file_exists( $metabox_base_script ) ) { wp_die( "Súbor {$metabox_base_script} neexistuje. Skontrolujte cesty!" ); } else { require_once $metabox_base_script; }

	    	$this->reality_metabox_add();
	    	add_action( 'admin_init', array( &$this, 'reality_metabox_def' ) ) ;
	    	
		}
		
			
    	
/*****************************************************************************************************************
  Aktivacia zasuvneho modulu
*****************************************************************************************************************/		
	
  
	    public function activate() {
			
			global $wpdb;
			
	    	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		  		
	        	if ( isset( $_GET[ 'networkwide' ] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
		  				
	            	$old_blog = $wpdb->blogid;
		  			$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
		  				
	            	foreach ( $blogids as $blog_id ) { switch_to_blog( $blog_id ); }
	            
		  			switch_to_blog( $old_blog );
		  			return;
		  		
	        	}	
			
	    	}
	    	 
		}
			
		
/*****************************************************************************************************************
  Deaktivacia zasuvneho modulu
*****************************************************************************************************************/		


		public function deactivate() {
				
			global $wpdb;
				
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
					
				if ( isset( $_GET[ 'networkwide'] ) && ( $_GET[ 'networkwide' ] == 1 ) ) {
		         
					$old_blog = $wpdb->blogid;
		  			$blogids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs" ) );
		  				
					foreach ( $blogids as $blog_id ) { witch_to_blog( $blog_id ); }
		  			
					switch_to_blog( $old_blog );
		  			return;
						
		        }	
				
	      	} 
			
	    }
			
		
/*****************************************************************************************************************
  Vytvorenie navigacie v administracii
*****************************************************************************************************************/


		public function menu() {
	    
			// Pridaj menu na top urovni
			add_menu_page( 'Reality GAKOS', 'Reality GAKOS', 'manage_options', 'menu', array( &$this, 'reality_main' ), REALITY_URL.'/img/reality_icon_16x16.png' );
		    
			// Pridaj submenu
		    add_submenu_page( 'menu', 'Nastavenia', 'Nastavenia', 'manage_options', 'options.php', array( &$this, 'reality_options' ) );	
			
	    }	
	  	
      
/*****************************************************************************************************************
  Volania suboru zakladnych informacii o plugine
*****************************************************************************************************************/


		public function reality_main() { include_once( 'reality_main.php' ); }
    
    
/*****************************************************************************************************************
  Volanie suboru nastaveni pluginu
*****************************************************************************************************************/
    
   
		public function reality_options() { include_once ( 'reality_options.php' );	}
	  	
	    
/*****************************************************************************************************************
  Premenovanie poloziek administracneho menu
*****************************************************************************************************************/	    


		public function rename_admin_menu_items( $menu ) {
			
			$menu = str_ireplace( 'Články', 'Nehnuteľnosti', $menu );
			$menu = str_ireplace( 'články', 'nehnuteľnosti', $menu );
			$menu = str_ireplace( 'Používatelia', 'Makléri', $menu );
			$menu = str_ireplace( 'používatelia', 'makléri', $menu );
			$menu = str_ireplace( 'používateľa', 'makléra', $menu );
				
			return $menu;
	      
		}
			
    
/*****************************************************************************************************************
  Rozsirenie profilu pouzivatelov o nove polia
*****************************************************************************************************************/

	
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
			
    
/*****************************************************************************************************************
  Ulozenie obsahu novych poli do db
*****************************************************************************************************************/		


		public function save_user_profile( $user_id ) {
			
			if ( current_user_can( 'edit_user', $user_id ) ){
		   		         
				update_user_meta( $user_id, 'street', $_POST['street'] );
		   		update_user_meta( $user_id, 'city', $_POST['city'] );
		   		update_user_meta( $user_id, 'postal_code', $_POST['postal_code'] );
		   		update_user_meta( $user_id, 'country', $_POST['country'] );
		   		update_user_meta( $user_id, 'phone', $_POST['phone'] );
				
	      	}	
			
	    }


/*****************************************************************************************************************
  Zaregistrovanie rozsirujuceho metaboxu vo WordPresse
*****************************************************************************************************************/		

    
		public function reality_metabox_add() {
			
			// mytheme_add_box		-	reality_metabox_def
			// mytheme_show_box		-	reality_metabox_show
			// mytheme_save_data	-	reality_metabox_save
			
			$prefix = 'reality_';

			global $meta_boxes;
			
			$meta_boxes = array();
			
			// 1st meta box
			$meta_boxes[] = array(
				// Meta box id, UNIQUE per meta box. Optional since 4.1.5
				'id' => 'byty',
			
				// Meta box title - Will appear at the drag and drop handle bar. Required.
				'title' => 'Rozširujúce informácie o nehnuteľnosti',
			
				// Post types, accept custom post types as well - DEFAULT is array('post'). Optional.
				'pages' => array( 'post' ),
			
				// Where the meta box appear: normal (default), advanced, side. Optional.
				'context' => 'normal',
			
				// Order of meta box: high (default), low. Optional.
				'priority' => 'high',
			
				// List of meta fields
				'fields' => array(
					// SELECT BOX
					array(
						'name'     => 'Nehnuteľnosť na',
						'id'       => "{$prefix}typ",
						'type'     => 'select',
						// Array of 'value' => 'Label' pairs for select box
						'options'  => array(
							'value1' => 'Predaj',
							'value2' => 'Prenájom',
						),
						// Select multiple values, optional. Default is false.
						'multiple' => false,
					),
					// TEXT
					array(
						// Field name - Will be used as label
						'name'  => 'Kraj',
						// Field ID, i.e. the meta key
						'id'    => "{$prefix}kraj",
						// Field description (optional)
						'desc'  => 'Text description',
						'type'  => 'text',
						// Default value (optional)
						'std'   => 'Default text value',
						// CLONES: Add to make the field cloneable (i.e. have multiple value)
						'clone' => false,
					),
					array(
						// Field name - Will be used as label
						'name'  => 'Okres',
						// Field ID, i.e. the meta key
						'id'    => "{$prefix}okres",
						// Field description (optional)
						'desc'  => 'Text description',
						'type'  => 'text',
						// Default value (optional)
						'std'   => 'Default text value',
						// CLONES: Add to make the field cloneable (i.e. have multiple value)
						'clone' => false,
					),
					array(
						// Field name - Will be used as label
						'name'  => 'Mesto',
						// Field ID, i.e. the meta key
						'id'    => "{$prefix}mesto",
						// Field description (optional)
						'desc'  => 'Text description',
						'type'  => 'text',
						// Default value (optional)
						'std'   => 'Default text value',
						// CLONES: Add to make the field cloneable (i.e. have multiple value)
						'clone' => false,
					),
					// NUMBER
					array(
						'name' => 'Cena od',
						'id'   => "{$prefix}cena_od",
						'type' => 'number',
			
						'min'  => 0,
						'step' => 1000,
					),
					// NUMBER
					array(
						'name' => 'Cena do',
						'id'   => "{$prefix}cena_do",
						'type' => 'number',
			
						'min'  => 0,
						'step' => 1000,
					),
					// NUMBER
					array(
						'name' => 'Výmer od',
						'id'   => "{$prefix}vymera_od",
						'type' => 'number',
			
						'min'  => 0,
						'step' => 5,
					),
					// NUMBER
					array(
						'name' => 'Vymera do do',
						'id'   => "{$prefix}vymera_do",
						'type' => 'number',
			
						'min'  => 0,
						'step' => 5,
					),
					// CHECKBOX LIST
					array(
						'name' => 'Počet izieb',
						'id'   => "{$prefix}pocet_izieb",
						'type' => 'checkbox_list',
						// Options of checkboxes, in format 'value' => 'Label'
						'options' => array(
							'value1' => '1',
							'value2' => '2',
							'value3' => '3',
							'value4' => '4',
							'value5' => '5 a viac',
							'value6' => 'garzónka',
						),
					),
					// CHECKBOX
					array(
						'name' => 'Loggia/balkón',
						'id'   => "{$prefix}loagia_balkon",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'Výťah',
						'id'   => "{$prefix}vytah",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'Osobné vlastníctvo',
						'id'   => "{$prefix}osobne_vlastnictvo",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'Tehlové byty',
						'id'   => "{$prefix}tehlove_byty",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'prizemie',
						'id'   => "{$prefix}prizemie",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => '1. poschodie',
						'id'   => "{$prefix}poschodie_1",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => '2. - 5. poschodie',
						'id'   => "{$prefix}poschodie_2_5",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => '6. - predposledné',
						'id'   => "{$prefix}poschodie_6_predposledne",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'Posledné poschodie',
						'id'   => "{$prefix}posledne_poschodie",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'pôvodný stav',
						'id'   => "{$prefix}povodny_stav",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'novostavba',
						'id'   => "{$prefix}novostavba",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'rekonštrukcia',
						'id'   => "{$prefix}rekonstrukcia",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'čiastočná rekon.',
						'id'   => "{$prefix}ciastocna_rekon",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// CHECKBOX
					array(
						'name' => 'len ponuky s fotografiou',
						'id'   => "{$prefix}len_s_foto",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 0,
					),
					// SELECT BOX
					array(
						'name'     => 'Maklér',
						'id'       => "{$prefix}makler",
						'type'     => 'select',
						// Array of 'value' => 'Label' pairs for select box
						'options'  => array(
							'value1' => 'Meno1 Priezvisko1',
							'value2' => 'Meno2 Priezvisko2',
							'value3' => 'Meno3 Priezvisko3',
							'value4' => 'Meno4 Priezvisko4',
							'value5' => 'Meno5 Priezvisko5',
							'value6' => 'Meno6 Priezvisko6',
							'value7' => 'Meno7 Priezvisko7',
							'value8' => 'Meno8 Priezvisko8',
						),
						// Select multiple values, optional. Default is false.
						'multiple' => false,
					),
				),
				'validation' => array(
					'rules' => array(
						"{$prefix}password" => array(
							'required'  => true,
							'minlength' => 7,
						),
					),
					// optional override of default jquery.validate messages
					'messages' => array(
						"{$prefix}password" => array(
							'required'  => 'Password is required',
							'minlength' => 'Password must be at least 7 characters',
						),
					)
				)
			);

    	}


/*****************************************************************************************************************
  Definovanie struktury metaboxu, kt. pridava nove polia do administracie clankov
*****************************************************************************************************************/		


		public function reality_metabox_def() {
			
			// mytheme_add_box		-	reality_metabox_def
			// mytheme_show_box		-	reality_metabox_show
			// mytheme_save_data	-	reality_metabox_save
			
			// Make sure there's no errors when the plugin is deactivated or during upgrade
			global $meta_boxes;
			if ( !class_exists( 'RW_Meta_Box' ) ) return;
			foreach ( $meta_boxes as $meta_box ) { new RW_Meta_Box( $meta_box ); }
			
		}


/*****************************************************************************************************************
  Registracia globalnej premennej a vytvorenie objektu triedy
*****************************************************************************************************************/		

	    
	}	
  
}
	    

global $reality;
if ( class_exists( 'reality' ) && ! $reality ) { $reality = new reality(); }	


?>