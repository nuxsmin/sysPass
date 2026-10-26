<?php
/**
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Mvc\View\TemplateInterface;

use function SP\__;

?>
<?php if ($_getvar('message')): ?>
    <div class="noRes round"><?php echo $_getvar('message'); ?></div>
<?php else: ?>
    <div class="noRes round"><?php echo __('No records found'); ?></div>
<?php endif; ?>
