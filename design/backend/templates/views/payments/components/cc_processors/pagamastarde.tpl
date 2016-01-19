{* $Id: pagamastarde.tpl  $cas *}

<div class="control-group">
	<label class="control-label" for="test_public_key">Test Public Key:</label>
  <div class="controls">
    <input type="text" name="payment_data[processor_params][pagamastarde_test_public_key]" id="pagamastarde_test_public_key" value="{$processor_params.pagamastarde_test_public_key}" class="input-text" />
  </div>
</div>

<div class="control-group">
	<label class="control-label" for="test_secret_key">Test Secret Key:</label>
  <div class="controls">
    <input type="text" name="payment_data[processor_params][pagamastarde_test_secret_key]" id="pagamastarde_test_secret_key" value="{$processor_params.pagamastarde_test_secret_key}" class="input-text" />
  </div>
</div>

<div class="control-group">
	<label class="control-label" for="real_public_key">Real Public Key:</label>
  <div class="controls">
    <input type="text" name="payment_data[processor_params][pagamastarde_real_public_key]" id="pagamastarde_real_public_key" value="{$processor_params.pagamastarde_real_public_key}" class="input-text" />
  </div>
</div>

<div class="control-group">
	<label class="control-label" for="real_secret_key">Real Secret Key:</label>
  <div class="controls">
    <input type="text" name="payment_data[processor_params][pagamastarde_real_secret_key]" id="pagamastarde_real_secret_key" value="{$processor_params.pagamastarde_real_secret_key}" class="input-text" />
  </div>
</div>

<div class="control-group">
	<label class="control-label" for="discount">Discount:</label>
  <div class="controls">
    <select name="payment_data[processor_params][pagamastarde_discount]" id="pagamastarde_discount">
        <option value="0" {if $processor_params.pagamastarde_discount == "0"}selected="selected"{/if}>{__("false")}</option>
        <option value="1" {if $processor_params.pagamastarde_discount == "1"}selected="selected"{/if}>{__("true")}</option>
    </select>
  </div>
</div>

<div class="control-group">
	<label class="control-label" for="environment">Test/Live mode:</label>
  <div class="controls">
    <select name="payment_data[processor_params][pagamastarde_environment]" id="pagamastarde_environment">
        <option value="0" {if $processor_params.pagamastarde_environment == "0"}selected="selected"{/if}>{__("test")}</option>
        <option value="1" {if $processor_params.pagamastarde_environment == "1"}selected="selected"{/if}>{__("live")}</option>
    </select>
  </div>
</div>
