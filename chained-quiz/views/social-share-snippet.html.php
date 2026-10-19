<html>
	<head>
		<title><?php echo esc_html($og_title);?></title>
		<meta property="og:title" content="<?php echo esc_attr($og_title);?>" />
		<meta property="og:image" content="<?php echo esc_url($target_image);?>" />
		<meta property="og:description" content="<?php echo esc_attr(wp_strip_all_tags(apply_filters('the_content', $og_description)));?>" />
	</head>
	<body itemscope itemtype="http://schema.org/Product" style="display:none;">
		<h1 itemprop="name"><?php echo esc_html($og_title);?></h1>
		<img itemprop="image" src="<?php echo esc_url($target_image);?>" />
		<p itemprop="description"><?php echo wp_kses_post(apply_filters('the_content', $og_description));?></p>

		<?php if(!empty($_GET['return_to'])):?>
		<script type="text/javascript" >
		window.location = '<?php echo esc_url(get_permalink( absint($_GET['return_to']) ));?>';
		</script>
		<?php endif;?>
	</body>
</html>
