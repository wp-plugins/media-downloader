<?php

global $mdembedplayerdefaultcolors;
$mdmoreoptions = array();
foreach( $mdembedplayerdefaultcolors as $mdcolor => $mddefault ) $mdmoreoptions[$mdcolor] = get_option( $mdcolor . '_embed_color' );

$vazio = true;
foreach( $mdembedplayerdefaultcolors as $mdcolor => $mddefault ) 
  if ( trim( $mdmoreoptions[$mdcolor] ) ) {
    $vazio = false;
    break;
  }

?>


<div class="wrap">

<?php include('mediadownloader-options-header.php'); ?>

<form method="post" action="options.php">
<?php settings_fields( 'md_more_options' ); ?>

<fieldset id="mdf_embedcolors">
<h3><?php _mde( 'Embed Player Colors:' ) ;?></h3>

<?php foreach ( $mdembedplayerdefaultcolors as $mdcolor => $mddefault ) : ?>
<p>
<label for="md_<?php echo esc_attr( $mdcolor ); ?>"><em>"<?php echo $mdcolor; ?>"</em> <?php _mde( 'color:' ) ;?> </label>
<input type="text" name="<?php echo esc_attr( $mdcolor ); ?>_embed_color" id="md_<?php echo esc_attr( $mdcolor ); ?>" value="<?php echo $mdmoreoptions[$mdcolor]; ?>" size="7" maxlength="6" />
<small><?php _mde( 'Default:' ) ;?> <code><?php echo $mddefault; ?></code></small>
</p>
<?php endforeach; ?>

<p class="submit">
<input type="submit" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

</form>


</div>
