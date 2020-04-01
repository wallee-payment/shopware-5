{#
/**
 * wallee Shopware 5
 *
 * This Shopware 5 extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
#}

{block name="frontend_index_header_javascript_jquery"}
	{$smarty.block.parent}
	<script type="text/javascript" src="{$walleePaymentJavascriptUrl}"></script>
	<script type="text/javascript">
	var ShopwareWalleeCheckoutInit = function(){
		ShopwareWallee.Checkout.init('wallee_payment_method_form', '{$walleePaymentConfigurationId}', '{url controller='WalleePaymentCheckout' action='saveOrder'}', '{$walleePaymentPageUrl}');
	};
	{if $theme.asyncJavascriptLoading}
		if (typeof document.asyncReady == 'function') {
			document.asyncReady(function(){
				$(document).ready(ShopwareWalleeCheckoutInit);
			});
		} else {
			$(document).ready(ShopwareWalleeCheckoutInit);
		}
	{/if}
	</script>
{/block}

{block name="frontend_index_javascript_async_ready"}
	{$smarty.block.parent}
	{if !$theme.asyncJavascriptLoading}
		<script type="text/javascript">
			$(document).ready(ShopwareWalleeCheckoutInit);
		</script>
	{/if}
{/block}

{block name='frontend_checkout_confirm_premiums'}
	<div class="panel has--border" id="wallee_payment_method_form_container" style="position: absolute; left: -10000px;">
		<div class="panel--title is--underline">
			{s name=checkout/payment_information namespace=frontend/wallee_payment/main}Payment Information{/s}
		</div>
		<div class="panel--body is--wide">
			<div id="wallee_payment_method_form"></div>
		</div>
	</div>
	{$smarty.block.parent}
{/block}

{block name="frontend_checkout_confirm_error_messages"}
	{$smarty.block.parent}
	{if $walleePaymentFailureMessage}
		{include file="frontend/_includes/messages.tpl" type="error" content=$walleePaymentFailureMessage}
	{/if}
	<div class="wallee-payment-validation-failure-message" style="display: none;">
		{include file="frontend/_includes/messages.tpl" type="error" content=""}
	</div>
{/block}