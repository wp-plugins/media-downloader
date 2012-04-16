<?php

global $mdmarkupsettings, $mdtags, $mdmarkuptemplates;
$mdoptions = array();
foreach( $mdmarkupsettings as $mdmarkupsetting => $mdsanitizefunction ) $mdoptions[$mdmarkupsetting] = get_option( $mdmarkupsetting );

?>


<div class="wrap">

<?php include('mediadownloader-options-header.php'); ?>

<form method="post" action="options.php">
<?php settings_fields( 'md_markup_options' ); ?>

<fieldset id="mdf_replaceheaders">

<h2><?php _mde('General tag info template'); ?></h2>

<p>
<?php
$markuptemplate = $mdoptions['markuptemplate'];
if ( !sanitizeMarkupTemplate( $markuptemplate ) ) $markuptemplate = array_shift( array_keys( $mdmarkuptemplates ) );
?>
<?php foreach ( $mdmarkuptemplates as $key => $value ) : ?>
    <label for="md_markuptemplate_<?php echo $key; ?>"><input type="radio" name="markuptemplate" id="md_markuptemplate_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php if ( $key == $markuptemplate ) : ?>checked="checked"<?php endif; ?> /> <?php _mde( $value ) ;?></label> <br />
<?php endforeach; ?>
</p>

<h2><?php _mde('List options'); ?></h2>

<h3><?php _mde( 'Replace ID3 tags column headers' ); ?></h3>

<p>
<label for="md_replaceheaders"><?php _mde( 'Replaces default columns headers (ie: "title", "comments") with custom values:' ) ;?></label> <br />
<textarea name="replaceheaders" id="md_replaceheaders" cols="75" rows="10"><?php echo $mdoptions['replaceheaders'] ;?></textarea> <br />
<small>Syntax example: <br /><code>comments:Description</code><br /><code>title:Episode</code></small>
</p>

<p class="submit">
<input type="submit" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

<hr />

<fieldset id="mdf_downloadtext">

<h2><?php _mde('Each item options'); ?></h2>

<h4><?php _mde( 'Wildcards: ' ); ?> <code>[<?php echo implode( ']</code>, <code>[', $mdtags ) ;?>]</code>.</h4>

<p>
<label for="md_downloadtext"><?php _mde( 'Download Text:' ) ;?></label> <br />
<input type="text" name="downloadtext" id="md_downloadtext" value="<?php echo $mdoptions['downloadtext'] ;?>" size="75" />
<small>Default: <i><code>Download: [title]</code></i></small>
</p>

<p>
<label for="md_playtext"><?php _mde( 'Play Text:' ) ;?></label> <br />
<input type="text" name="playtext" id="md_playtext" value="<?php echo $mdoptions['playtext'] ;?>" size="75" />
<small>Default: <i><code>Play: [title]</code></i></small>
</p>

<p class="submit">
<input type="submit" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

</form>

