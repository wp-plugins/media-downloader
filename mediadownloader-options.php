<?php

global $mdsettings, $mdencodings, $mdtags;
$mdoptions = array();
foreach( $mdsettings as $mdsetting => $mdsanitizefunction ) $mdoptions[$mdsetting] = get_option( $mdsetting );

?>


<div class="wrap">
<h2><?php _mde( 'Media Downloader' ) ;?></h2>

<form method="post" action="options.php">
<?php settings_fields( 'md_options' ); ?>

<fieldset id="mdf_mp3folder">
<h3><label for="md_mp3folder"><?php _mde( 'MP3 Folder:' ) ;?></label></h3>
<p>
<label for="md_mp3folder"><code><?php echo ABSPATH ;?></code></label>
<input type="text" name="mp3folder" id="md_mp3folder" value="<?php echo $mdoptions['mp3folder'] ;?>" size="20" />

<?php if( '' != trim( $mdoptions['mp3folder'] ) ){
    $dirok = is_readable( ABSPATH . '/' . $mdoptions['mp3folder'] ) ;?>
    <br /><small style="color:#999;background-color:#<?php echo $dirok ? 'DFD' : 'FDD' ;?>"><?php _mde( $dirok ? 'Folder successfully read.' : 'Could not read folder.') ;?></small>
<?php };?>

</p>

<p class="submit">
<input type="submit" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

<hr />

<fieldset id="mdf_customcss">
<h3><label for="md_showtags"><?php _mde( 'Show MP3 Info:' ) ;?></label></h3>
<p>
<?php _mde( 'Comma-separated MP3 info to show for each file on the list.' ) ;?><br />
<input type="text" id="md_showtags" name="showtags" size="75" value="<?php echo $mdoptions['showtags'] ;?>" />
<br />
<small><?php _mde( 'Possible values:' ) ;?> <code><?php echo implode( '</code>, <code>', $mdtags ) ;?></code>.</small>
<br />
<small><?php _mde( 'Default:' ) ;?> <code><?php echo $mdtags[0] ;?></code>.</small>
</p>

<h3><label for="md_customcss"><?php _mde( 'Custom CSS:' ) ;?></label></h3>
<p>
<textarea id="md_customcss" name="customcss" cols="75" rows="7"><?php echo $mdoptions['customcss'] ;?></textarea>
</p>

<p class="submit">
<input type="submit" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

<hr />

<fieldset id="mdf_advanced">
<h3><?php _mde( 'Advanced:' ) ;?></h3>
<p>
<input type="checkbox" name="removeextension" id="md_removeextension" value="1" <?php if ( $mdoptions['removeextension'] ) echo ' checked="checked" ' ;?> />
<label for="md_removeextension">
<?php _mde( 'Remove ".mp3" from download URL' ) ;?>
<br />
<small><?php _mde( '(it may cause some server overloading)' ) ;?></small>
</label>
</p>

<p>
<input type="checkbox" name="embedplayer" id="md_embedplayer" value="1" <?php if ( $mdoptions['embedplayer'] ) echo ' checked="checked" ' ;?> />
<label for="md_embedplayer"><?php _mde( 'Embed player' ) ;?></label>
</p>

<p>
<label for="md_tagencoding"><?php _mde( 'MP3 tag encoding:' ) ;?></label>
<select name="tagencoding" id="md_tagencoding">
<?php foreach ( $mdencodings as $mdencoding) { ?>
    <option value="<?php echo $mdencoding ;?>" <?php if ( $mdencoding == $mdoptions['tagencoding'] ) echo 'selected="selected"' ;?> ><?php echo $mdencoding ;?></option>
<?php } ;?>
</select>
</p>

<p>
<label for="md_cachedir"><?php _mde( 'Cache dir: ' ) ;?> <code><?php echo ABSPATH ;?></code></label>
<input type="text" name="cachedir" id="md_cachedir" value="<?php echo $mdoptions['cachedir'] ;?>" size="20" />

<?php if( '' != trim( $mdoptions['cachedir'] ) ){
    $dirok = is_writeable( ABSPATH . '/' . $mdoptions['cachedir'] ) ;?>
    <br /><small style="color:#999;background-color:#<?php echo $dirok ? 'DFD' : 'FDD' ;?>"><?php _mde( $dirok ? 'Folder successfully written on.' : 'Could not write on folder.') ;?></small>
<?php };?>

</p>

<p class="submit">
<input type="submit" value="<?php _mde( 'Update Options' ) ;?>" />
</p>
</fieldset>

</form>

<hr />

<h2><?php _mde( 'Sample Usage' ) ;?></h2>
<p><?php _mde( 'Media Downloader plugin lists MP3 files from a folder by replacing the <code>[media]</code> smart tag.' ) ;?></p>

<p><?php _mde( 'An example may help... Let\'s say you have a folder called <em>"music"</em> under your root folder, and it has some subfolders, like: <em>"Beethoven",</em> <em>"Mozart",</em> <em>"Bach"</em> and <em>"Haendel".</em>' ) ;?></p>

<p><?php _mde( 'First of all, you should configure Media Downloader by typing <em>"music"</em> in the <label for="md_mp3folder"><em>"MP3 Folder"</em> field.</label> That done, you can edit a post talking about Johann Sebastian Bach and insert anywhere on it the smart tag <code>[media:Bach]</code>, then Media Downloader will create a list of all files under the <em>"music/Bach"</em> directory.' ) ;?></p>
</div>
