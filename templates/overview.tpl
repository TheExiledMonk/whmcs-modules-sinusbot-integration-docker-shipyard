<h2>Musical bot SinusBot</h2>

<p>SinusBot - multifunction radio bot for Teamspeak 3 servers</p>

<div class="alert alert-info">
  For SinusBot settings using the data provided below
</div>

<h3>{$LANG.clientareaproductdetails}</h3>

<hr>



  

{if $dedicatedip}
    <div class="row">
        <div class="col-sm-5">
            {$LANG.domainregisternsip}
        </div>
        <div class="col-sm-7">
            {$dedicatedip}
        </div>
    </div>
{/if}

{foreach from=$configurableoptions item=configoption}
    <div class="row">
        <div class="col-sm-5">
            {$configoption.optionname}
        </div>
        <div class="col-sm-7">
            {if $configoption.optiontype eq 3}
                {if $configoption.selectedqty}
                    {$LANG.yes}
                {else}
                    {$LANG.no}
                {/if}
            {elseif $configoption.optiontype eq 4}
                {$configoption.selectedqty} x {$configoption.selectedoption}
            {else}
                {$configoption.selectedoption}
            {/if}
        </div>
    </div>
{/foreach}



<div class="row">
    <div class="col-sm-5">
        {$LANG.orderpaymentmethod}
    </div>
    <div class="col-sm-7">
        {$paymentmethod}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.firstpaymentamount}
    </div>
    <div class="col-sm-7">
        {$firstpaymentamount}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.recurringamount}
    </div>
    <div class="col-sm-7">
        {$recurringamount}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.clientareahostingnextduedate}
    </div>
    <div class="col-sm-7">
        {$nextduedate}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.orderbillingcycle}
    </div>
    <div class="col-sm-7">
        {$billingcycle}
    </div>
</div>
        
{if $status eq "Активный"}
<div class="row">
    <div class="col-sm-5">
        {$LANG.clientareastatus}
    </div>
    <div class="col-sm-7">
        
            <span style="padding:2px 10px;background-color:#5bb75b;color:#fff;font-weight:bold;">Active</span>
    </div>
</div>
{/if}

<hr>
<h3>Data for access to the control panel</h3>

<hr>
{if $status eq "Активный"} 
<div class="row">
    <div class="col-sm-5">
       A link to the control panel:
    </div>
    <div class="col-sm-7">
        <a href="{$domain}"> {$domain}</a>
    </div>
</div>
<div class="row">
    <div class="col-sm-5">
       Login:
    </div>
    <div class="col-sm-7">
         {$username}
    </div>
</div>
    
    <div class="row">
    <div class="col-sm-5">
       Password:
    </div>
    <div class="col-sm-7">
      {$password}
    </div>
</div> 
{elseif $status eq "Удален"} 
    <span style="padding:2px 10px;background-color:#cc0000;color:#fff;text-align: center;display: block;margin: 0 auto;width: 149px;height: 100%;">
        <strong>service deleted</strong>
    </span> {/if}
    {if $status eq "Приостановлен"} 
         <span style="padding:2px 10px;background-color:#cc0000;color:#fff;text-align: center;display: block;margin: 0 auto;width: 400px;height: 100%;">
        <strong>service suspended</strong><br/>
     <strong>   {$LANG.suspendreason}: {$suspendreason}</strong>
    </span> {/if}
<br><br>
<hr>
<h3>log bot</h3>
<hr>


<textarea style="display: block;margin: 0 auto;font-size: 11px;" rows="10" cols="140" name="text" disabled> {$logs}</textarea>
