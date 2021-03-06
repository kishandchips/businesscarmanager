<?php 
$max_title_length = 60;
$class = array('post-item');
$class[] = ( !empty($color) ) ? $color : '';
$class[] = ( !empty($image_url) ) ? 'has-thumbnail' : 'no-thumbnail';
//$title = ( strlen($title) > $max_title_length + 3 ) ? substr($title, 0, $max_title_length) . '...' : $title;
?>
<div class="<?php echo implode(' ', $class); ?>">
	<?php if( !empty($image_url)) : ?>
	<figure class="post-image thumbnail featured-image">
		<a href="<?php echo $url; ?>" class="btn" style="background-image: url(<?php echo $image_url; ?>)">
			<img src="<?php echo $image_url; ?>" />
		</a>
	</figure>
	<?php endif; ?>
	<header class="header <?php echo (strlen($title) > $max_title_length) ? 'title-long' : 'title-short'; ?>">
		<?php if( !empty($category)) : ?>
		<?php include_module('post-category', $category); ?>
		<?php endif; ?>
		<h3 class="post-title title"><a href="<?php echo $url; ?>"><?php echo $title; ?></a></h3>						
		<?php if( !empty($date) ) : ?>
		<div class="meta">
			<span class="date"><?php echo $date; ?></span>
		</div>
		<?php endif; ?>
	</header>
</div>