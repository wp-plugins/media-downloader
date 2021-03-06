<div id="icon-options-general" class="icon32"><br /></div>

<h2><?php _mde( 'Media Downloader:' ) ;?></h2>

<h3 class="nav-tab-wrapper">

<?php
// Tabs array
$mdtabs = array(
    'general' => 'General Options',
    'markup-options' => 'Markup Options',
    'more-options' => 'More Options',
    'tag-editor' => 'Tag Editor',
);
// If no tab is set as active, we set the first
$anyTab = false;
foreach ( $mdtabs as $tabSlug => $tabText ) if ( isset( $_GET[$tabSlug] ) ) $anyTab = true;
if ( !$anyTab ) $_GET[array_shift(array_keys($mdtabs))] = true;

// Building tab's markup
foreach ( $mdtabs as $tabSlug => $tabText ) :
?>
    <a href="?page=mediadownloader-options&amp;<?php echo $tabSlug; ?>" class="nav-tab<?php if ( isset( $_GET[$tabSlug] ) ) { ?> nav-tab-active<?php }; ?>"><?php _mde( $tabText ); ?></a>
<?php endforeach; ?>

</h3>
