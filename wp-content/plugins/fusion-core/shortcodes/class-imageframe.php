<?php
class FusionSC_Imageframe {

	private $imageframe_counter = 1;
	public static $args;

	/**
	 * Initiate the shortcode
	 */
	public function __construct() {

		add_filter( 'fusion_attr_imageframe-shortcode', array( $this, 'attr' ) );
		add_filter( 'fusion_attr_imageframe-shortcode-link', array( $this, 'link_attr' ) );
		
		add_shortcode( 'imageframe', array( $this, 'render' ) );

	}

	/**
	 * Render the shortcode
	 * @param  array $args	 Shortcode paramters
	 * @param  string $content Content between shortcode
	 * @return string		  HTML output
	 */
	function render( $args, $content = '') {
		global $smof_data;

		$defaults = FusionCore_Plugin::set_shortcode_defaults(
			array(
				'class'					=> '',			
				'id'					=> '',				
				'align' 				=> '',
				'bordercolor' 			=> '',
				'borderradius' 			=> $smof_data['imageframe_border_radius'],
				'bordersize' 			=> $smof_data['imageframe_border_size'],
				'hide_on_mobile'		=> 'no',
				'lightbox' 				=> 'no',
				'lightbox_image'		=> '',
				'link'					=> '',
				'linktarget'			=> '_self',
				'style' 				=> '',
				'style_type'			=> 'none',	// deprecated
				'stylecolor' 			=> '',
				'animation_type' 		=> '',
				'animation_direction' 	=> 'left',
				'animation_speed' 		=> '',
				'hover_type'			=> 'none'
			), $args 
		);
		
		if( ! $defaults['style'] ) {
			$defaults['style'] = $defaults['style_type'];
		}		

		if( $defaults['borderradius'] && $defaults['style'] == 'bottomshadow' ) {
			$defaults['borderradius'] = '0';
		}
		
		if( $defaults['borderradius'] == 'round' ) {
			$defaults['borderradius'] = '50%';
		}	

		extract( $defaults );

		self::$args = $defaults;
		
		
		// Add the needed styles to the img tag
		if ( ! $bordercolor ) {
			$bordercolor = $smof_data['imgframe_border_color'];
		}

		if ( ! $stylecolor ) {
			$stylecolor = $smof_data['imgframe_style_color'];
		}
		
		$rgb = FusionCore_Plugin::hex2rgb( $stylecolor );
		$border_radius = $img_styles = '';
		
		if ( $borderradius != '0' && 
			 $borderradius != '0px' 
		) {
			$border_radius .= "-webkit-border-radius:{$borderradius};-moz-border-radius:{$borderradius};border-radius:{$borderradius};";	
		}

		if ( $border_radius ) {
			$img_styles = sprintf( ' style="%s"', $border_radius );
		}

		$img_classes = 'img-responsive';

		// CHANGED HERE
		$img_classes .= ' cld-responsive';
		
		// Get custom classes from the img tag
		preg_match( '/(class=["\'](.*?)["\'])/', $content, $classes );

		if ( ! empty( $classes ) ) {
			$img_classes .= ' ' . $classes[2];
		}
		
		$img_classes = sprintf( 'class="%s"', $img_classes );
		
		// Add custom and responsive class and the needed styles to the img tag
		if( ! empty( $classes ) ) {
			$content = str_replace( $classes[0], $img_classes . $img_styles , $content );
		} else {
			$content = str_replace( '/>', $img_classes . $img_styles . '/>', $content );
		}

		// Alt tag 
		$alt_tag = $image_url = '';
		
		preg_match( '/(src=["\'](.*?)["\'])/', $content, $src );
		if ( array_key_exists( '2', $src ) ) {
			$image_url = self::$args['pic_link'] = $src[2];
			$image_id = FusionCore_Plugin::get_attachment_id_from_url( $image_url );

			if( isset( $image_id ) && $image_id ) {
				$alt_tag = sprintf( 'alt="%s"', get_post_field( '_wp_attachment_image_alt', $image_id ) );
			}

			if( strpos( $content, 'alt=""' ) !== false && $alt_tag ) {
				$content = str_replace( 'alt=""', $alt_tag, $content );
			} elseif ( strpos( $content, 'alt' ) === false && $alt_tag ) {
				$content = str_replace( '/> ', $alt_tag . ' />', $content );
			}
		}
		
		// Set the lightbox image to the dedicated linkm if it is set
		if ( $lightbox_image ) {
			self::$args['pic_link'] = $lightbox_image;
		}
		
		$output = do_shortcode( $content );		
		
		if( $lightbox == 'yes' ) {
			self::$args['data_caption'] = '';
			self::$args['data_title'] = '';
			if( $image_id ) {
				self::$args['data_caption']	= get_post_field( 'post_excerpt', $image_id );
				self::$args['data_title']	= get_post_field( 'post_title', $image_id );
			}
			$output = sprintf( '<a %s>%s</a>', FusionCore_Plugin::attributes( 'imageframe-shortcode-link' ), do_shortcode( $content ) );
		} elseif( $link ) {
			$output = sprintf( '<a %s>%s</a>', FusionCore_Plugin::attributes( 'imageframe-shortcode-link' ), do_shortcode( $content ) );
		}

		// CHANGED HERE
		$output = str_replace( 'src=', 'data-src=', $output );
		$output = str_replace( '/upload/', '/upload/c_scale,w_auto,q_auto,f_auto,dpr_auto/', $output );
		// echo('<!--' . 'HELP HERE' . $output . '-->');

		$html = sprintf( '<span %s>%s</span>', FusionCore_Plugin::attributes( 'imageframe-shortcode' ), $output );

		if( $hover_type == 'liftup' ) {
			$liftup_classes = 'imageframe-liftup';
			$liftup_styles = '';
			if ( $border_radius ) {
				$liftup_styles = sprintf( '<style scoped="scoped">.imageframe-liftup.imageframe-%s:before{%s}</style>', $this->imageframe_counter, $border_radius );
				$liftup_classes .= sprintf( ' imageframe-%s', $this->imageframe_counter );
			}
		
			$html = sprintf( '<div %s>%s%s</div>', FusionCore_Plugin::attributes( $liftup_classes ), $liftup_styles, $html );
		}

		if( $align == 'center' ) {
			$html = sprintf( '<div %s>%s</div>', FusionCore_Plugin::attributes( 'imageframe-align-center' ), $html );
		}

		$this->imageframe_counter++;		

		return $html;

	}

	function attr() {

		global $smof_data;

		$attr = array();

		$attr['style'] = '';

		$bordercolor = self::$args['bordercolor'];
		$stylecolor = self::$args['stylecolor'];
		$bordersize = self::$args['bordersize'];
		$borderradius = self::$args['borderradius'];
		$style = self::$args['style'];

		// Add the needed styles to the img tag
		if ( ! $bordercolor ) {
			$bordercolor = $smof_data['imgframe_border_color'];
		}

		if ( ! $stylecolor ) {
			$stylecolor = $smof_data['imgframe_style_color'];
		}

		$rgb = FusionCore_Plugin::hex2rgb( $stylecolor );
		$img_styles = '';
		
		if ( $bordersize != '0' && 
			 $bordersize != '0px' 
		) {
			$img_styles .= "border:{$bordersize} solid {$bordercolor};";
		}
		
		if ( $borderradius != '0' && 
			 $borderradius != '0px' 
		) {
			$img_styles .= "-webkit-border-radius:{$borderradius};-moz-border-radius:{$borderradius};border-radius:{$borderradius};";	
		}		
		
		if ( $style == 'glow' ) {
			$img_styles .= "-moz-box-shadow: 0 0 3px rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},.3);-webkit-box-shadow: 0 0 3px rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},.3);box-shadow: 0 0 3px rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},.3);";
		}
		
		if ( $style == 'dropshadow' ) {
			$img_styles .= "-moz-box-shadow: 2px 3px 7px rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},.3);-webkit-box-shadow: 2px 3px 7px rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},.3);box-shadow: 2px 3px 7px rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},.3);";
		}		

		if ( $img_styles ) {
			$attr['style'] .= $img_styles;
		}

		$attr['class'] = sprintf( 'fusion-imageframe imageframe-%s imageframe-%s', self::$args['style'], $this->imageframe_counter );

		if( self::$args['style'] == 'bottomshadow' ) {
			$attr['class'] .= ' element-bottomshadow'; 
		}		

		if( self::$args['align'] == 'left' ) {
			$attr['style'] .= 'margin-right:25px;float:left;'; 
		} elseif( self::$args['align'] == 'right' ) {
			$attr['style'] .= 'margin-left:25px;float:right;';
		}

		if( self::$args['hover_type'] != 'liftup' ) {
			$attr['class'] .= ' hover-type-' . self::$args['hover_type'];
		}

		if( self::$args['class'] ) {
			$attr['class'] .= ' ' . self::$args['class']; 
		}

		if( self::$args['id'] ) {
			$attr['id'] = self::$args['id']; 
		}

		if( self::$args['animation_type'] ) {
			$animations = FusionCore_Plugin::animations( array(
				'type'	  => self::$args['animation_type'],
				'direction' => self::$args['animation_direction'],
				'speed'	 => self::$args['animation_speed'],
			) );

			$attr = array_merge( $attr, $animations );
			
			$attr['class'] .= ' ' . $attr['animation_class'];
			unset($attr['animation_class']);		 
		}
		
		if( self::$args['hide_on_mobile'] == 'yes' ) {
			$attr['class'] .= ' fusion-hide-on-mobile';
		}

		return $attr;

	}
	
	function link_attr() {
	
		$attr = array();
		
		if( self::$args['lightbox'] == 'yes' ) {
			$attr['href'] = self::$args['pic_link'];
			$attr['class'] = 'fusion-lightbox';			
			$attr['data-rel'] = 'iLightbox['.uniqid().']';
			$attr['data-caption'] = self::$args['data_caption'];
			$attr['data-title'] = self::$args['data_title'];
		} elseif( self::$args['link'] ) {
			$attr['class'] = 'fusion-no-lightbox';		
			$attr['href'] = self::$args['link'];
			$attr['target'] = self::$args['linktarget'];
		}
		
		

		return $attr;

	}

}

new FusionSC_Imageframe();