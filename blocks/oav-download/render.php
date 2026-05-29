<?php
/**
 * OAV download block template.
 *
 * @param array       $block The block settings and attributes.
 * @param string      $content The block inner HTML (empty).
 * @param bool        $is_preview True during AJAX preview.
 * @param int|string  $post_id The post ID this block is saved to.
 */

$id = 'block-' . $block['id'];
if ( ! empty($block['anchor']) ) {
	$id = $block['anchor'];
}

$classes = 'oav-download-block';
if ( ! empty($block['className']) ) {
	$classes .= sprintf(' %s', $block['className']);
}
if ( ! empty($block['align']) ) {
	$classes .= sprintf(' align%s', $block['align']);
}

$engagement_title = get_field('engagement_title') ?: "En ma qualite de membre de l'OAV, je m'engage a :";
$engagement_items = get_field('engagement_items');
$file_1 = get_field('file_1');
$file_1_label = get_field('file_1_label') ?: 'Tableau calcul - garde exclusive';
$user_email = '';
if ( is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	$user_email = $current_user->user_email;
}

$default_items = array(
	"ne pas commercialiser l'outil sous quelque forme que ce soit ;",
	"utiliser uniquement la derniere version disponible de l'outil ;",
	"ne pas mettre l'outil a disposition de tiers.",
);

$resolve_file_url = static function ( $field_value ): string {
	if ( is_array($field_value) && ! empty($field_value['url']) ) {
		return (string) $field_value['url'];
	}

	if ( is_numeric($field_value) ) {
		$attachment_url = wp_get_attachment_url((int) $field_value);
		return $attachment_url ? (string) $attachment_url : '';
	}

	if ( is_string($field_value) && ! empty($field_value) ) {
		return $field_value;
	}

	return '';
};

$file_1_url = $resolve_file_url($file_1);
?>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($classes); ?>">
	<div class="oav-engagement">
		<p class="oav-engagement-title"><?php echo esc_html($engagement_title); ?></p>
		<div class="oav-conditions">
			<?php if ( ! empty($engagement_items) && is_array($engagement_items) ) : ?>
				<?php foreach ( $engagement_items as $index => $item ) : ?>
					<?php if ( ! empty($item['item']) ) : ?>
						<?php $condition_id = 'oav-engage-' . sanitize_html_class($block['id']) . '-' . (int) $index; ?>
						<label class="oav-condition-label" for="<?php echo esc_attr($condition_id); ?>">
							<input type="checkbox" id="<?php echo esc_attr($condition_id); ?>" class="oav-engage-checkbox">
							<span><?php echo esc_html($item['item']); ?></span>
						</label>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php else : ?>
				<?php foreach ( $default_items as $index => $default_item ) : ?>
					<?php $condition_id = 'oav-engage-' . sanitize_html_class($block['id']) . '-default-' . (int) $index; ?>
					<label class="oav-condition-label" for="<?php echo esc_attr($condition_id); ?>">
						<input type="checkbox" id="<?php echo esc_attr($condition_id); ?>" class="oav-engage-checkbox">
						<span><?php echo esc_html($default_item); ?></span>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="oav-user-email">
		<label for="<?php echo esc_attr('oav-email-' . sanitize_html_class($block['id'])); ?>">Email</label>
		<input
			type="email"
			id="<?php echo esc_attr('oav-email-' . sanitize_html_class($block['id'])); ?>"
			value="<?php echo esc_attr($user_email); ?>"
			readonly
		>
	</div>

	<div class="oav-buttons">
		<?php if ( ! empty($file_1_url) ) : ?>
			<a href="<?php echo esc_url($file_1_url); ?>" class="oav-btn" aria-disabled="true" download>
				<span class="oav-btn-icon" aria-hidden="true">
					<svg viewBox="0 0 640 640" role="img" focusable="false">
						<path d="M192 96L320 96L320 192C320 227.3 348.7 256 384 256L480 256L480 512C480 529.7 465.7 544 448 544L192 544C174.3 544 160 529.7 160 512L160 128C160 110.3 174.3 96 192 96zM352 109.3L466.7 224L384 224C366.3 224 352 209.7 352 192L352 109.3zM192 64C156.7 64 128 92.7 128 128L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 250.5C512 233.5 505.3 217.2 493.3 205.2L370.7 82.7C358.7 70.7 342.5 64 325.5 64L192 64zM224 320C206.3 320 192 334.3 192 352L192 480C192 497.7 206.3 512 224 512L416 512C433.7 512 448 497.7 448 480L448 352C448 334.3 433.7 320 416 320L224 320zM304 352L304 400L224 400L224 352L304 352zM304 432L304 480L224 480L224 432L304 432zM336 480L336 432L416 432L416 480L336 480zM336 400L336 352L416 352L416 400L336 400z"></path>
					</svg>
				</span>
				<?php echo esc_html($file_1_label); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
