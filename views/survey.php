<div class="survey">
	<h2><?php echo $survey->title; ?></h2>
	<?php if ($survey->description): ?>
		<p><?php echo $survey->description; ?></p>
	<?php endif; ?>

	<?php echo $section ?>
</div>