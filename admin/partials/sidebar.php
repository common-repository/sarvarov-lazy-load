<?php

/**
 * Admin sidebar.
 *
 * @since      1.0.0
 * @package    SARVAROV_Lazy_Load
 * @subpackage SARVAROV_Lazy_Load/admin/partials
 */
?>

<aside class="plugin-sidebar">
	<div class="sidebar-inner">
		<?php
			$sarvarov_plugins = array(
				array( 'name' => 'scrollToTop', 'slug' => 'scrolltotop', 'url' => 'scrolltotop', 'description' => esc_html__( 'Displays cool back to top button or bar', $this->plugin_name ) ), 
				array( 'name' => 'Lazy Load', 'slug' => 'sarvarov_lazy_load', 'url' => 'sarvarov-lazy-load', 'description' => esc_html__( 'Make all your images and iframes lazy', $this->plugin_name ) )
			);
		?>
		<div class="paypal-donate">
			<div  class="widget-header">
				<?php _e( 'Like the plugin? Support the developer.', $this->plugin_name ); ?>
			</div>
			
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
				<input type="hidden" name="cmd" value="_s-xclick" />
				<input type="hidden" name="hosted_button_id" value="LNVRV7LL39E2E" />
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
			</form>
			
			<div style="margin-top: 20px; opacity: .5;">
				<?php _e( 'Even 1 dollar will make me happy :)', $this->plugin_name ); ?>
			</div>
			
			<?php
				foreach( $sarvarov_plugins as $key => $value ) {
					
					if( $value['slug'] != $this->plugin_slug )
						continue;
					
					printf( '<a class="button button-primary" href="https://wordpress.org/plugins/%1$s/" target="_blank">&#11088; %2$s</a>', $value['url'], esc_html__( 'Rate the plugin or ask a question', $this->plugin_name ) );
					
				}
			?>
		</div>
		
		<div class="more-plugins">
			<div  class="widget-header">
				<?php _e( 'Check out other plugins:', $this->plugin_name ); ?>
			</div>
			
			<?php
				foreach( $sarvarov_plugins as $key => $value ) {
					
					if( $value['slug'] == $this->plugin_slug )
						continue;
					
					printf( '<a href="https://wordpress.org/plugins/%1$s/" target="_blank">%2$s<span>%3$s</span></a>', $value['url'], $value['name'], $value['description'] );
					
				}
			?>
		</div>
		
		<div class="twitter-widget">
			<a class="twitter-timeline" data-height="400" data-theme="light" href="https://twitter.com/sarvarovdev?ref_src=twsrc%5Etfw">Tweets by sarvarovdev</a>
			<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
		</div>
	</div>
</aside>