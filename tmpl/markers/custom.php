<?php
/**
 * @package    Fields - WT Yandex Map
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */


defined('_JEXEC') or die;

$field = $this->options->get('field');
$fieldParams = $this->options->get('field_params');
$id = $this->options->get('id');

/**
 * This is an example file. Fill free to copy and modify it as you want
 *
 *
 * You can place any HTML code between <template> </template>
 * Take care about id attribute. It should be like id="<?php echo $id;?>_marker"
 */

?>
<template id="<?php echo $id;?>_marker">
	<div class="bg-white p-2 border border-danger shadow rounded-5">
		<svg xmlns="http://www.w3.org/2000/svg" class="plugin-info-img-svg" width="63" height="20">
		<g>
			<title>Go to https://web-tolk.ru</title>
			<text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="12" id="svg_3" y="10" x="4" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#0fa2e6">Web</text>
			<text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="12" id="svg_4" y="10" x="30" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#384148">Tolk</text>
			<text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="10" id="svg_4" y="20" x="18" stroke-opacity="null" stroke-width="0" stroke="#ff0000" fill="#ff0000">demo</text>
		</g>
		</svg>
	</div>
</template>