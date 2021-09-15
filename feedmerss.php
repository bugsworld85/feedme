<?php
/**
 * Plugin Name: Feed Me RSS
 * Plugin URI: https://github.com/bugsworld85/feedme
 * Description: Plugin that allows you to display RSS feed as post list via shortcode in Wordpress.
 * Author: Jovanni G
 * Author URI: https://github.com/bugsworld85
 * License: GPLv2 or later
 */

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

use Carbon\Carbon;

class FeedMe {
	static function init() {
		add_shortcode( 'feedme', [ __CLASS__, 'handle_shortcode' ] );
		add_action( 'init', [ __CLASS__, 'register_assets' ] );
	}

	static function handle_shortcode( $atts ): string {
		$atts    = shortcode_atts( [
			'columns' => 3,
			'wrapped' => true,
			'posts' => 5,
			'url'   => 'https://www.konstructdigital.com/feed/',
			'image_placeholder' => 'https://www.konstructdigital.com/wp-content/uploads/2019/12/cropped-konstruct-site-icon-270x270.png',
		], $atts, 'feedme' );
		$items   = "";
		$index   = 0;
		$content = file_get_contents( $atts['url'] );
		$rss     = simplexml_load_string(
			preg_replace( "/(<\/?)(\w+):([^>]*>)/", "$1$3", $content ),
			null,
			LIBXML_NOCDATA
		);

		foreach ( $rss->channel->item as $item ) {
			if($index >= (int) $atts['posts']){
				break;
			}
			$imageLink = self::get_image( (string) $item->link , $atts['image_placeholder']);
			$date      = Carbon::createFromFormat( 'D, d M Y H:i:s O', $item->pubDate, 'PST' )->format( 'F jS Y' );

			$items .= "<div class=\"feed-me-item\">";
				$items .= "<div class=\"feed-me-item-image\">";
					$items .= "<a href=\"{$item->link}\"><img src=\"{$imageLink}\"/></a>";
				$items .= "</div>";
				$items .= "<div class=\"feed-me-item-content\">";
					$items .= "<div class=\"feed-me-item-date\">{$date}</div>";
					$items .= "<div class=\"feed-me-item-title\"><a href=\"{$item->link}\">{$item->title}</a></div>";
					$items .= "<div class=\"feed-me-item-footer\">";
						$items .= "<span class=\"feed-me-item-category\">{$item->category}</span>";
						$items .= "<span class=\"feed-me-item-author\">{$item->creator}</span>";
					$items .= "</div>";
				$items .= "</div>";
			$items .= "</div>";

			$index++;
		}

		$classes = ['feed-me'];
		if((int) $atts['columns'] > 3 && (int) $atts['columns'] <= 6){
			$classes[] = 'columns-' . (int) $atts['columns'];
		}
		if($atts['wrapped'] == true || $atts['wrapped'] == 'true'){
			$classes[] = 'wrapped';
		}
		$classes = implode(' ', $classes);

		return "<div class=\"{$classes}\">{$items}</div>";
	}

	static function register_assets() {
		wp_enqueue_style( 'feed-me-style', plugins_url( "/dist/css/style.css", __FILE__ ) );
	}

	private static function get_image( string $url , string $imagePlaceholder ): string {
		libxml_use_internal_errors( true );

		try {
			$content = file_get_contents( $url );
			$doc     = new DomDocument();
			$doc->loadHTML( $content );
			$xhtml = new domxpath( $doc );

			return ( $xhtml->query( "//meta[@property='og:image']" ) )[0]->getAttribute( 'content' );
		} catch ( \Exception $e ) {
			return $imagePlaceholder;
		}
	}
}

FeedMe::init();