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
	{if $offerPosition.walleeTransaction.refunds  && $offerPosition.walleeTransaction.canDownloadRefunds}
		<div class="panel--tr is--odd">
			<div class="panel--td column--name">
				<p class="is--strong">{s name="account/header/refunds" namespace="frontend/wallee_payment/main"}Refunds{/s}</p>
				{foreach $offerPosition.walleeTransaction.refunds as $refund}
					<p>
                        {$refund.date|date}
					</p>
				{/foreach}
			</div>
			<div class="panel--td column--price">
				<p>&nbsp;</p>
				{foreach $offerPosition.walleeTransaction.refunds as $refund}
					<p>
						{if $offerPosition.currency_position == "32"}
                            {$offerPosition.currency_html} {$refund.amount}
                        {else}
                            {$refund.amount} {$offerPosition.currency_html}
                        {/if}
					</p>
				{/foreach}
			</div>
			<div class="panel--td column--total">
				<p>&nbsp;</p>
				{foreach $offerPosition.walleeTransaction.refunds as $refund}
					<p>
						{if $refund.canDownload}
                        	<a href="{url controller='WalleePaymentTransaction' action='downloadRefund' id=$offerPosition.walleeTransaction.id refund=$refund.id}" title="{s name="account/button/download" namespace="frontend/wallee_payment/main"}Download{/s}">
								{s name="account/button/download" namespace="frontend/wallee_payment/main"}Download{/s}
							</a>
                        {/if}
					</p>
				{/foreach}
			</div>
		</div>
	{/if}
	{$smarty.block.parent}
{/block}