<fieldset>

{include file="common/subheader.tpl" title=__("general_info")}
{assign var="sendcloud_shipping_methods" value=fn_sg_sendcloud__shipping_cost_get_all_shipping_methods($company_id)}

<div class="control-group">
    <label class="control-label" for="sendcloud_method_id_{$shipping.shipping_id}">{__("sg_sendcloud_shipping_cost.shipping_method")} <span class="required">*</span>:</label>
    <div class="controls">
        <select name="shipping_data[service_params][sendcloud_method_id]" id="sendcloud_method_id_{$shipping.shipping_id}" class="input-large">
            <option value="">{__("sg_sendcloud_shipping_cost.select_method")}</option>
            {foreach from=$sendcloud_shipping_methods key=method_id item=method_name}
                <option value="{$method_id}" {if $shipping.service_params.sendcloud_method_id == $method_id}selected="selected"{/if}>
                    {$method_name}
                </option>
            {/foreach}
        </select>
        <p class="muted">{__("sg_sendcloud_shipping_cost.shipping_method_description")}</p>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="surcharge_amount_{$shipping.shipping_id}">{__("sg_sendcloud_shipping_cost.surcharge_amount")}:</label>
    <div class="controls">
        <input type="text" name="shipping_data[service_params][surcharge_amount]" id="surcharge_amount_{$shipping.shipping_id}" value="{$shipping.service_params.surcharge_amount|default:0}" size="10" class="input-text" />
        <span class="input-append">
            <span class="add-on">{$currencies.$primary_currency.symbol nofilter}</span>
        </span>
        <p class="muted">{__("sg_sendcloud_shipping_cost.surcharge_amount_description")}</p>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="default_weight_{$shipping.shipping_id}">{__("sg_sendcloud_shipping_cost.default_weight")}:</label>
    <div class="controls">
        <input type="text" name="shipping_data[service_params][default_weight]" id="default_weight_{$shipping.shipping_id}" value="{$shipping.service_params.default_weight|default:1}" size="10" class="input-text" />
        <span class="input-append">
            <span class="add-on">Kg</span>
        </span>
        <p class="muted">{__("sg_sendcloud_shipping_cost.default_weight_description")}</p>
    </div>
</div>

<div class="control-group">
    <div class="controls">
        <p class="muted">{__("sg_sendcloud_shipping_cost.rates_note")}</p>
    </div>
</div>
</fieldset>