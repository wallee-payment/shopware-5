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

{block name='frontend_account_order_item_repeat_order'}
	{if $offerPosition.walleeTransaction && ($offerPosition.walleeTransaction.canDownloadInvoice || $offerPosition.walleeTransaction.canDownloadPackingSlip)}
		<div class="panel--tr is--odd">
			<div class="panel--td">
				{if $offerPosition.walleeTransaction.canDownloadInvoice}
					<a href="{url controller='WalleePaymentTransaction' action='downloadInvoice' id=$offerPosition.walleeTransaction.id}" title="{s name="account/button/download_invoice" namespace="frontend/wallee_payment/main"}Download Invoice{/s}" class="btn is--small">
						{s name="account/button/download_invoice" namespace="frontend/wallee_payment/main"}Download Invoice{/s}
					</a>
				{/if}
				{if $offerPosition.walleeTransaction.canDownloadPackingSlip}
					<a href="{url controller='WalleePaymentTransaction' action='downloadPackingSlip' id=$offerPosition.walleeTransaction.id}" title="{s name="account/button/download_packing_slip" namespace="frontend/wallee_payment/main"}Download Packing Slip{/s}" class="btn is--small">
						{s name="account/button/download_packing_slip" namespace="frontend/wallee_payment/main"}Download Packing Slip{/s}
					</a>
				{/if}
			</div>
		</div>
	{/if}
	{$smarty.block.parent}
{/block}