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

{block name="frontend_index_header_javascript_jquery" append}
	<script type="text/javascript" src="{$walleePaymentJavascriptUrl}"></script>
	<script type="text/javascript" src="{link file='frontend/checkout/wallee_payment/_resources/checkout.js'}"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		ShopwareWallee.Checkout.init('wallee_payment_method_form', '{$walleePaymentConfigurationId}', '{url controller='WalleePaymentCheckout' action='saveOrder'}');
	});
	</script>
{/block}

{block name='frontend_checkout_confirm_premiums' prepend}
	<div class="panel has--border">
		<div class="panel--title is--underline">
			{s name=checkout/payment_information namespace=frontend/wallee_payment/main}Payment Information{/s}
		</div>
		<div class="panel--body is--wide">
			<div id="wallee_payment_method_form"></div>
		</div>
	</div>
{/block}

{block name="frontend_checkout_confirm_error_messages" append}
	{if $walleePaymentFailureMessage}
		{include file="frontend/_includes/messages.tpl" type="error" content=$walleePaymentFailureMessage}
	{/if}
{/block}