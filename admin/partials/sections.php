<?php

/**
 * Provide a admin area view for the plugin
 *
 * @since      1.0.0
 * @package    SARVAROV_Lazy_Load
 * @subpackage SARVAROV_Lazy_Load/admin/partials
 */
?>

<div class="wrap sarvarov-wrap">
	<h2>
		<div class="svg-icon svg-baseline">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000">
				<path d="M1000,600c0-55,0-200-200-200H400c-243,0-200,16-200-200H300c36,0,100,1,100,100H600C600,138,471,0,300,0H0V400c0,55,0,200,200,200H600c202,2,202,2,200,200H700c-36,0-100-1-100-100H400c0,162,129,300,300,300h300Z" transform="translate(0 0)"></path>
				<polyline points="0 1000 300 1000 300 800 200 800 200 700 0 700" opacity=".5"></polyline>
				<polyline points="1000 0 700 0 700 200 800 200 800 300 1000 300" opacity=".5"></polyline>
			</svg>
		</div>
		<?php echo $this->plugin_title; ?> 
		<code>v<?php echo $this->plugin_version; ?></code>
	</h2>

	<section class="plugin-settings" role="main">
		<form method="post" action="options.php">
			<?php settings_fields( $this->plugin_name ); ?>
			<?php $this->do_wrapped_settings_sections( $this->plugin_name ); ?>
			<?php submit_button(); ?>
		</form>
		
		<?php $this->sidebar(); ?>
			
		<div id="loader" class="loader loader-inprogress">
			<div class="loader-activity"></div>
			<noscript>
				<style>#loader { display: none !important; }</style>
			</noscript>
		</div>
	</section>
</div>