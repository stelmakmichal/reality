<?php
	
// Zamedzenie pokusu o neopravneny pristup
if ( ! function_exists( 'is_admin' ) ) { header( 'Status: 403 Forbidden' ); header( 'HTTP/1.1 403 Forbidden' ); exit(); }

$title = 'Nastavenia zásuvného modulu';

//#1 <div id="icon-edit" class="icon32"></div>
//#2 <div id="icon-upload" class="icon32"></div>
//#3 <div id="icon-link-manager" class="icon32"></div>
//#4 <div id="icon-edit-pages" class="icon32"></div>
//#5 <div id="icon-edit-comments" class="icon32"></div>
//#6 <div id="icon-themes" class="icon32"></div>
//#7 <div id="icon-plugins" class="icon32"></div>
//#8 <div id="icon-users" class="icon32"></div>
//#9 <div id="icon-tools" class="icon32"></div>
//#10 <div id="icon-options-general" class="icon32"></div>

?>

<div class="wrap">  
  <div id="icon-options-general" class="icon32"></div>
  <h2><?php echo esc_html( $title ); ?></h2>
  <div class="updated"><p>Zásuvný modul Reality GAKOS sa momentálne nachádza v štádiu vývoja, a tak môžu byť niektoré jeho súčasti čiastočne alebo úplne nefunkčné.</p></div>
</div>