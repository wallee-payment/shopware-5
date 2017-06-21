{extends file='parent:backend/index/parent.tpl'}
 
{block name="backend/base/header/css" append}
   <link rel="stylesheet" type="text/css" href="{link file="backend/_resources/styles/wallee_payment.css"}" />
{/block}

{block name="backend/base/header/javascript" append}
	<script type="text/javascript">
		window.WalleeActive = true;
	</script>
{/block}