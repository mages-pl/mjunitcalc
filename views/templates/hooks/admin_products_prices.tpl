<div class="col-md-12">
{* ID: {$id_product} *}

    <h2>
    Przelicznik jednostek dla produktu
    </h2>
    <div class="row">
    <div class="col-md-6">
    <label>Podaj propocje produktu w odniesieniu do jednostki podstawowej</label>
    <input type="number" onkeyup="przeliczJednostki()" id="mjunitcalc_volume" name="mjunitcalc_volume" value="{$mjunitcalc_volume}" steps="6" class="form-control">
    <input type="hidden" id="product_price_net" value="{$price}" />
    </div> 
    
    <div class="col-md-6">
    <label>Podaj jednostkę postawową</label>
    <select name="mjunitcalc_base_unit" id="mjunitcalc_base_unit" onchange="przeliczJednostki()" class="form-control">
    {foreach $jednostki as $key => $jednostka}
    <option value="{$key}" {if $mjunitcalc_base_unit == $key} selected="selected" {/if}>{$key}</option>
    {/foreach}

    </select>
    </div>
    </div>
    </div>
    <script type="text/javascript">

    function przeliczJednostki() {
    var mjunitcalc_volume =   document.getElementById('mjunitcalc_volume').value;
    var mjunitcalc_base_unit =   document.getElementById('mjunitcalc_base_unit').value;
    var product_price_net = document.getElementById("product_price_net").value;

    document.getElementById('form_step2_unit_price').value = product_price_net/mjunitcalc_volume;
    document.getElementById('form_step2_unity').value = mjunitcalc_base_unit;
    }
    </script>
    