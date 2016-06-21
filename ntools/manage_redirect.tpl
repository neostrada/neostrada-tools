{if not empty($message)}

    {include file="$template/includes/alert.tpl" type=$message.type textcenter=true hide=false msg=$message.content}

{/if}

<h3>Stel redirect in voor {$domain}</h3>

<form action="index.php?m=ntools&a=redirect&d={$domain_id}" method="post" class="form-horizontal">
    <div class="form-group">
        <label for="redirect_url" class="col-xs-4 control-label">Redirect URL:</label>
        <div class="col-xs-6 col-sm-5">
            <input type="text" name="redirect_url" value="{if $redirect}{$redirect}{else}http://{/if}" class="form-control">
        </div>
    </div>

    <div class="form-group">
        <label for="redirect_type" class="col-xs-4 control-label">Redirect type:</label>
        <div class="col-xs-6 col-sm-5">
            <select name="redirect_type" class="form-control">
                <option{if $type=='1'} selected="selected"{/if} value="1">Standaard (HTTP 301)</option>
                <option{if $type=='3'} selected="selected"{/if} value="3">Tijdelijk (HTTP 302)</option>
                <option{if $type=='2'} selected="selected"{/if} value="2">Verborgen (domeinnaam blijft in de adresbalk staan)</option>
            </select>
        </div>
    </div>

    {if $delete}
    <div class="form-group">
        <label class="col-xs-4 control-label" for="delete_redirect">Redirect verwijderen:</label>
        <div class="col-xs-6 col-sm-5 controls checkbox">
            <label>
                <input type="checkbox" value="1" name="delete_redirect">
            </label>
        </div>
    </div>
    {/if}

    <p class="text-center">
        <button type="submit" name="manage_redirect" class="btn btn-success">Stel redirect in</button>
        <a href="clientarea.php?action=domains" class="btn btn-primary">Terug naar overzicht</a>
    </p>
</form>

