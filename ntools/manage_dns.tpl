<style>
    button:focus {
        outline: 0 !important;
    }
</style>

{if not empty($message)}

    {include file="$template/includes/alert.tpl" type=$message.type textcenter=true hide=false msg=$message.content}

{/if}

<h3>DNS-records van {$domain}</h3>

<form action="index.php?m=ntools&a=dns&d={$domain_id}" method="post">
    <table id="records" class="table table-hover">
        <thead>
            <tr>
                <th>Naam</th>
                <th>Type</th>
                <th>Inhoud</th>
                <th>Prioriteit</th>
                <th>TTL</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
        {foreach from=$records key=id item=record}
            <tr>
                <td><input type="text" name="records[{$id}][name]" class="form-control" value="{$record.name}"></td>
                <td>
                    <select name="records[{$id}][type]" class="form-control">
                        {foreach from=$types item=type}
                            {if $record.type eq $type}
                                <option value="{$type}" selected="true">{$type}</option>
                            {else}
                                <option value="{$type}">{$type}</option>
                            {/if}
                        {/foreach}
                    </select>
                </td>
                <td><input type="text" name="records[{$id}][content]" class="form-control" value="{$record.content}"></td>
                <td><input type="text" name="records[{$id}][prio]" class="form-control" value="{$record.prio}"></td>
                <td><input type="text" name="records[{$id}][ttl]" class="form-control" value="{$record.ttl}"></td>
                <td class="text-center">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default delete" data-id="{$id}" style="color: red;"><i class="fa fa-times fa-fw"></i></button>
                    </div>
                </td>
            </tr>
            {foreachelse}
            <tr>
                <td colspan="6" class="text-center">Deze domeinnaam heeft geen DNS-records.</td>
            </tr>
        {/foreach}
            <tr>
                <td><input type="text" name="add[0][name]" class="form-control"></td>
                <td>
                    <select name="add[0][type]" class="form-control">
                        {foreach from=$types item=type}
                            {if $record.type eq $type}
                                <option value="{$type}" selected="true">{$type}</option>
                            {else}
                                <option value="{$type}">{$type}</option>
                            {/if}
                        {/foreach}
                    </select>
                </td>
                <td><input type="text" name="add[0][content]" class="form-control"></td>
                <td><input type="text" name="add[0][prio]" class="form-control"></td>
                <td><input type="text" name="add[0][ttl]" class="form-control" value="3600"></td>
                <td class="text-center">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default add" style="color: green;"><i class="fa fa-plus fa-fw"></i></button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- /.table -->

    <script>
        // Add new row.
        $('#records').on('click', 'button.add', function() {
            var row = 1;
            $('.table').append(
                    '<tr>' +
                    '<td><input type="text" name="add[' + row + '][name]" class="form-control"></td>' +
                    '<td><select name="add[' + row + '][type]" class="form-control">{foreach from=$types item=type}<option value="{$type}">{$type}</option>{/foreach}</select></td>' +
                    '<td><input type="text" name="add[' + row + '][content]" class="form-control"></td>' +
                    '<td><input type="text" name="add[' + row + '][prio]" class="form-control"></td>' +
                    '<td><input type="text" name="add[' + row + '][ttl]" class="form-control" value="3600"></td>' +
                    '<td class="text-center"><div class="btn-group"><button type="button" class="add btn btn-default" style="color: green;"><i class="fa fa-plus fa-fw"></i></button></div></td>' +
                    '</tr>'
            );
            row++;

            $(this).removeClass('add').addClass('delete').css('color', 'red').html('<i class="fa fa-times fa-fw"></i>');
        });

        // Delete row from DNS or only delete it from the table if it's empty.
        $('#records').on('click', 'button.delete', function() {
            var attr = $(this).attr('data-id');
            var domain_id = '{$domain_id}';
            var thisElement = $(this);

            thisElement.prop('disabled', true);

            // Attribute 'data-id' exists, so both table row and record will be deleted.
            if (typeof attr !== typeof undefined && attr !== false) {
                $.get('index.php?m=ntools&a=dns', { d: domain_id, r: attr }, function(response) {
                    if (response == 'true') {
                        $(thisElement).closest('tr').remove();
                    } else {
                        thisElement.prop('disabled', false);
                        alert('The record was not deleted.');
                    }
                });
            // Delete the row when it's empty.
            } else {
                $(this).closest('tr').remove();
            }
        });
    </script>

    <button type="submit" name="edit_records" class="btn btn-success">Wijzig records</button>
    <a href="clientarea.php?action=domains" class="btn btn-primary">Terug naar overzicht</a>
</form>