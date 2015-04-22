<?php

global $mdmarkupsettings, $mdtags, $mdmarkuptemplates;
$mdoptions = array();
foreach( $mdmarkupsettings as $mdmarkupsetting => $mdsanitizefunction ) $mdoptions[$mdmarkupsetting] = get_option( $mdmarkupsetting );

?>


<div class="wrap">

<?php include( dirname( __FILE__ ) . '/mediadownloader-options-header.php' ); ?>

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

<h2><?php _mde( 'List options' ); ?></h2>

<h3><?php _mde( 'Replace ID3 tags column headers' ); ?></h3>

<p>
<label for="md_replaceheaders"><?php _mde( 'Replaces default columns headers (ie: "title", "comments") with custom values:' ) ;?></label> <br />
<textarea name="replaceheaders" id="md_replaceheaders" cols="75" rows="10"><?php echo $mdoptions['replaceheaders'] ;?></textarea> <br />
<small><?php _mde( 'Syntax example: <br /><code>comments:Description</code><br /><code>title:Episode</code>' ); ?></small>
</p>

<p class="submit">
<input type="submit" class="button button-primary" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

<hr />

<fieldset>

<h2><?php _mde( 'Cover image markup' ); ?></h2>

<h4><label for="md_covermarkup"><?php _mde( 'Wildcards:' ) ;?> <code>[coverimage]</code></label></h4>
<p>
<input type="text" name="covermarkup" id="md_covermarkup" value="<?php echo esc_attr( $mdoptions['covermarkup'] ) ;?>" size="75" />
<small><?php _mde( 'Default:' ); ?> <i><code>&lt;img class="md_coverImage" src="[coverimage]" alt="<?php _mde( 'Album Cover' ); ?>" /&gt;</code></i></small>
</p>

<p class="submit">
<input type="submit" class="button button-primary" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

<hr />

<?php
$pexts = md_packageExtensions();
if ( count( $pexts ) ) :
    ?>
    <fieldset>
    <h2><?php _mde( 'Links for compacted files' ); ?></h2>
    
    <h3><label for="md_packagetitle"><?php _mde( 'File list title:' ); ?></label></h3>
    <p>
    <input type="text" name="packagetitle" id="md_packagetitle" value="<?php echo esc_attr( $mdoptions['packagetitle'] ) ;?>" size="75" /><br />
    <small><?php _mde( 'Example:' ); ?> <i><code><?php _mde( 'Compacted Files' ); ?></code></i></small>
    </p>
    
    <h3><?php _mde( 'Links texts:' ); ?></h3>
    <?php foreach ( $pexts as $pext ) : ?>
        <p>
        <label for="md_packagetext_<?php echo $pext; ?>"><?php printf( _md( '<code>%s</code> file link text:' ), $pext ) ;?></label> <br />
        <input type="text" name="packagetexts[<?php echo $pext; ?>]" id="md_packagetext_<?php echo $pext; ?>" value="<?php echo esc_attr( $mdoptions['packagetexts'][$pext] ) ;?>" size="75" /> <small><?php _mde( 'Default:' ); ?> <i><code><?php _mde( 'Download ' . strtoupper( $pext ) ); ?></code></i></small>
        </p>
    <?php endforeach; ?>
    <h4><?php _mde( 'Wildcards:' ) ;?> <code>[filename]</code></h4>
    
    <p class="submit">
    <input type="submit" class="button button-primary" value="<?php _mde( 'Update Options' ) ;?>" />
    </p>
    </fieldset>

    <hr />
<?php endif; ?>

<fieldset id="mdf_downloadtext">

<h2><?php _mde( 'Each item options' ); ?></h2>

<h4><?php _mde( 'Wildcards:' ); ?> <code>[<?php echo implode( ']</code>, <code>[', $mdtags ) ;?>]</code>.</h4>

<p>
<label for="md_downloadtext"><?php _mde( 'Download Text:' ) ;?></label> <br />
<input type="text" name="downloadtext" id="md_downloadtext" value="<?php echo esc_attr( $mdoptions['downloadtext'] ) ;?>" size="75" />
<small><?php _mde( 'Default:' ); ?> <i><code>Download: [title]</code></i></small>
</p>

<p>
<label for="md_playtext"><?php _mde( 'Play Text:' ) ;?></label> <br />
<input type="text" name="playtext" id="md_playtext" value="<?php echo esc_attr( $mdoptions['playtext'] ) ;?>" size="75" />
<small><?php _mde( 'Default:' ); ?> <i><code>Play: [title]</code></i></small>
</p>

<p>
<label for="md_stoptext"><?php _mde( 'Stop Text:' ) ;?></label> <br />
<input type="text" name="stoptext" id="md_stoptext" value="<?php echo esc_attr( $mdoptions['stoptext'] ) ;?>" size="75" />
<small><?php _mde( 'Default:' ); ?> <i><code>Stop: [title]</code></i></small>
</p>

<p class="submit">
<input type="submit" class="button button-primary" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

</form>

