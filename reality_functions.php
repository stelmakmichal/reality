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
				'id' => 'standard',
			
				// Meta box title - Will appear at the drag and drop handle bar. Required.
				'title' => 'Standard Fields',
			
				// Post types, accept custom post types as well - DEFAULT is array('post'). Optional.
				'pages' => array( 'post', 'page' ),
			
				// Where the meta box appear: normal (default), advanced, side. Optional.
				'context' => 'normal',
			
				// Order of meta box: high (default), low. Optional.
				'priority' => 'high',
			
				// List of meta fields
				'fields' => array(
					// TEXT
					array(
						// Field name - Will be used as label
						'name'  => 'Text',
						// Field ID, i.e. the meta key
						'id'    => "{$prefix}text",
						// Field description (optional)
						'desc'  => 'Text description',
						'type'  => 'text',
						// Default value (optional)
						'std'   => 'Default text value',
						// CLONES: Add to make the field cloneable (i.e. have multiple value)
						'clone' => true,
					),
					// CHECKBOX
					array(
						'name' => 'Checkbox',
						'id'   => "{$prefix}checkbox",
						'type' => 'checkbox',
						// Value can be 0 or 1
						'std'  => 1,
					),
					// RADIO BUTTONS
					array(
						'name'    => 'Radio',
						'id'      => "{$prefix}radio",
						'type'    => 'radio',
						// Array of 'value' => 'Label' pairs for radio options.
						// Note: the 'value' is stored in meta field, not the 'Label'
						'options' => array(
							'value1' => 'Label1',
							'value2' => 'Label2',
						),
					),
					// SELECT BOX
					array(
						'name'     => 'Select',
						'id'       => "{$prefix}select",
						'type'     => 'select',
						// Array of 'value' => 'Label' pairs for select box
						'options'  => array(
							'value1' => 'Label1',
							'value2' => 'Label2',
						),
						// Select multiple values, optional. Default is false.
						'multiple' => false,
					),
					// HIDDEN
					array(
						'id'   => "{$prefix}hidden",
						'type' => 'hidden',
						// Hidden field must have predefined value
						'std'  => 'Hidden value',
					),
					// PASSWORD
					array(
						'name' => 'Password',
						'id'   => "{$prefix}password",
						'type' => 'password',
					),
					// TEXTAREA
					array(
						'name' => 'Textarea',
						'desc' => 'Textarea description',
						'id'   => "{$prefix}textarea",
						'type' => 'textarea',
						'cols' => '20',
						'rows' => '3',
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
			
			// 2nd meta box
			$meta_boxes[] = array(
				'title' => 'Advanced Fields',
			
				'fields' => array(
					// NUMBER
					array(
						'name' => 'Number',
						'id'   => "{$prefix}number",
						'type' => 'number',
			
						'min'  => 0,
						'step' => 5,
					),
					// DATE
					array(
						'name' => 'Date picker',
						'id'   => "{$prefix}date",
						'type' => 'date',
			
						// jQuery date picker options. See here http://jqueryui.com/demos/datepicker
						'js_options' => array(
							'appendText'      => '(yyyy-mm-dd)',
							'dateFormat'      => 'yy-mm-dd',
							'changeMonth'     => true,
							'changeYear'      => true,
							'showButtonPanel' => true,
						),
					),
					// DATETIME
					array(
						'name' => 'Datetime picker',
						'id'   => $prefix . 'datetime',
						'type' => 'datetime',
			
						// jQuery datetime picker options. See here http://trentrichardson.com/examples/timepicker/
						'js_options' => array(
							'stepMinute'     => 15,
							'showTimepicker' => true,
						),
					),
					// TIME
					array(
						'name' => 'Time picker',
						'id'   => $prefix . 'time',
						'type' => 'time',
			
						// jQuery datetime picker options. See here http://trentrichardson.com/examples/timepicker/
						'js_options' => array(
							'stepMinute' => 5,
							'showSecond' => true,
							'stepSecond' => 10,
						),
					),
					// COLOR
					array(
						'name' => 'Color picker',
						'id'   => "{$prefix}color",
						'type' => 'color',
					),
					// CHECKBOX LIST
					array(
						'name' => 'Checkbox list',
						'id'   => "{$prefix}checkbox_list",
						'type' => 'checkbox_list',
						// Options of checkboxes, in format 'value' => 'Label'
						'options' => array(
							'value1' => 'Label1',
							'value2' => 'Label2',
						),
					),
					// TAXONOMY
					array(
						'name'    => 'Taxonomy',
						'id'      => "{$prefix}taxonomy",
						'type'    => 'taxonomy',
						'options' => array(
							// Taxonomy name
							'taxonomy' => 'category',
							// How to show taxonomy: 'checkbox_list' (default) or 'checkbox_tree', 'select_tree' or 'select'. Optional
							'type' => 'select_tree',
							// Additional arguments for get_terms() function. Optional
							'args' => array()
						),
					),
					// WYSIWYG/RICH TEXT EDITOR
					array(
						'name' => 'WYSIWYG / Rich Text Editor',
						'id'   => "{$prefix}wysiwyg",
						'type' => 'wysiwyg',
						'std'  => 'WYSIWYG default value',
			
						// Editor settings, see wp_editor() function: look4wp.com/wp_editor
						'options' => array(
							'textarea_rows' => 4,
							'teeny'         => true,
							'media_buttons' => false,
						),
					),
					// FILE UPLOAD
					array(
						'name' => 'File Upload',
						'id'   => "{$prefix}file",
						'type' => 'file',
					),
					// IMAGE UPLOAD
					array(
						'name' => 'Image Upload',
						'id'   => "{$prefix}image",
						'type' => 'image',
					),
					// THICKBOX IMAGE UPLOAD (WP 3.3+)
					array(
						'name' => 'Thichbox Image Upload',
						'id'   => "{$prefix}thickbox",
						'type' => 'thickbox_image',
					),
					// PLUPLOAD IMAGE UPLOAD (WP 3.3+)
					array(
						'name'             => 'Plupload Image Upload',
						'id'               => "{$prefix}plupload",
						'type'             => 'plupload_image',
						'max_file_uploads' => 4,
					),
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
  Styly a forma rozsirujucich poli
*****************************************************************************************************************/		


		public function reality_metabox_show() {
			
			// mytheme_add_box		-	reality_metabox_def
			// mytheme_show_box		-	reality_metabox_show
			// mytheme_save_data	-	reality_metabox_save
		/*
			global $meta_box, $post;
			
		    // Use nonce for verification
		    echo '<input type="hidden" name="reality_metabox_show_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
		    echo '<table class="form-table">';
		    
		    foreach ( $meta_box[ 'fields' ] as $field ) {
		    	
		        // get current post meta data
		        $meta = get_post_meta( $post->ID, $field[ 'id' ], true );
		        
		        echo '<tr>',
		                '<th style="width:20%"><label for="', $field[ 'id' ], '">', $field[ 'name' ], '</label></th>',
		                '<td>';
		        
				switch ( $field[ 'type' ] ) {
		            
					case 'text':
		                echo '<input type="text" name="', $field[ 'id' ], '" id="', $field[ 'id' ], '" value="', $meta ? $meta : $field[ 'std' ], '" size="30" style="width:97%" />', '<br />', $field[ 'desc' ];
		                break;
		            
					case 'textarea':
		                echo '<textarea name="', $field[ 'id' ], '" id="', $field[ 'id' ], '" cols="60" rows="4" style="width:97%">', $meta ? $meta : $field[ 'std' ], '</textarea>', '<br />', $field[ 'desc' ];
		                break;
		                
		            case 'select':
		                echo '<select name="', $field[ 'id' ], '" id="', $field[ 'id' ], '">';
		                foreach ( $field[ 'options' ] as $option ) {
		                    echo '<option ', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
		                }
		                echo '</select>';
		                break;
		                
		            case 'radio':
		                foreach ( $field[ 'options' ] as $option ) {
		                    echo '<input type="radio" name="', $field[ 'id' ], '" value="', $option[ 'value' ], '"', $meta == $option[ 'value' ] ? ' checked="checked"' : '', ' />', $option[ 'name' ];
		                }
		                break;
		                
		            case 'checkbox':
		                echo '<input type="checkbox" name="', $field[ 'id' ], '" id="', $field[ 'id' ], '"', $meta ? ' checked="checked"' : '', ' />';
		                break;

		        }
		        
		        echo     '</td><td>',
		            '</td></tr>';
		       
		    }
		    
		    echo '</table>';
			
		*/	
	    }


/*****************************************************************************************************************
  Uklada udaje z poli do wp_post_meta pre kazdy clanok
*****************************************************************************************************************/		


		public function reality_metabox_save( $post_id ) {
			
			// mytheme_add_box		-	reality_metabox_def
			// mytheme_show_box		-	reality_metabox_show
			// mytheme_save_data	-	reality_metabox_save
		/*
			global $meta_box;
			
		    // verify nonce
		    if ( ! wp_verify_nonce( $_POST[ 'reality_metabox_show_nonce' ], basename(__FILE__) ) ) { return $post_id; }
		    // check autosave
		    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return $post_id; }
		    
		    // check permissions
		    if ( 'page' == $_POST[ 'post_type' ] ) {
		        if ( ! current_user_can( 'edit_page', $post_id ) ) {
		        	return $post_id;
		        }
		    } 
		    elseif ( ! current_user_can( 'edit_post', $post_id ) ) { return $post_id; }
		    
		    foreach ( $meta_box[ 'fields' ] as $field ) {
		    	
		        $old = get_post_meta( $post_id, $field[ 'id' ], true );
		        $new = $_POST[ $field[ 'id' ] ];

		        if ( $new && $new != $old ) { update_post_meta( $post_id, $field[ 'id' ], $new ); } 
		        elseif ( '' == $new && $old ) { delete_post_meta( $post_id, $field[ 'id' ], $old ); }
		        
		    }
			*/
	    }


/*****************************************************************************************************************
  Registracia globalnej premennej a vytvorenie objektu triedy
*****************************************************************************************************************/		

	    
	}	
  
}
	    

global $reality;
if ( class_exists( 'reality' ) && ! $reality ) { $reality = new reality(); }	


?>