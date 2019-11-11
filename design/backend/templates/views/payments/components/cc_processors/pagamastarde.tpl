{* $Id: pagamastarde.tpl  $cas *}

<div class="control-group">
    <label class="control-label" for="real_public_key">Public Key:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][pagamastarde_public_key]" id="pagamastarde_real_public_key" value="{$processor_params.pagamastarde_public_key}" class="input-text" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="real_secret_key">Secret Key:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][pagamastarde_secret_key]" id="pagamastarde_real_secret_key" value="{$processor_params.pagamastarde_secret_key}" class="input-text" />
    </div>
</div>
