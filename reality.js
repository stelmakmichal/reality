jQuery(document).ready(function() {
  //hide all blocks
  hideBlocks();

  //show block on load website
  jQuery('#'+jQuery('#reality_typ_nehnutelnosti').val()).show();

  //action on change
  jQuery('#reality_typ_nehnutelnosti').live('change',function(){
    //hide all blocks
    hideBlocks();
    //show current block to reality
    jQuery('#'+jQuery(this).val()).show();
  });

});

function hideBlocks () {
  jQuery('#reality_typ_nehnutelnosti option').each(function( index ) {
    jQuery('#'+jQuery(this).val()).hide();
  });
}