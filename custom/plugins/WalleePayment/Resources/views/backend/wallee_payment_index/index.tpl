{#
/**
 * Wallee Shopware
 *
 * This Shopware extension enables to process payments with Wallee (https://wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 * @link https://github.com/wallee-payment/shopware
 */
#}

{extends file='parent:backend/index/parent.tpl'}
 
{block name="backend/base/header/css" append}
   <link rel="stylesheet" type="text/css" href="{link file="backend/_resources/styles/wallee_payment.css"}" />
{/block}

{block name="backend/base/header/javascript" append}
	<script type="text/javascript">
		window.WalleeActive = true;
	</script>
{/block}